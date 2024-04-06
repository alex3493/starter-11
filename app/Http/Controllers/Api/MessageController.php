<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageAdded;
use App\Events\ChatMessageDeleted;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    /**
     * @param \App\Models\Chat $chat
     * @return \Illuminate\Database\Eloquent\Collection|array
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Chat $chat): Collection|array
    {
        Gate::authorize('listMessages', $chat);

        $afterId = request('afterId', null);
        $pageSize = request('perPage', 15);
        $idDir = request('idDir', 'desc');
        $operator = $idDir == 'asc' ? '>' : '<';

        $query = ChatMessage::where('chat_id', $chat->id)->with('user');

        $count = $query->count();

        if (! is_null($afterId)) {
            $query->where('id', $operator, $afterId);
        }

        $messages = $query->orderBy('id', $idDir)->limit($pageSize)->get();

        return [
            // We query messages in pages from newest to oldest, but we return each chunk in "ASC" order.
            'items' => array_reverse($messages->toArray()),
            'total' => $count,
        ];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Chat $chat
     * @return \App\Models\ChatMessage|\Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request, Chat $chat): ChatMessage|Model
    {
        Gate::authorize('addMessage', $chat);

        $request->validate([
            'message' => 'required',
        ]);

        $user_id = $request->user()->id;

        $message = ChatMessage::create([
            'message' => $request->get('message'),
            'user_id' => $user_id,
            'chat_id' => $chat->id,
        ]);

        $message = $message->load('user');

        event(new ChatMessageAdded($message));

        return $message;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * @param \App\Models\ChatMessage $message
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(ChatMessage $message): void
    {
        Gate::authorize('delete', $message);

        $message->delete();

        event(new ChatMessageDeleted($message->chat->id, $message->id));
    }
}
