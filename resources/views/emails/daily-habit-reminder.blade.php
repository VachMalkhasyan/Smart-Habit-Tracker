<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f9fafb; color: #111827; }
        .wrapper { max-width: 580px; margin: 40px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.07); }
        .header { background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 36px 40px; text-align: center; }
        .header h1 { color: #fff; font-size: 22px; font-weight: 700; }
        .header p { color: rgba(255,255,255,0.85); margin-top: 6px; font-size: 14px; }
        .body { padding: 36px 40px; }
        .greeting { font-size: 16px; color: #374151; margin-bottom: 20px; }
        .section-title { font-size: 13px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; }
        .habit-list { list-style: none; margin-bottom: 28px; }
        .habit-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f5f3ff; border-left: 3px solid #6366f1; border-radius: 8px; margin-bottom: 8px; }
        .habit-name { font-size: 14px; font-weight: 600; color: #111827; }
        .habit-meta { font-size: 12px; color: #6b7280; margin-top: 2px; }
        .streak-badge { font-size: 13px; font-weight: 700; color: #f97316; white-space: nowrap; }
        .cta { text-align: center; margin: 28px 0; }
        .cta a { display: inline-block; background: #6366f1; color: #fff; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 600; }
        .motivation { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px 16px; margin-bottom: 24px; font-size: 13px; color: #166534; line-height: 1.6; }
        .footer { padding: 24px 40px; border-top: 1px solid #f3f4f6; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>🌅 Good Morning, {{ $user->name }}!</h1>
        <p>{{ now()->format('l, F j, Y') }}</p>
    </div>
    <div class="body">
        <div class="motivation">
            💪 You have <strong>{{ $habits->count() }} habit{{ $habits->count() > 1 ? 's' : '' }}</strong> to complete today.
            Every day you show up builds the person you want to become!
        </div>

        <p class="section-title">Today's habits</p>
        <ul class="habit-list">
            @foreach($habits as $habit)
                <li class="habit-item">
                    <div>
                        <div class="habit-name">{{ $habit->name }}</div>
                        <div class="habit-meta">
                            {{ $habit->repeat_count }}x today
                            · {{ $habit->category?->name ?? 'No category' }}
                        </div>
                    </div>
                    @if($habit->current_streak > 0)
                        <div class="streak-badge">🔥 {{ $habit->current_streak }}d</div>
                    @endif
                </li>
            @endforeach
        </ul>

        <div class="cta">
            <a href="{{ url('/dashboard') }}">Open Dashboard →</a>
        </div>
    </div>
    <div class="footer">
        You're receiving this because you enabled daily reminders.
        <br>
        <a href="{{ url('/settings') }}" style="color:#6366f1;">Manage preferences</a>
    </div>
</div>
</body>
</html>
