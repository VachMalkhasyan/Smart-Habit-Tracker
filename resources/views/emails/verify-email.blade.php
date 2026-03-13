<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f9fafb; color: #111827; }
        .wrapper { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.07); }
        .header { background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 36px 40px; text-align: center; }
        .header h1 { color: #fff; font-size: 22px; font-weight: 700; }
        .header p { color: rgba(255,255,255,0.85); margin-top: 6px; font-size: 14px; }
        .body { padding: 36px 40px; text-align: center; }
        .greeting { font-size: 15px; color: #374151; margin-bottom: 24px; text-align: left; line-height: 1.6; }
        .code-wrapper { background: #f5f3ff; border: 2px dashed #6366f1; border-radius: 16px; padding: 28px; margin: 24px 0; }
        .code { font-size: 42px; font-weight: 800; letter-spacing: 12px; color: #6366f1; font-family: monospace; }
        .code-label { font-size: 12px; color: #6b7280; margin-top: 8px; }
        .expiry { font-size: 13px; color: #f59e0b; font-weight: 600; margin-top: 4px; }
        .divider { height: 1px; background: #f3f4f6; margin: 24px 0; }
        .warning { font-size: 12px; color: #9ca3af; line-height: 1.6; text-align: left; }
        .footer { padding: 20px 40px; border-top: 1px solid #f3f4f6; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>🔐 Verify your email</h1>
        <p>Smart Habit Tracker</p>
    </div>
    <div class="body">
        <p class="greeting">
            Hey {{ $user->name }}, welcome aboard! 👋<br><br>
            Use the code below to verify your email address and activate your account.
        </p>

        <div class="code-wrapper">
            <div class="code">{{ $code }}</div>
            <div class="code-label">Your verification code</div>
            <div class="expiry">⏱ Expires in 30 minutes</div>
        </div>

        <div class="divider"></div>

        <p class="warning">
            ⚠️ If you didn't create an account on Smart Habit Tracker, you can safely ignore this email.
            Never share this code with anyone.
        </p>
    </div>
    <div class="footer">
        © {{ date('Y') }} Smart Habit Tracker. All rights reserved.
    </div>
</div>
</body>
</html>
