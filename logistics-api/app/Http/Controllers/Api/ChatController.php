<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function contacts(Request $request)
    {
        $currentUserId = $request->user()->id;

        $contacts = User::with(['roles', 'hub'])
            ->where('id', '!=', $currentUserId)
            ->where('approval_status', 'approved')
            ->orderBy('name')
            ->get(['id', 'name', 'first_name', 'last_name', 'email', 'phone_number', 'hub_id']);

        return response()->json($contacts);
    }

    public function conversations(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Conversation::with(['userOne.roles', 'userTwo.roles', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->where(fn ($q) => $q->where('user_one_id', $userId)->orWhere('user_two_id', $userId))
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($conv) use ($userId) {
                $otherUser = $conv->user_one_id === $userId ? $conv->userTwo : $conv->userOne;
                $unreadCount = ChatMessage::where('conversation_id', $conv->id)
                    ->where('sender_id', '!=', $userId)
                    ->where('is_read', false)
                    ->count();

                return [
                    'id' => $conv->id,
                    'other_user' => $otherUser,
                    'last_message' => $conv->messages->first(),
                    'last_message_at' => $conv->last_message_at,
                    'unread_count' => $unreadCount,
                ];
            });

        return response()->json($conversations);
    }

    public function startConversation(Request $request)
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
        ]);

        $userId = $request->user()->id;
        $recipientId = (int) $validated['recipient_id'];

        if ($userId === $recipientId) {
            return response()->json(['message' => 'Cannot start conversation with yourself.'], 422);
        }

        $userOne = min($userId, $recipientId);
        $userTwo = max($userId, $recipientId);

        $conversation = Conversation::firstOrCreate(
            ['user_one_id' => $userOne, 'user_two_id' => $userTwo],
            ['last_message_at' => now()]
        );

        return response()->json($conversation->load(['userOne', 'userTwo']));
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $userId = $request->user()->id;

        if ($conversation->user_one_id !== $userId && $conversation->user_two_id !== $userId) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        // Mark incoming messages as read
        ChatMessage::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = $conversation->messages()->with('sender')->oldest()->get();

        return response()->json($messages);
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $userId = $request->user()->id;

        if ($conversation->user_one_id !== $userId && $conversation->user_two_id !== $userId) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'attachment' => 'nullable|file|max:10240', // up to 10MB
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat_attachments', 'public');
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userId,
            'message' => $validated['message'],
            'attachment_path' => $attachmentPath,
            'is_read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json($message->load('sender'), 201);
    }
}
