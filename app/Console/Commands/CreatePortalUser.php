<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('portal:create-user {name : The name of the user} {email : The email address to log in with} {--herd= : Limit the user to the animals of this herd id}')]
#[Description('Create a portal user; registration is closed, so this is how customers get an account')]
class CreatePortalUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $validator = Validator::make([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'herd' => $this->option('herd'),
            'password' => $this->secret('Password'),
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'herd' => ['nullable', 'integer', 'min:1'],
            'password' => ['required', 'string', Password::default()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        /** @var array{name: string, email: string, herd: ?string, password: string} $data */
        $data = $validator->validated();

        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $user->herd_id = $data['herd'] === null ? null : (int) $data['herd'];
        $user->markEmailAsVerified();

        $this->info("User {$user->email} created.");

        return self::SUCCESS;
    }
}
