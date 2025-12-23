        {{-- resources/views/admin/payments/create.blade.php --}}
        @extends('admin.layouts.app')
        @section('title','Add Payment')

        @section('content')
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0"><i class="bi bi-cash-coin me-2"></i> Add Payment</h3>
                <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Payments
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.payments.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
            <label class="form-label fw-semibold">Tenant <span class="text-danger">*</span></label>
            <select name="tenant_id" class="form-select select2-tenant" required id="tenantSelect">
                <option value="">-- Select Tenant --</option>
                @foreach($tenants as $t)
                <option value="{{ $t->id }}" {{ old('tenant_id')==$t->id?'selected':'' }}
                        data-rent="{{ $t->apartment->rent ?? 0 }}"
                        data-apartment="{{ $t->apartment->number ?? 'N/A' }}"
                        data-landlord="{{ $t->apartment->landlord->name ?? 'N/A' }}">
                    {{ $t->name }} ({{ $t->phone ?? 'No phone' }})
                    @if($t->apartment)
                        - Apt: {{ $t->apartment->number }}
                    @endif
                </option>
                @endforeach
            </select>
        </div>

        <!-- Add this after the tenant select field -->
        <!-- Auto-filled Amount Section -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Suggested Amount</label>
            <div class="input-group">
                <span class="input-group-text">UGX</span>
                <input type="text" id="suggestedAmount" class="form-control" readonly>
                <button type="button" class="btn btn-outline-secondary" onclick="useSuggestedAmount()">
                    Use This Amount
                </button>
            </div>
            <small class="text-muted">Based on apartment rent: <span id="rentDetails">Select a tenant</span></small>
        </div>

        <!-- Payment Status Alert -->
        <div id="paymentStatusAlert" class="mb-3" style="display: none;">
            <div class="alert mb-0" id="paymentStatusMessage">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                                    <select name="payment_method" class="form-select" required id="paymentMethod">
                                        <option value="">-- Select Method --</option>
                                        <option value="cash" {{ old('payment_method')=='cash'?'selected':'' }}>Cash</option>
                                        <option value="pesapal" {{ old('payment_method')=='pesapal'?'selected':'' }}>Pesapal Online</option>
                                        <option value="bank_transfer" {{ old('payment_method')=='bank_transfer'?'selected':'' }}>Bank Transfer</option>
                                        <option value="mobile_money" {{ old('payment_method')=='mobile_money'?'selected':'' }}>Mobile Money</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Month <span class="text-danger">*</span></label>
                                    <input type="month" name="month" id="monthInput" class="form-control" value="{{ old('month', date('Y-m')) }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Amount (UGX) <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" class="form-control" value="{{ old('amount') }}" required min="1">
                                </div>
                            </div>
                        </div>

                        <!-- Payment Allocation Options -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Payment Allocation</label>
                                    <div class="card border">
                                        <div class="card-body py-2">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="allocation_type" id="allocateCurrent" value="current" checked>
                                                <label class="form-check-label" for="allocateCurrent">
                                                    <i class="bi bi-calendar-check"></i> Current/Future Month
                                                    <small class="text-muted d-block">Apply payment starting from selected month forward</small>
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline ms-4">
                                                <input class="form-check-input" type="radio" name="allocation_type" id="allocateArrears" value="arrears">
                                                <label class="form-check-label" for="allocateArrears">
                                                    <i class="bi bi-clock-history"></i> Arrears (Back Months)
                                                    <small class="text-muted d-block">Apply payment to unpaid previous months first</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted">Choose how to allocate this payment if it covers multiple months</small>
                                </div>
                            </div>
                        </div>

                        <!-- Arrears Selection (shown when arrears is selected) -->
                        <div class="row" id="arrearsSection" style="display: none;">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Select Arrears Month</label>
                                    <input type="month" name="arrears_start_month" id="arrearsStartMonth" class="form-control"
                                           max="{{ date('Y-m') }}">
                                    <small class="text-muted">Payment will be allocated starting from this month forward</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label fw-semibold">Actual Payment Date</label>
            <input type="date" name="actual_payment_date" class="form-control" 
                   value="{{ old('actual_payment_date', date('Y-m-d')) }}">
            <small class="text-muted">The date when tenant actually made the payment</small>
        </div>
    </div>
