<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Services\AiService;
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

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $responseContent = $aiService->chat($request->user(), $conversation, $request->message);

        return response()->json([
            'response' => $responseContent,
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
