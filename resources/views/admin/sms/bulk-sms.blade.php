@extends('admin.layouts.app')

@section('title', 'Bulk SMS - Unpaid Tenants')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-chat-text me-2"></i> Bulk SMS to Unpaid Tenants</h3>
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

    <!-- Show sent/failed messages summary -->
    @if(session('sent_messages') || session('failed_messages'))
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-send-check"></i> SMS Send Results</h6>
        </div>
        <div class="card-body">
            @if(session('sent_messages'))
                <div class="alert alert-success mb-3">
                    <h6><i class="bi bi-check-circle"></i> Successfully Sent ({{ count(session('sent_messages')) }})</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tenant</th>
                                    <th>Apartment</th>
                                    <th>Phone</th>
                                    <th>Message Preview</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(session('sent_messages') as $sent)
                                <tr>
                                    <td>{{ $sent['tenant'] }}</td>
                                    <td>{{ $sent['apartment'] }}</td>
                                    <td>{{ $sent['phone'] }}</td>
                                    <td><small>{{ Str::limit($sent['message'], 50) }}...</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            
            @if(session('failed_messages'))
                <div class="alert alert-warning">
                    <h6><i class="bi bi-exclamation-triangle"></i> Failed to Send ({{ count(session('failed_messages')) }})</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tenant</th>
                                    <th>Apartment</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(session('failed_messages') as $failed)
                                <tr>
                                    <td>{{ $failed['tenant'] }}</td>
                                    <td>{{ $failed['apartment'] }}</td>
                                    <td><small>{{ $failed['reason'] }}</small></td>
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
        <!-- SMS Form -->
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
                                        <option value="paid" {{ $selectedStatus == 'paid' ? 'selected' : '' }}>Paid Only</option>
                                        <option value="all" {{ $selectedStatus == 'all' ? 'selected' : '' }}>All Tenants</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Message Type</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="message_type" 
                                               value="reminder" id="reminderType" checked>
                                        <label class="form-check-label" for="reminderType">
                                            <strong>Standard Reminder</strong>
                                        </label>
                                    </div>
                                    <div class="ms-4 text-muted">
                                        <small>Automatic reminder with tenant details</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="message_type" 
                                               value="custom" id="customType">
                                        <label class="form-check-label" for="customType">
                                            <strong>Custom Message</strong>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Custom Message Input (Hidden by default) -->
                        <div class="mb-3" id="customMessageDiv" style="display: none;">
                            <label class="form-label fw-semibold">Custom Message <span class="text-danger">*</span></label>

<textarea name="custom_message" class="form-control" rows="4" 
          placeholder="Enter your custom message here. You can use these placeholders: {tenant_name}, {apartment_number}, {rent_amount}, {month}, {balance}"
          maxlength="160"></textarea>
<div class="mt-2">
    <small class="text-muted">
        <span id="charCount">0</span>/160 characters • 
        Available placeholders: 
        <span class="badge bg-info">{tenant_name}</span>
        <span class="badge bg-info">{apartment_number}</span>
        <span class="badge bg-info">{rent_amount}</span>
        <span class="badge bg-info">{month}</span>
        <span class="badge bg-info">{balance}</span>
    </small>
