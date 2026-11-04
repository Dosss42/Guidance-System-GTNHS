<?php

namespace App\Console\Commands;

use App\Enums\AuditAction;
use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

#[Signature('guidance:create-admin {--name= : Full name} {--email= : School email address}')]
#[Description('Create a System Administrator account (for a new installation). The password is asked for, never passed as an option.')]
class CreateAdministratorCommand extends Command
{
    public function handle(AuditLogger $audit): int
    {
        $name = $this->option('name') ?: $this->ask('Full name');
        $email = strtolower(trim((string) ($this->option('email') ?: $this->ask('School email address'))));
        $password = (string) $this->secret('Password (at least 10 characters, upper and lower case letters and a number)');
        $confirmation = (string) $this->secret('Type the password again');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
                'password' => ['required', 'confirmed', Password::defaults()],
            ],
        );

        if ($validator->fails()) {
            $this->components->error('The account was not created:');
            $this->components->bulletList($validator->errors()->all());

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role_id' => Role::forSlug(RoleSlug::Administrator)->id,
            'is_active' => true,
        ]);

        Role::forSlug(RoleSlug::Counselor);

        $audit->record(AuditAction::AccountCreated, 'accounts', $user, 'Role: System Administrator · created from the command line');

        $this->components->info("Administrator account created for {$user->email}. Sign in and create the counselor accounts from Accounts.");

        return self::SUCCESS;
    }
}
