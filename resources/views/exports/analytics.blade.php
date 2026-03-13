<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; padding: 30px; }
        .header { margin-bottom: 24px; border-bottom: 2px solid #6366f1; padding-bottom: 12px; }
        .header h1 { font-size: 22px; color: #6366f1; }
        .header p { color: #6b7280; font-size: 11px; margin-top: 4px; }
        .stats-grid { display: flex; gap: 12px; margin-bottom: 24px; }
        .stat-card { flex: 1; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px; text-align: center; }
        .stat-card .value { font-size: 24px; font-weight: bold; color: #6366f1; }
        .stat-card .label { font-size: 10px; color: #9ca3af; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #6366f1; color: white; padding: 8px 10px; text-align: left; font-size: 11px; }
        td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; font-size: 11px; }
        tr:nth-child(even) td { background: #f9fafb; }
        .bar-cell { position: relative; }
        .bar-bg { background: #e5e7eb; border-radius: 4px; height: 8px; width: 100%; }
        .bar-fill { height: 8px; border-radius: 4px; }
        .green  { background: #22c55e; }
        .indigo { background: #6366f1; }
        .yellow { background: #f59e0b; }
        .red    { background: #ef4444; }
        .rate-text { font-weight: bold; }
        .footer { margin-top: 24px; text-align: center; color: #9ca3af; font-size: 10px; }
    </style>
</head>
<body>
<div class="header">
    <h1>HabitFlow — Analytics Report</h1>
    <p>{{ $user->name }} · Generated {{ now()->format('F j, Y') }}</p>
</div>

<!-- Overview Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="value">{{ $totalCompletions }}</div>
        <div class="label">Total Completions</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ $activeDays }}</div>
        <div class="label">Active Days</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ $bestStreak }}d</div>
        <div class="label">Best Streak</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ round($avgCompletionRate) }}%</div>
        <div class="label">Avg Completion</div>
    </div>
</div>

<!-- Habit Performance Table -->
<table>
    <thead>
    <tr>
        <th>#</th>
        <th>Habit</th>
        <th>Completion Rate</th>
        <th>Total Done</th>
        <th>Current Streak</th>
        <th>Longest Streak</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    @foreach($habits as $i => $habit)
        @php
            $color = match(true) {
                $habit['completion_rate'] >= 80 => 'green',
                $habit['completion_rate'] >= 60 => 'indigo',
                $habit['completion_rate'] >= 40 => 'yellow',
                default                         => 'red',
            };
        @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td><strong>{{ $habit['name'] }}</strong></td>
            <td class="bar-cell">
                <div style="display:flex; align-items:center; gap:6px;">
                    <div class="bar-bg" style="width:80px">
                        <div class="bar-fill {{ $color }}"
                             style="width:{{ $habit['completion_rate'] }}%"></div>
                    </div>
                    <span class="rate-text" style="color:
                            {{ $color === 'green' ? '#16a34a' :
                               ($color === 'indigo' ? '#6366f1' :
                               ($color === 'yellow' ? '#d97706' : '#ef4444')) }}">
                            {{ $habit['completion_rate'] }}%
                        </span>
                </div>
            </td>
            <td>{{ $habit['total_completions'] }}</td>
            <td>🔥 {{ $habit['current_streak'] }}d</td>
            <td>⭐ {{ $habit['longest_streak'] }}d</td>
            <td>{{ ucfirst($habit['status']) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">HabitFlow · {{ count($habits) }} habits analyzed</div>
</body>
</html>
