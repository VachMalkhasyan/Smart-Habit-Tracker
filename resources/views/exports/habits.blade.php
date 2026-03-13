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
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #6366f1; color: white; padding: 8px 10px; text-align: left; font-size: 11px; }
        td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; font-size: 11px; }
        tr:nth-child(even) td { background: #f9fafb; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: bold; }
        .active    { background: #dcfce7; color: #16a34a; }
        .paused    { background: #fef9c3; color: #ca8a04; }
        .completed { background: #dbeafe; color: #2563eb; }
        .inactive  { background: #f3f4f6; color: #6b7280; }
        .high   { color: #ef4444; font-weight: bold; }
        .medium { color: #f59e0b; font-weight: bold; }
        .low    { color: #22c55e; font-weight: bold; }
        .footer { margin-top: 24px; text-align: center; color: #9ca3af; font-size: 10px; }
    </style>
</head>
<body>
<div class="header">
    <h1>HabitFlow — Habits Report</h1>
    <p>{{ $user->name }} · Generated {{ now()->format('F j, Y') }}</p>
</div>

<table>
    <thead>
    <tr>
        <th>Name</th>
        <th>Category</th>
        <th>Status</th>
        <th>Priority</th>
        <th>Goal</th>
        <th>Streak</th>
        <th>Best</th>
    </tr>
    </thead>
    <tbody>
    @foreach($habits as $habit)
        <tr>
            <td><strong>{{ $habit->name }}</strong>
                @if($habit->description)
                    <br><span style="color:#9ca3af">{{ Str::limit($habit->description, 40) }}</span>
                @endif
            </td>
            <td>{{ $habit->category?->name ?? '—' }}</td>
            <td><span class="badge {{ $habit->status }}">{{ ucfirst($habit->status) }}</span></td>
            <td class="{{ match($habit->priority) { 1 => 'high', 2 => 'medium', 3 => 'low', default => '' } }}">
                {{ match($habit->priority) { 1 => 'High', 2 => 'Medium', 3 => 'Low', default => '—' } }}
            </td>
            <td>{{ $habit->goal }} {{ $habit->goal_unit }}</td>
            <td>🔥 {{ $habit->current_streak }}d</td>
            <td>{{ $habit->longest_streak }}d</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">HabitFlow · {{ $habits->count() }} habits total</div>
</body>
</html>
