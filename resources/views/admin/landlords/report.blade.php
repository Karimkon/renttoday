@extends('admin.layouts.app')

@section('title', 'Landlord Monthly Report')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="mb-0">RENT TODAY - MANAGEMENT AGENCY</h4>
                    <h5 class="mb-0">CLIENT'S MONTHLY REPORT FOR THE MONTH OF: {{ strtoupper($reportData['month']->format('F Y')) }}</h5>
                    <h6 class="mb-0">CLIENT'S NAME: {{ strtoupper($reportData['landlord']->name) }}</h6>
                    <h6 class="mb-0">PROPERTY LOCATION: {{ $locations->implode(' and ') }}</h6>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.landlords.report.pdf', ['landlord' => $reportData['landlord']->id, 'month' => $reportData['month']->format('Y-m')]) }}" 
                       class="btn btn-primary">
                        <i class="bi bi-download"></i> Export PDF
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @foreach($locations as $location)
            <h5 class="mt-4">{{ strtoupper($location) }}</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Apartment No</th>
                            <th>Month Rate</th>
                            <th>Rent Paid</th>
                            <th>Commission</th>
                            <th>Amount</th>
                            <th>Month/s Paid</th>
                            <th>Date of payment</th>
                            <th>Next of pyt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $locationApartments = $reportData['apartments']->where('location', $location);
                            $locationTotal = 0;
                            $locationCommission = 0;
                        @endphp
                        
                        @foreach($locationApartments as $apartment)
                        @php
                            $payment = $apartment->getCurrentMonthPayment();
                            $rentPaid = $payment ? $payment->amount : 0;
                            $commission = $payment ? ($rentPaid * ($reportData['landlord']->commission_rate / 100)) : 0;
                            $amount = $rentPaid - $commission;
                            
                            $locationTotal += $rentPaid;
                            $locationCommission += $commission;
                        @endphp
                        <tr>
                            <td>{{ $apartment->number }}</td>
                            <td>UGX {{ number_format($apartment->rent) }}</td>
                            <td>UGX {{ number_format($rentPaid) }}</td>
                            <td>UGX {{ number_format($commission) }}</td>
                            <td>UGX {{ number_format($amount) }}</td>
                            <td>{{ $payment ? $reportData['month']->format('F') : 'UNPAID' }}</td>
                            <td>{{ $payment ? $payment->created_at->format('jS/m/Y') : '-' }}</td>
                            <td>{{ $reportData['month']->copy()->addMonth()->format('F') }}</td>
                        </tr>
                        @endforeach
                        
                        <!-- Location Totals -->
                        <tr class="table-warning fw-bold">
                            <td colspan="2">TOTAL {{ strtoupper($location) }}</td>
                            <td>UGX {{ number_format($locationTotal) }}</td>
                            <td>UGX {{ number_format($locationCommission) }}</td>
                            <td>UGX {{ number_format($locationTotal - $locationCommission) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endforeach

            <!-- Grand Totals -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6>SUMMARY FOR {{ strtoupper($reportData['month']->format('F Y')) }}</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td>Total Rent Collected:</td>
                                    <td class="fw-bold">UGX {{ number_format($reportData['totalRent']) }}</td>
                                </tr>
                                <tr>
                                    <td>Total Commission ({{ $reportData['landlord']->commission_rate }}%):</td>
                                    <td class="fw-bold">UGX {{ number_format($reportData['totalCommission']) }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td>Amount Due to Landlord:</td>
                                    <td class="fw-bold text-success">UGX {{ number_format($reportData['amountDue']) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection