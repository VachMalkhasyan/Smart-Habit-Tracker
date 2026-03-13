<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
        .habit-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 8px; margin-bottom: 8px; }
        .habit-name { font-size: 14px; font-weight: 600; color: #111827; }
        .habit-meta { font-size: 12px; color: #6b7280; margin-top: 2px; }
        .cta { text-align: center; margin: 28px 0; }
        .cta a { display: inline-block; background: #6366f1; color: #fff; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 600; }
        .footer { padding: 24px 40px; border-top: 1px solid #f3f4f6; text-align: center; font-size: 12px; color: #9ca3af; }
        .streak-warning { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 14px 16px; margin-bottom: 24px; font-size: 13px; color: #ef4444; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>⚠️ Missed Habits Alert</h1>
        <p>{{ now()->subDay()->format('l, F j') }}</p>
    </div>
    <div class="body">
        <p class="greeting">Hey {{ $user->name }},</p>
        <p style="font-size:14px; color:#6b7280; margin-bottom:20px; line-height:1.6;">
            You missed <strong>{{ $missedHabits->count() }} habit{{ $missedHabits->count() > 1 ? 's' : '' }}</strong> yesterday.
            Don't worry — today is a new chance to get back on track!
        </p>

        @php $atRisk = $missedHabits->filter(fn($h) => $h->current_streak > 0); @endphp
        @if($atRisk->count() > 0)
            <div class="streak-warning">
                🔥 <strong>{{ $atRisk->count() }} streak{{ $atRisk->count() > 1 ? 's' : '' }} at risk!</strong>
                Complete these today to keep your progress alive.
            </div>
        @endif

        <p class="section-title">Missed yesterday</p>
        <ul class="habit-list">
            @foreach($missedHabits as $habit)
                <li class="habit-item">
                    <div>
                        <div class="habit-name">{{ $habit->name }}</div>
                        <div class="habit-meta">
                            🔥 {{ $habit->current_streak }} day streak
                            · {{ $habit->category?->name ?? 'No category' }}
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="cta">
            <a href="{{ url('/habits') }}">Complete Today's Habits →</a>
        </div>
    </div>
    <div class="footer">
        You're receiving this because you enabled missed habit alerts.
        <br>
        <a href="{{ url('/settings') }}" style="color:#6366f1;">Manage preferences</a>
    </div>
</div>
</body>
</html>
