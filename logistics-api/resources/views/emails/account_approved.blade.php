<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Approved</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 24px; }
        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header { border-bottom: 2px solid #10b981; padding-bottom: 16px; margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: bold; color: #0f172a; margin: 0; }
        .badge { display: inline-block; background-color: #d1fae5; color: #065f46; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 4px; margin-top: 8px; }
        .content { line-height: 1.6; font-size: 14px; }
        .cta-button { display: inline-block; background-color: #0284c7; color: #ffffff !important; font-weight: 600; padding: 12px 24px; border-radius: 6px; text-decoration: none; margin-top: 20px; }
        .footer { font-size: 12px; color: #94a3b8; margin-top: 32px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Welcome to Logistics OS!</h1>
            <span class="badge">Account Approved</span>
        </div>
        <div class="content">
            <p>Hello <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>
            <p>Great news! Your registration and submitted verification documents have been reviewed and <strong>approved</strong> by our administrative team.</p>
            
            <p>You can now sign in to your Logistics OS account using your registered email: <strong>{{ $user->email }}</strong>.</p>
            
            <p style="text-align: center;">
                <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/login" class="cta-button">Sign In to Logistics OS</a>
            </p>

            <p>Thank you for partnering with us.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Logistics OS Platform. All rights reserved.
        </div>
    </div>
</body>
</html>

