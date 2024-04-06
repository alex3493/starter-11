<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $usersGroup1 = User::factory(2)->create();
        $usersGroup2 = User::factory(3)->create();

        $chatsGroup1 = Chat::factory(10)->create();
        $chatsGroup2 = Chat::factory(10)->create();

        $chatsGroup1->each(function ($chat) use ($usersGroup1) {
            $chat->users()->saveMany($usersGroup1);
        });

        $chatsGroup2->each(function ($chat) use ($usersGroup2) {
            $chat->users()->saveMany($usersGroup2);
        });

        foreach([$chatsGroup1, $chatsGroup2] as $group) {
            foreach ($group as $chat) {
                /** @var \App\Models\Chat $chat */
                $users = $chat->users;
                $chat_id = $chat->id;

                foreach ($users as $user) {
                    /** @var \App\Models\User $user */
                    ChatMessage::factory(25)->create([
                        'user_id' => $user->id,
                        'chat_id' => $chat_id,
                    ]);
                }
            }
        }
    }
}
