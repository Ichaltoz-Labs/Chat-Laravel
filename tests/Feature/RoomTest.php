<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_room_generates_code_and_sets_expiry(): void
    {
        $response = $this->post('/rooms', ['name' => 'Rafif']);

        $room = Room::firstOrFail();

        $response->assertRedirect(route('room.show', $room->code));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $room->code);
        $this->assertTrue(
            $room->expired_at->between(now()->addHours(23), now()->addHours(25)),
        );

        $this->assertDatabaseHas('users', [
            'room_id' => $room->id,
            'name' => 'Rafif',
        ]);
        $this->assertTrue(session()->has("room_user_{$room->id}"));
    }

    public function test_create_room_requires_name(): void
    {
        $this->post('/rooms', ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_room_code_is_unique(): void
    {
        Room::factory()->create(['code' => 'ABCDEF12']);

        $this->expectException(QueryException::class);

        Room::factory()->create(['code' => 'ABCDEF12']);
    }

    public function test_join_room_creates_participant_and_sets_session(): void
    {
        $room = Room::factory()->create();

        $this->post(route('room.join', $room->code), ['name' => 'Budi'])
            ->assertRedirect(route('room.show', $room->code));

        $this->assertDatabaseHas('users', [
            'room_id' => $room->id,
            'name' => 'Budi',
        ]);
        $this->assertTrue(session()->has("room_user_{$room->id}"));
        $this->assertDatabaseHas('messages', [
            'room_id' => $room->id,
            'is_system' => true,
            'message' => 'Budi joined the room',
        ]);
    }

    public function test_join_room_prevents_duplicate_names_with_suffix(): void
    {
        $room = Room::factory()->create();

        $this->post(route('room.join', $room->code), ['name' => 'Rafif']);
        $this->post(route('room.join', $room->code), ['name' => 'Rafif']);
        $this->post(route('room.join', $room->code), ['name' => 'Rafif']);

        $this->assertDatabaseHas('users', ['room_id' => $room->id, 'name' => 'Rafif']);
        $this->assertDatabaseHas('users', ['room_id' => $room->id, 'name' => 'Rafif (2)']);
        $this->assertDatabaseHas('users', ['room_id' => $room->id, 'name' => 'Rafif (3)']);
    }

    public function test_expired_room_returns_404(): void
    {
        $room = Room::factory()->create(['expired_at' => now()->subMinute()]);

        $this->get(route('room.show', $room->code))->assertNotFound();
        $this->postJson(route('room.messages.index', $room->code))->assertNotFound();
    }

    public function test_room_show_renders_join_form_when_not_joined(): void
    {
        $room = Room::factory()->create();

        $this->get(route('room.show', $room->code))
            ->assertOk()
            ->assertSee($room->code)
            ->assertSee('Join Room');
    }

    public function test_room_show_renders_chat_when_joined(): void
    {
        $room = Room::factory()->create();
        $user = User::factory()->for($room)->create(['name' => 'Rafif']);

        $this->withSession(["room_user_{$room->id}" => $user->id])
            ->get(route('room.show', $room->code))
            ->assertOk()
            ->assertSee('kamu:')
            ->assertSee('Copy');
    }
}