</div>

                        <!-- Reference Number (shown for non-cash methods) -->
                        <div class="row" id="referenceField" style="display: none;">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Reference Number</label>
                                    <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}" 
                                        placeholder="Transaction ID, MOMO number, etc.">
                                </div>
                            </div>
                        </div>

                        <!-- Payment Options -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="send_sms" value="1" class="form-check-input" checked>
                                        <label class="form-check-label">
                                            <i class="bi bi-chat-text"></i> Send payment confirmation SMS
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="paid_to_landlord_directly" value="1" class="form-check-input" id="paidToLandlord">
                                        <label class="form-check-label" for="paidToLandlord">
                                            <i class="bi bi-person-fill-check"></i> Paid Directly to Landlord
                                        </label>
                                        <small class="d-block text-muted">
                                            Check if tenant paid landlord directly. Commission still applies.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Any additional notes...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle"></i> Payment Instructions:</h6>
                            <ul class="mb-0">
                                <li><strong>Cash:</strong> Payment will be marked as paid immediately</li>
                                <li><strong>Pesapal:</strong> Tenant will receive payment link to complete transaction</li>
                                <li><strong>Bank Transfer/Mobile Money:</strong> Mark as paid after confirming receipt</li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle me-1"></i> Process Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
        $(document).ready(function() {
            // Initialize Select2 for tenant search
            $('.select2-tenant').select2({
                theme: 'bootstrap-5',
                placeholder: 'Search for tenant by name or phone...',
                allowClear: true,
                width: '100%'
            });

            // Auto-fill amount when tenant is selected
            $('#tenantSelect').on('change', function() {
                const selected = this.options[this.selectedIndex];
                const rent = selected.getAttribute('data-rent');
                const apartment = selected.getAttribute('data-apartment');
                const landlord = selected.getAttribute('data-landlord');

                if (rent && rent > 0) {
                    $('#suggestedAmount').val(Number(rent).toLocaleString());
                    $('#rentDetails').text(`Apartment ${apartment} (${landlord}) - UGX ${Number(rent).toLocaleString()}/month`);
                    // Check payment status for selected month
                    checkPaymentStatus();
                } else {
                    $('#suggestedAmount').val('');
                    $('#rentDetails').text('No apartment assigned or rent not set');
                    $('#paymentStatusAlert').hide();
                }
            });

            // Also check when month changes
            $('input[name="month"]').on('change', function() {
                checkPaymentStatus();
            });
        });

        // Check payment status for tenant/month combination
        function checkPaymentStatus() {
            const tenantId = $('#tenantSelect').val();
            const month = $('input[name="month"]').val();

            if (!tenantId || !month) {
                $('#paymentStatusAlert').hide();
                return;
            }

            $.ajax({
                url: '{{ route("admin.payments.payment-status") }}',
                method: 'GET',
                data: {
                    tenant_id: tenantId,
                    month: month
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        const alertDiv = $('#paymentStatusAlert');
                        const messageDiv = $('#paymentStatusMessage');

                        // Update suggested amount to remaining balance
                        $('#suggestedAmount').val(Number(data.suggested_amount).toLocaleString());

                        // Show status message
                        alertDiv.show();
                        if (data.is_fully_paid) {
                            messageDiv.removeClass('alert-warning alert-info').addClass('alert-success');
                            messageDiv.html('<i class="bi bi-check-circle-fill"></i> <strong>Fully Paid!</strong> ' + data.message +
                                '<br><small class="text-muted">Adding more will count as advance payment for future months.</small>');
                        } else if (data.total_paid > 0) {
                            messageDiv.removeClass('alert-success alert-info').addClass('alert-warning');
                            messageDiv.html('<i class="bi bi-exclamation-triangle-fill"></i> <strong>Partial Payment Exists!</strong><br>' +
                                data.message +
                                '<br><small>Monthly rent: UGX ' + Number(data.monthly_rent).toLocaleString() + '</small>');
                        } else {
                            messageDiv.removeClass('alert-success alert-warning').addClass('alert-info');
                            messageDiv.html('<i class="bi bi-info-circle-fill"></i> ' + data.message +
                                '<br><small>Monthly rent: UGX ' + Number(data.monthly_rent).toLocaleString() + '</small>');
                        }
                    }
                },
                error: function() {
                    $('#paymentStatusAlert').hide();
                }
            });
        }

        function useSuggestedAmount() {
            const suggested = $('#suggestedAmount').val().replace(/,/g, '');
            if (suggested) {
                $('input[name="amount"]').val(suggested);
            }
        }

        // Your existing payment method logic remains the same
        document.getElementById('paymentMethod').addEventListener('change', function() {
            const referenceField = document.getElementById('referenceField');
            if (this.value !== 'cash' && this.value !== 'pesapal') {
                referenceField.style.display = 'block';
            } else {
                referenceField.style.display = 'none';
            }
        });
        document.getElementById('paymentMethod').dispatchEvent(new Event('change'));

        // Handle allocation type toggle
        $('input[name="allocation_type"]').on('change', function() {
            if ($(this).val() === 'arrears') {
                $('#arrearsSection').slideDown();
                // When switching to arrears, update the main month field behavior
                $('#monthInput').prop('required', false);
                $('#arrearsStartMonth').prop('required', true);
            } else {
                $('#arrearsSection').slideUp();
                $('#monthInput').prop('required', true);
                $('#arrearsStartMonth').prop('required', false);
            }
        });

        // When arrears month is selected, update the month field
        $('#arrearsStartMonth').on('change', function() {
            if ($('#allocateArrears').is(':checked')) {
                $('#monthInput').val($(this).val());
                checkPaymentStatus();
            }
        });
        </script>
        @endpush
        @endsection