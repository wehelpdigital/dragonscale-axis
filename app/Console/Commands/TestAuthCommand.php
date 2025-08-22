<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class TestAuthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:auth {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test authentication for a user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $this->info("Testing authentication for: {$email}");

                // Find the user - only look for active users
        $user = User::where('email', $email)
                   ->where(function($query) {
                       $query->where('delete_status', 'active')
                             ->orWhereNull('delete_status');
                   })
                   ->first();

        if (!$user) {
            $this->error("No active user found with email: {$email}");
            return 1;
        }

        $this->info("User found: {$user->name} (ID: {$user->id})");
        $this->info("Delete status: " . ($user->delete_status ?? 'null'));

        // Check password
        $passwordCheck = Hash::check($password, $user->password);
        $this->info("Password check: " . ($passwordCheck ? 'PASS' : 'FAIL'));

        // Check if user can login based on delete_status
        $canLogin = $user->delete_status === 'active' || $user->delete_status === null;
        $this->info("Can login (status check): " . ($canLogin ? 'YES' : 'NO'));

        // Test authentication with our custom logic
        $canLogin = $passwordCheck && ($user->delete_status === 'active' || $user->delete_status === null);
        $this->info("Can login (custom logic): " . ($canLogin ? 'YES' : 'NO'));

        if ($canLogin) {
            $this->info("Authentication would be successful!");
        } else {
            $this->error("Authentication would fail!");
            if (!$passwordCheck) {
                $this->error("Reason: Password is incorrect");
            } else {
                $this->error("Reason: User is deleted (delete_status: {$user->delete_status})");
            }
        }

        return 0;
    }
}
