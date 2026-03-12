<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMode;
use App\Models\Product;
use App\Models\ProductDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    // ─── FALLBACK ACCOUNT CODES (used when category has no mapping) ───────
    const CASH_IN_HAND        = '11001';
    const BANK_ACCOUNT        = '11002';
    const ACCOUNTS_RECEIVABLE = '12001';
    const INVENTORY           = '13007'; // Accessories / General Inventory
    const ACCOUNTS_PAYABLE    = '21001';
    const SALES_REVENUE       = '41006'; // Small Appliances Sales (fallback)
    const COGS                = '51006'; // Cost of Small Appliances (fallback)

    // ─── ACCOUNT ID CACHE ────────────────────────────────────────────────
    private static array $accountCache  = [];
    private static array $categoryCache = [];

    // ─── RESOLVE ACCOUNT ID FROM CODE ────────────────────────────────────
    public static function getAccountId(string $code, int $companyId = 1): ?int
    {
        $key = $companyId . '_' . $code;
        if (!isset(self::$accountCache[$key])) {
            $acct = ChartOfAccount::where('company_id', $companyId)
                ->where('account_code', $code)
                ->value('id');
            self::$accountCache[$key] = $acct;
        }
        return self::$accountCache[$key] ?: null;
    }

    // ─── LOAD CATEGORY WITH COA ACCOUNT IDS ──────────────────────────────
    private static function getCategoryAccounts(int $categoryId, int $companyId): array
    {
        $key = $companyId . '_cat_' . $categoryId;
        if (!isset(self::$categoryCache[$key])) {
            $cat = Category::withoutGlobalScope(\App\Scopes\CompanyScope::class)
                ->where('id', $categoryId)
                ->first(['id', 'sales_account_id', 'cogs_account_id', 'inventory_account_id']);

            self::$categoryCache[$key] = [
                'sales'     => $cat?->sales_account_id ?: self::getAccountId(self::SALES_REVENUE, $companyId),
                'cogs'      => $cat?->cogs_account_id  ?: self::getAccountId(self::COGS, $companyId),
                'inventory' => $cat?->inventory_account_id ?: self::getAccountId(self::INVENTORY, $companyId),
            ];
        }
        return self::$categoryCache[$key];
    }

    // ─── ENTRY NUMBER GENERATOR ───────────────────────────────────────────
    private static function nextEntryNumber(int $companyId): string
    {
        $count = JournalEntry::where('company_id', $companyId)->count();
        return 'JE-' . date('Ymd') . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }

    // ─── CORE: CREATE JOURNAL ENTRY (by account CODE) ─────────────────────
    public static function createEntry(
        int    $companyId,
        string $description,
        string $reference,
        string $date,
        array  $lines   // [['account_code'=>'11001','debit'=>100,'credit'=>0,'note'=>''], ...]
    ): ?JournalEntry {
        $totalDebit  = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));

        if (round($totalDebit, 2) !== round($totalCredit, 2) || $totalDebit <= 0) {
            Log::warning("AccountingService: Imbalanced entry skipped — $description D:{$totalDebit} C:{$totalCredit}");
            return null;
        }

        $resolvedLines = [];
        foreach ($lines as $line) {
            $accountId = self::getAccountId($line['account_code'], $companyId);
            if (!$accountId) {
                Log::warning("AccountingService: Account {$line['account_code']} not found, skipping: $description");
                return null;
            }
            $resolvedLines[] = [
                'account_id'  => $accountId,
                'debit'       => $line['debit'],
                'credit'      => $line['credit'],
                'description' => $line['note'] ?? null,
            ];
        }

        return self::createEntryWithLines($companyId, $description, $reference, $date, $resolvedLines);
    }

    // ─── CORE: CREATE JOURNAL ENTRY (by account ID, multi-line) ──────────
    public static function createEntryById(
        int    $companyId,
        string $description,
        string $reference,
        string $date,
        array  $lines   // [['account_id'=>7,'debit'=>100,'credit'=>0,'note'=>''], ...]
    ): ?JournalEntry {
        $totalDebit  = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));

        if (round($totalDebit, 2) !== round($totalCredit, 2) || $totalDebit <= 0) {
            Log::warning("AccountingService: Imbalanced entry skipped — $description D:{$totalDebit} C:{$totalCredit}");
            return null;
        }

        // Filter zero-value lines
        $lines = array_filter($lines, fn($l) => ($l['debit'] + $l['credit']) > 0);
        if (empty($lines)) return null;

        $resolvedLines = array_map(fn($l) => [
            'account_id'  => $l['account_id'],
            'debit'       => $l['debit'],
            'credit'      => $l['credit'],
            'description' => $l['note'] ?? null,
        ], array_values($lines));

        return self::createEntryWithLines($companyId, $description, $reference, $date, $resolvedLines);
    }

    private static function createEntryWithLines(
        int    $companyId,
        string $description,
        string $reference,
        string $date,
        array  $resolvedLines
    ): ?JournalEntry {
        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'company_id'   => $companyId,
                'entry_number' => self::nextEntryNumber($companyId),
                'entry_date'   => $date,
                'reference'    => $reference,
                'description'  => $description,
                'status'       => 'posted',
                'created_by'   => auth('api')->id(),
            ]);

            foreach ($resolvedLines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $line['account_id'],
                    'description'      => $line['description'],
                    'debit'            => $line['debit'],
                    'credit'           => $line['credit'],
                ]);
            }

            DB::commit();
            return $entry;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("AccountingService: Failed to create entry — " . $e->getMessage());
            return null;
        }
    }

    // ─── LOAD ORDER ITEMS WITH PRODUCT CATEGORY ──────────────────────────
    private static function loadItemsWithCategory(Order $order, int $companyId): array
    {
        $items = OrderItem::where('order_id', $order->id)->get();
        $result = [];

        foreach ($items as $item) {
            $product = Product::withoutGlobalScope(\App\Scopes\CompanyScope::class)
                ->find($item->product_id, ['id', 'category_id']);

            $categoryId = $product?->category_id ?? 0;
            $catAccts   = self::getCategoryAccounts((int)$categoryId, $companyId);

            $productDetail = ProductDetails::withoutGlobalScope('current_warehouse')
                ->where('product_id', $item->product_id)
                ->where('warehouse_id', $order->warehouse_id)
                ->first(['purchase_price']);

            $result[] = [
                'subtotal'          => round((float)$item->subtotal, 2),
                'cost'              => round(((float)($productDetail?->purchase_price ?? 0)) * (float)$item->quantity, 2),
                'sales_account_id'  => $catAccts['sales'],
                'cogs_account_id'   => $catAccts['cogs'],
                'inventory_account_id' => $catAccts['inventory'],
            ];
        }

        return $result;
    }

    // ─── AGGREGATE ITEMS BY ACCOUNT ──────────────────────────────────────
    private static function sumByAccount(array $items, string $amountKey, string $accountKey): array
    {
        $grouped = [];
        foreach ($items as $item) {
            $acctId = $item[$accountKey];
            if (!$acctId) continue;
            $grouped[$acctId] = ($grouped[$acctId] ?? 0) + $item[$amountKey];
        }
        return $grouped; // [account_id => amount]
    }

    // ─── SALES TRANSACTION ────────────────────────────────────────────────
    public static function onSaleCreated(Order $order): void
    {
        try {
            $companyId = $order->company_id ?? 1;
            $total     = round((float)$order->total, 2);
            if ($total <= 0) return;

            $date      = $order->order_date ?? now()->toDateString();
            $reference = $order->invoice_number;

            $items = self::loadItemsWithCategory($order, $companyId);

            // ── 1. REVENUE ENTRY ──────────────────────────────────────────
            // DR: AR or Cash (total)
            // CR: Per-category Sales Revenue (subtotals)
            $paidAmount = round((float)$order->paid_amount, 2);
            $debitAcctId = $paidAmount >= $total
                ? self::getAccountId(self::CASH_IN_HAND, $companyId)
                : self::getAccountId(self::ACCOUNTS_RECEIVABLE, $companyId);

            $revByAcct = self::sumByAccount($items, 'subtotal', 'sales_account_id');
            $revTotal  = array_sum($revByAcct);

            // Build revenue lines
            $revenueLines = [['account_id' => $debitAcctId, 'debit' => $revTotal ?: $total, 'credit' => 0, 'note' => 'Sale']];
            foreach ($revByAcct as $acctId => $amount) {
                if ($amount > 0) {
                    $revenueLines[] = ['account_id' => $acctId, 'debit' => 0, 'credit' => $amount, 'note' => 'Sales revenue'];
                }
            }
            // Fallback if no items loaded
            if (count($revenueLines) === 1) {
                $fallbackSales = self::getAccountId(self::SALES_REVENUE, $companyId);
                $revenueLines[] = ['account_id' => $fallbackSales, 'debit' => 0, 'credit' => $total, 'note' => 'Sales revenue'];
                $revenueLines[0]['debit'] = $total;
            }

            self::createEntryById($companyId, "Sale — {$reference}", $reference, $date, $revenueLines);

            // ── 2. COGS ENTRY ─────────────────────────────────────────────
            // DR: Per-category COGS
            // CR: Per-category Inventory
            self::buildAndPostCogsEntry($items, $companyId, $date, $reference, "COGS — {$reference}");

        } catch (\Throwable $e) {
            Log::error('AccountingService::onSaleCreated — ' . $e->getMessage() . ' ' . $e->getTraceAsString());
        }
    }

    private static function buildAndPostCogsEntry(
        array  $items,
        int    $companyId,
        string $date,
        string $reference,
        string $description,
        bool   $reverse = false
    ): void {
        $cogsByAcct  = self::sumByAccount($items, 'cost', 'cogs_account_id');
        $invByAcct   = self::sumByAccount($items, 'cost', 'inventory_account_id');
        $totalCost   = array_sum($cogsByAcct);

        if ($totalCost <= 0) return;

        $cogsLines = [];

        if (!$reverse) {
            // Normal: DR COGS / CR Inventory
            foreach ($cogsByAcct as $acctId => $amount) {
                if ($amount > 0) $cogsLines[] = ['account_id' => $acctId, 'debit' => round($amount, 2), 'credit' => 0, 'note' => 'Cost of goods sold'];
            }
            foreach ($invByAcct as $acctId => $amount) {
                if ($amount > 0) $cogsLines[] = ['account_id' => $acctId, 'debit' => 0, 'credit' => round($amount, 2), 'note' => 'Inventory reduction'];
            }
        } else {
            // Reversal: DR Inventory / CR COGS
            foreach ($invByAcct as $acctId => $amount) {
                if ($amount > 0) $cogsLines[] = ['account_id' => $acctId, 'debit' => round($amount, 2), 'credit' => 0, 'note' => 'Return to inventory'];
            }
            foreach ($cogsByAcct as $acctId => $amount) {
                if ($amount > 0) $cogsLines[] = ['account_id' => $acctId, 'debit' => 0, 'credit' => round($amount, 2), 'note' => 'COGS reversal'];
            }
        }

        if (!empty($cogsLines)) {
            self::createEntryById($companyId, $description, $reference, $date, $cogsLines);
        }
    }

    // ─── PURCHASE TRANSACTION ─────────────────────────────────────────────
    public static function onPurchaseCreated(Order $order): void
    {
        try {
            $companyId = $order->company_id ?? 1;
            $total     = round((float)$order->total, 2);
            if ($total <= 0) return;

            $date      = $order->order_date ?? now()->toDateString();
            $reference = $order->invoice_number;

            $items = self::loadItemsWithCategory($order, $companyId);

            // DR: Per-category Inventory (subtotals)
            // CR: Accounts Payable (total)
            $invByAcct = self::sumByAccount($items, 'subtotal', 'inventory_account_id');
            $apAcctId  = self::getAccountId(self::ACCOUNTS_PAYABLE, $companyId);
            $invTotal  = array_sum($invByAcct);

            $purchaseLines = [];
            foreach ($invByAcct as $acctId => $amount) {
                if ($amount > 0) $purchaseLines[] = ['account_id' => $acctId, 'debit' => round($amount, 2), 'credit' => 0, 'note' => 'Stock purchased'];
            }
            $purchaseLines[] = ['account_id' => $apAcctId, 'debit' => 0, 'credit' => $invTotal ?: $total, 'note' => 'Supplier payable'];

            // Fallback if no items
            if (count($purchaseLines) === 1) {
                $fallbackInv = self::getAccountId(self::INVENTORY, $companyId);
                array_unshift($purchaseLines, ['account_id' => $fallbackInv, 'debit' => $total, 'credit' => 0, 'note' => 'Stock purchased']);
                $purchaseLines[count($purchaseLines) - 1]['credit'] = $total;
            }

            self::createEntryById($companyId, "Purchase — {$reference}", $reference, $date, $purchaseLines);

        } catch (\Throwable $e) {
            Log::error('AccountingService::onPurchaseCreated — ' . $e->getMessage());
        }
    }

    // ─── SALES RETURN ─────────────────────────────────────────────────────
    public static function onSaleReturnCreated(Order $order): void
    {
        try {
            $companyId = $order->company_id ?? 1;
            $total     = round((float)$order->total, 2);
            if ($total <= 0) return;

            $date      = $order->order_date ?? now()->toDateString();
            $reference = $order->invoice_number;

            $items = self::loadItemsWithCategory($order, $companyId);

            // DR: Sales Revenue / CR: AR
            $revByAcct  = self::sumByAccount($items, 'subtotal', 'sales_account_id');
            $arAcctId   = self::getAccountId(self::ACCOUNTS_RECEIVABLE, $companyId);
            $revTotal   = array_sum($revByAcct);

            $returnLines = [];
            foreach ($revByAcct as $acctId => $amount) {
                if ($amount > 0) $returnLines[] = ['account_id' => $acctId, 'debit' => round($amount, 2), 'credit' => 0, 'note' => 'Sales return'];
            }
            $returnLines[] = ['account_id' => $arAcctId, 'debit' => 0, 'credit' => $revTotal ?: $total, 'note' => 'Return to customer'];

            if (count($returnLines) === 1) {
                $fallback = self::getAccountId(self::SALES_REVENUE, $companyId);
                array_unshift($returnLines, ['account_id' => $fallback, 'debit' => $total, 'credit' => 0, 'note' => 'Sales return']);
                $returnLines[count($returnLines) - 1]['credit'] = $total;
            }

            self::createEntryById($companyId, "Sales Return — {$reference}", $reference, $date, $returnLines);

            // Reverse COGS (inventory back)
            self::buildAndPostCogsEntry($items, $companyId, $date, $reference, "COGS Reversal — {$reference}", true);

        } catch (\Throwable $e) {
            Log::error('AccountingService::onSaleReturnCreated — ' . $e->getMessage());
        }
    }

    // ─── PURCHASE RETURN ──────────────────────────────────────────────────
    public static function onPurchaseReturnCreated(Order $order): void
    {
        try {
            $companyId = $order->company_id ?? 1;
            $total     = round((float)$order->total, 2);
            if ($total <= 0) return;

            $date      = $order->order_date ?? now()->toDateString();
            $reference = $order->invoice_number;

            $items = self::loadItemsWithCategory($order, $companyId);

            // DR: AP / CR: Per-category Inventory
            $invByAcct = self::sumByAccount($items, 'subtotal', 'inventory_account_id');
            $apAcctId  = self::getAccountId(self::ACCOUNTS_PAYABLE, $companyId);
            $invTotal  = array_sum($invByAcct);

            $returnLines = [['account_id' => $apAcctId, 'debit' => $invTotal ?: $total, 'credit' => 0, 'note' => 'AP reduced']];
            foreach ($invByAcct as $acctId => $amount) {
                if ($amount > 0) $returnLines[] = ['account_id' => $acctId, 'debit' => 0, 'credit' => round($amount, 2), 'note' => 'Inventory returned'];
            }

            if (count($returnLines) === 1) {
                $fallbackInv = self::getAccountId(self::INVENTORY, $companyId);
                $returnLines[] = ['account_id' => $fallbackInv, 'debit' => 0, 'credit' => $total, 'note' => 'Inventory returned'];
                $returnLines[0]['debit'] = $total;
            }

            self::createEntryById($companyId, "Purchase Return — {$reference}", $reference, $date, $returnLines);

        } catch (\Throwable $e) {
            Log::error('AccountingService::onPurchaseReturnCreated — ' . $e->getMessage());
        }
    }

    // ─── PAYMENT IN (Customer pays us) ────────────────────────────────────
    public static function onPaymentInCreated(Payment $payment): void
    {
        try {
            $companyId = $payment->company_id ?? 1;
            $amount    = round((float)$payment->amount, 2);
            if ($amount <= 0) return;

            $date      = $payment->date ? date('Y-m-d', strtotime($payment->date)) : now()->toDateString();
            $reference = $payment->payment_number;
            $cashAcct  = self::getPaymentAccount($payment);

            self::createEntry($companyId, "Customer Payment — {$reference}", $reference, $date, [
                ['account_code' => $cashAcct,                 'debit' => $amount, 'credit' => 0,      'note' => 'Cash/Bank received'],
                ['account_code' => self::ACCOUNTS_RECEIVABLE, 'debit' => 0,       'credit' => $amount, 'note' => 'AR cleared'],
            ]);
        } catch (\Throwable $e) {
            Log::error('AccountingService::onPaymentInCreated — ' . $e->getMessage());
        }
    }

    // ─── PAYMENT OUT (We pay supplier) ────────────────────────────────────
    public static function onPaymentOutCreated(Payment $payment): void
    {
        try {
            $companyId = $payment->company_id ?? 1;
            $amount    = round((float)$payment->amount, 2);
            if ($amount <= 0) return;

            $date      = $payment->date ? date('Y-m-d', strtotime($payment->date)) : now()->toDateString();
            $reference = $payment->payment_number;
            $cashAcct  = self::getPaymentAccount($payment);

            self::createEntry($companyId, "Supplier Payment — {$reference}", $reference, $date, [
                ['account_code' => self::ACCOUNTS_PAYABLE, 'debit' => $amount, 'credit' => 0,      'note' => 'AP cleared'],
                ['account_code' => $cashAcct,              'debit' => 0,       'credit' => $amount, 'note' => 'Cash/Bank paid'],
            ]);
        } catch (\Throwable $e) {
            Log::error('AccountingService::onPaymentOutCreated — ' . $e->getMessage());
        }
    }

    // ─── HELPER: Cash or Bank account code from payment mode ─────────────
    private static function getPaymentAccount(Payment $payment): string
    {
        if (!$payment->payment_mode_id) return self::CASH_IN_HAND;
        $mode = PaymentMode::find($payment->payment_mode_id);
        if ($mode && $mode->mode_type === 'bank') return self::BANK_ACCOUNT;
        return self::CASH_IN_HAND;
    }

    // ─── DISPATCH based on order_type ─────────────────────────────────────
    public static function handleOrder(Order $order): void
    {
        match ($order->order_type) {
            'sales'            => self::onSaleCreated($order),
            'purchases', 'grn' => self::onPurchaseCreated($order),
            'sales-returns'    => self::onSaleReturnCreated($order),
            'purchase-returns' => self::onPurchaseReturnCreated($order),
            default            => null,
        };
    }

    // ─── DISPATCH based on payment_type ───────────────────────────────────
    public static function handlePayment(Payment $payment): void
    {
        if ($payment->payment_type === 'in') {
            self::onPaymentInCreated($payment);
        } elseif ($payment->payment_type === 'out') {
            self::onPaymentOutCreated($payment);
        }
    }
}
