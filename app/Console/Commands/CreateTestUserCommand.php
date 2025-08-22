<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateTestUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:test-user {email} {password} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test user with specified credentials';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        $name = $this->argument('name');

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $this->error("User with email {$email} already exists!");
            return 1;
        }

        // Create the user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'dob' => '2000-01-01',
            'avatar' => 'images/avatar-1.jpg',
            'email_verified_at' => now(),
            'delete_status' => 'active',
        ]);

        $this->info("Test user created successfully!");
        $this->info("Email: {$email}");
        $this->info("Password: {$password}");
        $this->info("Name: {$name}");
        $this->info("Delete Status: {$user->delete_status}");

        return 0;
    }
}
