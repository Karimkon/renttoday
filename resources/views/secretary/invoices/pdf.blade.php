<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $payment->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; padding: 30px; }
        h2 { text-align: center; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ccc; }
        th, td { padding: 10px; text-align: left; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <h2>Payment Receipt</h2>
    <p><strong>Tenant:</strong> {{ $tenant->name }}</p>
    <p><strong>Apartment:</strong> {{ $apartment->number ?? 'Unassigned' }}</p>
    <p><strong>Month:</strong> {{ \Carbon\Carbon::parse($payment->month)->format('F Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Amount (UGX)</th>
                <th>Includes Gym</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ number_format($payment->amount) }}</td>
                <td>{{ $payment->includes_gym ? 'Yes' : 'No' }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
