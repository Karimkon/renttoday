@extends('admin.layouts.app')

@section('title', 'Bulk SMS Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-chat-text me-2"></i> Bulk SMS Management</h3>
        <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Payments
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- SMS Send Results -->
    @if(session('sent_messages') || session('failed_messages'))
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-send-check"></i> SMS Send Results</h6>
            <span class="badge bg-primary">
                Sent: {{ count(session('sent_messages', [])) }} | 
                Failed: {{ count(session('failed_messages', [])) }}
            </span>
        </div>
        <div class="card-body p-0">
            @if(session('sent_messages'))
                <div class="p-3 border-bottom">
                    <h6 class="text-success mb-3">
                        <i class="bi bi-check-circle"></i> Successfully Sent 
                        <span class="badge bg-success">{{ count(session('sent_messages')) }}</span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="20%">Tenant</th>
                                    <th width="15%">Apartment</th>
                                    <th width="15%">Status</th>
                                    <th width="20%">Payment Details</th>
                                    <th width="30%">Message Preview</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(session('sent_messages') as $sent)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $sent['tenant'] }}</div>
                                        <small class="text-muted">{{ $sent['phone'] }}</small>
                                    </td>
                                    <td>{{ $sent['apartment'] }}</td>
                                    <td>
                                        <span class="badge 
                                            {{ $sent['status'] == 'paid' ? 'bg-success' : '' }}
                                            {{ $sent['status'] == 'unpaid' ? 'bg-danger' : '' }}
                                            {{ $sent['status'] == 'partial' ? 'bg-warning text-dark' : '' }}">
                                            {{ strtoupper($sent['status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($sent['status'] == 'partial')
                                            <small>Paid: UGX {{ $sent['paid'] }}</small><br>
                                            <small>Balance: UGX {{ $sent['balance'] }}</small>
                                        @elseif($sent['status'] == 'paid')
                                            <small>Fully Paid</small>
                                        @else
                                            <small>Not Paid</small>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ Str::limit($sent['message'], 60) }}...</small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            
            @if(session('failed_messages'))
                <div class="p-3">
                    <h6 class="text-warning mb-3">
                        <i class="bi bi-exclamation-triangle"></i> Failed to Send 
                        <span class="badge bg-warning text-dark">{{ count(session('failed_messages')) }}</span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="25%">Tenant</th>
                                    <th width="15%">Apartment</th>
                                    <th width="20%">Status</th>
                                    <th width="40%">Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(session('failed_messages') as $failed)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $failed['tenant'] }}</div>
                                        <small class="text-muted">{{ $failed['apartment'] }}</small>
                                    </td>
                                    <td>{{ $failed['apartment'] }}</td>
                                    <td>
                                        <span class="badge 
                                            {{ $failed['status'] == 'paid' ? 'bg-success' : '' }}
                                            {{ $failed['status'] == 'unpaid' ? 'bg-danger' : '' }}
                                            {{ $failed['status'] == 'partial' ? 'bg-warning text-dark' : '' }}">
                                            {{ strtoupper($failed['status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-danger">{{ $failed['reason'] }}</small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @endif

    <div class="row">
        <!-- SMS Configuration -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-gear"></i> SMS Configuration</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.sms.bulk-sms.send') }}" method="POST" id="smsForm">
                        @csrf

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Select Month <span class="text-danger">*</span></label>
                                    <select name="month" class="form-select" id="monthSelect" required>
                                        @foreach($months as $m)
                                            <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                                {{ \Carbon\Carbon::createFromFormat('Y-m', $m)->format('F Y') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Filter Tenants</label>
                                    <select name="status" class="form-select" id="statusFilter">
                                        <option value="unpaid" {{ $selectedStatus == 'unpaid' ? 'selected' : '' }}>Unpaid Only</option>
                                        <option value="partial" {{ $selectedStatus == 'partial' ? 'selected' : '' }}>Partial Payments</option>
                                        <option value="partial_high" {{ $selectedStatus == 'partial_high' ? 'selected' : '' }}>High Partial (≥50%)</option>
                                        <option value="partial_low" {{ $selectedStatus == 'partial_low' ? 'selected' : '' }}>Low Partial (<50%)</option>
                                        <option value="paid" {{ $selectedStatus == 'paid' ? 'selected' : '' }}>Paid Only</option>
                                        <option value="all" {{ $selectedStatus == 'all' ? 'selected' : '' }}>All Tenants</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Message Type</label>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="message_type" 
                                               value="reminder" id="reminderType" checked>
                                        <label class="form-check-label" for="reminderType">
                                            <strong>Smart Reminder</strong>
                                        </label>
                                        <div class="form-text">Auto-detects payment status</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="message_type" 
                                               value="partial_reminder" id="partialType">
                                        <label class="form-check-label" for="partialType">
                                            <strong>Partial Reminder</strong>
                                        </label>
                                        <div class="form-text">For partial payments</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="message_type" 
                                               value="balance_reminder" id="balanceType">
                                        <label class="form-check-label" for="balanceType">
                                            <strong>Balance Reminder</strong>
                                        </label>
                                        <div class="form-text">Focus on outstanding balance</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="message_type" 
                                               value="custom" id="customType">
                                        <label class="form-check-label" for="customType">
                                            <strong>Custom Message</strong>
                                        </label>
                                        <div class="form-text">Write your own message</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Custom Message Input -->
                        <div class="mb-3" id="customMessageDiv" style="display: none;">
                            <label class="form-label fw-semibold">Custom Message <span class="text-danger">*</span></label>
                            <textarea name="custom_message" class="form-control" rows="4" 
                                      placeholder="Enter your custom message here. Available placeholders: 
@{{tenant_name}}, @{{apartment_number}}, @{{rent_amount}}, @{{month}}, @{{balance}}, @{{paid_amount}}, @{{percentage_paid}}, @{{payment_status}}, @{{due_date}}, @{{next_month}}"
                                      maxlength="160"></textarea>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <span id="charCount">0</span>/160 characters • 
                                    SMS cost: UGX 25 per message
                                </small>
                            </div>
                            
                            <!-- Message Preview -->
                            <div class="mt-3 border rounded p-3 bg-light" id="messagePreview">
                                <small class="text-muted">Message preview will appear here...</small>
                            </div>
                        </div>

                        <!-- Selected Tenants Info -->
                        <div class="alert alert-info mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-info-circle"></i>
                                    <span id="selectedCount">0</span> tenants selected for SMS
                                    <span class="ms-3" id="statusBreakdown"></span>
                                </div>
                                <div>
                                    <small class="text-muted me-3" id="estimatedCost">Estimated cost: UGX 0</small>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllBtn">
                                        <i class="bi bi-check-all"></i> Select All
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllBtn">
                                        <i class="bi bi-x-circle"></i> Deselect All
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- HIDDEN FIELD FOR SELECTED TENANTS -->
                        <div id="selectedTenantsInputs">
                            <!-- This will be populated with hidden inputs via JavaScript -->
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="sendSmsBtn">
                                <i class="bi bi-send"></i> Send Bulk SMS to 
                                <span class="badge bg-white text-primary ms-1" id="sendCount">0</span> 
                                Selected Tenants
                            </button>
                            <small class="text-center text-muted">
                                <i class="bi bi-exclamation-triangle"></i> 
                                SMS will be sent immediately to selected tenants. This action cannot be undone.
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tenants List -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-people"></i> Tenants List</h6>
                    <div>
                        <span class="badge bg-primary" id="totalTenants">{{ $tenants->count() }}</span>
                        <span class="badge bg-danger ms-1" id="unpaidCount">0</span>
                        <span class="badge bg-warning text-dark ms-1" id="partialCount">0</span>
                        <span class="badge bg-success ms-1" id="paidCount">0</span>
                    </div>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    @if($tenants->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($tenants as $tenant)
                            <div class="list-group-item list-group-item-action px-0">
                                <div class="form-check">
                                    <input class="form-check-input tenant-checkbox" type="checkbox" 
                                           value="{{ $tenant->id }}" 
                                           id="tenant_{{ $tenant->id }}"
                                           data-status="{{ $tenant->paymentStatus }}"
                                           data-paid="{{ $tenant->paidAmount }}"
                                           data-balance="{{ $tenant->balance }}">
                                    <label class="form-check-label w-100" for="tenant_{{ $tenant->id }}">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <strong class="text-truncate">{{ $tenant->name }}</strong>
                                            <span class="badge 
                                                {{ $tenant->paymentStatus == 'paid' ? 'bg-success' : '' }}
                                                {{ $tenant->paymentStatus == 'unpaid' ? 'bg-danger' : '' }}
                                                {{ $tenant->paymentStatus == 'partial_low' ? 'bg-warning text-dark' : '' }}
                                                {{ $tenant->paymentStatus == 'partial_high' ? 'bg-info' : '' }}">
                                                @if($tenant->paymentStatus == 'partial_low')
                                                    PARTIAL (<50%)
                                                @elseif($tenant->paymentStatus == 'partial_high')
                                                    PARTIAL (≥50%)
                                                @else
                                                    {{ strtoupper($tenant->paymentStatus) }}
                                                @endif
                                            </span>
                                        </div>
                                        <div class="text-muted small mb-1">
                                            <i class="bi bi-building"></i> Apt: {{ $tenant->apartment->number ?? 'N/A' }}
                                            • <i class="bi bi-cash"></i> UGX {{ number_format($tenant->dueAmount) }}
                                        </div>
                                        @if($tenant->paymentStatus == 'partial_low' || $tenant->paymentStatus == 'partial_high')
                                        <div class="small">
                                            <span class="badge bg-light text-dark me-1">
                                                Paid: UGX {{ number_format($tenant->paidAmount) }}
                                            </span>
                                            <span class="badge bg-light text-dark">
                                                Balance: UGX {{ number_format($tenant->balance) }}
                                            </span>
                                            <span class="badge bg-secondary ms-1">
                                                {{ $tenant->percentagePaid }}%
                                            </span>
                                        </div>
                                        @endif
                                        <div class="text-muted small mt-1">
                                            <i class="bi bi-telephone"></i> {{ $tenant->phone }}
                                        </div>
                                        @if($tenant->lastPaymentDate)
                                        <div class="text-muted small">
                                            <i class="bi bi-calendar"></i> 
                                            Last paid: {{ $tenant->lastPaymentDate->format('M d') }}
                                        </div>
                                        @endif
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-emoji-frown fs-1 text-muted"></i>
                            <p class="mt-2 text-muted">No tenants found for the selected filters.</p>
                            <a href="{{ route('admin.sms.bulk-sms') }}?status=all" class="btn btn-sm btn-outline-primary">
                                Show All Tenants
                            </a>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white">
                    <div class="row">
                        <div class="col">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> 
                                Legend: 
                                <span class="badge bg-danger">Unpaid</span>
                                <span class="badge bg-warning text-dark">Partial <50%</span>
                                <span class="badge bg-info">Partial ≥50%</span>
                                <span class="badge bg-success">Paid</span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize select2
    $('#monthSelect').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select month...',
        allowClear: false
    });

    $('#statusFilter').select2({
        theme: 'bootstrap-5',
        minimumResultsForSearch: Infinity
    });

    // Handle message type toggle
    $('input[name="message_type"]').change(function() {
        if ($(this).val() === 'custom') {
            $('#customMessageDiv').slideDown();
            updateMessagePreview();
        } else {
            $('#customMessageDiv').slideUp();
        }
        updateMessagePreview();
    });

    // Character count for custom message
    $('textarea[name="custom_message"]').on('input', function() {
        const length = $(this).val().length;
        $('#charCount').text(length);
        updateMessagePreview();
    });

    // Initialize counts
    updateSelectionCount();
    updateStatusCounts();

    // Handle tenant selection
    $('.tenant-checkbox').change(function() {
        updateSelectionCount();
        updateStatusBreakdown();
        updateHiddenInputs();
    });

    // Select all / deselect all
    $('#selectAllBtn').click(function() {
        $('.tenant-checkbox').prop('checked', true);
        updateSelectionCount();
        updateStatusBreakdown();
        updateHiddenInputs();
    });

    $('#deselectAllBtn').click(function() {
        $('.tenant-checkbox').prop('checked', false);
        updateSelectionCount();
        updateStatusBreakdown();
        updateHiddenInputs();
    });

    // Filter tenants when month/status changes
    $('#monthSelect, #statusFilter').change(function() {
        const month = $('#monthSelect').val();
        const status = $('#statusFilter').val();
        window.location.href = `{{ route('admin.sms.bulk-sms') }}?month=${month}&status=${status}`;
    });

    // Update message preview
    function updateMessagePreview() {
        const messageType = $('input[name="message_type"]:checked').val();
        const month = $('#monthSelect').find('option:selected').text();
        const nextMonth = new Date($('#monthSelect').val() + '-01');
        nextMonth.setMonth(nextMonth.getMonth() + 1);
        const nextMonthName = nextMonth.toLocaleString('default', { month: 'long', year: 'numeric' });
        
        if (messageType === 'custom') {
            const customMessage = $('textarea[name="custom_message"]').val();
            if (customMessage) {
                $('#messagePreview').html(`<small>${customMessage.replace(/\n/g, '<br>')}</small>`);
            } else {
                $('#messagePreview').html('<small class="text-muted">Enter a custom message to see preview...</small>');
            }
        } else if (messageType === 'partial_reminder') {
            $('#messagePreview').html(`
                <small>
                    <strong>Partial Payment Reminder Preview:</strong><br>
                    • For unpaid: Standard rent reminder<br>
                    • For partial: Balance reminder with paid amount<br>
                    • For paid: Thank you message
                </small>
            `);
        } else if (messageType === 'balance_reminder') {
            $('#messagePreview').html(`
                <small>
                    <strong>Balance Reminder Preview:</strong><br>
                    • For unpaid: Full amount due reminder<br>
                    • For partial: Focus on outstanding balance<br>
                    • For paid: Confirmation and next due date
                </small>
            `);
        } else {
            $('#messagePreview').html(`
                <small>
                    <strong>Smart Reminder Preview (auto-detects status):</strong><br>
                    • Unpaid: Rent due with due date and late fee<br>
                    • Partial: Paid amount and balance reminder<br>
                    • Paid: Thank you with next payment date
                </small>
            `);
        }
    }

    // Update selection count and estimated cost
    function updateSelectionCount() {
        const selectedCount = $('.tenant-checkbox:checked').length;
        const estimatedCost = selectedCount * 25;
        
        $('#selectedCount').text(selectedCount);
        $('#estimatedCost').text(`Estimated cost: UGX ${estimatedCost}`);
        $('#sendCount').text(selectedCount);
        
        // Update send button state
        $('#sendSmsBtn').prop('disabled', selectedCount === 0);
        if (selectedCount === 0) {
            $('#sendSmsBtn').addClass('disabled');
        } else {
            $('#sendSmsBtn').removeClass('disabled');
        }
    }

    // Update status breakdown
    function updateStatusBreakdown() {
        const selected = $('.tenant-checkbox:checked');
        let unpaid = 0, partialLow = 0, partialHigh = 0, paid = 0;
        
        selected.each(function() {
            const status = $(this).data('status');
            if (status === 'unpaid') unpaid++;
            else if (status === 'partial_low') partialLow++;
            else if (status === 'partial_high') partialHigh++;
            else if (status === 'paid') paid++;
        });
        
        const breakdown = [];
        if (unpaid > 0) breakdown.push(`<span class="badge bg-danger">${unpaid} unpaid</span>`);
        if (partialLow > 0) breakdown.push(`<span class="badge bg-warning text-dark">${partialLow} partial <50%</span>`);
        if (partialHigh > 0) breakdown.push(`<span class="badge bg-info">${partialHigh} partial ≥50%</span>`);
        if (paid > 0) breakdown.push(`<span class="badge bg-success">${paid} paid</span>`);
        
        $('#statusBreakdown').html(breakdown.join(' '));
    }

    // Update status counts
    function updateStatusCounts() {
        const tenants = $('.tenant-checkbox');
        let unpaid = 0, partialLow = 0, partialHigh = 0, paid = 0;
        
        tenants.each(function() {
            const status = $(this).data('status');
            if (status === 'unpaid') unpaid++;
            else if (status === 'partial_low') partialLow++;
            else if (status === 'partial_high') partialHigh++;
            else if (status === 'paid') paid++;
        });
        
        $('#unpaidCount').text(unpaid);
        $('#partialCount').text(partialLow + partialHigh);
        $('#paidCount').text(paid);
    }

    // Update hidden inputs for selected tenants
    function updateHiddenInputs() {
        const selectedTenants = $('.tenant-checkbox:checked');
        const container = $('#selectedTenantsInputs');
        container.empty(); // Clear existing inputs
        
        selectedTenants.each(function() {
            const tenantId = $(this).val();
            container.append(`<input type="hidden" name="tenant_ids[]" value="${tenantId}">`);
        });
    }

    // Form submission confirmation
    $('#smsForm').submit(function(e) {
        const selectedCount = $('.tenant-checkbox:checked').length;
        const estimatedCost = selectedCount * 25;
        
        if (selectedCount === 0) {
            e.preventDefault();
            alert('Please select at least one tenant to send SMS to.');
            return false;
        }
        
        // First, update the hidden inputs
        updateHiddenInputs();
        
        // Simple confirmation
        const confirmation = confirm(
            `Are you sure you want to send SMS to ${selectedCount} tenant(s)?\n\n` +
            `Estimated cost: UGX ${estimatedCost}\n\n` +
            `This action cannot be undone.`
        );
        
        if (!confirmation) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        $('#sendSmsBtn').html('<i class="bi bi-hourglass-split"></i> Sending...').prop('disabled', true);
        
        return true;
    });

    // Initialize preview
    updateMessagePreview();
    updateHiddenInputs(); // Initialize hidden inputs
});
</script>
@endpush
@endsection