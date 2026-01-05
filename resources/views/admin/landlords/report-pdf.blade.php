<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>RENT TODAY - Monthly Report</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 11px; 
            margin: 15px;
            line-height: 1.2;
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
            margin: 3px 0; 
            font-size: 14px; 
        }
        .table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 10px 0;
            font-size: 9px;
        }
        .table th, .table td { 
            border: 1px solid #000; 
            padding: 4px 3px; 
            text-align: left;
        }
        .table th { 
            background-color: #f0f0f0; 
            font-weight: bold;
            text-align: center;
        }
        .location-header { 
            background-color: #333; 
            color: white; 
            padding: 6px;
            margin: 15px 0 8px 0;
            font-weight: bold;
            font-size: 11px;
        }
        .summary { 
            margin-top: 20px; 
            padding: 12px; 
            background-color: #f8f9fa;
            border: 1px solid #ddd;
        }
        .total-row {
            background-color: #fff3cd;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .footer { 
            margin-top: 20px; 
            padding-top: 8px; 
            border-top: 1px solid #ccc;
            font-size: 9px;
        }
        .badge {
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-success { background-color: #28a745; color: white; }
        .badge-info { background-color: #17a2b8; color: white; }
        .badge-warning { background-color: #ffc107; color: black; }
        .badge-danger { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>RENT TODAY - MANAGEMENT AGENCY</h1>
        <h2>CLIENT'S MONTHLY REPORT FOR: {{ strtoupper($reportData['month']->format('F Y')) }}</h2>
        <p><strong>CLIENT'S NAME:</strong> {{ strtoupper($reportData['landlord']->name) }}</p>
        <p><strong>PROPERTY LOCATION:</strong> {{ $locations->implode(' and ') }}</p>
        <p><strong>COMMISSION RATE:</strong> {{ $reportData['landlord']->commission_rate }}%</p>
        
        <p><strong>PAYMENT STATUS:</strong> 
            @if($reportData['landlordPaymentStatus']['paid'])
                <strong style="color: #28a745;">PAID TO LANDLORD</strong>
                ({{ \Carbon\Carbon::parse($reportData['landlordPaymentStatus']['paid_at'])->format('M j, Y') }})
            @else
                <strong style="color: #ffc107;">PENDING PAYMENT</strong>
            @endif
        </p>
    </div>

    @foreach($locations as $location)
        @php
            $locationApartments = $reportData['apartments']->where('location', $location);
            $locationTotalValue = 0;
            $locationAgencyTotal = 0;
            $locationCommission = 0;
        @endphp
        
        <div class="location-header">
            {{ strtoupper($location) }}
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th width="12%">Apartment</th>
                    <th width="12%">Rate</th>
                    <th width="15%">Rent Paid</th>
                    <th width="12%">Comm.</th>
                    <th width="12%">To L/Lord</th>
                    <th width="12%">Status</th>
                    <th width="12%">Date</th>
                    <th width="13%">Next Due</th>
                </tr>
            </thead>
            <tbody>
                @foreach($locationApartments as $apartment)
                    @php 
                        $rowData = $apartment->getReportRowData($reportData['month']->format('Y-m'));
                        
                        $commission = $rowData['commissionable_amount'] * ($reportData['landlord']->commission_rate / 100);
                        
                        // Net Row-level payout
                        $payout = max(0, $rowData['rent_collected_agency'] - $commission);

                        // Increment location totals
                        $locationTotalValue += $rowData['commissionable_amount'];
                        $locationAgencyTotal += $rowData['rent_collected_agency'];
                        $locationCommission += $commission;
                    @endphp
                    <tr>
                        <td>{{ $apartment->number }}</td>
                        <td>UGX {{ number_format($apartment->rent) }}</td>
                        <td>
                            UGX {{ number_format($rowData['rent_collected_agency']) }}
                            @if($rowData['is_covered_by_advance'])
                                <br><small style="color: #17a2b8;">(Advance)</small>
                            @endif
                            @if($rowData['rent_paid_direct'] > 0)
                                <br><small style="color: #17a2b8;">Paid Direct: {{ number_format($rowData['rent_paid_direct']) }}</small>
                            @endif
                        </td>
                        <td>UGX {{ number_format($commission) }}</td>
                        <td>UGX {{ number_format($payout) }}</td>
                        <td>
                            <span class="badge {{ $rowData['status_label'] === 'PAID' || str_contains($rowData['status_label'], 'ADVANCE') ? 'badge-success' : ($rowData['status_label'] === 'UNPAID' ? 'badge-danger' : 'badge-info') }}">
                                {{ $rowData['status_label'] }}
                            </span>
                        </td>
                        <td>{{ $rowData['payment_date'] }}</td>
                        <td>{{ $rowData['next_payment'] }}</td>
                    </tr>
                @endforeach
                
                <tr class="total-row">
                    <td colspan="2">TOTAL {{ strtoupper($location) }}</td>
                    <td>UGX {{ number_format($locationAgencyTotal) }}</td>
                    <td>UGX {{ number_format($locationCommission) }}</td>
                    <td>UGX {{ number_format(max(0, $locationAgencyTotal - $locationCommission)) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>
    @endforeach

    @if(isset($reportData['expenses']) && $reportData['expenses']->count() > 0)
    <div style="margin-top: 20px;">
        <div class="location-header" style="background-color: #ffc107; color: black;">
            EXPENSES FOR {{ strtoupper($reportData['month']->format('F Y')) }}
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Apartment</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['expenses'] as $expense)
                <tr>
                    <td>{{ $expense->expense_date->format('M j, Y') }}</td>
                    <td>{{ $expense->description }}</td>
                    <td>{{ $expense->category_label }}</td>
                    <td>{{ $expense->apartment ? $expense->apartment->number : 'General' }}</td>
                    <td style="color: #dc3545;">UGX {{ number_format($expense->amount) }}</td>
                </tr>
                @endforeach
                <tr class="total-row" style="background-color: #f8f9fa;">
                    <td colspan="4">TOTAL EXPENSES</td>
                    <td style="color: #dc3545;">UGX {{ number_format($reportData['totalExpenses']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <div class="summary">
        <h4>SUMMARY FOR {{ strtoupper($reportData['month']->format('F Y')) }}</h4>
        <table style="width: 100%;">
            <tr>
                <td><strong>Total Rent Collected by Agency:</strong></td>
                <td class="text-right"><strong>UGX {{ number_format($reportData['totalCollectedRent']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Total Rent Value (Incl. Direct):</strong></td>
                <td class="text-right"><strong>UGX {{ number_format($reportData['totalRent']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Total Commission ({{ $reportData['landlord']->commission_rate }}%):</strong></td>
                <td class="text-right" style="color: #dc3545;"><strong>- UGX {{ number_format($reportData['totalCommission']) }}</strong></td>
            </tr>
            @if($reportData['totalExpenses'] > 0)
            <tr>
                <td><strong>Total Expenses Deducted:</strong></td>
                <td class="text-right" style="color: #dc3545;"><strong>- UGX {{ number_format($reportData['totalExpenses']) }}</strong></td>
            </tr>
            @endif
            <tr style="border-top: 1px solid #000;">
                @if($reportData['amountDue'] >= 0)
                    <td style="font-size: 14px;"><strong>Amount Due to Landlord:</strong></td>
                    <td class="text-right" style="font-size: 14px; color: #28a745;"><strong>UGX {{ number_format($reportData['amountDue']) }}</strong></td>
                @else
                    <td style="font-size: 14px; color: #dc3545;"><strong>Balance Owed by Landlord:</strong></td>
                    <td class="text-right" style="font-size: 14px; color: #dc3545;"><strong>UGX {{ number_format(abs($reportData['amountDue'])) }}</strong></td>
                @endif
            </tr>
        </table>
    </div>

    <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td>
                    <strong>Generated On:</strong> {{ now()->format('F j, Y g:i A') }}
                </td>
                <td class="text-right">
                    <strong>RENT TODAY MANAGEMENT AGENCY</strong><br>
                    Professional Property Management
                </td>
            </tr>
        </table>
    </div>
</body>
</html>