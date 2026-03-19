<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Services\AiService;
use App\Services\PlanService;
use App\Models\AiMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AiController extends Controller
{
    /**
     * Renders the main frontend AI Coach page
     */
    public function page()
    {
        return Inertia::render('AiCoach/Index');
    }

    /**
     * Returns user's conversations list
     */
    public function index(Request $request)
    {
        $conversations = $request->user()->aiConversations()
            ->select('id', 'title', 'tokens_used', 'created_at')
            ->orderByDesc('updated_at')
            ->get();

        // Include preview of the last message if needed, or we can fetch it via a subquery
        $conversations->each(function ($conv) {
            $lastMessage = $conv->messages()->latest()->first();
            $conv->last_message_preview = $lastMessage ? \Str::limit($lastMessage->content, 50) : null;
        });

        return response()->json($conversations);
    }

    /**
     * Creates new conversation
     */
    public function store(Request $request)
    {
        $conversation = $request->user()->aiConversations()->create([
            'title' => $request->input('title', 'New Conversation'),
        ]);

        return response()->json($conversation);
    }

    /**
     * Returns conversation + all messages
     */
    public function show(AiConversation $conversation, Request $request)
    {
        // Authorize user owns it
        if ($conversation->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        $conversation->load(['messages' => function ($query) {
            $query->oldest();
        }]);

        return response()->json($conversation);
    }

    /**
     * Validates message string, calls AiService::chat(), returns response
     */
    public function chat(AiConversation $conversation, Request $request, AiService $aiService)
    {
        if ($conversation->user_id !== $request->user()->id) {
            abort(403);
        }

        $user = $request->user();

        // Check daily AI message limit
        $todayCount = AiMessage::whereHas('conversation',
                fn($q) => $q->where('user_id', $user->id)
            )
            ->where('role', 'user')
            ->whereDate('created_at', today())
            ->count();

        if (PlanService::hasReached($user, 'ai_messages_per_day', $todayCount)) {
            return response()->json([
                'error'         => PlanService::upgradeMessage('ai_messages_per_day'),
                'upgrade'       => true,
                'required_plan' => 'pro',
                'limit_reached' => 'ai_messages_per_day',
            ], 403);
        }

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $responseContent = $aiService->chat($request->user(), $conversation, $request->message);

        // After getting AI response, if conversation still has default title
        if ($conversation->title === 'New Conversation') {
            // Use first 6 words of user's message as title
            $words = explode(' ', $request->message);
            $title = implode(' ', array_slice($words, 0, 6));
            $title = strlen($title) > 40 ? substr($title, 0, 40) . '...' : $title;
            $conversation->update(['title' => ucfirst($title)]);
        }

        return response()->json([
            'response' => $responseContent,
            'conversation_title' => $conversation->fresh()->title,
        ]);
    }

    /**
     * Deletes conversation + messages
     */
    public function destroy(AiConversation $conversation, Request $request)
    {
        if ($conversation->user_id !== $request->user()->id) {
            abort(403);
        }

        $conversation->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Calls AiService::suggestHabits($user), returns suggestions array
     */
    public function suggestHabits(Request $request, AiService $aiService)
    {
        $suggestions = $aiService->suggestHabits($request->user());

        return response()->json($suggestions);
    }
}
