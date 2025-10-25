<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome to Apartments</title>
</head>
<body>
    <h2>Hello {{ $tenant->name }},</h2>
    <p>Welcome! Your tenant account has been created.</p>
    <p><strong>Login email:</strong> {{ $tenant->email }}</p>
    <p><strong>Temporary password:</strong> {{ $password }}</p>
    <p>Please log in and change your password immediately.</p>
    <p><a href="{{ url('/tenant/login') }}">Login here</a></p>
</body>
</html>
