<?php

namespace App\Actions\Fortify;

use App\Domain\Categories\Actions\SeedDefaultCategories;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]);

            $workspace = Workspace::create([
                'name' => 'Keuangan Pribadi',
                'owner_user_id' => $user->id,
                'currency' => 'IDR',
            ]);

            $user->workspaces()->attach($workspace->id, ['role' => 'owner']);

            session(['current_workspace_id' => $workspace->id]);

            SeedDefaultCategories::run($workspace);

            return $user;
        });
    }
}