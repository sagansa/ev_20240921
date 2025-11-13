<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test user for authentication testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = new User();
        $user->name = 'Test User';
        $user->email = 'test@example.com';
        $user->password = Hash::make('password');
        $user->email_verified_at = now();
        $user->save();
        
        $user->assignRole('user');
        
        $this->info('Test user created successfully!');
        $this->line('Email: test@example.com');
        $this->line('Password: password');
        
        return 0;
    }
}