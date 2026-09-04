<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Registration Under Review</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 24px; }
        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header { border-bottom: 2px solid #0d9488; padding-bottom: 16px; margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: bold; color: #0f172a; margin: 0; }
        .badge { display: inline-block; background-color: #fef3c7; color: #92400e; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 4px; margin-top: 8px; }
        .content { line-height: 1.6; font-size: 14px; }
        .details-box { background: #f1f5f9; border-radius: 6px; padding: 16px; margin: 20px 0; }
        .footer { font-size: 12px; color: #94a3b8; margin-top: 32px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Logistics OS Registration</h1>
            <span class="badge">Verification In Progress</span>
        </div>
        <div class="content">
            <p>Hello <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>
            <p>Thank you for registering with Logistics OS. We have received your application and supporting verification documents.</p>
            
            <div class="details-box">
                <p style="margin: 0 0 8px 0;"><strong>Application Summary:</strong></p>
                <p style="margin: 4px 0;"><strong>Email:</strong> {{ $user->email }}</p>
                <p style="margin: 4px 0;"><strong>Phone:</strong> {{ $user->phone_number }}</p>
                <p style="margin: 4px 0;"><strong>Business / Individual:</strong> {{ $user->business_name ?: 'Individual Shipper' }}</p>
                <p style="margin: 4px 0;"><strong>Location:</strong> {{ $user->barangay }}, {{ $user->city_municipality }}, {{ $user->province }}</p>
            </div>

            <p>Our administrative team is currently reviewing your identity and business credentials. You will receive an email confirmation once your account has been approved.</p>
            
            <p>If you have any urgent inquiries, please reach out to our support department.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Logistics OS Platform. All rights reserved.
        </div>
    </div>
</body>
</html>

