<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeAdmin extends Command
{
    protected $signature = 'platform:make-admin {email} {--name=Admin}';
    protected $description = 'Create a super admin (or promote an existing user). Use this to create the first admin.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();
        $password = null;

        if (! $user) {
            $password = Str::password(14, symbols: false);
            $user = User::create(['name' => $this->option('name'), 'email' => $email, 'password' => $password]);
        }
        $user->setPlatformRole('admin');

        $this->info("{$user->email} is a super admin.");
        if ($password) {
            $this->line("Temporary password (shown once): $password");
        }

        return self::SUCCESS;
    }
}
