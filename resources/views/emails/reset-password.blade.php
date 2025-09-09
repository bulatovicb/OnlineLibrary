<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset</title>
    <style>
        .container {
            font-family: Arial, sans-serif;
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            margin: auto;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3490dc;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #6b7280;
        }
        .link {
            word-break: break-word;
            overflow-wrap: anywhere;
            margin-top: 10px;
            font-size: 14px;
            color: #2563eb;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Password Reset Request</h2>
    <p>You are receiving this email because we received a password reset request for your account.</p>
    <a href="{{ $resetUrl }}" class="button">Reset Password</a>
    <p>This password reset link will expire in 60 minutes.<br>
        If you did not request a password reset, no further action is required.</p>


    <p class="link">
        If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:<br>
        <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
    </p>
</div>
</body>
</html>
