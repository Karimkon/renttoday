<!DOCTYPE html>
<html>
<head>
    <title>Payment Receipt - Rent Today</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
            .receipt-container { border: none; box-shadow: none; }
        }
        
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            margin: 20px;
            line-height: 1.4;
            background: #f5f5f5;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #333;
            padding: 25px;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .company-tagline {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .receipt-number {
            background: #f8f9fa;
            padding: 8px 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
        }
        
        .payment-details {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .payment-details td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .payment-details td:first-child {
            font-weight: bold;
            width: 30%;
            color: #2c3e50;
        }
        
        .amount-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px dashed #3498db;
        }
        
        .amount-figure {
            font-size: 32px;
            font-weight: bold;
            color: #27ae60;
            margin: 10px 0;
        }
        
        .thank-you-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: linear-gradient(to right, #f8f9fa, #e8f4f8);
            border-left: 5px solid #3498db;
        }
        
        .thank-you-title {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .thank-you-message {
            font-size: 14px;
            color: #34495e;
            line-height: 1.6;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 11px;
            color: #7f8c8d;
        }
        
        .print-button {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-align: center;
        }
        
        .print-button:hover {
            background: #2980b9;
        }
        
        .advance-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            text-align: center;
            font-weight: bold;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Receipt Number -->
        <div class="receipt-number">
            RECEIPT #: {{ $receiptData['receipt_number'] }}
        </div>
        
        <!-- Header -->
        <div class="header">
            <div class="company-name">RENT TODAY MANAGEMENT AGENCY</div>
            <div class="company-tagline">Professional Property Management Services</div>
            <div>{{ $receiptData['company_address'] }} | {{ $receiptData['company_phone'] }} | {{ $receiptData['company_email'] }}</div>
        </div>
        
        <!-- Title -->
        <div class="receipt-title">OFFICIAL PAYMENT RECEIPT</div>
        
        <!-- Payment Details -->
        <table class="payment-details">
            <tr>
                <td>Date of Payment:</td>
                <td>{{ $receiptData['payment_date']->format('F j, Y') }}</td>
            </tr>
            <tr>
                <td>Receipt Date:</td>
                <td>{{ now()->format('F j, Y') }}</td>
            </tr>
            <tr>
                <td>Tenant Name:</td>
                <td>{{ $receiptData['tenant_name'] }}</td>
            </tr>
            <tr>
                <td>Contact:</td>
                <td>{{ $receiptData['tenant_phone'] }}</td>
            </tr>
            <tr>
                <td>Apartment:</td>
                <td>{{ $receiptData['apartment_number'] }} - {{ $receiptData['apartment_location'] }}</td>
            </tr>
            <tr>
                <td>Property Owner:</td>
                <td>{{ $receiptData['landlord_name'] }}</td>
            </tr>
            <tr>
                <td>Rent Period:</td>
                <td>{{ $receiptData['month'] }}</td>
            </tr>
            @if($receiptData['is_advance_payment'])
            <tr>
                <td>Payment Type:</td>
                <td>Advance Payment ({{ $receiptData['months_covered'] }} month/s)</td>
            </tr>
            @endif
            <tr>
                <td>Payment Method:</td>
                <td>{{ $receiptData['payment_method'] }}</td>
            </tr>
            @if($receiptData['reference_number'])
            <tr>
                <td>Reference:</td>
                <td>{{ $receiptData['reference_number'] }}</td>
            </tr>
            @endif
        </table>
        
        <!-- Advance Notice -->
        @if($receiptData['is_advance_payment'])
        <div class="advance-notice">
            ⭐ ADVANCE PAYMENT: Covers {{ $receiptData['months_covered'] }} month(s)
        </div>
        @endif
        
        <!-- Amount Section -->
        <div class="amount-section">
            <div style="font-size: 14px; color: #7f8c8d;">AMOUNT RECEIVED</div>
            <div class="amount-figure">UGX {{ number_format($receiptData['amount'], 2) }}</div>
        </div>
        
        <!-- Thank You Section -->
        <div class="thank-you-section">
            <div class="thank-you-title">THANK YOU FOR YOUR PAYMENT! 🎉</div>
            <div class="thank-you-message">
                {{ $receiptData['thank_you_message'] }}<br>
                Your payment ensures continuous quality service and maintenance of your residence.<br>
                Keep this receipt as proof of payment for your records.
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div>Generated on: {{ now()->format('F j, Y \a\t g:i A') }}</div>
            <div style="margin-top: 10px;">
                <strong>For inquiries:</strong> {{ $receiptData['company_phone'] }} | {{ $receiptData['company_email'] }}<br>
                <strong>Website:</strong> https://renttoday.site
            </div>
        </div>
    </div>
    
    <!-- Print Button -->
    @if(isset($isHtmlFallback) && $isHtmlFallback)
    <button class="print-button no-print" onclick="window.print()">
        🖨️ Print Receipt
    </button>
    <div class="no-print" style="text-align: center; margin: 20px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7;">
        <strong>Note:</strong> This is an HTML receipt. Use the print button above to print.<br>
        <small>For PDF version, please contact support.</small>
    </div>
    @endif
    
    <script>
        // Auto-print option (optional)
        @if(request()->has('autoprint'))
        window.onload = function() {
            window.print();
        }
        @endif
    </script>
</body>
</html>
EOF