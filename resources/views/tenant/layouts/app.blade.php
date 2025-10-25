<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title','PhilWil Apartments - Tenant')</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: linear-gradient(180deg, #111827 0%, #1f2937 100%);
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 2px 0 10px rgba(0,0,0,0.25);
            transition: all .3s ease;
            z-index: 1050;
        }

        .sidebar-header {
            padding: 1.5rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-header h4 {
            font-weight: 600;
            color: #fff;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .sidebar-nav {
            flex-grow: 1;
            padding: 1.25rem;
        }

        .sidebar-nav a {
            color: #cbd5e1;
            display: flex;
            align-items: center;
            padding: 0.65rem 0.9rem;
            border-radius: 0.5rem;
            margin-bottom: 0.35rem;
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.2s ease;
            position: relative;
        }

        .sidebar-nav a i {
            font-size: 1.1rem;
            margin-right: 0.75rem;
        }

        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
            transform: translateX(3px);
        }

        .sidebar-nav a.active {
            background: linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
            box-shadow: 0 2px 6px rgba(37,99,235,0.4);
        }

        /* Logout Button */
        .sidebar-footer {
            padding: 1.25rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-footer button {
            width: 100%;
            border: none;
            background: #dc2626;
            color: #fff;
            border-radius: 0.5rem;
            padding: 0.6rem 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            transition: all 0.2s ease;
        }

        .sidebar-footer button:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }

        /* Main content */
        .content {
            margin-left: 260px;
            padding: 2rem;
            transition: margin-left .3s ease;
        }

        /* Top bar */
        .topbar {
            background: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .topbar h5 {
            margin: 0;
            font-weight: 600;
            color: #1e293b;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                left: -260px;
            }
            .sidebar.show {
                left: 0;
            }
            .content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar">
        <div>
            <div class="sidebar-header">
                <h4><i class="bi bi-person-badge"></i> Tenant</h4>
            </div>
            <div class="sidebar-nav">
                <a href="{{ route('tenant.dashboard') }}" class="{{ request()->routeIs('tenant.dashboard') ? 'active':'' }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                @php
                    $tenant = auth()->user()->tenant;
                @endphp

                @if($tenant && $tenant->apartment)
                    <a href="{{ route('tenant.payments.index') }}" class="{{ request()->routeIs('tenant.payments.*') ? 'active':'' }}">
                        <i class="bi bi-wallet2"></i> Payments
                    </a>
                @endif
            </div>
        </div>

        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-fill content">
        <!-- Top bar -->
        <div class="topbar d-flex justify-content-between align-items-center">
            <button class="btn btn-outline-secondary d-md-none" 
                    onclick="document.querySelector('.sidebar').classList.toggle('show')">
                <i class="bi bi-list"></i>
            </button>
            <h5>@yield('title','Dashboard')</h5>
        </div>

        <!-- Page content -->
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
