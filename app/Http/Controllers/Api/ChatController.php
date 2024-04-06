<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatCreated;
use App\Events\ChatDeleted;
use App\Events\ChatUpdated;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ChatController extends Controller
{
    /**
     * @return Collection|LengthAwarePaginator|array
     */
    public function index(): Collection|LengthAwarePaginator|array
    {
        $user_id = request('user_id');

        if (is_null($user_id)) {
            // All chats.
            $query = Chat::with('users');
        } else {
            // Filter by participant user.
            $query = Chat::with('users')->whereHas('users', function ($q) use ($user_id) {
                $q->where('user_id', $user_id);
            });
        }

        $afterId = request('afterId', null);
        $pageSize = request('perPage', 15);
        $idDir = request('idDir', 'desc');
        $operator = $idDir == 'asc' ? '>' : '<';
        $queryString = request('query', null);

        if (! is_null($queryString)) {
            $query->where(function (Builder $q1) use ($queryString) {
                $q1->where('topic', 'like', '%'.$queryString.'%')
                   ->orWhereHas('users', function (Builder $q2) use ($queryString) {
                       $q2->where('name', 'like', '%'.$queryString.'%')->orWhere('email', 'like', '%'.$queryString.'%');
                   });
            });
        }

        // Do not show abandoned chats. TODO: make it configurable.
        // $query->has('users');

        // Get items count before applying pagination.
        $count = $query->count();

        if (! is_null($afterId)) {
            $query->where('id', $operator, $afterId);
        }

        $chats = $query->orderBy('id', $idDir)->limit($pageSize)->get();

        return [
            'items' => $chats,
            'total' => $count,
        ];
    }

    /**
     * @param  Request  $request
     * @return Model|Chat
     */
    public function store(Request $request): Model|Chat
    {
        $request->validate([
            'topic' => 'required',
        ]);

        $user_id = $request->user()->id;

        $chat = Chat::create([
            'topic' => $request->get('topic'),
        ]);

        $chat->users()->attach($user_id);

        $chat = $chat->load('users');

        event(new ChatCreated($chat));

        return $chat;
    }

    /**
     * @param  Request  $request
     * @param  Chat  $chat
     * @return Model|Collection|Chat|array
     * @throws AuthorizationException
     */
    public function join(Request $request, Chat $chat): Model|Collection|Chat|array
    {
        $user_id = $request->user()->id;

        Gate::authorize('join', $chat);

        $chat->users()->attach($user_id);

        $chat = $chat->load('users');

        event(new ChatUpdated($chat));

        return $chat;
    }

    /**
     * @param  Request  $request
     * @param  Chat  $chat
     * @return Chat|Chat[]|Collection|Model
     */
    public function leave(Request $request, Chat $chat): Model|Collection|Chat|array
    {
        $user_id = $request->user()->id;

        $chat->users()->detach($user_id);

        $chat = $chat->load('users');

        // TODO: when the last user leaves the chat we can delete it.
        if ($chat->users->count() === 0) {
            $chat->delete();

            event(new ChatDeleted($chat->id));
        } else {
            event(new ChatUpdated($chat));
        }

        return $chat;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * @param  Request  $request
     * @param  Chat  $chat
     * @return Chat
     * @throws \Throwable
     */
    public function update(Request $request, Chat $chat): Chat
    {
        Gate::authorize('update', $chat);

        $request->validate([
            'topic' => 'required',
        ]);

        $chat->fill($request->only('topic'));

        $chat->save();

        $chat = $chat->load('users');

        event(new ChatUpdated($chat));

        return $chat;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
