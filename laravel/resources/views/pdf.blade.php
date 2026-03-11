<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background: #fff;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 10mm 12mm 8mm 12mm;
            position: relative;
        }

        /* ── HEADER ─────────────────────────────────── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .header-left {
            width: 50%;
            vertical-align: top;
        }
        .header-left img {
            max-width: 130px;
            max-height: 70px;
            display: block;
            margin-bottom: 4px;
        }
        .company-name {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .company-sub {
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }
        .header-right {
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        .bill-to-box {
            display: inline-block;
            text-align: left;
            border: 1px solid #000;
            padding: 6px 10px;
            min-width: 200px;
        }
        .bill-to-title {
            font-size: 11px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 4px;
        }
        .bill-to-row {
            font-size: 11px;
            line-height: 1.7;
        }
        .bill-to-row strong {
            display: inline-block;
            min-width: 90px;
        }

        /* ── INVOICE TITLE ──────────────────────────── */
        .invoice-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 3px;
            margin: 10px 0 6px 0;
            text-transform: uppercase;
        }
        .invoice-meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .invoice-meta-table th {
            background: #000;
            color: #fff;
            border: 1px solid #000;
            padding: 5px 8px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
        }
        .invoice-meta-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            text-align: center;
            font-size: 11px;
        }

        /* ── PRODUCT TABLE ──────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .items-table th {
            background: #000;
            color: #fff;
            border: 1px solid #000;
            padding: 6px 7px;
            text-align: left;
            font-size: 11px;
        }
        .items-table th.text-right,
        .items-table td.text-right {
            text-align: right;
        }
        .items-table th.text-center,
        .items-table td.text-center {
            text-align: center;
        }
        .items-table td {
            border: 1px solid #000;
            padding: 5px 7px;
            font-size: 11px;
            vertical-align: top;
        }
        .items-table tr:nth-child(even) td {
            background: #f7f7f7;
        }
        .items-table .code-col { width: 9%; }
        .items-table .desc-col { width: 38%; }
        .items-table .qty-col  { width: 10%; }
        .items-table .price-col{ width: 20%; }
        .items-table .total-col{ width: 23%; }

        /* ── PAYMENT + TOTALS ───────────────────────── */
        .payment-section {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .payment-left {
            width: 55%;
            vertical-align: top;
            padding-right: 8px;
        }
        .payment-right {
            width: 45%;
            vertical-align: top;
        }
        .payment-modes-table {
            width: 100%;
            border-collapse: collapse;
        }
        .payment-modes-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 11px;
        }
        .payment-modes-table td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .totals-box {
            border: 2px solid #000;
            width: 100%;
            border-collapse: collapse;
        }
        .totals-box td {
            border: 1px solid #000;
            padding: 6px 10px;
            font-size: 12px;
        }
        .totals-box td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .totals-box .grand-row td {
            background: #000;
            color: #fff;
            font-weight: bold;
            font-size: 13px;
        }

        /* ── CUSTOMER BALANCE ───────────────────────── */
        .balance-section {
            border: 1px solid #000;
            padding: 8px 12px;
            margin-bottom: 10px;
        }
        .balance-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
            text-decoration: underline;
        }
        .balance-table {
            width: 100%;
            border-collapse: collapse;
        }
        .balance-table td {
            padding: 3px 6px;
            font-size: 11px;
            width: 50%;
        }
        .balance-table td strong {
            min-width: 80px;
            display: inline-block;
        }

        /* ── FOOTER ─────────────────────────────────── */
        .footer-divider {
            border-top: 1px solid #000;
            margin: 10px 0 6px 0;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td {
            vertical-align: top;
            font-size: 10px;
            color: #333;
            padding: 0 4px;
        }
        .footer-left  { width: 38%; }
        .footer-center{ width: 24%; text-align: center; }
        .footer-right { width: 38%; text-align: right; }

        .delivered-stamp {
            display: inline-block;
            border: 3px solid #000;
            border-radius: 4px;
            padding: 6px 14px;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 3px;
            color: #000;
            opacity: 0.25;
            transform: rotate(-10deg);
            margin-top: 4px;
        }

        /* ── PRINT ───────────────────────────────────── */
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .page { padding: 8mm 10mm; }
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ══ HEADER ══════════════════════════════════════════ --}}
    <table class="header-table">
        <tr>
            <td class="header-left">
                <img src="{{ $warehouse->logo ? public_path('uploads/warehouses/'.$warehouse->logo) : App\Classes\Common::getWarehouseImage('light', $company->id, 'public') }}" alt="Logo" />
                <div class="company-name">{{ $warehouse->name }}</div>
                <div class="company-sub">
                    @if($warehouse->address){{ $warehouse->address }}<br>@endif
                    @if($warehouse->phone)<strong>Contact No:</strong> {{ $warehouse->phone }}<br>@endif
                    @if($warehouse->email){{ $warehouse->email }}@endif
                </div>
            </td>
            <td class="header-right">
                <div class="bill-to-box">
                    <div class="bill-to-title">BILL TO:</div>
                    @if($order->order_type !== 'stock-transfers')
                        @php $cust = $order->user; @endphp
                        @if(isset($customer) && $customer && $customer->customer_code ?? false)
                        <div class="bill-to-row"><strong>Code:</strong> {{ $customer->customer_code }}</div>
                        @endif
                        <div class="bill-to-row"><strong>Name:</strong> {{ $cust->name ?? '-' }}</div>
                        @if($cust->phone ?? false)
                        <div class="bill-to-row"><strong>Phone:</strong> {{ $cust->phone }}</div>
                        @endif
                        @if($cust->address ?? false)
                        <div class="bill-to-row"><strong>Address:</strong> {{ $cust->address }}</div>
                        @endif
                    @else
                        <div class="bill-to-row"><strong>Warehouse:</strong> {{ $order->warehouse->name ?? '-' }}</div>
                    @endif
                    @if($staffMember)
                    <div class="bill-to-row" style="margin-top:4px;"><strong>Salesman:</strong> {{ $staffMember->name }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- ══ INVOICE TITLE ════════════════════════════════════ --}}
    <div class="invoice-title">
        @if($order->order_type == 'purchases') PURCHASE INVOICE
        @elseif($order->order_type == 'purchase-returns') PURCHASE RETURN
        @elseif($order->order_type == 'sales-returns') SALES RETURN
        @elseif($order->order_type == 'quotations') QUOTATION
        @else INVOICE
        @endif
    </div>

    <table class="invoice-meta-table">
        <thead>
            <tr>
                <th>Invoice Type</th>
                <th>Invoice Number</th>
                <th>Location</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    @if($order->order_type == 'sales') INVCR
                    @elseif($order->order_type == 'purchases') PUR
                    @elseif($order->order_type == 'purchase-returns') PUR-RET
                    @elseif($order->order_type == 'sales-returns') SALE-RET
                    @elseif($order->order_type == 'quotations') QUOT
                    @else {{ strtoupper($order->order_type) }}
                    @endif
                </td>
                <td><strong>{{ $order->invoice_number }}</strong></td>
                <td>{{ $warehouse->name }}</td>
                <td>{{ $order->order_date->format('j F Y') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ══ PRODUCT TABLE ════════════════════════════════════ --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="code-col">Code</th>
                <th class="desc-col">Description</th>
                <th class="qty-col text-center">QTY</th>
                <th class="price-col text-right">PRICE</th>
                <th class="total-col text-right">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; @endphp
            @foreach($order->items as $item)
            @php $totalQty += $item->quantity; @endphp
            <tr>
                <td class="code-col">{{ $item->product->item_code ?? '-' }}</td>
                <td class="desc-col">{{ $item->product->name ?? '-' }}</td>
                <td class="qty-col text-center">{{ $item->quantity }} {{ $item->unit->short_name ?? '' }}</td>
                <td class="price-col text-right">{{ App\Classes\Common::formatAmountCurrency($company->currency, $item->single_unit_price) }}</td>
                <td class="total-col text-right">{{ App\Classes\Common::formatAmountCurrency($company->currency, $item->subtotal) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ══ PAYMENT + TOTALS ═════════════════════════════════ --}}
    @php
        $cashPaid   = 0;
        $cardPaid   = 0;
        $otherPaid  = 0;
        $otherLabel = 'Other';
        if ($order->orderPayments) {
            foreach ($order->orderPayments as $op) {
                $modeName = strtolower($op->payment->paymentMode->name ?? '');
                if (str_contains($modeName, 'cash')) {
                    $cashPaid += $op->amount;
                } elseif (str_contains($modeName, 'card') || str_contains($modeName, 'credit')) {
                    $cardPaid += $op->amount;
                } else {
                    $otherPaid  += $op->amount;
                    $otherLabel  = $op->payment->paymentMode->name ?? 'Other';
                }
            }
        }
    @endphp

    <table class="payment-section">
        <tr>
            <td class="payment-left">
                <table class="payment-modes-table">
                    <tr>
                        <td>Cash Paid</td>
                        <td>{{ App\Classes\Common::formatAmountCurrency($company->currency, $cashPaid) }}</td>
                    </tr>
                    <tr>
                        <td>Credit Card</td>
                        <td>{{ App\Classes\Common::formatAmountCurrency($company->currency, $cardPaid) }}</td>
                    </tr>
                    <tr>
                        <td>{{ $otherLabel }}</td>
                        <td>{{ App\Classes\Common::formatAmountCurrency($company->currency, $otherPaid) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Due Amount</strong></td>
                        <td>{{ App\Classes\Common::formatAmountCurrency($company->currency, $order->due_amount) }}</td>
                    </tr>
                </table>
            </td>
            <td class="payment-right">
                <table class="totals-box">
                    <tr>
                        <td>Qty</td>
                        <td>{{ $totalQty }}</td>
                    </tr>
                    @if($order->discount > 0)
                    <tr>
                        <td>Discount</td>
                        <td>{{ App\Classes\Common::formatAmountCurrency($company->currency, $order->discount) }}</td>
                    </tr>
                    @endif
                    @if($order->tax_amount > 0)
                    <tr>
                        <td>Tax ({{ $order->tax_rate }}%)</td>
                        <td>{{ App\Classes\Common::formatAmountCurrency($company->currency, $order->tax_amount) }}</td>
                    </tr>
                    @endif
                    <tr class="grand-row">
                        <td>TOTAL</td>
                        <td>{{ App\Classes\Common::formatAmountCurrency($company->currency, $order->total) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ══ CUSTOMER BALANCE ═════════════════════════════════ --}}
    @if($order->order_type == 'sales' || $order->order_type == 'sales-returns')
    <div class="balance-section">
        <div class="balance-title">Customer Balance</div>
        <table class="balance-table">
            <tr>
                <td><strong>Previous:</strong>
                    {{ App\Classes\Common::formatAmountCurrency($company->currency, $customer->details->due_amount ?? 0) }}
                </td>
                <td><strong>Current:</strong>
                    {{ App\Classes\Common::formatAmountCurrency($company->currency, $order->due_amount) }}
                </td>
            </tr>
        </table>
    </div>
    @endif

    {{-- ══ NOTES ════════════════════════════════════════════ --}}
    @if($order->notes)
    <div style="font-size: 11px; margin-bottom: 8px; border: 1px solid #ccc; padding: 5px 8px;">
        <strong>Notes:</strong> {{ $order->notes }}
    </div>
    @endif

    {{-- ══ FOOTER ══════════════════════════════════════════ --}}
    <div class="footer-divider"></div>
    <table class="footer-table">
        <tr>
            <td class="footer-left">
                @if($staffMember)<div><strong>{{ $staffMember->name }}</strong></div>@endif
                @if($warehouse->email)<div>{{ $warehouse->email }}</div>@endif
                @if($warehouse->website ?? false)<div>{{ $warehouse->website }}</div>@endif
                @if($warehouse->facebook ?? false)<div>{{ $warehouse->facebook }}</div>@endif
                <div>Printed: {{ now()->format('d-m-Y H:i') }}</div>
            </td>
            <td class="footer-center">
                <div class="delivered-stamp">DELIVERED</div>
            </td>
            <td class="footer-right">
                <div>Page 1 of 1</div>
                @if($warehouse->terms_condition)
                <div style="margin-top:4px; font-size:9px; color:#555;">{!! strip_tags($warehouse->terms_condition) !!}</div>
                @endif
            </td>
        </tr>
    </table>

</div>
</body>
</html>
