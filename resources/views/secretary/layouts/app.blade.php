<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title','PhilWil Apartments - Secretary')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { width: 250px; min-height: 100vh; background-color: #343a40; }
        .sidebar a { color: #fff; display: block; padding: .75rem 1rem; text-decoration: none; border-radius: .3rem; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; left: -250px; transition: all .3s; z-index: 1050; }
            .sidebar.show { left: 0; }
            .content { margin-left: 0 !important; }
        }
    </style>
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar p-3">
        <h4 class="text-white mb-4">Secretary</h4>
        <a href="{{ route('secretary.dashboard') }}" class="{{ request()->routeIs('secretary.dashboard') ? 'active':'' }}"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
        <a href="{{ route('secretary.tenants.index') }}" class="{{ request()->routeIs('secretary.tenants.*') ? 'active':'' }}"><i class="bi bi-people-fill me-2"></i> Tenants</a>
        <a href="{{ route('secretary.apartments.index') }}" class="{{ request()->routeIs('secretary.apartments.*') ? 'active':'' }}"><i class="bi bi-building me-2"></i> Apartments</a>
        <a href="{{ route('secretary.payments.index') }}" class="{{ request()->routeIs('secretary.payments.*') ? 'active':'' }}"><i class="bi bi-cash-stack me-2"></i> Payments</a>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button class="btn btn-danger w-100"><i class="bi bi-box-arrow-right"></i> Logout</button>
        </form>
    </div>

    <!-- Main Content -->
    <div class="flex-fill p-4 content">
        <!-- Mobile toggle button -->
        <button class="btn btn-outline-secondary d-md-none mb-3" onclick="document.querySelector('.sidebar').classList.toggle('show')">
            <i class="bi bi-list"></i> Menu
        </button>

        @yield('content')
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
