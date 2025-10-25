@extends('secretary.layouts.app')

@section('title', 'Dashboard')

@section('content')
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<style>
    :root {
        --primary-blue: #2563eb;
        --secondary-blue: #3b82f6;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --bg-subtle: #f8fafc;
        --border-color: #e2e8f0;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    body {
        background-color: var(--bg-subtle);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        color: var(--text-primary);
    }

    .dashboard-header {
        margin-bottom: 2rem;
        animation: slideDown 0.6s ease-out;
    }

    .dashboard-title {
        font-weight: 600;
        font-size: 1.875rem;
        color: var(--text-primary);
        letter-spacing: -0.025em;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .dashboard-title i {
        color: var(--primary-blue);
        font-size: 1.5rem;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Card Styles */
    .metric-card {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
        padding: 1.5rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        height: 100%;
    }

    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--primary-blue), var(--secondary-blue));
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.3s ease;
    }

    .metric-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary-blue);
    }

    .metric-card:hover::before {
        transform: scaleX(1);
    }

    .metric-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        letter-spacing: -0.02em;
        margin-bottom: 0.5rem;
        line-height: 1;
    }

    .metric-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 0;
    }

    .metric-icon {
        position: absolute;
        right: 1.5rem;
        top: 1.5rem;
        width: 2.5rem;
        height: 2.5rem;
        background: var(--bg-subtle);
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-blue);
        font-size: 1.25rem;
        opacity: 0.6;
        transition: all 0.3s ease;
    }

    .metric-card:hover .metric-icon {
        opacity: 1;
        transform: scale(1.1);
        background: var(--primary-blue);
        color: white;
    }

    /* Status Cards */
    .status-card {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 0.625rem;
        padding: 1.25rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        height: 100%;
    }

    .status-card::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 2px;
        transition: all 0.3s ease;
    }

    .status-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .status-card.status-paid::after { background: #10b981; }
    .status-card.status-partial::after { background: #f59e0b; }
    .status-card.status-unpaid::after { background: #ef4444; }
    .status-card.status-empty::after { background: #6b7280; }
    .status-card.status-info::after { background: var(--primary-blue); }

    .status-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.375rem;
        line-height: 1;
    }

    .status-label {
        font-size: 0.8125rem;
        font-weight: 500;
        color: var(--text-secondary);
        margin: 0;
    }

    /* Section Divider */
    .section-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--border-color), transparent);
        margin: 3rem 0;
        border: none;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .dashboard-title {
            font-size: 1.5rem;
        }
        
        .metric-value {
            font-size: 1.5rem;
        }
        
        .metric-icon {
            width: 2rem;
            height: 2rem;
            font-size: 1rem;
        }

        .status-value {
            font-size: 1.5rem;
        }
    }

    /* Smooth entrance animations */
    .fade-in {
        animation: fadeIn 0.6s ease-out forwards;
        opacity: 0;
    }

    @keyframes fadeIn {
        to {
            opacity: 1;
        }
    }

    /* Currency styling */
    .currency {
        font-variant-numeric: tabular-nums;
    }
</style>

<div class="container-fluid py-4">
    <div class="dashboard-header">
        <h1 class="dashboard-title">
            <i class="bi bi-grid-1x2"></i>
            <span>Dashboard Overview</span>
        </h1>
    </div>

    <!-- Primary Metrics -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-duration="500">
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="metric-value">{{ $tenantsCount }}</div>
                <p class="metric-label">Active Tenants</p>
            </div>
        </div>

        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-duration="500" data-aos-delay="100">
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="bi bi-building"></i>
                </div>
                <div class="metric-value">{{ $apartmentsCount }}</div>
                <p class="metric-label">Total Apartments</p>
            </div>
        </div>

        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-duration="500" data-aos-delay="200">
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="metric-value currency">UGX {{ number_format($paymentsThisMonth, 0) }}</div>
                <p class="metric-label">Payments This Month</p>
            </div>
        </div>
    </div>

    <hr class="section-divider">

    <!-- Payment Status Grid -->
    <div class="row g-3 mb-4">
        <div class="col-lg-2 col-md-4 col-6" data-aos="fade-up" data-aos-duration="500">
            <div class="status-card status-paid">
                <div class="status-value">{{ $statusCounts['paid'] }}</div>
                <p class="status-label">Paid</p>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-6" data-aos="fade-up" data-aos-duration="500" data-aos-delay="50">
            <div class="status-card status-partial">
                <div class="status-value">{{ $statusCounts['partial'] }}</div>
                <p class="status-label">Partial</p>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-6" data-aos="fade-up" data-aos-duration="500" data-aos-delay="100">
            <div class="status-card status-unpaid">
                <div class="status-value">{{ $statusCounts['unpaid'] }}</div>
                <p class="status-label">Unpaid</p>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-6" data-aos="fade-up" data-aos-duration="500" data-aos-delay="150">
            <div class="status-card status-empty">
                <div class="status-value">{{ $statusCounts['empty'] }}</div>
                <p class="status-label">Empty</p>
            </div>
        </div>

        <div class="col-lg-4 col-md-8" data-aos="fade-up" data-aos-duration="500" data-aos-delay="200">
            <div class="status-card status-info">
                <div class="status-value currency">UGX {{ number_format($totalDue, 0) }}</div>
                <p class="status-label">Total Due Amount</p>
            </div>
        </div>
    </div>

    <!-- Additional Metrics -->
    <div class="row g-4">
        <div class="col-lg-6 col-md-6" data-aos="fade-up" data-aos-duration="500" data-aos-delay="100">
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="metric-value">{{ $paidAhead }}</div>
                <p class="metric-label">Tenants Paid Ahead</p>
            </div>
        </div>

        <div class="col-lg-6 col-md-6" data-aos="fade-up" data-aos-duration="500" data-aos-delay="200">
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="bi bi-arrow-up-circle"></i>
                </div>
                <div class="metric-value currency">UGX {{ number_format($paidAheadSum, 0) }}</div>
                <p class="metric-label">Total Paid Ahead Amount</p>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 600,
        easing: 'ease-out',
        once: true,
        offset: 50
    });
</script>
@endsection