</div>
                            
                            <!-- Message Preview -->
                            <div class="mt-3 border rounded p-3 bg-light" id="messagePreview">
                                <small class="text-muted">Message preview will appear here...</small>
                            </div>
                        </div>

                        <!-- Selected Tenants Info -->
                        <div class="alert alert-info">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-info-circle"></i>
                                    <span id="selectedCount">0</span> tenants selected for SMS
                                    <small class="text-muted ms-2" id="estimatedCost">Estimated cost: UGX 0</small>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllBtn">
                                        <i class="bi bi-check-square"></i> Select All
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllBtn">
                                        <i class="bi bi-square"></i> Deselect All
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" id="sendSmsBtn">
                                <i class="bi bi-send"></i> Send Bulk SMS
                                <span class="badge bg-white text-primary ms-2" id="sendCount">0</span>
                            </button>
                            <small class="text-center text-muted mt-2">
                                SMS will be sent to selected tenants only. Rate: UGX 25 per SMS.
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
                    <span class="badge bg-primary" id="totalTenants">{{ $tenants->count() }}</span>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    @if($tenants->count() > 0)
                        <div class="list-group">
                            @foreach($tenants as $tenant)
                            <div class="list-group-item list-group-item-action">
                                <div class="form-check">
                                    <input class="form-check-input tenant-checkbox" type="checkbox" 
                                           name="tenant_ids[]" value="{{ $tenant->id }}" 
                                           id="tenant_{{ $tenant->id }}">
                                    <label class="form-check-label w-100" for="tenant_{{ $tenant->id }}">
                                        <div class="d-flex justify-content-between">
                                            <strong>{{ $tenant->name }}</strong>
                                            <span class="badge {{ $tenant->paymentStatus == 'paid' ? 'bg-success' : 'bg-danger' }}">
                                                {{ $tenant->paymentStatus == 'paid' ? 'Paid' : 'Unpaid' }}
                                            </span>
                                        </div>
                                        <div class="text-muted small">
                                            Apt: {{ $tenant->apartment->number ?? 'N/A' }}
                                            • UGX {{ number_format($tenant->dueAmount) }}
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-telephone"></i> {{ $tenant->phone }}
                                        </div>
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-emoji-frown fs-1 text-muted"></i>
                            <p class="mt-2 text-muted">No tenants found for the selected filters.</p>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white">
                    <div class="row">
                        <div class="col">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> 
                                Check tenants to include in SMS
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
    // Initialize month select
    $('#monthSelect').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select month...',
        allowClear: false
    });

    // Initialize status filter
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
    });

    // Character count for custom message
    $('textarea[name="custom_message"]').on('input', function() {
        const length = $(this).val().length;
        $('#charCount').text(length);
        updateMessagePreview();
    });

    // Handle tenant selection
    let selectedCount = 0;
    updateSelectionCount();

    $('.tenant-checkbox').change(function() {
        updateSelectionCount();
    });

    // Select all / deselect all buttons
    $('#selectAllBtn').click(function() {
        $('.tenant-checkbox').prop('checked', true);
        updateSelectionCount();
    });

    $('#deselectAllBtn').click(function() {
        $('.tenant-checkbox').prop('checked', false);
        updateSelectionCount();
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
        
        if (messageType === 'custom') {
            const customMessage = $('textarea[name="custom_message"]').val();
            if (customMessage) {
                // Show preview with placeholders
                $('#messagePreview').html(`<small>${customMessage.replace(/\n/g, '<br>')}</small>`);
            }
        } else {
            // Show standard reminder preview
            const month = $('#monthSelect').find('option:selected').text();
            $('#messagePreview').html(`
                <small>
                    <strong>Standard Reminder Preview:</strong><br>
                    Hello [Tenant Name], rent reminder for ${month}.<br>
                    Apartment [Apartment Number]: UGX [Rent Amount].<br>
                    Due date: 5th ${month}. Late fees apply after due date.<br>
                    Thank you! - PhilWil Apartments
                </small>
            `);
        }
    }

    // Update selection count and estimated cost
    function updateSelectionCount() {
        selectedCount = $('.tenant-checkbox:checked').length;
        const estimatedCost = selectedCount * 25;
        
        $('#selectedCount').text(selectedCount);
        $('#estimatedCost').text(`Estimated cost: UGX ${estimatedCost}`);
        $('#sendCount').text(selectedCount);
        
        // Update send button state
        if (selectedCount === 0) {
            $('#sendSmsBtn').prop('disabled', true).addClass('disabled');
        } else {
            $('#sendSmsBtn').prop('disabled', false).removeClass('disabled');
        }
    }

    // Form submission confirmation
   $('#smsForm').submit(function(e) {
    const selectedCount = $('.tenant-checkbox:checked').length;
    const estimatedCost = selectedCount * 25;
    
    // Validate at least one tenant is selected
    if (selectedCount === 0) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'No Tenants Selected',
            text: 'Please select at least one tenant to send SMS to.',
            confirmButtonText: 'OK'
        });
        return false;
    }
    
    // Confirm before sending
    if (!confirm(`Are you sure you want to send SMS to ${selectedCount} tenant(s)?\n\nEstimated cost: UGX ${estimatedCost}`)) {
        e.preventDefault();
        return false;
    }
    
    // Show loading state
    $('#sendSmsBtn').html('<i class="bi bi-hourglass-split"></i> Sending...').prop('disabled', true);
    
    return true;
});

    // Initialize preview
    updateMessagePreview();
    updateSelectionCount();
});
</script>
@endpush
@endsection