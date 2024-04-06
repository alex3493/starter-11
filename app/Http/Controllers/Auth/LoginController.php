<?php

namespace App\Http\Controllers\Auth;

use App\Events\ChatUpdated;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function login(): array
    {
        request()->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $user = User::where('email', request('email'))->first();

        if (!$user || !Hash::check(request('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Replace existing token for the same device name.
        $user->tokens()->where('name', request('device_name'))->delete();

        return [
            'token' => $user->createToken(request('device_name'))->plainTextToken,
        ];
    }

    /**
     * @return mixed
     */
    public function logout(): mixed
    {
        /** @var User $user */
        $user = request()->user();

        return $user->currentAccessToken()->delete();
    }

    /**
     * @return int
     */
    public function logoutFromDevice(): int
    {
        request()->validate([
            'id' => 'required',
        ]);

        /** @var User $user */
        $user = request()->user();

        return $user->tokens()->where([
            'id' => request('id'),
        ])->delete();
    }

    /**
     * @return int
     */
    public function logoutFromAll(): int
    {
        /** @var User $user */
        $user = request()->user();

        return $user->tokens()->delete();
    }

    public function deleteAccount(): void
    {
        /** @var User $user */
        $user = request()->user()->load('chats');

        $user->tokens()->delete();

        foreach ($user->chats as $chat) {
            /** @var Chat $chat */
            $chat->users()->detach($user->id);
            $chat->save();

            event(new ChatUpdated($chat->fresh()->load('users')));
        }

        $user->delete();
    }

    /**
     * @return array
     */
    public function tokens(): array
    {
        /** @var User $user */
        $user = request()->user();

        return $user->tokens()->where('expires_at',
            null)->orWhereDate('expires_at', '<', Carbon::now())->get()
            ->toArray();
    }
}
