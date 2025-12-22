<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt - Rent Today</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 12px; 
            margin: 20px;
            line-height: 1.4;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #333;
            padding: 25px;
            position: relative;
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
            position: absolute;
            top: 25px;
            right: 25px;
            background: #f8f9fa;
            padding: 8px 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            font-weight: bold;
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
        
        .amount-words {
            font-style: italic;
            color: #7f8c8d;
            margin-top: 10px;
            padding: 10px;
            background: white;
            border-radius: 5px;
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
        
        .signature-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #333;
        }
        
        .signature-line {
            width: 300px;
            border-top: 1px solid #333;
            margin: 40px auto 10px;
        }
        
        .signature-label {
            text-align: center;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 11px;
            color: #7f8c8d;
        }
        
        .stamp {
            position: absolute;
            bottom: 100px;
            right: 50px;
            color: #e74c3c;
            font-weight: bold;
            opacity: 0.7;
            transform: rotate(-15deg);
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
                <td>Contact Details:</td>
                <td>{{ $receiptData['tenant_phone'] }} | {{ $receiptData['tenant_email'] }}</td>
            </tr>
            <tr>
                <td>Apartment Details:</td>
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
                <td>Reference Number:</td>
                <td>{{ $receiptData['reference_number'] }}</td>
            </tr>
            @endif
            <tr>
                <td>Processed By:</td>
                <td>{{ $receiptData['processed_by'] }}</td>
            </tr>
        </table>
        
        <!-- Advance Notice -->
        @if($receiptData['is_advance_payment'])
        <div class="advance-notice">
            ⭐ ADVANCE PAYMENT NOTICE: This payment covers {{ $receiptData['months_covered'] }} month(s) in advance
        </div>
        @endif
        
        <!-- Amount Section -->
        <div class="amount-section">
            <div style="font-size: 14px; color: #7f8c8d;">AMOUNT RECEIVED</div>
            <div class="amount-figure">UGX {{ number_format($receiptData['amount'], 2) }}</div>
            <div class="amount-words">
                {{ $receiptData['amount_in_words'] }}
            </div>
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
        
        <!-- Notes -->
        @if($receiptData['notes'])
        <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <strong>Notes:</strong> {{ $receiptData['notes'] }}
        </div>
        @endif
        
        <!-- Signature -->
        <div class="signature-section">
            <div class="signature-line"></div>
            <div class="signature-label">Authorized Signature / Stamp</div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div>This is a computer-generated receipt. Valid without signature.</div>
            <div>Generated on: {{ now()->format('F j, Y \a\t g:i A') }}</div>
            <div style="margin-top: 10px;">
                <strong>For inquiries:</strong> {{ $receiptData['company_phone'] }} | {{ $receiptData['company_email'] }}<br>
                <strong>Website:</strong> https://renttoday.site
            </div>
        </div>
        
        <!-- Stamp -->
        <div class="stamp">
            <div style="font-size: 18px;">PAID</div>
            <div style="font-size: 12px;">Rent Today</div>
            <div style="font-size: 10px;">{{ now()->format('M Y') }}</div>
        </div>
    </div>
</body>
</html>