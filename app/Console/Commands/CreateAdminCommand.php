<?php

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create {--name=} {--email=} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create (or promote) a production Admin user without seeding a known default password';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('نام مدیر (Admin name)');
        $email = $this->option('email') ?: $this->ask('ایمیل (Admin email)');
        $password = $this->option('password') ?: $this->secret('رمز عبور (Admin password)');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_active' => true,
            ]
        );

        $user->syncRoles([RoleName::Admin->value]);

        $this->info("Admin user ready: {$user->email}");

        return self::SUCCESS;
    }
}
