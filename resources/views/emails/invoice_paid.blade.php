<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; color: #333; }
        h2 { color: #2c3e50; text-align: center; }
        .content { max-width: 600px; margin: 0 auto; }
        .invoice-details { margin-top: 20px; border-collapse: collapse; width: 100%; }
        .invoice-details th, .invoice-details td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        .invoice-details th { background-color: #f4f4f4; }
        .total { font-weight: bold; }
        .footer { margin-top: 30px; font-size: 0.9em; color: #555; }
    </style>
</head>
<body>
    <div class="content">
        <h2>PhilWil Apartments - Invoice #{{ $invoice->id }}</h2>

        <p>Dear {{ $invoice->tenant->name }},</p>

        @if($invoice->status == 'paid')
            <p>We have received your payment of <strong>UGX {{ number_format($invoice->amount) }}</strong> 
            for the month of <strong>{{ \Carbon\Carbon::parse($invoice->month)->format('F, Y') }}</strong>.</p>
            <p>Attached is your official payment receipt. Thank you for staying with <strong>PhilWil Apartments</strong>.</p>
        @else
            <p>This is a friendly reminder that your payment for the month of 
            <strong>{{ \Carbon\Carbon::parse($invoice->month)->format('F, Y') }}</strong> is now due. 
            The total amount payable is <strong>UGX {{ number_format($invoice->amount) }}</strong>.</p>
            <p>Please make the payment at your earliest convenience. Attached is the official invoice for your reference.</p>
        @endif

        <table class="invoice-details">
            <tr>
                <th>Tenant Name</th>
                <td>{{ $invoice->tenant->name }}</td>
            </tr>
            <tr>
                <th>Apartment</th>
                <td>{{ $invoice->apartment?->number ?? 'Unassigned' }}</td>
            </tr>
            <tr>
                <th>Month</th>
                <td>{{ \Carbon\Carbon::parse($invoice->month)->format('F, Y') }}</td>
            </tr>
            <tr>
                <th>Amount</th>
                <td>UGX {{ number_format($invoice->amount) }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ ucfirst($invoice->status) }}</td>
            </tr>
        </table>

        <div class="footer">
            <p>Warm regards,<br>
            The Management Team<br>
            PhilWil Apartments</p>
        </div>
    </div>
</body>
</html>
