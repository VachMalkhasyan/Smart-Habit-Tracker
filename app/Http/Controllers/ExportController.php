<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\PlanService;

class ExportController extends Controller
{
    public function csv(Request $request)
    {
        if (!PlanService::can($request->user(), 'export')) {
            return back()->with('error', PlanService::upgradeMessage('export'));
        }

        $user   = $request->user();
        $type   = $request->get('type', 'habits');

        return match($type) {
            'habits'     => $this->exportHabitsCsv($user),
            'completions'=> $this->exportCompletionsCsv($user),
            'streaks'    => $this->exportStreaksCsv($user),
            'analytics'   => $this->exportAnalyticsCsv($user),
            default      => abort(404),
        };
    }

    public function pdf(Request $request)
    {
        if (!PlanService::can($request->user(), 'export')) {
            return back()->with('error', PlanService::upgradeMessage('export'));
        }

        $user = $request->user();
        $type = $request->get('type', 'habits');

        return match($type) {
            'habits'      => $this->exportHabitsPdf($user),
            'completions' => $this->exportCompletionsPdf($user),
            'streaks'     => $this->exportStreaksPdf($user),
            'analytics'   => $this->exportAnalyticsPdf($user),
            default       => abort(404),
        };
    }

    // ── CSV exports ──────────────────────────────────────

    private function exportHabitsCsv($user)
    {
        $habits = $user->habits()->with('category')->orderBy('priority')->get();

        $rows = collect([['Name', 'Description', 'Category', 'Status', 'Priority',
            'Goal', 'Start Date', 'Deadline', 'Current Streak', 'Longest Streak']]);

        foreach ($habits as $h) {
            $rows->push([
                $h->name,
                $h->description ?? '',
                $h->category?->name ?? 'No category',
                $h->status,
                match($h->priority) { 1 => 'High', 2 => 'Medium', 3 => 'Low', default => '' },
                "{$h->goal} {$h->goal_unit}",
                $h->start_date?->format('Y-m-d'),
                "{$h->deadline_value} {$h->deadline_unit}",
                $h->current_streak,
                $h->longest_streak,
            ]);
        }

        return $this->streamCsv($rows, 'habits');
    }

    private function exportCompletionsCsv($user)
    {
        $completions = $user->completions()
            ->with('habit')
            ->orderBy('completed_at', 'desc')
            ->get();

        $rows = collect([['Habit', 'Date', 'Count', 'Target', 'Completed']]);

        foreach ($completions as $c) {
            $rows->push([
                $c->habit?->name ?? 'Deleted habit',
                $c->completed_at->format('Y-m-d'),
                $c->count,
                $c->habit?->repeat_count ?? '—',
                $c->is_done ? 'Yes' : 'No',
            ]);
        }

        return $this->streamCsv($rows, 'completions');
    }

    private function exportStreaksCsv($user)
    {
        $habits = $user->habits()->orderBy('longest_streak', 'desc')->get();

        $rows = collect([['Name', 'Current Streak', 'Longest Streak', 'Status']]);

        foreach ($habits as $h) {
            $rows->push([
                $h->name,
                $h->current_streak,
                $h->longest_streak,
                $h->status,
            ]);
        }

        return $this->streamCsv($rows, 'streaks');
    }

    private function streamCsv($rows, string $name)
    {
        $filename = "{$name}_" . now()->format('Y-m-d') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, $headers);
    }

    // ── PDF exports ──────────────────────────────────────

    private function exportHabitsPdf($user)
    {
        $habits = $user->habits()->with('category')->orderBy('priority')->get();
        $pdf    = Pdf::loadView('exports.habits', compact('habits', 'user'));
        return $pdf->download('habits_' . now()->format('Y-m-d') . '.pdf');
    }

    private function exportCompletionsPdf($user)
    {
        $completions = $user->completions()
            ->with('habit')
            ->orderBy('completed_at', 'desc')
            ->take(100)
            ->get();
        $pdf = Pdf::loadView('exports.completions', compact('completions', 'user'));
        return $pdf->download('completions_' . now()->format('Y-m-d') . '.pdf');
    }

    private function exportStreaksPdf($user)
    {
        $habits = $user->habits()->orderBy('longest_streak', 'desc')->get();
        $pdf    = Pdf::loadView('exports.streaks', compact('habits', 'user'));
        return $pdf->download('streaks_' . now()->format('Y-m-d') . '.pdf');
    }

    private function exportAnalyticsCsv($user)
    {
        $habits = $user->habits()->with('completions')->get();

        $rows = collect([['Habit', 'Total Completions', 'Completion Rate %',
            'Current Streak', 'Longest Streak', 'Status']]);

        foreach ($habits as $habit) {
            $total = $habit->completions->count();
            $done  = $habit->completions->where('is_done', true)->count();
            $rate  = $total > 0 ? round(($done / $total) * 100) : 0;

            $rows->push([
                $habit->name,
                $done,
                $rate,
                $habit->current_streak,
                $habit->longest_streak,
                $habit->status,
            ]);
        }

        return $this->streamCsv($rows, 'analytics');
    }
    private function exportAnalyticsPdf($user)
    {
        $habits = $user->habits()->with('completions')->get()->map(function ($habit) {
            $total = $habit->completions->count();
            $done  = $habit->completions->where('is_done', true)->count();
            return [
                'name'             => $habit->name,
                'completion_rate'  => $total > 0 ? round(($done / $total) * 100) : 0,
                'total_completions'=> $done,
                'current_streak'   => $habit->current_streak,
                'longest_streak'   => $habit->longest_streak,
                'status'           => $habit->status,
            ];
        })->sortByDesc('completion_rate')->values();

        $totalCompletions  = $user->completions()->where('is_done', true)->count();
        $activeDays        = $user->completions()->where('is_done', true)->distinct('completed_at')->count('completed_at');
        $bestStreak        = $user->habits()->max('longest_streak') ?? 0;
        $avgCompletionRate = $habits->avg('completion_rate') ?? 0;

        $pdf = Pdf::loadView('exports.analytics', compact(
            'habits', 'user', 'totalCompletions',
            'activeDays', 'bestStreak', 'avgCompletionRate'
        ));

        return $pdf->download('analytics_' . now()->format('Y-m-d') . '.pdf');
    }
}
