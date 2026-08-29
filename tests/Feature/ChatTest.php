<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->room = Room::factory()->create();
        $this->user = User::factory()->for($this->room)->create(['name' => 'Rafif']);
        $this->withSession(["room_user_{$this->room->id}" => $this->user->id]);
    }

    protected function sendMessage(array $payload): TestResponse
    {
        return $this->postJson(route('room.messages.store', $this->room->code), $payload);
    }

    public function test_send_message_creates_message_and_updates_last_seen(): void
    {
        $response = $this->sendMessage(['message' => 'Halo semua']);

        $response->assertCreated()->assertJsonPath('message.user_name', 'Rafif');

        $this->assertDatabaseHas('messages', [
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'is_system' => false,
            'message' => 'Halo semua',
        ]);
        $this->assertTrue($this->user->refresh()->last_seen->gt(now()->subMinute()));
    }

    public function test_send_message_requires_valid_length(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $this->sendMessage(['message' => ''])->assertStatus(422);
        $this->sendMessage(['message' => str_repeat('a', 501)])->assertStatus(422);
        $this->sendMessage([])->assertStatus(422);
    }

    public function test_send_message_requires_joined_user(): void
    {
        $room = Room::factory()->create();

        $this->postJson(route('room.messages.store', $room->code), ['message' => 'Halo'])
            ->assertStatus(403);
    }

    public function test_message_is_sanitized_from_html(): void
    {
        $this->sendMessage(['message' => '<script>alert("xss")</script>Halo'])->assertCreated();

        $stored = Message::firstOrFail();
        $this->assertStringNotContainsString('<script', $stored->message);
        $this->assertStringContainsString('Halo', $stored->message);
    }

    public function test_name_is_sanitized_when_joining(): void
    {
        $room = Room::factory()->create();

        $this->post(route('room.join', $room->code), ['name' => '<b>Budi</b>']);

        $this->assertDatabaseHas('users', [
            'room_id' => $room->id,
            'name' => 'Budi',
        ]);
    }

    public function test_send_message_is_rate_limited(): void
    {
        $this->sendMessage(['message' => 'pesan satu'])->assertCreated();
        $this->sendMessage(['message' => 'pesan dua'])->assertStatus(429);
    }

    public function test_messages_index_is_incremental(): void
    {
        $first = Message::factory()->create(['room_id' => $this->room->id, 'user_id' => $this->user->id]);
        $second = Message::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'message' => 'pesan kedua',
        ]);

        $this->getJson(route('room.messages.index', $this->room->code))
            ->assertOk()
            ->assertJsonCount(2, 'messages');

        $this->getJson(route('room.messages.index', [$this->room->code, 'after' => $first->id]))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.message', 'pesan kedua');
    }

    public function test_typing_updates_typing_at(): void
    {
        $this->postJson(route('room.typing', $this->room->code))->assertOk();

        $this->assertTrue($this->user->refresh()->typing_at->gt(now()->subMinute()));
    }

    public function test_typing_status_is_read_only(): void
    {
        $other = User::factory()->for($this->room)->create(['name' => 'Budi']);
        $other->update(['typing_at' => now()]);

        $before = $other->refresh()->typing_at;

        $this->getJson(route('room.typing.status', $this->room->code))
            ->assertOk()
            ->assertJsonPath('typing.0', 'Budi');

        $this->assertTrue(
            $other->refresh()->typing_at->equalTo($before),
            'GET typing/status tidak boleh mengubah typing_at.',
        );
    }

    public function test_presence_returns_only_recent_users(): void
    {
        $online = User::factory()->for($this->room)->create([
            'name' => 'Budi',
            'last_seen' => now()->subSeconds(10),
        ]);

        $offline = User::factory()->for($this->room)->create([
            'name' => 'Citra',
            'last_seen' => now()->subMinutes(5),
        ]);

        $this->getJson(route('room.presence', $this->room->code))
            ->assertOk()
            ->assertJsonPath('online_count', 2)
            ->assertJsonPath('users', ['Budi', 'Rafif'])
            ->assertJsonMissing(['users' => [$offline->name]]);
    }

    public function test_leave_records_system_message_and_clears_session(): void
    {
        $this->postJson(route('room.leave', $this->room->code))->assertOk();

        $this->assertDatabaseHas('messages', [
            'room_id' => $this->room->id,
            'is_system' => true,
            'message' => 'Rafif left the room',
        ]);
        $this->assertNull(session("room_user_{$this->room->id}"));

        $this->postJson(route('room.messages.store', $this->room->code), ['message' => 'halo'])
            ->assertStatus(403);
    }
}
