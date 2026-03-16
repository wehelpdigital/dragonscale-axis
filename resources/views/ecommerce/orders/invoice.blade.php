<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $payment->invoiceNumber }} - {{ $invoiceSettings->businessName ?? 'Invoice' }}</title>
    <link rel="icon" href="{{ URL::asset('images/favicon.ico') }}" type="image/x-icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        .invoice-container {
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .invoice-header {
            background: {{ $invoiceSettings->headerBgColor ?? '#556ee6' }};
            color: {{ $invoiceSettings->headerTextColor ?? '#ffffff' }};
            padding: 30px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .invoice-header-left h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .invoice-header-left p {
            opacity: 0.9;
            font-size: 14px;
        }
        .invoice-header-right {
            text-align: right;
        }
        .invoice-header-right img {
            max-height: 60px;
            max-width: 150px;
        }
        .invoice-body {
            padding: 40px;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .invoice-info-section h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .invoice-info-section p {
            color: #333;
            margin-bottom: 3px;
        }
        .invoice-info-section .value {
            font-weight: 600;
            color: {{ $invoiceSettings->primaryColor ?? '#556ee6' }};
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-verified {
            background: #d4edda;
            color: #155724;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table thead {
            background: #f8f9fa;
        }
        .items-table th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        .items-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            color: #555;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .total-row {
            font-weight: 600;
            background: #f8f9fa;
        }
        .items-table .grand-total {
            font-size: 18px;
            color: {{ $invoiceSettings->primaryColor ?? '#556ee6' }};
        }
        .payment-details {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .payment-details h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
        }
        .payment-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .payment-item label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }
        .payment-item p {
            font-weight: 600;
            color: #333;
        }
        .invoice-footer {
            text-align: center;
            padding: 30px 40px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }
        .thank-you {
            font-size: 18px;
            color: {{ $invoiceSettings->secondaryColor ?? '#34c38f' }};
            margin-bottom: 10px;
        }
        .footer-note {
            font-size: 13px;
            color: #666;
        }
        .terms {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #888;
        }
        @media print {
            body {
                background: #fff;
            }
            .invoice-container {
                box-shadow: none;
                margin: 0;
            }
            .no-print {
                display: none;
            }
        }
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: {{ $invoiceSettings->primaryColor ?? '#556ee6' }};
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .print-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="invoice-header-left">
                <h1>INVOICE</h1>
                <p>{{ $payment->invoiceNumber }}</p>
            </div>
            <div class="invoice-header-right">
                @if($invoiceSettings->showLogo && $invoiceSettings->logoPath)
                    <img src="{{ asset($invoiceSettings->logoPath) }}" alt="{{ $invoiceSettings->businessName }}">
                @else
                    <div style="font-size: 24px; font-weight: 700;">{{ $invoiceSettings->businessName }}</div>
                @endif
            </div>
        </div>

        <!-- Body -->
        <div class="invoice-body">
            <!-- Invoice Info -->
            <div class="invoice-info">
                <div class="invoice-info-section">
                    <h3>Bill To</h3>
                    <p><strong>{{ $order->clientFirstName }} {{ $order->clientLastName }}</strong></p>
                    @if($order->clientEmail)
                        <p>{{ $order->clientEmail }}</p>
                    @endif
                    @if($order->clientPhone)
                        <p>{{ $order->clientPhone }}</p>
                    @endif
                    @if($order->shippingProvince)
                        <p>{{ $order->shippingMunicipality }}, {{ $order->shippingProvince }}</p>
                    @endif
                </div>
                <div class="invoice-info-section" style="text-align: right;">
                    <h3>Invoice Details</h3>
                    <p>Invoice #: <span class="value">{{ $payment->invoiceNumber }}</span></p>
                    <p>Order #: <span class="value">{{ $order->orderNumber }}</span></p>
                    <p>Date: {{ $payment->invoiceGeneratedAt ? $payment->invoiceGeneratedAt->format('F j, Y') : now()->format('F j, Y') }}</p>
                    <p>Status: <span class="status-badge status-verified">PAID</span></p>
                </div>
            </div>

            <!-- From Section -->
            @if($invoiceSettings->businessAddress || $invoiceSettings->businessPhone || $invoiceSettings->businessEmail)
            <div class="invoice-info-section" style="margin-bottom: 30px;">
                <h3>From</h3>
                <p><strong>{{ $invoiceSettings->businessName }}</strong></p>
                @if($invoiceSettings->businessAddress)
                    <p>{{ $invoiceSettings->businessAddress }}</p>
                @endif
                @if($invoiceSettings->businessPhone)
                    <p>{{ $invoiceSettings->businessPhone }}</p>
                @endif
                @if($invoiceSettings->businessEmail)
                    <p>{{ $invoiceSettings->businessEmail }}</p>
                @endif
                @if($invoiceSettings->showTaxId && $invoiceSettings->taxId)
                    <p>TIN: {{ $invoiceSettings->taxId }}</p>
                @endif
            </div>
            @endif

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Variant</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $item->productName }}</td>
                        <td>{{ $item->variantName ?: '-' }}</td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->unitPrice, 2) }}</td>
                        <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="4" class="text-right">Subtotal</td>
                        <td class="text-right">{{ number_format($order->subtotal, 2) }}</td>
                    </tr>
                    @if($order->shippingTotal > 0)
                    <tr class="total-row">
                        <td colspan="4" class="text-right">Shipping</td>
                        <td class="text-right">{{ number_format($order->shippingTotal, 2) }}</td>
                    </tr>
                    @endif
                    @if($order->discountTotal > 0)
                    <tr class="total-row">
                        <td colspan="4" class="text-right">Discount</td>
                        <td class="text-right">-{{ number_format($order->discountTotal, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row grand-total">
                        <td colspan="4" class="text-right"><strong>Grand Total</strong></td>
                        <td class="text-right"><strong>PHP {{ number_format($order->grandTotal, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <!-- Payment Details -->
            <div class="payment-details">
                <h3>Payment Information</h3>
                <div class="payment-grid">
                    <div class="payment-item">
                        <label>Payment Method</label>
                        <p>{{ $payment->paymentMethodLabel }}</p>
                    </div>
                    <div class="payment-item">
                        <label>Amount Paid</label>
                        <p>PHP {{ number_format($payment->amountVerified ?? $payment->amountSent, 2) }}</p>
                    </div>
                    @if($payment->referenceNumber)
                    <div class="payment-item">
                        <label>Reference Number</label>
                        <p>{{ $payment->referenceNumber }}</p>
                    </div>
                    @endif
                    @if($payment->payerName)
                    <div class="payment-item">
                        <label>Payer Name</label>
                        <p>{{ $payment->payerName }}</p>
                    </div>
                    @endif
                    <div class="payment-item">
                        <label>Payment Date</label>
                        <p>{{ $payment->verifiedAt ? $payment->verifiedAt->format('F j, Y g:i A') : '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Bank Details -->
            @if($invoiceSettings->showBankDetails && ($invoiceSettings->bankName || $invoiceSettings->gcashNumber || $invoiceSettings->mayaNumber))
            <div class="payment-details" style="background: #fff; border: 1px solid #eee;">
                <h3>Payment Options</h3>
                <div class="payment-grid">
                    @if($invoiceSettings->bankName)
                    <div class="payment-item">
                        <label>Bank Transfer</label>
                        <p>{{ $invoiceSettings->bankName }}</p>
                        <p style="font-weight: normal; font-size: 13px;">{{ $invoiceSettings->bankAccountName }}</p>
                        <p style="font-weight: normal; font-size: 13px;">{{ $invoiceSettings->bankAccountNumber }}</p>
                    </div>
                    @endif
                    @if($invoiceSettings->gcashNumber)
                    <div class="payment-item">
                        <label>GCash</label>
                        <p>{{ $invoiceSettings->gcashNumber }}</p>
                    </div>
                    @endif
                    @if($invoiceSettings->mayaNumber)
                    <div class="payment-item">
                        <label>Maya</label>
                        <p>{{ $invoiceSettings->mayaNumber }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            @if($invoiceSettings->showThankYou && $invoiceSettings->thankYouMessage)
                <p class="thank-you">{{ $invoiceSettings->thankYouMessage }}</p>
            @else
                <p class="thank-you">Thank you for your business!</p>
            @endif

            @if($invoiceSettings->footerNote)
                <p class="footer-note">{{ $invoiceSettings->footerNote }}</p>
            @endif

            @if($invoiceSettings->showTerms && $invoiceSettings->termsAndConditions)
                <div class="terms">
                    {!! nl2br(e($invoiceSettings->termsAndConditions)) !!}
                </div>
            @endif
        </div>
    </div>

    <!-- Print Button -->
    <button class="print-btn no-print" onclick="window.print()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
            <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
        </svg>
        Print Invoice
    </button>
</body>
</html>
