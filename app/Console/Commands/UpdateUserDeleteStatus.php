<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class UpdateUserDeleteStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update-delete-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing users to have delete_status set to active';

    /**
     * Execute the console command.
     *
     * @return int
     */
        public function handle()
    {
        $users = User::whereNull('delete_status')
                    ->orWhere('delete_status', '')
                    ->orWhere('delete_status', 'deleted')
                    ->get();

        if ($users->count() === 0) {
            $this->info('No users found that need to be updated.');
            return 0;
        }

        $this->info("Found {$users->count()} users to update.");

        foreach ($users as $user) {
            $user->update(['delete_status' => 'active']);
            $this->line("Updated user: {$user->email} from '{$user->getOriginal('delete_status')}' to 'active'");
        }

        $this->info('All users have been updated successfully!');
        return 0;
    }
}
