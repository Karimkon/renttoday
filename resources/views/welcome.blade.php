<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PhilWil Apartments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container text-center mt-5">
        <h1 class="display-4 fw-bold text-primary">🏢 PhilWil Apartments</h1>
        <p class="lead">Your Comfort, Our Priority</p>
        <div class="mt-4">
            <a href="{{ route('admin.login') }}" class="btn btn-dark m-2">Admin Login</a>
            <a href="{{ route('finance.login') }}" class="btn btn-success m-2">Finance Login</a>
            <a href="{{ route('secretary.login') }}" class="btn btn-primary m-2">Secretary Login</a>
        </div>
    </div>
</body>
</html>
