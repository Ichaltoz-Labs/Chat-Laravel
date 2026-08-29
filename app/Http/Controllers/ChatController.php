<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private readonly RoomService $rooms)
    {
        //
    }

    /**
     * GET: daftar pesan (incremental via ?after=id) — polling 2 detik.
     */
    public function index(Request $request, Room $room): JsonResponse
    {
        $user = $this->currentUser($request, $room);

        if ($user) {
            $user->update(['last_seen' => now()]);
        }

        $query = $room->messages()
            ->with('user:id,name')
            ->orderBy('id');

        if ($after = (int) $request->query('after')) {
            $query->where('id', '>', $after);
        }

        $messages = $query->limit(100)->get()->map(function ($message) {
            return [
                'id' => $message->id,
                'message' => $message->message,
                'is_system' => $message->is_system,
                'user_name' => $message->is_system ? null : ($message->user?->name ?? null),
                'time' => $message->created_at->format('H:i'),
            ];
        });

        return response()->json([
            'messages' => $messages,
            'last_id' => $messages->last()['id'] ?? 0,
        ]);
    }

    /**
     * POST: kirim pesan (rate limited: 1 msg/detik/user).
     */
    public function store(Request $request, Room $room): JsonResponse
    {
        $user = $this->currentUser($request, $room);

        if (! $user) {
            return response()->json(['error' => 'Anda belum bergabung ke room ini.'], 403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $message = $room->messages()->create([
            'user_id' => $user->id,
            'message' => $this->rooms->clean($validated['message']),
            'is_system' => false,
        ]);

        $user->update(['last_seen' => now()]);

        return response()->json([
            'message' => [
                'id' => $message->id,
                'message' => $message->message,
                'is_system' => false,
                'user_name' => $user->name,
                'time' => $message->created_at->format('H:i'),
            ],
        ], 201);
    }

    /**
     * POST: tandai user sedang mengetik (typing_at = now).
     */
    public function typing(Request $request, Room $room): JsonResponse
    {
        $user = $this->currentUser($request, $room);

        if (! $user) {
            return response()->json(['error' => 'Anda belum bergabung ke room ini.'], 403);
        }

        $user->update([
            'typing_at' => now(),
            'last_seen' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * GET: siapa yang sedang mengetik (read-only, TIDAK update typing_at).
     */
    public function typingStatus(Request $request, Room $room): JsonResponse
    {
        $me = $this->currentUser($request, $room)?->id;

        $typing = $room->users()
            ->where('typing_at', '>=', now()->subSeconds(3))
            ->when($me, fn ($q) => $q->where('id', '!=', $me))
            ->pluck('name');

        return response()->json(['typing' => $typing]);
    }

    /**
     * GET: jumlah + daftar user online (last_seen >= 30 detik lalu).
     */
    public function presence(Request $request, Room $room): JsonResponse
    {
        $online = $room->users()
            ->where('last_seen', '>=', now()->subSeconds(30))
            ->orderBy('name')
            ->get(['name']);

        return response()->json([
            'online_count' => $online->count(),
            'users' => $online->pluck('name'),
        ]);
    }

    /**
     * POST: tinggalkan room (system message + status offline + clear session).
     */
    public function leave(Request $request, Room $room): JsonResponse
    {
        $user = $this->currentUser($request, $room);

        if ($user) {
            $this->rooms->leave($request, $room, $user);
        }

        return response()->json(['ok' => true]);
    }

    private function currentUser(Request $request, Room $room)
    {
        return $this->rooms->currentUser($request, $room);
    }
}
