<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class RoomService
{
    /**
     * Bersihkan input teks bebas HTML + trim.
     */
    public function clean(?string $value): string
    {
        $value = Purifier::clean((string) $value);

        return trim($value);
    }

    /**
     * Generate kode room 8 karakter (huruf kapital + angka), collision resistant.
     */
    public function generateCode(): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = substr(str_shuffle(str_repeat($characters, 8)), 0, 8);
        } while (Room::where('code', $code)->exists());

        return $code;
    }

    /**
     * Buat room baru + peserta pertama, lalu simpan ke session.
     *
     * @return array{Room, User}
     */
    public function createRoom(Request $request, string $name): array
    {
        $name = $this->clean($name);

        $room = Room::create([
            'code' => $this->generateCode(),
            'expired_at' => now()->addHours(24),
        ]);

        $user = $this->join($request, $room, $name);

        return [$room, $user];
    }

    /**
     * Daftarkan peserta ke room (nama unik via suffix), simpan ke session.
     */
    public function join(Request $request, Room $room, string $name): User
    {
        $name = $this->uniqueName($room, $this->clean($name));

        $user = User::create([
            'room_id' => $room->id,
            'name' => $name,
            'last_seen' => now(),
        ]);

        $this->rememberUser($request, $room, $user);

        $this->recordSystemMessage($room, "{$name} joined the room");

        return $user;
    }

    /**
     * Peserta aktif untuk room ini dari session, atau null.
     */
    public function currentUser(Request $request, Room $room): ?User
    {
        $userId = $request->session()->get("room_user_{$room->id}");

        if ($userId === null) {
            return null;
        }

        return $room->users()->find($userId);
    }

    /**
     * User meninggalkan room: system message + status offline + hapus session.
     */
    public function leave(Request $request, Room $room, User $user): void
    {
        $this->recordSystemMessage($room, "{$user->name} left the room");

        $user->update([
            'last_seen' => now()->subMinutes(5),
            'typing_at' => null,
        ]);

        $request->session()->forget("room_user_{$room->id}");
    }

    /**
     * Cegah nama duplikat di room yang sama → tambah suffix "Rafif (2)".
     */
    public function uniqueName(Room $room, string $name): string
    {
        $name = Str::limit($name, 30, '');
        $taken = $room->users()->pluck('name')->map(fn ($n) => mb_strtolower($n))->all();

        if (! in_array(mb_strtolower($name), $taken, true)) {
            return $name;
        }

        $i = 2;
        while (in_array(mb_strtolower("{$name} ({$i})"), $taken, true)) {
            $i++;
        }

        return "{$name} ({$i})";
    }

    /**
     * Catat pesan sistem ke room.
     */
    public function recordSystemMessage(Room $room, string $text): Message
    {
        return Message::create([
            'room_id' => $room->id,
            'user_id' => null,
            'message' => $this->clean($text),
            'is_system' => true,
        ]);
    }

    /**
     * Simpan kunci session untuk peserta aktif room.
     */
    public function rememberUser(Request $request, Room $room, User $user): void
    {
        $request->session()->put("room_user_{$room->id}", $user->id);
        $request->session()->put('chat_name', $user->name);
    }
}
