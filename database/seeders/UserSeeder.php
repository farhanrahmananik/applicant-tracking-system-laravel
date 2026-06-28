<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@ats.test',
                'role' => 'super_admin',
            ],
            [
                'name' => 'HR Manager',
                'email' => 'hr@ats.test',
                'role' => 'hr_manager',
            ],
            [
                'name' => 'Recruiter',
                'email' => 'recruiter@ats.test',
                'role' => 'recruiter',
            ],
            [
                'name' => 'Interviewer',
                'email' => 'interviewer@ats.test',
                'role' => 'interviewer',
            ],
            [
                'name' => 'Candidate',
                'email' => 'candidate@ats.test',
                'role' => 'candidate',
            ],
        ];

        DB::transaction(function () use ($users): void {
            foreach ($users as $definition) {
                $role = Role::query()->where('slug', $definition['role'])->first();

                if ($role === null) {
                    throw new RuntimeException("Role [{$definition['role']}] must be seeded before demo users.");
                }

                $user = User::query()->updateOrCreate(
                    ['email' => $definition['email']],
                    [
                        'name' => $definition['name'],
                        'email_verified_at' => now(),
                        'password' => Hash::make('password'),
                    ],
                );

                $user->forceFill(['is_active' => true])->save();
                $user->roles()->sync([$role->getKey()]);
            }
        });
    }
}
