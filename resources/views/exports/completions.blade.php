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
        .yes { color: #16a34a; font-weight: bold; }
        .no  { color: #ef4444; }
        .footer { margin-top: 24px; text-align: center; color: #9ca3af; font-size: 10px; }
    </style>
</head>
<body>
<div class="header">
    <h1>HabitFlow — Completion History</h1>
    <p>{{ $user->name }} · Generated {{ now()->format('F j, Y') }} · Last 100 entries</p>
</div>

<table>
    <thead>
    <tr>
        <th>Habit</th>
        <th>Date</th>
        <th>Count</th>
        <th>Target</th>
        <th>Done</th>
    </tr>
    </thead>
    <tbody>
    @foreach($completions as $c)
        <tr>
            <td>{{ $c->habit?->name ?? 'Deleted habit' }}</td>
            <td>{{ $c->completed_at->format('M j, Y') }}</td>
            <td>{{ $c->count }}</td>
            <td>{{ $c->habit?->repeat_count ?? '—' }}</td>
            <td class="{{ $c->is_done ? 'yes' : 'no' }}">{{ $c->is_done ? '✓ Yes' : '✗ No' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">HabitFlow · {{ $completions->count() }} records shown</div>
</body>
</html>
