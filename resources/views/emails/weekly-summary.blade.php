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
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 28px; }
        .stat-card { background: #f5f3ff; border-radius: 10px; padding: 16px; text-align: center; }
        .stat-value { font-size: 28px; font-weight: 800; color: #6366f1; }
        .stat-label { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .section-title { font-size: 13px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; }
        .habit-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
        .habit-name { font-size: 14px; font-weight: 500; color: #111827; }
        .habit-rate { font-size: 13px; font-weight: 700; }
        .rate-high { color: #10b981; }
        .rate-mid  { color: #f59e0b; }
        .rate-low  { color: #ef4444; }
        .progress-bar { height: 4px; background: #f3f4f6; border-radius: 999px; margin-top: 4px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 999px; background: #6366f1; }
        .cta { text-align: center; margin: 28px 0; }
        .cta a { display: inline-block; background: #6366f1; color: #fff; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 600; }
        .footer { padding: 24px 40px; border-top: 1px solid #f3f4f6; text-align: center; font-size: 12px; color: #9ca3af; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-red   { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>📊 Weekly Summary</h1>
        <p>{{ now()->subDays(7)->format('M j') }} – {{ now()->format('M j, Y') }}</p>
    </div>
    <div class="body">
        <p style="font-size:15px; color:#374151; margin-bottom:24px;">
            Hey {{ $user->name }}, here's how your week went:
        </p>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">{{ $stats['completions'] }}</div>
                <div class="stat-label">Total Completions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['completion_rate'] }}%</div>
                <div class="stat-label">Completion Rate</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['best_streak'] }}</div>
                <div class="stat-label">Best Streak (days)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['active_habits'] }}</div>
                <div class="stat-label">Active Habits</div>
            </div>
        </div>

        <!-- Per habit breakdown -->
        <p class="section-title">Habit breakdown</p>
        @foreach($habitStats as $habit)
            @php
                $rateClass = $habit['rate'] >= 70 ? 'rate-high' : ($habit['rate'] >= 40 ? 'rate-mid' : 'rate-low');
            @endphp
            <div class="habit-row">
                <div style="flex:1; min-width:0; margin-right:16px;">
                    <div class="habit-name">{{ $habit['name'] }}</div>
                    <div class="progress-bar" style="margin-top:6px;">
                        <div class="progress-fill" style="width:{{ $habit['rate'] }}%;
                            background: {{ $habit['rate'] >= 70 ? '#10b981' : ($habit['rate'] >= 40 ? '#f59e0b' : '#ef4444') }}">
                        </div>
                    </div>
                </div>
                <div style="text-align:right; white-space:nowrap;">
                    <div class="habit-rate {{ $rateClass }}">{{ $habit['rate'] }}%</div>
                    <div style="font-size:11px; color:#9ca3af;">{{ $habit['completed'] }}/{{ $habit['possible'] }} days</div>
                </div>
            </div>
        @endforeach

        <div class="cta" style="margin-top:32px;">
            <a href="{{ url('/analytics') }}">View Full Analytics →</a>
        </div>
    </div>
    <div class="footer">
        You're receiving this because you enabled weekly summaries.
        <br>
        <a href="{{ url('/settings') }}" style="color:#6366f1;">Manage preferences</a>
    </div>
</div>
</body>
</html>
