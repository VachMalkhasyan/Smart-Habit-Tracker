<?php

use App\Models\Habit;
use App\Models\User;
use App\Services\XpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\HabitTemplate;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/templates', function () {
    return HabitTemplate::orderBy('category')->orderBy('sort_order')->get();
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/extension/me', function (Request $request) {
        $user   = $request->user()->fresh();
        $habits = $user->habits()
            ->where('status', 'active')
            ->with(['completions' => fn($q) => $q->whereDate('completed_at', today())])
            ->orderBy('priority')
            ->get()
            ->map(fn($h) => [
                'id'              => $h->id,
                'name'            => $h->name,
                'current_streak'  => $h->current_streak,
                'completed_today' => $h->completions->where('is_done', true)->count() > 0,
            ]);

        return response()->json([
            'name'        => $user->name,
            'level'       => $user->level,
            'xp'          => $user->xp,
            'xp_progress' => XpService::progressToNextLevel($user),
            'habits'      => $habits,
        ]);
    });

    Route::post('/extension/habits/{habit}/toggle', function (Request $request, Habit $habit) {
        $user       = $request->user();
        $today      = now()->toDateString();
        $completion = $habit->completions()->firstOrCreate(
            ['completed_at' => $today],
            ['user_id' => $user->id, 'count' => 0, 'is_done' => false]
        );

        $completion->update([
            'is_done' => !$completion->is_done,
            'count'   => !$completion->is_done ? $habit->repeat_count : 0,
        ]);

        $xpResult = null;
        if ($completion->is_done) {
            $xpResult = XpService::award($user, XpService::XP_COMPLETE_HABIT,
                "Completed: {$habit->name}", 'habit', $habit->id);
        }

        $user->refresh();

        return response()->json([
            'is_done'     => $completion->is_done,
            'xp_result'   => $xpResult,
            'xp_progress' => XpService::progressToNextLevel($user),
        ]);
    });

});
Route::post('/extension/token', function (Request $request) {
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // Delete old extension tokens
    $user->tokens()->where('name', 'chrome-extension')->delete();

    $token = $user->createToken('chrome-extension')->plainTextToken;

    return response()->json(['token' => $token]);
});
