<?php

namespace App\Http\Middleware;

use App\Models\Room;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoomExpired
{
    /**
     * Resolve room dari param route (bisa string code atau model Room),
     * lalu abort 404 jika room tidak ada / sudah expired.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $room = $request->route('room');

        if (is_string($room)) {
            $room = Room::where('code', $room)->first();

            if (! $room) {
                abort(404, 'Room not found.');
            }

            $request->route()->setParameter('room', $room);
        }

        if ($room instanceof Room && $room->isExpired()) {
            abort(404, 'Room not found or expired.');
        }

        return $next($request);
    }
}
