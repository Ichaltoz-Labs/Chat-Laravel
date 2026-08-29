<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function __construct(private readonly RoomService $rooms)
    {
        //
    }

    /**
     * Homepage: input nama + tombol Create Room.
     */
    public function index(Request $request): View
    {
        return view('home', [
            'name' => $request->session()->get('chat_name', ''),
        ]);
    }

    /**
     * Buat room baru lalu redirect ke halaman chat room.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:30'],
        ]);

        [$room] = $this->rooms->createRoom($request, $validated['name']);

        return redirect()->route('room.show', $room->code);
    }

    /**
     * Halaman chat room (atau form join bila pengunjung belum daftar).
     */
    public function show(Request $request, Room $room): View
    {
        $user = $this->rooms->currentUser($request, $room);

        return view('room', [
            'room' => $room,
            'user' => $user,
        ]);
    }

    /**
     * Join room: simpan nama ke session (dedup nama), lalu kembali ke chat.
     */
    public function join(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:30'],
        ]);

        $this->rooms->join($request, $room, $validated['name']);

        return redirect()->route('room.show', $room->code);
    }
}
