<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        /* General */
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #2c3e50;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
        }
        .container {
            width: 700px;
            margin: 30px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        h2, h3 {
            margin: 0;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header .company {
            font-size: 1.25rem;
            font-weight: bold;
            color: #2563eb;
        }
        .header .invoice-number {
            font-size: 1rem;
            font-weight: 500;
        }

        /* Info sections */
        .info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .info div {
            width: 48%;
        }
        .info p {
            margin: 4px 0;
        }
        .info strong {
            color: #1e293b;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #dee2e6;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #2563eb;
            color: white;
            font-weight: 600;
        }
        td.amount, th.amount {
            text-align: right;
        }

        /* Totals */
        .totals {
            float: right;
            width: 300px;
            margin-top: 10px;
        }
        .totals table {
            border: none;
        }
        .totals th, .totals td {
            border: none;
            padding: 8px 12px;
        }
        .totals td {
            text-align: right;
        }
        .totals .grand-total {
            font-size: 1.1rem;
            font-weight: bold;
            color: #2563eb;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 30px;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company">
                MyHotel Management
            </div>
            <div class="invoice-number">
                Invoice #{{ $invoice->id }}<br>
                Date: {{ \Carbon\Carbon::parse($invoice->created_at)->format('F j, Y') }}
            </div>
        </div>

        <!-- Tenant & Apartment Info -->
        <div class="info">
            <div>
                <h3>Tenant Information</h3>
                <p><strong>Name:</strong> {{ $invoice->tenant->name }}</p>
                <p><strong>Email:</strong> {{ $invoice->tenant->email }}</p>
                <p><strong>Phone:</strong> {{ $invoice->tenant->phone ?? 'N/A' }}</p>
            </div>
            <div>
                <h3>Apartment Details</h3>
                <p><strong>Apartment #:</strong> {{ $invoice->apartment?->number ?? 'Unassigned' }}</p>
                <p><strong>Rent Amount:</strong> UGX {{ number_format($invoice->apartment?->rent ?? $invoice->amount) }}</p>
                <p><strong>Invoice Month:</strong> {{ \Carbon\Carbon::parse($invoice->month)->format('F, Y') }}</p>
                <p><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
            </div>
        </div>

        <!-- Invoice Items Table -->
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="amount">Amount (UGX)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Monthly Rent - {{ \Carbon\Carbon::parse($invoice->month)->format('F, Y') }}</td>
                    <td class="amount">{{ number_format($invoice->amount) }}</td>
                </tr>
                @if($invoice->credit_balance ?? 0)
                <tr>
                    <td>Credit Applied</td>
                    <td class="amount">-{{ number_format($invoice->credit_balance) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <th>Total:</th>
                    <td>UGX {{ number_format($invoice->amount - ($invoice->credit_balance ?? 0)) }}</td>
                </tr>
            </table>
        </div>

        <div style="clear: both;"></div>

        <!-- Footer -->
        <div class="footer">
            Thank you for your payment!<br>
            MyHotel Management, Kampala, Uganda
        </div>
    </div>
</body>
</html>
