<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected $geminiService;

    public function __construct(\App\Services\GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = \Illuminate\Support\Facades\Auth::user();

        // 1. Save User Message
        $userMessage = \App\Models\ChatMessage::create([
            'user_id' => $user->id,
            'message' => $request->message,
            'sender' => 'user',
        ]);

        // 2. Fetch History (Last 10 messages for context)
        $history = \App\Models\ChatMessage::where('user_id', $user->id)
            ->where('id', '<', $userMessage->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->reverse()
            ->values();

        // 3. Prepare Context
        $context = [
            'user' => $user,
            'message' => $request->message,
            'history' => $history,
        ];

        // 4. Call AI
        $aiResponse = $this->geminiService->chat($context);

        // 5. Save AI Response
        $aiMessage = \App\Models\ChatMessage::create([
            'user_id' => $user->id,
            'message' => $aiResponse,
            'sender' => 'ai',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'user_message' => $userMessage,
                'ai_message' => $aiMessage
            ]
        ]);
    }

    public function getHistory()
    {
        $history = \App\Models\ChatMessage::where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['success' => true, 'data' => $history]);
    }
}
