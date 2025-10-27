<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title','Admin Dashboard')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            min-height: 100vh;
            background: #212529;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
        }
        .sidebar h4 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            text-align: center;
            color: #fff;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: .6rem 1rem;
            border-radius: .375rem;
            text-decoration: none;
            color: #adb5bd;
            transition: all .2s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: #343a40;
            color: #fff !important;
        }

        /* Content */
        .content {
            margin-left: 240px;
            padding: 2rem;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: .75rem;
            background: #fff;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        /* Tables */
        .table {
            border-radius: .5rem;
            overflow: hidden;
        }
        .table thead {
            background: #212529;
            color: #fff;
        }
        .table tbody tr:hover {
            background-color: #f1f3f5;
        }

        /* Buttons & Badges */
        .btn {
            border-radius: .4rem;
            font-weight: 500;
        }
        .btn-sm {
            padding: .3rem .6rem;
            font-size: .8rem;
        }
        .badge {
            font-size: .75rem;
            padding: .4em .7em;
            border-radius: .5rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            .content {
                margin-left: 0;
            }
        }

        .input-group .form-control:focus {
    border-color: #0d6efd;
    box-shadow: none;
}
.card {
    border: 1px solid #e3e6f0;
    border-radius: 0.5rem;
}
.table th {
    border-top: none;
    font-weight: 600;
}

    </style>
</head>
<body>
    <div class="sidebar p-3">
        <h4><i class="bi bi-shield-lock"></i> Admin</h4>

        <a href="{{ route('admin.dashboard') }}" 
           class="{{ request()->routeIs('admin.dashboard') ? 'active text-white' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <a href="{{ route('admin.users.index') }}" 
           class="{{ request()->routeIs('admin.users.*') ? 'active text-white' : '' }}">
            <i class="bi bi-people"></i> Manage Users
        </a>

        <!-- Add these links to your admin sidebar -->
        <a href="{{ route('admin.landlords.index') }}" 
        class="{{ request()->routeIs('admin.landlords.*') ? 'active text-white' : '' }}">
            <i class="bi bi-person-badge"></i> Manage Landlords
        </a>

        <a href="{{ route('admin.tenants.index') }}" 
           class="{{ request()->routeIs('admin.tenants.*') ? 'active text-white' : '' }}">
            <i class="bi bi-person-lines-fill"></i> Manage Tenants
        </a>

        <a href="{{ route('admin.apartments.index') }}" 
           class="{{ request()->routeIs('admin.apartments.*') ? 'active text-white' : '' }}">
            <i class="bi bi-building"></i> Manage Apartments
        </a>

        <a href="{{ route('admin.inventory.index') }}" 
           class="{{ request()->routeIs('admin.inventory.*') ? 'active text-white' : '' }}">
            <i class="bi bi-box-seam"></i> Inventory
        </a>

        <a href="{{ route('admin.payments.index') }}" 
           class="{{ request()->routeIs('admin.payments.*') ? 'active text-white' : '' }}">
            <i class="bi bi-credit-card"></i> Payments
        </a>

        <a href="{{ route('admin.expenses.index') }}" 
        class="{{ request()->routeIs('admin.expenses.*') ? 'active text-white' : '' }}">
            <i class="bi bi-cash-coin"></i> Manage Expenses
        </a>

        <a href="{{ route('admin.financial-reports.index') }}" 
        class="{{ request()->routeIs('admin.financial-reports.*') ? 'active text-white' : '' }}">
            <i class="bi bi-graph-up"></i> Financial Reports
        </a>

    

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button class="btn btn-danger w-100">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
    </div>

    <div class="content">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- jQuery & Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

@stack('scripts')
</body>
</html>
