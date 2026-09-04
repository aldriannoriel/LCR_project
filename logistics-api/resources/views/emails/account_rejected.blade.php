<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Registration Update</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 24px; }
        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header { border-bottom: 2px solid #ef4444; padding-bottom: 16px; margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: bold; color: #0f172a; margin: 0; }
        .badge { display: inline-block; background-color: #fee2e2; color: #991b1b; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 4px; margin-top: 8px; }
        .content { line-height: 1.6; font-size: 14px; }
        .reason-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 16px; margin: 20px 0; color: #7f1d1d; }
        .footer { font-size: 12px; color: #94a3b8; margin-top: 32px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Logistics OS Registration Update</h1>
            <span class="badge">Application Rejected</span>
        </div>
        <div class="content">
            <p>Hello <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>
            <p>Thank you for your interest in Logistics OS. After carefully reviewing your submitted credentials, our administrative team is unable to approve your application at this time.</p>
            
            <div class="reason-box">
                <strong>Reason for rejection:</strong>
                <p style="margin: 8px 0 0 0;">{{ $reason }}</p>
            </div>

            <p>If you believe this was in error or if you have updated verification documents to provide, please contact our support team or submit a new registration with the required credentials.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Logistics OS Platform. All rights reserved.
        </div>
    </div>
</body>
</html>

