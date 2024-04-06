<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * @param  Request  $request
     * @return void
     */
    public function updateProfile(Request $request): void
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($request->user()->id),
            ],
        ]);

        /** @var User $user */
        $user = $request->user();

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }

    /**
     * @param  Request  $request
     * @return void
     */
    public function updatePassword(Request $request): void
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', Password::default()],
        ]);

        /** @var User $user */
        $user = $request->user();

        $user->fill([
            'password' => Hash::make($validated['new_password']),
        ]);

        $user->save();
    }
}
