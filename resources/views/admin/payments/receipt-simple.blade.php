cat > resources/views/admin/payments/receipt-simple.blade.php << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>Payment Receipt - Rent Today</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .receipt { border: 1px solid #000; padding: 20px; max-width: 600px; margin: 0 auto; }
        .error { background: #ffe6e6; padding: 10px; border: 1px solid #ff9999; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="receipt">
        <h2 style="text-align: center;">RENT TODAY MANAGEMENT AGENCY</h2>
        <h3 style="text-align: center;">PAYMENT RECEIPT</h3>
        
        <div class="error">
            <strong>System Notice:</strong> {{ $error ?? 'PDF generation temporarily unavailable' }}
        </div>
        
        <table width="100%" cellpadding="5">
            <tr>
                <td><strong>Receipt #:</strong></td>
                <td>RENT-TDY-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <td><strong>Date:</strong></td>
                <td>{{ now()->format('F j, Y') }}</td>
            </tr>
            <tr>
                <td><strong>Tenant:</strong></td>
                <td>{{ $payment->tenant->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Apartment:</strong></td>
                <td>{{ $payment->apartment->number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Month:</strong></td>
                <td>{{ \Carbon\Carbon::parse($payment->month)->format('F Y') }}</td>
            </tr>
            <tr>
                <td><strong>Amount:</strong></td>
                <td><strong>UGX {{ number_format($payment->amount, 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Method:</strong></td>
                <td>{{ $payment->payment_method_label }}</td>
            </tr>
        </table>
        
        <hr>
        <p style="text-align: center;">
            <strong>Thank you for your payment!</strong><br>
            This receipt confirms your payment has been received.<br>
            Please keep this for your records.
        </p>
        
        <p style="text-align: center; font-size: 12px; color: #666;">
            Generated: {{ now()->format('Y-m-d H:i:s') }}<br>
            Rent Today Management Agency | https://renttoday.site
        </p>
        
        <button onclick="window.print()" style="display: block; margin: 20px auto; padding: 10px 20px;">
            Print Receipt
        </button>
    </div>
</body>
</html>
EOF