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
        table { width: 100%; border-collapse: collapse; }
        th { background: #6366f1; color: white; padding: 8px 10px; text-align: left; font-size: 11px; }
        td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; font-size: 11px; }
        tr:nth-child(even) td { background: #f9fafb; }
        .streak { color: #f97316; font-weight: bold; }
        .footer { margin-top: 24px; text-align: center; color: #9ca3af; font-size: 10px; }
    </style>
</head>
<body>
<div class="header">
    <h1>HabitFlow — Streaks Summary</h1>
    <p>{{ $user->name }} · Generated {{ now()->format('F j, Y') }}</p>
</div>

<table>
    <thead>
    <tr>
        <th>#</th>
        <th>Habit</th>
        <th>Current Streak</th>
        <th>Longest Streak</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    @foreach($habits as $i => $habit)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td><strong>{{ $habit->name }}</strong></td>
            <td class="streak">🔥 {{ $habit->current_streak }} days</td>
            <td class="streak">⭐ {{ $habit->longest_streak }} days</td>
            <td>{{ ucfirst($habit->status) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">HabitFlow · {{ $habits->count() }} habits total</div>
</body>
</html>
