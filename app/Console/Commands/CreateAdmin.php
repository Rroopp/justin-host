<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating a new admin user...');

        $name = $this->ask('Full Name');
        $username = $this->ask('Username');
        $email = $this->ask('Email (optional)');
        $password = $this->secret('Password');
        $confirmPassword = $this->secret('Confirm Password');

        if ($password !== $confirmPassword) {
            $this->error('Passwords do not match!');
            return 1;
        }

        if (\App\Models\Staff::where('username', $username)->exists()) {
             $this->error('Username already exists!');
             return 1;
        }

        $admin = \App\Models\Staff::create([
            'full_name' => $name,
            'username' => $username,
            'email' => $email,
            'password_hash' => \Illuminate\Support\Facades\Hash::make($password),
            'role' => 'admin',
            'status' => 'active',
            'permissions' => ['*'], // Grant all permissions
            'designation' => 'System Administrator',
        ]);

        $this->info("Admin user '{$admin->username}' created successfully!");
        return 0;
    }
}
