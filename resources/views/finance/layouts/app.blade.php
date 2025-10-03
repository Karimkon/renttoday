<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title','Finance Dashboard - PhilWil Apartments')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar bg-success text-white p-3" style="min-height:100vh;width:220px;">
        <h4 class="mb-3">Finance</h4>
        <a href="{{ route('finance.dashboard') }}" class="text-white d-block mb-2">
            <i class="bi bi-cash-coin me-2"></i> Dashboard
        </a>
        <a href="{{ route('finance.payments.index') }}" class="text-white d-block mb-2">
            <i class="bi bi-wallet2 me-2"></i> Payments
        </a>
        <a href="{{ route('finance.expenses.index') }}" class="text-white d-block mb-2">
            <i class="bi bi-receipt me-2"></i> Expenses
        </a>
        <form action="{{ route('logout') }}" method="POST" class="mt-3">@csrf
            <button class="btn btn-light w-100">
                <i class="bi bi-box-arrow-left"></i> Logout
            </button>
        </form>
    </div>

    <!-- Content -->
    <div class="flex-fill p-4">
        @yield('content')
    </div>
</div>
</body>
</html>
