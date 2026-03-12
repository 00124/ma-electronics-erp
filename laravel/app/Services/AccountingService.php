<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMode;
use App\Models\ProductDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    // ─── ACCOUNT CODE CONSTANTS ───────────────────────────────────────────
    const CASH_IN_HAND        = '11001';
    const BANK_ACCOUNT        = '11002';
    const ACCOUNTS_RECEIVABLE = '12001';
    const INVENTORY           = '13001'; // General Inventory
    const ACCOUNTS_PAYABLE    = '21001';
    const SALES_REVENUE       = '41006'; // General Sales (Small Appliances)
    const COGS                = '51006'; // General COGS

    // ─── ACCOUNT CACHE ────────────────────────────────────────────────────
    private static array $accountCache = [];

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

    // ─── ENTRY NUMBER GENERATOR ───────────────────────────────────────────
    private static function nextEntryNumber(int $companyId): string
    {
        $count = JournalEntry::where('company_id', $companyId)->count();
        return 'JE-' . date('Ymd') . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }

    // ─── CORE: CREATE JOURNAL ENTRY ───────────────────────────────────────
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

        // Validate all accounts exist
        $resolvedLines = [];
        foreach ($lines as $line) {
            $accountId = self::getAccountId($line['account_code'], $companyId);
            if (!$accountId) {
                Log::warning("AccountingService: Account {$line['account_code']} not found, skipping entry: $description");
                return null;
            }
            $resolvedLines[] = [
                'account_id'  => $accountId,
                'debit'       => $line['debit'],
                'credit'      => $line['credit'],
                'description' => $line['note'] ?? null,
            ];
        }

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

    // ─── SALES TRANSACTION ────────────────────────────────────────────────
    public static function onSaleCreated(Order $order): void
    {
        try {
            $companyId = $order->company_id ?? 1;
            $amount    = round((float)$order->total, 2);
            if ($amount <= 0) return;

            $date      = $order->order_date ?? now()->toDateString();
            $reference = $order->invoice_number;

            // Determine debit account: Cash if fully paid, AR if on credit
            $paidAmount = round((float)$order->paid_amount, 2);
            $debitCode  = ($paidAmount >= $amount) ? self::CASH_IN_HAND : self::ACCOUNTS_RECEIVABLE;

            // 1. Revenue Entry
            self::createEntry($companyId, "Sale — {$reference}", $reference, $date, [
                ['account_code' => $debitCode,       'debit' => $amount, 'credit' => 0,      'note' => 'Sale revenue'],
                ['account_code' => self::SALES_REVENUE, 'debit' => 0,     'credit' => $amount, 'note' => 'Sales revenue'],
            ]);

            // 2. COGS Entry
            self::createCogsEntry($order, $companyId, $date, $reference);

        } catch (\Throwable $e) {
            Log::error('AccountingService::onSaleCreated — ' . $e->getMessage());
        }
    }

    private static function createCogsEntry(Order $order, int $companyId, string $date, string $reference): void
    {
        $items = OrderItem::where('order_id', $order->id)->get();
        $totalCost = 0;

        foreach ($items as $item) {
            $productDetail = ProductDetails::withoutGlobalScope('current_warehouse')
                ->where('product_id', $item->product_id)
                ->where('warehouse_id', $order->warehouse_id)
                ->first();

            $costPrice = $productDetail ? (float)$productDetail->purchase_price : 0;
            $totalCost += $costPrice * (float)$item->quantity;
        }

        if ($totalCost > 0) {
            $totalCost = round($totalCost, 2);
            self::createEntry($companyId, "COGS — {$reference}", $reference, $date, [
                ['account_code' => self::COGS,      'debit' => $totalCost, 'credit' => 0,          'note' => 'Cost of goods sold'],
                ['account_code' => self::INVENTORY, 'debit' => 0,         'credit' => $totalCost,  'note' => 'Inventory reduction'],
            ]);
        }
    }

    // ─── PURCHASE TRANSACTION ────────────────────────────────────────────
    public static function onPurchaseCreated(Order $order): void
    {
        try {
            $companyId = $order->company_id ?? 1;
            $amount    = round((float)$order->total, 2);
            if ($amount <= 0) return;

            $date      = $order->order_date ?? now()->toDateString();
            $reference = $order->invoice_number;

            self::createEntry($companyId, "Purchase — {$reference}", $reference, $date, [
                ['account_code' => self::INVENTORY,       'debit' => $amount, 'credit' => 0,      'note' => 'Stock increase'],
                ['account_code' => self::ACCOUNTS_PAYABLE,'debit' => 0,       'credit' => $amount, 'note' => 'Supplier payable'],
            ]);
        } catch (\Throwable $e) {
            Log::error('AccountingService::onPurchaseCreated — ' . $e->getMessage());
        }
    }

    // ─── SALES RETURN ────────────────────────────────────────────────────
    public static function onSaleReturnCreated(Order $order): void
    {
        try {
            $companyId = $order->company_id ?? 1;
            $amount    = round((float)$order->total, 2);
            if ($amount <= 0) return;

            $date      = $order->order_date ?? now()->toDateString();
            $reference = $order->invoice_number;

            self::createEntry($companyId, "Sales Return — {$reference}", $reference, $date, [
                ['account_code' => self::SALES_REVENUE,      'debit' => $amount, 'credit' => 0,      'note' => 'Sales return'],
                ['account_code' => self::ACCOUNTS_RECEIVABLE,'debit' => 0,       'credit' => $amount, 'note' => 'Return to customer'],
            ]);

            // Reverse COGS (inventory back)
            self::createCogsReverseEntry($order, $companyId, $date, $reference);

        } catch (\Throwable $e) {
            Log::error('AccountingService::onSaleReturnCreated — ' . $e->getMessage());
        }
    }

    private static function createCogsReverseEntry(Order $order, int $companyId, string $date, string $reference): void
    {
        $items = OrderItem::where('order_id', $order->id)->get();
        $totalCost = 0;
        foreach ($items as $item) {
            $pd = ProductDetails::withoutGlobalScope('current_warehouse')
                ->where('product_id', $item->product_id)
                ->where('warehouse_id', $order->warehouse_id)
                ->first();
            $totalCost += ((float)($pd?->purchase_price ?? 0)) * (float)$item->quantity;
        }
        if ($totalCost > 0) {
            $totalCost = round($totalCost, 2);
            self::createEntry($companyId, "COGS Reversal — {$reference}", $reference, $date, [
                ['account_code' => self::INVENTORY, 'debit' => $totalCost, 'credit' => 0,         'note' => 'Return to inventory'],
                ['account_code' => self::COGS,      'debit' => 0,         'credit' => $totalCost, 'note' => 'COGS reversal'],
            ]);
        }
    }

    // ─── PURCHASE RETURN ─────────────────────────────────────────────────
    public static function onPurchaseReturnCreated(Order $order): void
    {
        try {
            $companyId = $order->company_id ?? 1;
            $amount    = round((float)$order->total, 2);
            if ($amount <= 0) return;

            $date      = $order->order_date ?? now()->toDateString();
            $reference = $order->invoice_number;

            self::createEntry($companyId, "Purchase Return — {$reference}", $reference, $date, [
                ['account_code' => self::ACCOUNTS_PAYABLE, 'debit' => $amount, 'credit' => 0,      'note' => 'Supplier payable reduced'],
                ['account_code' => self::INVENTORY,        'debit' => 0,       'credit' => $amount, 'note' => 'Inventory reduction'],
            ]);
        } catch (\Throwable $e) {
            Log::error('AccountingService::onPurchaseReturnCreated — ' . $e->getMessage());
        }
    }

    // ─── PAYMENT IN (Customer pays us) ───────────────────────────────────
    public static function onPaymentInCreated(Payment $payment): void
    {
        try {
            $companyId = $payment->company_id ?? 1;
            $amount    = round((float)$payment->amount, 2);
            if ($amount <= 0) return;

            $date      = $payment->date ? date('Y-m-d', strtotime($payment->date)) : now()->toDateString();
            $reference = $payment->payment_number;

            // Determine if cash or bank from payment mode
            $cashAccount = self::getPaymentAccount($payment);

            self::createEntry($companyId, "Customer Payment — {$reference}", $reference, $date, [
                ['account_code' => $cashAccount,             'debit' => $amount, 'credit' => 0,      'note' => 'Cash/Bank received'],
                ['account_code' => self::ACCOUNTS_RECEIVABLE,'debit' => 0,       'credit' => $amount, 'note' => 'AR cleared'],
            ]);
        } catch (\Throwable $e) {
            Log::error('AccountingService::onPaymentInCreated — ' . $e->getMessage());
        }
    }

    // ─── PAYMENT OUT (We pay supplier) ───────────────────────────────────
    public static function onPaymentOutCreated(Payment $payment): void
    {
        try {
            $companyId = $payment->company_id ?? 1;
            $amount    = round((float)$payment->amount, 2);
            if ($amount <= 0) return;

            $date      = $payment->date ? date('Y-m-d', strtotime($payment->date)) : now()->toDateString();
            $reference = $payment->payment_number;

            $cashAccount = self::getPaymentAccount($payment);

            self::createEntry($companyId, "Supplier Payment — {$reference}", $reference, $date, [
                ['account_code' => self::ACCOUNTS_PAYABLE, 'debit' => $amount, 'credit' => 0,      'note' => 'AP cleared'],
                ['account_code' => $cashAccount,           'debit' => 0,       'credit' => $amount, 'note' => 'Cash/Bank paid'],
            ]);
        } catch (\Throwable $e) {
            Log::error('AccountingService::onPaymentOutCreated — ' . $e->getMessage());
        }
    }

    // ─── HELPER: Determine Cash or Bank account from payment mode ─────────
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
