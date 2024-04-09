<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatClientTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_create_chat(): void
    {
        Sanctum::actingAs(User::first());

        $response = $this->postJson('/api/chats', [
            'topic' => 'Test topic',
        ]);

        $response->assertSuccessful();

        $response->assertJsonStructure([
            'topic',
            'created_at',
            'users',
        ]);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function test_chat_list(): void
    {
        Sanctum::actingAs(User::first());

        $response = $this->getJson('/api/chats?afterId=11&perPage=10');
        $response->assertSuccessful();

        $allChatsPaginated = json_decode($response->getContent(), true);
        $this->assertEquals(10, $allChatsPaginated['items'][0]['id']);
        $this->assertEquals(20, $allChatsPaginated['total']);

        $response = $this->getJson('/api/chats?user_id=3&afterId=15&perPage=10');
        $response->assertSuccessful();

        $filteredChatsPaginated = json_decode($response->getContent(), true);
        $this->assertEquals(14, $filteredChatsPaginated['items'][0]['id']);
        $this->assertEquals(10, $filteredChatsPaginated['total']);
    }

    public function test_join_chat(): void
    {
        Sanctum::actingAs(User::find(3));

        $response = $this->putJson('/api/chat/1/join');
        $response->assertSuccessful();

        $response->assertJsonStructure([
            'topic',
            'created_at',
            'users',
        ]);

        $chat = Chat::find(1)->load('users');

        $this->assertCount(3, $chat->users);
    }

    public function test_join_chat_error(): void
    {
        Sanctum::actingAs(User::find(1));

        $response = $this->putJson('/api/chat/1/join');
        $response->assertStatus(403);

        $error = json_decode($response->getContent(), true);
        $this->assertEquals('This action is unauthorized.', $error['message']);
    }

    public function test_message_list()
    {
        Sanctum::actingAs(User::find(1));

        $response = $this->getJson('/api/chat/1/messages');
        $response->assertSuccessful();

        $messagesPaginated = json_decode($response->getContent(), true);

        $this->assertEquals(50, $messagesPaginated['total']);
        $this->assertCount(15, $messagesPaginated['items']);

        $this->assertEquals(50, $messagesPaginated['items'][14]['id']);

        $response = $this->getJson('/api/chat/1/messages?afterId=36&perPage=15');
        $response->assertSuccessful();

        $nextMessagesPage = json_decode($response->getContent(), true);

        $this->assertEquals(50, $nextMessagesPage['total']);
        $this->assertCount(15, $nextMessagesPage['items']);

        $this->assertEquals(35, $nextMessagesPage['items'][14]['id']);
    }

    public function test_update_chat()
    {
        Sanctum::actingAs(User::find(1));

        $response = $this->patchJson('/api/chat/1', [
            'topic' => 'Updated topic',
        ]);

        // User ID 1 is the first user (owner) of the chat.
        // Check that hi is allowed to update chat.
        $response->assertSuccessful();

        $response->assertJsonFragment([
            'topic' => 'Updated topic',
        ]);
    }

    public function test_update_chat_unauthorized()
    {
        Sanctum::actingAs(User::find(2));

        $response = $this->patchJson('/api/chat/1', [
            'topic' => 'Updated topic',
        ]);

        // Although user ID 2 is a member of chat hi is not the first user (owner).
        // Check that we do not allow updates in this case.
        $response->assertStatus(403);

        Sanctum::actingAs(User::find(3));

        $response = $this->patchJson('/api/chat/1', [
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

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/chat/1', [
            'topic' => 'Updated topic',
        ]);

        $response->assertSuccessful();

        $response->assertJsonFragment([
            'topic' => 'Updated topic',
        ]);
    }

    public function test_update_chat_validation_error()
    {
        Sanctum::actingAs(User::find(1));

        $response = $this->patchJson('/api/chat/1', [
            'topic' => '',
        ]);

        $response->assertStatus(422);

        $result = json_decode($response->getContent(), true);
        $this->assertEquals('The topic field is required.', $result['message']);
        $this->assertEquals('The topic field is required.', $result['errors']['topic'][0]);
    }

    public function test_create_message()
    {
        Sanctum::actingAs(User::find(1));

        $response = $this->postJson('/api/chat/1/messages', [
            'message' => 'Test message',
        ]);

        $response->assertSuccessful();

        $response->assertJsonStructure([
            'message',
            'created_at',
            'user',
        ]);
    }

    /**
     * @return void
     */
    public function test_delete_message()
    {
        Sanctum::actingAs(User::find(1));

        $response = $this->deleteJson('/api/message/1');

        $response->assertSuccessful();
    }

    public function test_delete_message_error()
    {
        Sanctum::actingAs(User::find(3));

        $response = $this->deleteJson('/api/message/1');

        $response->assertStatus(403);

        $error = json_decode($response->getContent(), true);
        $this->assertEquals('This action is unauthorized.', $error['message']);
    }
}
