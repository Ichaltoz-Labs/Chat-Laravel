<?php

namespace App\Providers;

use App\Models\Room;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Maks 1 pesan per detik per user dalam room — PRD.
        RateLimiter::for('chat', function (Request $request) {
            $room = $request->route('room');

            if (is_string($room)) {
                $room = Room::where('code', $room)->first();
            }

            // Key berbasis session user dalam room, bukan session id
            // (session id bisa berganti antar request / browser baru).
            $key = $request->session()->getId();

            if ($room instanceof Room) {
                $key = $request->session()->get("room_user_{$room->id}", "room_{$room->id}");
            }

            return Limit::perSecond(1)->by($key);
        });
    }
}
