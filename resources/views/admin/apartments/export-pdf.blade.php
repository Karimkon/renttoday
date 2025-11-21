<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Apartments Report - {{ ucfirst($statusFilter ?? 'All') }}</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 9px; 
            margin: 10px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 { 
            margin: 0; 
            font-size: 18px; 
            font-weight: bold;
        }
        .header h2 { 
            margin: 5px 0; 
            font-size: 14px; 
        }
        .summary {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 8px;
        }
        th, td { 
            border: 1px solid #000; 
            padding: 4px 2px; 
            text-align: left;
        }
        th { 
            background-color: #333; 
            color: white; 
            font-weight: bold;
            text-align: center;
        }
        .badge-paid { 
            background-color: #28a745; 
            color: white; 
            padding: 2px 6px; 
            border-radius: 3px;
            font-weight: bold;
        }
        .badge-partial { 
            background-color: #ffc107; 
            color: black; 
            padding: 2px 6px; 
            border-radius: 3px;
            font-weight: bold;
        }
        .badge-unpaid { 
            background-color: #dc3545; 
            color: white; 
            padding: 2px 6px; 
            border-radius: 3px;
            font-weight: bold;
        }
        .badge-vacant { 
            background-color: #6c757d; 
            color: white; 
            padding: 2px 6px; 
            border-radius: 3px;
            font-weight: bold;
        }
        .footer { 
            margin-top: 20px; 
            text-align: center;
            font-size: 8px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>RENT TODAY - APARTMENTS REPORT</h1>
        <h2>{{ ucfirst($statusFilter ?? 'All Apartments') }} - {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</h2>
        <p><strong>Generated:</strong> {{ now()->format('F j, Y g:i A') }}</p>
    </div>

    <!-- Summary -->
    <div class="summary">
        <h3 style="margin: 0 0 10px 0;">Summary</h3>
        @php
            $totalApartments = $apartments->count();
            $totalRent = $apartments->sum('rent');
            $totalPaid = $apartments->sum('totalPaid');
            $totalDue = $apartments->sum('dueAmount');
            $statusCounts = $apartments->groupBy('status')->map->count();
        @endphp
        <div class="summary-row">
            <strong>Total Apartments:</strong> <span>{{ $totalApartments }}</span>
        </div>
        <div class="summary-row">
            <strong>Total Monthly Rent:</strong> <span>UGX {{ number_format($totalRent) }}</span>
        </div>
        <div class="summary-row">
            <strong>Total Amount Paid:</strong> <span style="color: #28a745;">UGX {{ number_format($totalPaid) }}</span>
        </div>
        <div class="summary-row">
            <strong>Total Amount Due:</strong> <span style="color: #dc3545;">UGX {{ number_format($totalDue) }}</span>
        </div>
        <div class="summary-row">
            <strong>Payment Progress:</strong> 
            <span>{{ $totalRent > 0 ? number_format(($totalPaid / $totalRent) * 100, 1) : 0 }}%</span>
        </div>
    </div>

    <!-- Apartments Table -->
    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="8%">Number</th>
                <th width="12%">Landlord</th>
                <th width="5%">Comm.</th>
                <th width="10%">Location</th>
                <th width="4%">Rooms</th>
                <th width="10%">Rent</th>
                <th width="12%">Tenant</th>
                <th width="8%">Phone</th>
                <th width="8%">Status</th>
                <th width="9%">Paid</th>
                <th width="9%">Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach($apartments as $index => $apartment)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $apartment->number }}</td>
                <td>{{ $apartment->landlord->name ?? 'N/A' }}</td>
                <td>{{ $apartment->landlord->commission_rate ?? '-' }}%</td>
                <td>{{ $apartment->location ?? 'N/A' }}</td>
                <td>{{ $apartment->rooms }}</td>
                <td>UGX {{ number_format($apartment->rent) }}</td>
                <td>{{ $apartment->tenant->name ?? 'Vacant' }}</td>
                <td>{{ $apartment->tenant->phone ?? '-' }}</td>
                <td>
                    @if($apartment->status === 'paid')
                        <span class="badge-paid">PAID</span>
                    @elseif($apartment->status === 'partial')
                        <span class="badge-partial">PARTIAL</span>
                    @elseif($apartment->status === 'unpaid')
                        <span class="badge-unpaid">UNPAID</span>
                    @else
                        <span class="badge-vacant">VACANT</span>
                    @endif
                </td>
                <td>UGX {{ number_format($apartment->totalPaid) }}</td>
                <td>UGX {{ number_format($apartment->dueAmount) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p><strong>RENT TODAY MANAGEMENT AGENCY</strong></p>
        <p>Professional Property Management</p>
        <p>This is a computer-generated document. Total Records: {{ $totalApartments }}</p>
    </div>
</body>
</html>