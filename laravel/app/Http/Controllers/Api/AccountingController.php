<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Examyou\RestAPI\ApiResponse;
use Examyou\RestAPI\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;

class AccountingController extends ApiBaseController
{
    // ─── RESPONSE HELPERS ─────────────────────────────────────────────────

    protected function sendResponse($data, $message)
    {
        return ApiResponse::make($message, $data);
    }

    protected function sendError($message)
    {
        throw new ApiException($message);
    }

    // ─── CHART OF ACCOUNTS ────────────────────────────────────────────────

    public function coaIndex(Request $request)
    {
        $companyId = company()->id;
        $accounts = ChartOfAccount::where('company_id', $companyId)
            ->orderBy('account_code')
            ->get();

        // Build tree
        $map = [];
        foreach ($accounts as $a) {
            $map[$a->id] = $a->toArray();
            $map[$a->id]['children'] = [];
        }
        $tree = [];
        foreach ($map as &$node) {
            if ($node['parent_id'] && isset($map[$node['parent_id']])) {
                $map[$node['parent_id']]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        return $this->sendResponse(['data' => $tree, 'flat' => $accounts], '');
    }

    public function coaStore(Request $request)
    {
        $request->validate([
            'account_code' => 'required|string|max:20',
            'account_name' => 'required|string|max:150',
            'account_type' => 'required|in:Asset,Liability,Equity,Income,Expense,COGS',
        ]);
        $companyId = company()->id;
        $account = ChartOfAccount::create([
            'company_id'   => $companyId,
            'account_code' => $request->account_code,
            'account_name' => $request->account_name,
            'account_type' => $request->account_type,
            'parent_id'    => $request->parent_id ?: null,
            'description'  => $request->description,
            'status'       => 1,
        ]);
        return $this->sendResponse(['data' => $account], __('messages.created_successfully', ['name' => 'Account']));
    }

    public function coaUpdate(Request $request, $id)
    {
        $account = ChartOfAccount::findOrFail($id);
        $account->update($request->only(['account_code', 'account_name', 'account_type', 'parent_id', 'description', 'status']));
        return $this->sendResponse(['data' => $account], __('messages.updated_successfully', ['name' => 'Account']));
    }

    public function coaDestroy($id)
    {
        $account = ChartOfAccount::findOrFail($id);
        if (JournalEntryLine::where('account_id', $id)->exists()) {
            return $this->sendError('Cannot delete account with journal entries.');
        }
        $account->delete();
        return $this->sendResponse([], __('messages.deleted_successfully', ['name' => 'Account']));
    }

    // ─── JOURNAL ENTRIES ──────────────────────────────────────────────────

    public function journalIndex(Request $request)
    {
        $companyId = company()->id;
        $q = JournalEntry::where('company_id', $companyId)
            ->with('lines.account')
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->date_from) $q->where('entry_date', '>=', $request->date_from);
        if ($request->date_to)   $q->where('entry_date', '<=', $request->date_to);

        $entries = $q->paginate($request->per_page ?? 20);
        return $this->sendResponse($entries, '');
    }

    public function journalStore(Request $request)
    {
        $request->validate([
            'entry_date'  => 'required|date',
            'lines'       => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit'      => 'required|numeric|min:0',
            'lines.*.credit'     => 'required|numeric|min:0',
        ]);

        $totalDebit  = collect($request->lines)->sum('debit');
        $totalCredit = collect($request->lines)->sum('credit');
        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return $this->sendError('Debit and Credit must be equal. Debit: ' . $totalDebit . ', Credit: ' . $totalCredit);
        }

        $companyId = company()->id;
        $entryNumber = 'JE-' . date('Ymd') . '-' . str_pad(
            JournalEntry::where('company_id', $companyId)->count() + 1, 5, '0', STR_PAD_LEFT
        );

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'company_id'   => $companyId,
                'entry_number' => $entryNumber,
                'entry_date'   => $request->entry_date,
                'reference'    => $request->reference,
                'description'  => $request->description,
                'status'       => 'posted',
                'created_by'   => auth('api')->id(),
            ]);

            foreach ($request->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $line['account_id'],
                    'description'      => $line['description'] ?? null,
                    'debit'            => $line['debit'],
                    'credit'           => $line['credit'],
                ]);
            }

            DB::commit();
            return $this->sendResponse(['data' => $entry->load('lines.account')], 'Journal entry created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }
    }

    public function journalShow($id)
    {
        $entry = JournalEntry::with('lines.account')->findOrFail($id);
        return $this->sendResponse(['data' => $entry], '');
    }

    public function journalDestroy($id)
    {
        $entry = JournalEntry::findOrFail($id);
        $entry->delete();
        return $this->sendResponse([], 'Journal entry deleted.');
    }

    // ─── REPORTS ──────────────────────────────────────────────────────────

    public function trialBalance(Request $request)
    {
        $companyId = company()->id;
        $dateFrom = $request->date_from ?? '2000-01-01';
        $dateTo   = $request->date_to   ?? now()->toDateString();

        $rows = DB::select("
            SELECT
                coa.id,
                coa.account_code,
                coa.account_name,
                coa.account_type,
                coa.parent_id,
                COALESCE(SUM(jel.debit), 0)  AS total_debit,
                COALESCE(SUM(jel.credit), 0) AS total_credit,
                COALESCE(SUM(jel.debit), 0) - COALESCE(SUM(jel.credit), 0) AS balance
            FROM chart_of_accounts coa
            LEFT JOIN journal_entry_lines jel ON jel.account_id = coa.id
            LEFT JOIN journal_entries je ON je.id = jel.journal_entry_id
                AND je.entry_date BETWEEN ? AND ?
                AND je.status = 'posted'
                AND je.company_id = ?
            WHERE coa.company_id = ?
            GROUP BY coa.id, coa.account_code, coa.account_name, coa.account_type, coa.parent_id
            ORDER BY coa.account_code
        ", [$dateFrom, $dateTo, $companyId, $companyId]);

        $totalDebit  = collect($rows)->sum('total_debit');
        $totalCredit = collect($rows)->sum('total_credit');

        return $this->sendResponse([
            'data'         => $rows,
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
        ], '');
    }

    public function profitLoss(Request $request)
    {
        $companyId = company()->id;
        $dateFrom = $request->date_from ?? date('Y-01-01');
        $dateTo   = $request->date_to   ?? now()->toDateString();

        $rows = DB::select("
            SELECT
                coa.account_code,
                coa.account_name,
                coa.account_type,
                COALESCE(SUM(jel.credit - jel.debit), 0) AS net
            FROM chart_of_accounts coa
            LEFT JOIN journal_entry_lines jel ON jel.account_id = coa.id
            LEFT JOIN journal_entries je ON je.id = jel.journal_entry_id
                AND je.entry_date BETWEEN ? AND ?
                AND je.status = 'posted'
                AND je.company_id = ?
            WHERE coa.company_id = ?
            AND coa.account_type IN ('Income','COGS','Expense')
            AND coa.parent_id IS NOT NULL
            GROUP BY coa.id
            ORDER BY coa.account_type, coa.account_code
        ", [$dateFrom, $dateTo, $companyId, $companyId]);

        $revenue   = collect($rows)->where('account_type', 'Income')->sum('net');
        $cogs      = collect($rows)->where('account_type', 'COGS')->sum('net');
        $expenses  = collect($rows)->where('account_type', 'Expense')->sum('net');
        $grossProfit = $revenue - abs($cogs);
        $netProfit   = $grossProfit - abs($expenses);

        return $this->sendResponse([
            'data'          => $rows,
            'total_revenue' => $revenue,
            'total_cogs'    => abs($cogs),
            'gross_profit'  => $grossProfit,
            'total_expenses'=> abs($expenses),
            'net_profit'    => $netProfit,
            'date_from'     => $dateFrom,
            'date_to'       => $dateTo,
        ], '');
    }

    public function balanceSheet(Request $request)
    {
        $companyId = company()->id;
        $asOf = $request->as_of ?? now()->toDateString();

        $rows = DB::select("
            SELECT
                coa.account_code,
                coa.account_name,
                coa.account_type,
                coa.parent_id,
                COALESCE(SUM(jel.debit - jel.credit), 0) AS balance
            FROM chart_of_accounts coa
            LEFT JOIN journal_entry_lines jel ON jel.account_id = coa.id
            LEFT JOIN journal_entries je ON je.id = jel.journal_entry_id
                AND je.entry_date <= ?
                AND je.status = 'posted'
                AND je.company_id = ?
            WHERE coa.company_id = ?
            AND coa.account_type IN ('Asset','Liability','Equity')
            AND coa.parent_id IS NOT NULL
            GROUP BY coa.id
            ORDER BY coa.account_type, coa.account_code
        ", [$asOf, $companyId, $companyId]);

        $assets      = collect($rows)->where('account_type', 'Asset')->sum('balance');
        $liabilities = collect($rows)->where('account_type', 'Liability')->sum(fn($r) => abs($r->balance));
        $equity      = collect($rows)->where('account_type', 'Equity')->sum(fn($r) => abs($r->balance));

        return $this->sendResponse([
            'data'              => $rows,
            'total_assets'      => $assets,
            'total_liabilities' => $liabilities,
            'total_equity'      => $equity,
            'as_of'             => $asOf,
        ], '');
    }

    public function generalLedger(Request $request)
    {
        $request->validate(['account_id' => 'required|exists:chart_of_accounts,id']);
        $companyId = company()->id;
        $dateFrom  = $request->date_from ?? '2000-01-01';
        $dateTo    = $request->date_to   ?? now()->toDateString();

        $account = ChartOfAccount::findOrFail($request->account_id);
        $lines = DB::select("
            SELECT
                je.entry_date,
                je.entry_number,
                je.description AS je_description,
                jel.description,
                jel.debit,
                jel.credit
            FROM journal_entry_lines jel
            JOIN journal_entries je ON je.id = jel.journal_entry_id
            WHERE jel.account_id = ?
            AND je.entry_date BETWEEN ? AND ?
            AND je.status = 'posted'
            AND je.company_id = ?
            ORDER BY je.entry_date, je.id
        ", [$request->account_id, $dateFrom, $dateTo, $companyId]);

        $runningBalance = 0;
        foreach ($lines as &$line) {
            $runningBalance += ($line->debit - $line->credit);
            $line->running_balance = $runningBalance;
        }

        return $this->sendResponse([
            'account'   => $account,
            'lines'     => $lines,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ], '');
    }

    // ─── CUSTOMER LEDGER ──────────────────────────────────────────────────

    public function customerLedger(Request $request)
    {
        $companyId = company()->id;
        $dateFrom  = $request->date_from ?? '2000-01-01';
        $dateTo    = $request->date_to   ?? now()->toDateString();
        $userId    = $request->user_id   ?? null;

        // Sales (debit: increases balance owed by customer)
        $salesQuery = Order::select(
                'order_date as date',
                'invoice_number as reference',
                DB::raw("'Sale' as type"),
                'total as debit',
                DB::raw('0 as credit'),
                'user_id'
            )
            ->where('company_id', $companyId)
            ->whereIn('order_type', ['sales'])
            ->whereBetween('order_date', [$dateFrom, $dateTo]);

        // Sales Returns (credit: reduces balance owed)
        $returnQuery = Order::select(
                'order_date as date',
                'invoice_number as reference',
                DB::raw("'Sales Return' as type"),
                DB::raw('0 as debit'),
                'total as credit',
                'user_id'
            )
            ->where('company_id', $companyId)
            ->whereIn('order_type', ['sales-returns'])
            ->whereBetween('order_date', [$dateFrom, $dateTo]);

        // Payments in (credit: customer paid, reduces balance owed)
        $paymentQuery = Payment::select(
                DB::raw('DATE(date) as date'),
                'payment_number as reference',
                DB::raw("'Payment Received' as type"),
                DB::raw('0 as debit'),
                'amount as credit',
                'user_id'
            )
            ->where('company_id', $companyId)
            ->where('payment_type', 'in')
            ->whereBetween(DB::raw('DATE(date)'), [$dateFrom, $dateTo]);

        if ($userId) {
            $salesQuery->where('user_id', $userId);
            $returnQuery->where('user_id', $userId);
            $paymentQuery->where('user_id', $userId);
        }

        $rows = $salesQuery->union($returnQuery)->union($paymentQuery)
            ->orderBy('date')->orderBy('reference')
            ->get();

        $runningBalance = 0;
        foreach ($rows as $row) {
            $runningBalance += ($row->debit - $row->credit);
            $row->running_balance = $runningBalance;
        }

        $customer = $userId ? User::find($userId) : null;

        return $this->sendResponse([
            'rows'      => $rows,
            'customer'  => $customer,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ], '');
    }

    // ─── SUPPLIER LEDGER ──────────────────────────────────────────────────

    public function supplierLedger(Request $request)
    {
        $companyId = company()->id;
        $dateFrom  = $request->date_from ?? '2000-01-01';
        $dateTo    = $request->date_to   ?? now()->toDateString();
        $userId    = $request->user_id   ?? null;

        // Purchases (credit: increases amount owed to supplier)
        $purchaseQuery = Order::select(
                'order_date as date',
                'invoice_number as reference',
                DB::raw("'Purchase' as type"),
                DB::raw('0 as debit'),
                'total as credit',
                'user_id'
            )
            ->where('company_id', $companyId)
            ->whereIn('order_type', ['purchases', 'grn'])
            ->whereBetween('order_date', [$dateFrom, $dateTo]);

        // Purchase Returns (debit: reduces amount owed to supplier)
        $returnQuery = Order::select(
                'order_date as date',
                'invoice_number as reference',
                DB::raw("'Purchase Return' as type"),
                'total as debit',
                DB::raw('0 as credit'),
                'user_id'
            )
            ->where('company_id', $companyId)
            ->whereIn('order_type', ['purchase-returns'])
            ->whereBetween('order_date', [$dateFrom, $dateTo]);

        // Payments out (debit: we paid supplier, reduces balance owed)
        $paymentQuery = Payment::select(
                DB::raw('DATE(date) as date'),
                'payment_number as reference',
                DB::raw("'Payment Made' as type"),
                'amount as debit',
                DB::raw('0 as credit'),
                'user_id'
            )
            ->where('company_id', $companyId)
            ->where('payment_type', 'out')
            ->whereBetween(DB::raw('DATE(date)'), [$dateFrom, $dateTo]);

        if ($userId) {
            $purchaseQuery->where('user_id', $userId);
            $returnQuery->where('user_id', $userId);
            $paymentQuery->where('user_id', $userId);
        }

        $rows = $purchaseQuery->union($returnQuery)->union($paymentQuery)
            ->orderBy('date')->orderBy('reference')
            ->get();

        $runningBalance = 0;
        foreach ($rows as $row) {
            $runningBalance += ($row->credit - $row->debit);
            $row->running_balance = $runningBalance;
        }

        $supplier = $userId ? User::find($userId) : null;

        return $this->sendResponse([
            'rows'      => $rows,
            'supplier'  => $supplier,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ], '');
    }
}
