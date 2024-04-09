<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientAuthTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_register(): void
    {
        $response = $this->postJson('/api/registration', [
            'name' => 'Sally',
            'email' => 'sally2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'device_name' => 'iPhone15',
        ]);

        $response->assertSuccessful();

        $this->assertNotEmpty($response->getContent());
        $this->assertDatabaseHas('users', ['email' => 'sally2@example.com']);
        $this->assertDatabaseHas('personal_access_tokens',
            ['name' => 'iPhone15']);

        $response->assertJsonStructure([
            'token',
            //'user' => [
            //    'name',
            //    'email',
            //    'id',
            //],
        ]);
    }

    public function test_register_password_error()
    {
        $response = $this->postJson('/api/registration', [
            'name' => 'Sally',
            'email' => 'sally2@example.com',
            'password' => 'password',
            'password_confirmation' => 'wrong',
            'device_name' => 'iPhone15',
        ]);

        $response->assertStatus(422);

        $this->assertNotEmpty($response->getContent());

        $response->assertJsonFragment([
            'message' => 'The password field confirmation does not match.',
        ]);
    }

    public function test_register_duplicate_error()
    {
        $user = User::create([
            'name' => 'Sally',
            'email' => 'sally2@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/registration', [
            'name' => 'Sally',
            'email' => 'sally2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'device_name' => 'iPhone15',
        ]);

        $response->assertStatus(422);

        $this->assertNotEmpty($response->getContent());

        $response->assertJsonFragment([
            'message' => 'The email has already been taken.',
        ]);
    }

    public function test_login(): void
    {
        $user = User::create([
            'name' => 'Sally',
            'email' => 'sally2@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/sanctum/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertSuccessful();

        $this->assertStringStartsWith('1|',
            json_decode($response->getContent())->token);
    }

    public function test_user_tokens_replaced_for_same_device()
    {
        $user = User::create([
            'name' => 'Sally',
            'email' => 'sally2@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/sanctum/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertSuccessful();

        $response = $this->postJson('/api/sanctum/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertSuccessful();

        $tokensCount = $user->tokens()->count();

        $this->assertEquals(1, $tokensCount);
    }

    public function test_failed_login(): void
    {
        $response = $this->postJson('/api/sanctum/token', [
            'email' => 'sally@example.com',
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(422);

        $response->assertJsonFragment([
            'message' => 'The provided credentials are incorrect.',
        ]);
    }

    public function test_logout(): void
    {
        $user = User::create([
            'name' => 'Sally',
            'email' => 'sally2@example.com',
            'password' => bcrypt('password'),
        ]);

        $token = $user->createToken('iPhone15')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_from_device(): void
    {
        $user = User::create([
            'name' => 'Sally',
            'email' => 'sally2@example.com',
            'password' => bcrypt('password'),
        ]);

        $token1 = $user->createToken('iPhone15')->plainTextToken;

        $token2id = $user->createToken('iPad')->accessToken->toArray()['id'];

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $response = $this->postJson('/api/logout-device', [
            'id' => $token2id,
        ], [
            'Authorization' => 'Bearer '.$token1,
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_logout_from_all(): void
    {
        $user = User::create([
            'name' => 'Sally',
            'email' => 'sally2@example.com',
            'password' => bcrypt('password'),
        ]);

        $token1 = $user->createToken('iPhone15')->plainTextToken;
        $token2 = $user->createToken('iPad')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $response = $this->postJson('/api/logout-all', [], [
            'Authorization' => 'Bearer '.$token1,
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_update_profile()
    {
        $user = User::find(1);

        $response = $this->actingAs($user)->patchJson('/api/update-profile', [
            'name' => 'Updated Name',
            'email' => 'updated-email@example.com',
        ]);

        $response->assertSuccessful();
    }

    public function test_update_password()
    {
        $user = User::find(1);

        $response = $this->actingAs($user)->patchJson('/api/update-password', [
            'current_password' => 'password',
            'new_password' => 'updated-password',
            'new_password_confirmation' => 'updated-password'
        ]);

        $response->assertSuccessful();
    }

    public function test_update_password_validation()
    {
        $user = User::find(1);

        $response = $this->actingAs($user)->patchJson('/api/update-password', [
            'current_password' => 'wrong-password',
            'new_password' => 'updated-password',
            'new_password_confirmation' => 'updated-password'
        ]);

        $response->assertStatus(422);

        $response->assertJsonFragment([
            'message' => 'The password is incorrect.',
        ]);
    }

    public function test_update_password_mismatch()
    {
        $user = User::find(1);

        $response = $this->actingAs($user)->patchJson('/api/update-password', [
            'current_password' => 'password',
            'new_password' => 'updated-password',
            'new_password_confirmation' => 'wrong-password'
        ]);

        $response->assertStatus(422);

        $response->assertJsonFragment([
            'message' => 'The new password field confirmation does not match.',
        ]);
    }

    public function test_delete_account()
    {
        $user = User::find(1);

        $token = $user->createToken('iPhone15')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $response = $this->postJson('/api/delete-account', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->assertDatabaseCount('users', 4);
    }

    public function test_get_registered_devices()
    {
        $user = User::create([
            'name' => 'Sally',
            'email' => 'sally2@example.com',
            'password' => bcrypt('password'),
        ]);

        $token1 = $user->createToken('iPhone15')->plainTextToken;
        $token2 = $user->createToken('iPad')->plainTextToken;

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/registered-devices');
        $response->assertSuccessful();

        $response->assertJsonCount(2);

        $response->decodeResponseJson()->assertPath('1.name', 'iPad');
        $response->decodeResponseJson()->assertPath('0.name', 'iPhone15');
    }

    public function test_get_user(): void
    {
        $user = User::create([
            'name' => 'Sally',
            'email' => 'sally2@example.com',
            'password' => bcrypt('password'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->get('/api/user');

        $this->assertEquals('sally2@example.com',
            json_decode($response->getContent())->email);
    }
}
