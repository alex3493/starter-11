<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatAdminTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_chat_list()
    {
        $user = User::find(1);

        // Homa page - chat list.
        $response = $this->actingAs($user)->get('/');

        $response->assertSessionHasNoErrors()->assertInertia(function (
            $inertiaResponse
        ) {
            /** @var \Inertia\Testing\AssertableInertia $inertiaResponse */
            $data = $inertiaResponse->toArray();

            // Check that we have paginated results.
            $this->assertEquals(20, $data['props']['chats']['total']);
            $this->assertCount(10, $data['props']['chats']['data']);
        });
    }

    public function test_chat_list_filter_by_topic()
    {
        $user = User::with('chats')->find(1);
        $chat = $user->chats()->with('users')->first();

        $searchTopic = $chat->topic;
        $response = $this->actingAs($user)->get("/?search={$searchTopic}&perPage=100");

        $response->assertSessionHasNoErrors()->assertInertia(function (
            $inertiaResponse
        ) {
            /** @var \Inertia\Testing\AssertableInertia $inertiaResponse */
            $data = $inertiaResponse->toArray();

            // Check that we find unique chat by topic.
            $this->assertEquals(1, $data['props']['chats']['total']);
        });
    }

    public function test_chat_list_filter_by_user()
    {
        $user = User::with('chats')->find(1);
        $chat = $user->chats()->with('users')->first();

        // We know that seeded chats have two users assigned.

        // Filter by first user's email.
        $user = $chat->users[0];
        $searchUser = $user->email;

        $response = $this->actingAs($user)->get("/?search={$searchUser}");

        $response->assertSessionHasNoErrors()->assertInertia(function (
            $inertiaResponse
        ) {
            /** @var \Inertia\Testing\AssertableInertia $inertiaResponse */
            $data = $inertiaResponse->toArray();

            // Check that all chats assigned to current user are listed.
            $this->assertEquals(10, $data['props']['chats']['total']);
        });

        // Filter by second user's name.
        $user = $chat->users[1];
        $searchUser = $user->name;

        $response = $this->actingAs($user)->get("/?search={$searchUser}");

        $response->assertSessionHasNoErrors()->assertInertia(function (
            $inertiaResponse
        ) {
            /** @var \Inertia\Testing\AssertableInertia $inertiaResponse */
            $data = $inertiaResponse->toArray();

            // Check that all chats assigned to current user are listed.
            $this->assertEquals(10, $data['props']['chats']['total']);
        });
    }

    public function test_chat_list_filter_by_message()
    {
        $user = User::with('chats')->find(1);
        $chat = $user->chats()->with('chatMessages')->first();

        $searchMessage = $chat->chatMessages()->first()->message;

        $response = $this->actingAs($user)->get("/?search={$searchMessage}");

        $response->assertSessionHasNoErrors()->assertInertia(function (
            $inertiaResponse
        ) {
            /** @var \Inertia\Testing\AssertableInertia $inertiaResponse */
            $data = $inertiaResponse->toArray();

            // Check that we find unique chat by message (may fail if there are duplicate messages in threads).
            $this->assertEquals(1, $data['props']['chats']['total']);
        });
    }

    public function test_read_chat()
    {
        $user = User::with('chats')->find(1);

        $response = $this->actingAs($user)->get('/chat/1/read');

        $response->assertSuccessful();

        $response->assertJsonStructure([
            'id',
            'topic',
            'users' => [
                [
                    'id',
                    'name',
                    'email',
                    'created_at',
                ],
            ],
            'can' => ['update', 'delete', 'join', 'leave'],
        ]);
    }

    public function test_create_chat()
    {
        $user = User::with('chats')->find(1);

        $response = $this->actingAs($user)->post('/chats', [
            'topic' => 'Test topic',
        ]);

        $response->assertSuccessful()->assertSessionHasNoErrors();
    }

    public function test_create_chat_validation_error()
    {
        $user = User::with('chats')->find(1);

        $response = $this->actingAs($user)->post('/chats', [
            'topic' => '',
        ]);

        $response->assertSessionHasErrors('topic')->assertRedirect('/');;
    }

    public function test_update_chat()
    {
        $user = User::find(1);

        $response = $this->actingAs($user)->patch('/chat/1', [
            'topic' => 'Updated topic',
        ]);

        // User ID 1 is the first user (owner) of the chat.
        // Check that hi is allowed to update chat.
        $response->assertSuccessful()->assertSessionHasNoErrors();
    }

    public function test_update_chat_unauthorized()
    {
        $user = User::find(2);

        $response = $this->actingAs($user)->patch('/chat/1', [
            'topic' => 'Updated topic',
        ]);

        // Although user ID 2 is a member of chat hi is not the first user (owner).
        // Check that we do not allow updates in this case.
        $response->assertStatus(403);

        $user = User::find(3);

        $response = $this->actingAs($user)->patch('/chat/1', [
            'topic' => 'Updated topic',
        ]);

        // User ID 3 is not a member of the chat, we expect same unauthorized response.
        $response->assertStatus(403);
    }

    public function test_update_chat_super_admin()
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@starter.loc',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->patch('/chat/1', [
            'topic' => 'Updated topic',
        ]);

        $response->assertSuccessful()->assertSessionHasNoErrors();
    }

    public function test_delete_chat()
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@starter.loc',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->delete('/chat/1');

        $response->assertSuccessful()->assertSessionHasNoErrors();
    }

    public function test_delete_chat_unauthorized()
    {
        $user = User::create([
            'name' => 'User',
            'email' => 'user@starter.loc',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->delete('/chat/1');

        $response->assertForbidden();
    }

    public function test_create_chat_message_by_admin()
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@starter.loc',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->post('/chat/1/messages', [
            'message' => 'New message',
        ]);

        // Super-admin can add messages to any chat, even if he is not a member.
        $response->assertSuccessful();
    }

    public function test_create_chat_message_by_user()
    {
        $user = User::find(1);

        $response = $this->actingAs($user)->post('/chat/1/messages', [
            'message' => 'Test message',
        ]);

        $response->assertSuccessful()->assertSessionHasNoErrors();
    }

    public function test_create_chat_message_by_user_unauthorized()
    {
        $user = User::find(3);

        $response = $this->actingAs($user)->post('/chat/1/messages', [
            'message' => 'Test message',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_chat_message_by_admin()
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@starter.loc',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->patch('/chat/1/message/1', [
            'message' => 'Updated message',
        ]);

        // Super-admin can update any message in any chat.
        $response->assertSuccessful()->assertSessionHasNoErrors();

        $message = ChatMessage::find(1);
        $this->assertEquals('Updated message', $message->message);
    }

    public function test_update_chat_message_by_user()
    {
        $message = ChatMessage::find(1);

        $response = $this->actingAs($message->user)->patch('/chat/'.$message->chat->id.'/message/'.$message->id,
            [
                'message' => 'Updated message',
            ]);

        $response->assertSuccessful()->assertSessionHasNoErrors();

        $message = ChatMessage::find(1);
        $this->assertEquals('Updated message', $message->message);
    }

    public function test_update_chat_message_by_user_unauthorized()
    {
        $message = ChatMessage::find(1);

        $unauthorizedUser = User::find(3);

        $response = $this->actingAs($unauthorizedUser)->patch('/chat/'.$message->chat->id.'/message/'.$message->id,
            [
                'message' => 'Updated message',
            ]);

        $response->assertStatus(403);

        $users = $message->chat->users;
        $messageUser = $message->user;

        $unauthorizedUser = $users->filter(function ($user) use ($messageUser) {
            return $messageUser->id != $user->id;
        });

        $unauthorizedUser = $unauthorizedUser->first();

        $response = $this->actingAs($unauthorizedUser)->patch('/chat/'.$message->chat->id.'/message/'.$message->id,
            [
                'message' => 'Updated message',
            ]);

        $response->assertStatus(403);
    }

    public function test_delete_chat_message_by_admin()
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@starter.loc',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->delete('/chat/1/message/1');

        // Super-admin can delete any message in any chat.
        $response->assertSuccessful()->assertSessionHasNoErrors();
    }

    // TODO: add similar tests for users...
}
