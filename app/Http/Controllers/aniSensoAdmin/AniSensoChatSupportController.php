<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsChatConversation;
use App\Models\AsChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AniSensoChatSupportController extends Controller
{
    /**
     * Display the chat support dashboard.
     */
    public function index()
    {
        $conversations = AsChatConversation::active()
            ->with('latestMessage')
            ->orderByDesc('updated_at')
            ->get();

        $settings = DB::table('as_chat_settings')
            ->pluck('settingValue', 'settingKey')
            ->toArray();

        return view('aniSensoAdmin.chat-support.index', compact('conversations', 'settings'));
    }

    /**
     * Get conversations list (for AJAX polling).
     */
    public function getConversations(Request $request)
    {
        $status = $request->get('status', 'all');

        $query = AsChatConversation::active()
            ->with('latestMessage')
            ->orderByDesc('updated_at');

        if ($status === 'active') {
            $query->where('status', 'active');
        } elseif ($status === 'closed') {
            $query->where('status', 'closed');
        }

        $conversations = $query->get()->map(function ($conversation) {
            return [
                'id' => $conversation->id,
                'visitorName' => $conversation->visitorName ?? 'Visitor',
                'visitorEmail' => $conversation->visitorEmail,
                'farmLocation' => $conversation->farmLocation,
                'visitorType' => $conversation->visitorType,
                'visitorTypeLabel' => AsChatConversation::VISITOR_TYPE_LABELS[$conversation->visitorType] ?? null,
                'leadId' => $conversation->leadId,
                'status' => $conversation->status,
                'statusBadge' => $conversation->status_badge,
                'statusLabel' => $conversation->status_label,
                'unreadCount' => $conversation->unreadCount(),
                'latestMessage' => $conversation->latestMessage ? [
                    'message' => $conversation->latestMessage->message,
                    'senderType' => $conversation->latestMessage->senderType,
                    'createdAt' => $conversation->latestMessage->created_at->format('M d, h:i A'),
                ] : null,
                'updatedAt' => $conversation->updated_at->format('M d, h:i A'),
            ];
        });

        return response()->json(['success' => true, 'conversations' => $conversations]);
    }

    /**
     * Get messages for a specific conversation (for AJAX polling).
     */
    public function getMessages($id)
    {
        $conversation = AsChatConversation::active()->findOrFail($id);

        // Mark visitor messages as read
        AsChatMessage::where('conversationId', $id)
            ->where('senderType', 'visitor')
            ->where('isRead', false)
            ->update(['isRead' => true]);

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'senderType' => $message->senderType,
                    'message' => $message->message,
                    'isRead' => $message->isRead,
                    'createdAt' => $message->created_at->format('M d, h:i A'),
                ];
            });

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'visitorName' => $conversation->visitorName ?? 'Visitor',
                'visitorEmail' => $conversation->visitorEmail,
                'farmLocation' => $conversation->farmLocation,
                'visitorType' => $conversation->visitorType,
                'visitorTypeLabel' => AsChatConversation::VISITOR_TYPE_LABELS[$conversation->visitorType] ?? null,
                'leadId' => $conversation->leadId,
                'status' => $conversation->status,
                'statusBadge' => $conversation->status_badge,
                'statusLabel' => $conversation->status_label,
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Send a message from admin.
     */
    public function sendMessage(Request $request, $id)
    {
        $conversation = AsChatConversation::active()->findOrFail($id);

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $message = AsChatMessage::create([
            'conversationId' => $conversation->id,
            'senderType' => 'admin',
            'senderId' => Auth::id(),
            'message' => $request->message,
            'isRead' => false,
            'deleteStatus' => 'active',
        ]);

        // Update conversation timestamp
        $conversation->touch();

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'senderType' => $message->senderType,
                'message' => $message->message,
                'isRead' => $message->isRead,
                'createdAt' => $message->created_at->format('M d, h:i A'),
            ],
        ]);
    }

    /**
     * Close a conversation.
     */
    public function closeConversation($id)
    {
        $conversation = AsChatConversation::active()->findOrFail($id);
        $conversation->update(['status' => 'closed']);

        return response()->json([
            'success' => true,
            'message' => 'Conversation closed successfully.',
        ]);
    }

    /**
     * Reopen a conversation.
     */
    public function reopenConversation($id)
    {
        $conversation = AsChatConversation::active()->findOrFail($id);
        $conversation->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Conversation reopened successfully.',
        ]);
    }

    /**
     * Delete a conversation (soft delete).
     */
    public function destroy($id)
    {
        $conversation = AsChatConversation::active()->findOrFail($id);

        if ($conversation->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Please close the conversation before deleting it.',
            ], 422);
        }

        $conversation->update(['deleteStatus' => 'deleted']);

        // Also soft-delete all messages
        AsChatMessage::where('conversationId', $id)->update(['deleteStatus' => 'deleted']);

        return response()->json([
            'success' => true,
            'message' => 'Conversation deleted successfully.',
        ]);
    }

    /**
     * Save chat support settings.
     */
    public function saveSettings(Request $request)
    {
        $request->validate([
            'auto_reply_enabled' => 'required|in:0,1',
            'auto_reply_message' => 'nullable|string|max:1000',
        ]);

        DB::table('as_chat_settings')
            ->where('settingKey', 'auto_reply_enabled')
            ->update(['settingValue' => $request->auto_reply_enabled, 'updated_at' => now()]);

        DB::table('as_chat_settings')
            ->where('settingKey', 'auto_reply_message')
            ->update(['settingValue' => $request->auto_reply_message ?? '', 'updated_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Settings saved successfully.',
        ]);
    }
}
