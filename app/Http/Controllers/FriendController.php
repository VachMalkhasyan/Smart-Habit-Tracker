<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friendship;
use App\Models\Completion;
use App\Models\Cheer;
use App\Notifications\FriendRequestReceived;
use App\Notifications\FriendCheeredCompletion;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FriendController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $user    = $request->user();
        $friends = $user->friends();

        $pending = $user->receivedFriendRequests()
            ->where('status', 'pending')
            ->with('sender')
            ->get();

        $friendsActivity = $friends->map(function ($friend) {
            $todayCompletions = $friend->completions()
                ->with(['habit', 'cheers'])
                ->whereDate('completed_at', today())
                ->where('is_done', true)
                ->get();

            return [
                'id'                => $friend->id,
                'name'              => $friend->name,
                'username'          => $friend->username,
                'profile_photo_url' => $friend->profile_photo_url,
                'is_public'         => $friend->is_public,
                'bio'               => $friend->bio,
                'longest_streak'    => $friend->habits()->max('longest_streak') ?? 0,
                'active_habits'     => $friend->habits()->where('status', 'active')->count(),
                'today_completions' => $todayCompletions,
            ];
        });

        return Inertia::render('Friends/Index', [
            'friends'         => $friendsActivity,
            'pendingRequests' => $pending,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $user  = $request->user();

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $users = User::where('id', '!=', $user->id)
            ->where(function ($q) use ($query) {       // ← wrap in closure
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('username', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->select('id', 'name', 'username', 'is_public', 'bio')
            ->limit(8)
            ->get()
            ->map(function ($u) use ($user) {
                $friendship = Friendship::where(function ($q) use ($user, $u) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $u->id);
                })->orWhere(function ($q) use ($user, $u) {
                    $q->where('sender_id', $u->id)->where('receiver_id', $user->id);
                })->first();

                $u->friendship_status    = $friendship?->status;
                $u->friendship_direction = $friendship?->sender_id === $user->id ? 'sent' : 'received';
                $u->profile_photo_url    = $u->profile_photo_url; // ← let the model accessor compute it
                return $u;
            });

        return response()->json($users);
    }

    public function sendRequest(Request $request, User $user)
    {
        $sender = $request->user();

        if ($sender->id === $user->id) {
            return back()->with('error', 'You cannot add yourself!');
        }

        $friendship = Friendship::firstOrCreate([
            'sender_id'   => $sender->id,
            'receiver_id' => $user->id,
        ], ['status' => 'pending']);

        // Notify receiver of new friend request
        $user->notify(new FriendRequestReceived($sender));

        return back()->with('success', "Friend request sent to {$user->name}!");
    }

    public function acceptRequest(Request $request, Friendship $friendship)
    {
        $this->authorize('update', $friendship);
        $friendship->update(['status' => 'accepted']);
        return back()->with('success', "You are now friends with {$friendship->sender->name}!");
    }

    public function declineRequest(Request $request, Friendship $friendship)
    {
        $this->authorize('update', $friendship);
        $friendship->update(['status' => 'declined']);
        return back()->with('info', 'Friend request declined.');
    }

    public function removeFriend(Request $request, User $user)
    {
        Friendship::where(function ($q) use ($request, $user) {
            $q->where('sender_id', $request->user()->id)
                ->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($request, $user) {
            $q->where('sender_id', $user->id)
                ->where('receiver_id', $request->user()->id);
        })->delete();

        return back()->with('success', 'Friend removed.');
    }

    public function cheer(Request $request, Completion $completion)
    {
        $request->validate(['emoji' => 'required|string|max:10']);

        $cheer = Cheer::updateOrCreate(
            ['user_id' => $request->user()->id, 'completion_id' => $completion->id],
            ['emoji'   => $request->emoji]
        );

        // Notify habit owner that someone cheered their completion
        $completion->load('habit.user');
        $habitOwner = $completion->habit->user;
        if ($habitOwner && $habitOwner->id !== $request->user()->id) {
            $habitOwner->notify(new FriendCheeredCompletion(
                $request->user(),
                $completion->habit->name,
                $request->emoji
            ));
        }

        return back()->with('success', 'Cheered!');
    }

    public function removeCheer(Request $request, Completion $completion)
    {
        Cheer::where('user_id', $request->user()->id)
            ->where('completion_id', $completion->id)
            ->delete();

        return back();
    }

    public function publicProfile(Request $request, User $user)
    {
        if (!$user->is_public && !$request->user()->isFriendWith($user)) {
            abort(403, 'This profile is private.');
        }

        $habits = $user->habits()
            ->with('category')
            ->where('status', 'active')
            ->orderBy('priority')
            ->get()
            ->map(fn($h) => [
                'id'             => $h->id,
                'name'           => $h->name,
                'category'       => $h->category?->name,
                'current_streak' => $h->current_streak,
                'longest_streak' => $h->longest_streak,
                'status'         => $h->status,
            ]);

        $stats = [
            'total_habits'    => $user->habits()->count(),
            'active_habits'   => $user->habits()->where('status', 'active')->count(),
            'longest_streak'  => $user->habits()->max('longest_streak') ?? 0,
            'total_completions' => $user->completions()->where('is_done', true)->count(),
        ];

        $isFriend      = $request->user()->isFriendWith($user);
        $hasPending    = Friendship::where('sender_id', $request->user()->id)
            ->where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        return Inertia::render('Friends/Profile', [
            'profileUser' => [
                'id'                => $user->id,
                'name'              => $user->name,
                'username'          => $user->username,
                'bio'               => $user->bio,
                'profile_photo_url' => $user->profile_photo_url,
                'is_public'         => $user->is_public,
            ],
            'habits'     => $habits,
            'stats'      => $stats,
            'isFriend'   => $isFriend,
            'hasPending' => $hasPending,
        ]);
    }
}
