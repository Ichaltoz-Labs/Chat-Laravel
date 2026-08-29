<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanupRoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_deletes_expired_rooms_with_cascade(): void
    {
        $expired = Room::factory()->create(['expired_at' => now()->subMinute()]);
        $user = User::factory()->for($expired)->create();
        $message = Message::factory()->create(['room_id' => $expired->id, 'user_id' => $user->id]);

        $active = Room::factory()->create(['expired_at' => now()->addHours(24)]);
        $activeUser = User::factory()->for($active)->create();

        $this->artisan('rooms:cleanup')->assertSuccessful();

        $this->assertDatabaseMissing('rooms', ['id' => $expired->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('messages', ['id' => $message->id]);

        $this->assertDatabaseHas('rooms', ['id' => $active->id]);
        $this->assertDatabaseHas('users', ['id' => $activeUser->id]);
    }

    public function test_cleanup_keeps_not_yet_expired_rooms(): void
    {
        Room::factory()->create(['expired_at' => now()->addHour()]);

        $this->artisan('rooms:cleanup')->assertSuccessful();

        $this->assertDatabaseCount('rooms', 1);
    }
}
