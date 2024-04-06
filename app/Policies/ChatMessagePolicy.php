<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChatMessagePolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ChatMessage $chatMessage): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $chatMessage->chat->users->contains('id', $user->id)
            && $chatMessage->user->id == $user->id;
    }

    /**
     * @param  User  $user
     * @param  ChatMessage  $chatMessage
     * @return bool
     */
    public function delete(User $user, ChatMessage $chatMessage): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->id == $chatMessage->user->id;
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->email === 'admin@starter.loc';
    }
}
