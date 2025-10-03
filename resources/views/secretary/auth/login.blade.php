<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secretary Login - Bakery ERP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #fff;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* floating orb background */
        .animated-orb {
            position: absolute;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, #0d6efd, #6610f2, #e83e8c);
            border-radius: 50%;
            filter: blur(120px);
            opacity: .4;
            z-index: 0;
            animation: floatOrb 18s infinite linear, shiftColors 10s infinite linear;
        }
        @keyframes floatOrb {
            0%   {transform: translate(10vw,60vh)}
            25%  {transform: translate(60vw,30vh)}
            50%  {transform: translate(40vw,10vh)}
            75%  {transform: translate(70vw,70vh)}
            100% {transform: translate(10vw,60vh)}
        }
        @keyframes shiftColors {
            0%   {background:radial-gradient(circle,#0d6efd,#6610f2,#e83e8c)}
            33%  {background:radial-gradient(circle,#6610f2,#20c997,#17a2b8)}
            66%  {background:radial-gradient(circle,#e83e8c,#fd7e14,#ffc107)}
            100% {background:radial-gradient(circle,#0d6efd,#6610f2,#e83e8c)}
        }

        /* login box */
        .login-container {
            max-width: 440px;
            margin: auto;
            padding: 2.5rem;
            border-radius: 18px;
            background-color: #fff;
            color: #333;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            position: relative;
            z-index: 2;
            animation: slideUp 0.7s ease-out;
        }

        /* logo */
        .login-logo {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 4px solid #0d6efd;
            animation: pulse 2s infinite, fadeIn 1s ease-out;
            box-shadow: 0 0 20px rgba(13,110,253,0.4);
        }

        .login-title {
            font-weight: 700;
            color: #0f172a;
            margin-top: 1rem;
            animation: fadeIn 1.2s ease-out;
        }

        .btn-blue {
            background-color: #0d6efd;
            border: none;
            transition: background 0.3s ease, transform 0.2s ease;
        }
        .btn-blue:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
        }

        /* animations */
        @keyframes pulse {
            0%,100% {transform:scale(1)}
            50%     {transform:scale(1.05)}
        }
        @keyframes fadeIn {
            from {opacity:0; transform: translateY(-10px)}
            to   {opacity:1; transform: translateY(0)}
        }
        @keyframes slideUp {
            from {opacity:0; transform:translateY(30px)}
            to   {opacity:1; transform:translateY(0)}
        }

        @media(max-width: 576px){
            .login-container { padding: 1.5rem; }
            .login-logo { width: 85px; height: 85px; border-width: 3px; }
            .login-title { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
<div class="animated-orb"></div>

<div class="d-flex align-items-center justify-content-center vh-100 px-3">
    <div class="login-container">
        <div class="text-center mb-4">
            <img src="{{ asset('images/bakerylogo.jpg') }}" alt="Bakery ERP Logo" class="login-logo mb-2">
            <h4 class="login-title">Secretary Login</h4>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('secretary.login.submit') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="d-grid">
                <button class="btn btn-blue text-white fw-bold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Login
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <small class="text-muted">© {{ date('Y') }} Hotel ERP. All rights reserved.</small>
        </div>
    </div>
</div>

<!-- Bootstrap JS (optional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
