<?php

namespace App\Policies;

use App\Models\Chat;
use App\Models\User;

class ChatPolicy
{
    /**
     * @param  User  $user
     * @param  Chat  $chat
     * @return bool
     */
    public function listMessages(User $user, Chat $chat): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $chat->users->contains('id', $user->id);
    }

    /**
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * @param  User  $user
     * @param  Chat  $chat
     * @return bool
     */
    public function update(User $user, Chat $chat): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $users = $chat->users;

        // Only current first user (owner) can update chat.
        return count($users) && $user->id == $users[0]->id;
    }

    /**
     * @param  User  $user
     * @param  Chat  $chat
     * @return bool
     */
    public function join(User $user, Chat $chat): bool
    {
        return ! $chat->users->contains('id', $user->id);
    }

    /**
     * @param  User  $user
     * @param  Chat  $chat
     * @return bool
     */
    public function leave(User $user, Chat $chat): bool
    {
        return $chat->users->contains('id', $user->id);
    }

    /**
     * @param  User  $user
     * @param  Chat  $chat
     * @return bool
     */
    public function addMessage(User $user, Chat $chat): bool
    {
        // Super admin can add messages to any chat.
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $chat->users->contains('id', $user->id);
    }

    /**
     * @param  User  $user
     * @param  Chat|null $chat
     * @return bool
     */
    public function delete(User $user, ?Chat $chat = null): bool
    {
        // Super admin can delete any chat.
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $users = $chat->users;
        return count($users) === 1 && $users[0]->id === $user->id;
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->email === 'admin@starter.loc';
    }
}
