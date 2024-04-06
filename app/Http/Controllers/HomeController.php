<?php

namespace App\Http\Controllers;

use App\Events\ChatCreated;
use App\Events\ChatDeleted;
use App\Events\ChatUpdated;
use App\Models\Chat;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('Home', [
            'chats' => Chat::latest()->filter([
                'search' => request('search'),
            ])->with('users')
                ->paginate(request('perPage', 10))
                ->withQueryString()
                ->through(fn($chat) => [
                    'id' => $chat->id,
                    'topic' => $chat->topic,
                    'created_at' => $chat->created_at,
                    'users' => $chat->users->map(function ($user) use ($chat) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            // TODO: just for now we disclose user email.
                            'email' => $user->email,
                            'created_at' => $user->created_at,
                            // Just in case we need it.
                            // 'pivot' => $user->pivot,
                        ];
                    }),
                    'can' => [
                        'listMessages' => Auth::user()->can('listMessages', $chat),
                        'delete' => Auth::user()->can('delete', $chat),
                        'join' => Auth::user()->can('join', $chat),
                        'leave' => Auth::user()->can('leave', $chat),
                        'update' => Auth::user()->can('update', $chat),
                    ],
                ]),
            'filters' => request()->only(['search', 'perPage']),
        ]);
    }

    /**
     * @param  Chat  $chat
     * @return Response
     */
    public function show(Chat $chat): Response
    {
        $search = request('search', '');
        $search = '%'.$search.'%';

        return Inertia::render('Chat', [
            'chat' => $chat->load('users'),
            'messages' => $chat->chatMessages()
                ->latest()
                ->where('message', 'like', $search)
                ->with('user')
                ->paginate(request('perPage', 10))
                ->withQueryString()
                ->through(fn($message) => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'created_at' => $message->created_at,
                    'user' => [
                        'id' => $message->user->id,
                        'name' => $message->user->name,
                        'email' => $message->user->email,
                        'created_at' => $message->user->created_at,
                    ],
                    'can' => [
                        'delete' => Auth::user()->can('delete', $message),
                        'update' => Auth::user()->can('update', $message),
                    ],
                ]),
            'returnUrl' => request('returnUrl'),
            'filters' => request()->only(['search', 'perPage']),
        ]);
    }

    /**
     * @param  Chat  $chat
     * @return JsonResponse
     */
    public function read(Chat $chat): JsonResponse
    {
        $chat->load('users');

        $chat->setAttribute('can', [
            'listMessages' => Auth::user()->can('listMessages', $chat),
            'delete' => Auth::user()->can('delete', $chat),
            'join' => Auth::user()->can('join', $chat),
            'leave' => Auth::user()->can('leave', $chat),
            'update' => Auth::user()->can('update', $chat),
        ]);

        return response()->json($chat);
    }

    /**
     * @param  Request  $request
     * @return void
     */
    public function store(Request $request): void
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
    }

    /**
     * @param  Request  $request
     * @param  Chat  $chat
     * @return void
     * @throws AuthorizationException
     */
    public function update(Request $request, Chat $chat): void
    {
        Gate::authorize('update', $chat);

        $request->validate([
            'topic' => 'required',
        ]);

        $chat->fill($request->only('topic'));

        $chat->save();

        $chat = $chat->load('users');

        event(new ChatUpdated($chat));
    }

    /**
     * @throws AuthorizationException
     */
    public function delete(Chat $chat): void
    {
        Gate::authorize('delete', $chat);

        $chat->delete();

        event(new ChatDeleted($chat->id));
    }

    public function join(Request $request, Chat $chat): void
    {
        $user_id = $request->user()->id;

        Gate::authorize('join', $chat);

        $chat->users()->attach($user_id);

        $chat = $chat->load('users');

        event(new ChatUpdated($chat));
    }

    public function leave(Request $request, Chat $chat): void
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
    }
}
