<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageAdded;
use App\Events\ChatMessageDeleted;
use App\Events\MessageUpdated;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChatMessageController extends Controller
{
    /**
     * @param  Request  $request
     * @param  Chat  $chat
     * @return void
     * @throws AuthorizationException
     */
    public function store(Request $request, Chat $chat): void
    {
        Gate::authorize('addMessage', $chat);

        $request->validate([
            'message' => 'required',
        ]);

        $user_id = $request->user()->id;

        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'user_id' => $user_id,
            'message' => $request->get('message'),
        ]);

        $message->user_id = $user_id;

        $message->load('user');

        event(new ChatMessageAdded($message));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Chat $chat
     * @param \App\Models\ChatMessage $message
     * @return void
     */
    public function update(
        Request $request,
        Chat $chat,
        ChatMessage $message
    ): void {
        Gate::authorize('update', $message);

        $request->validate([
            'message' => 'required',
        ]);

        $message->fill($request->only('message'));

        $message->save();

        $message->load('user');

        event(new MessageUpdated($message));
    }

    /**
     * @param  Chat  $chat
     * @param  ChatMessage  $message
     * @return void
     * @throws AuthorizationException
     */
    public function delete(Chat $chat, ChatMessage $message): void
    {
        Gate::authorize('delete', $message);

        $message->delete();

        event(new ChatMessageDeleted($chat->id, $message->id));
    }
}
