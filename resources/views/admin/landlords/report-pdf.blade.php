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
            $locationTotal = 0;
            $locationCommission = 0;
            $locationAmount = 0;
        @endphp
        
        <div class="location-header">
            {{ strtoupper($location) }}
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th width="12%">Apartment No</th>
                    <th width="12%">Month Rate</th>
                    <th width="12%">Rent Paid</th>
                    <th width="12%">Commission</th>
                    <th width="12%">Amount</th>
                    <th width="12%">Status</th>
                    <th width="14%">Date of Pyt</th>
                    <th width="14%">Next Pyt Due</th>
                </tr>
            </thead>
            <tbody>
                @foreach($locationApartments as $apartment)
                    @php 
                        $data = $apartment->getReportDataForMonth($reportData['month']->format('Y-m'));
                        $rentReceived = $data['cash_received'];
                        $commission = $rentReceived * ($reportData['landlord']->commission_rate / 100);
                        $netAmount = $rentReceived - $commission;

                        // Increment location totals
                        $locationTotal += $rentReceived;
                        $locationCommission += $commission;
                        $locationAmount += $netAmount;
                    @endphp
                    <tr>
                        <td>{{ $apartment->number }}</td>
                        <td>UGX {{ number_format($apartment->rent) }}</td>
                        <td>
                            @if($data['is_covered_by_advance'] && $rentReceived == 0)
                                <span style="color: #17a2b8; font-weight: bold;">ADVANCE COVER</span>
                            @else
                                UGX {{ number_format($rentReceived) }}
                            @endif
                        </td>
                        <td>UGX {{ number_format($commission) }}</td>
                        <td>UGX {{ number_format($netAmount) }}</td>
                        <td>
                            @if($data['is_covered_by_advance'] && $rentReceived == 0)
                                <span class="badge badge-info">ADVANCE</span>
                            @elseif($rentReceived >= $apartment->rent)
                                <span class="badge badge-success">PAID</span>
                            @elseif($rentReceived > 0)
                                <span class="badge badge-warning">PARTIAL</span>
                            @else
                                <span class="badge badge-danger">UNPAID</span>
                            @endif
                        </td>
                        <td>{{ $data['recent_payment'] ? \Carbon\Carbon::parse($data['recent_payment']->paid_at)->format('d/m/Y') : '-' }}</td>
                        <td>{{ $data['next_payment_label'] }}</td>
                    </tr>
                @endforeach
                
                <tr class="total-row">
                    <td colspan="2">TOTAL {{ strtoupper($location) }}</td>
                    <td>UGX {{ number_format($locationTotal) }}</td>
                    <td>UGX {{ number_format($locationCommission) }}</td>
                    <td>UGX {{ number_format($locationAmount) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>
    @endforeach

    <div class="summary">
        <h4>SUMMARY FOR {{ strtoupper($reportData['month']->format('F Y')) }}</h4>
        <table style="width: 100%;">
            <tr>
                <td><strong>Total Rent Collected:</strong></td>
                <td class="text-right"><strong>UGX {{ number_format($reportData['totalRent']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Total Commission ({{ $reportData['landlord']->commission_rate }}%):</strong></td>
                <td class="text-right"><strong>UGX {{ number_format($reportData['totalCommission']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Amount Due to Landlord:</strong></td>
                <td class="text-right"><strong>UGX {{ number_format($reportData['amountDue']) }}</strong></td>
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