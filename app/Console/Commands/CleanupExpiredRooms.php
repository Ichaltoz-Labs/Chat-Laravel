<?php

namespace App\Console\Commands;

use App\Models\Room;
use Illuminate\Console\Command;

class CleanupExpiredRooms extends Command
{
    /**
     * @var string
     */
    protected $signature = 'rooms:cleanup';

    /**
     * @var string
     */
    protected $description = 'Hapus room yang sudah expired beserta semua datanya (cascade delete).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = Room::where('expired_at', '<=', now())->delete();

        $this->info("Deleted {$count} expired room(s).");

        return Command::SUCCESS;
    }
}
