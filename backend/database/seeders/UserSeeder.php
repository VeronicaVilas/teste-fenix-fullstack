<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public const TEACHER_EMAIL = 'teacher@fenix.local';

    public const STUDENT_EMAIL = 'student@fenix.local';

    public const DEVELOPMENT_PASSWORD = 'password';

    public const ADDITIONAL_STUDENTS = [
        'ana.souza@fenix.local' => 'Ana Souza',
        'bruno.lima@fenix.local' => 'Bruno Lima',
        'carla.mendes@fenix.local' => 'Carla Mendes',
        'diego.rocha@fenix.local' => 'Diego Rocha',
        'elisa.martins@fenix.local' => 'Elisa Martins',
    ];

    /**
     * Seed the teacher and student accesses used by the frontend.
     *
     * Idempotent: users are matched by email and existing records are never modified.
     */
    public function run(): void
    {
        $this->seedUser(self::TEACHER_EMAIL, 'Professor Fênix', UserRole::Teacher);
        $this->seedUser(self::STUDENT_EMAIL, 'Aluno Fênix', UserRole::Student);

        foreach (self::ADDITIONAL_STUDENTS as $email => $name) {
            $this->seedUser($email, $name, UserRole::Student);
        }
    }

    private function seedUser(string $email, string $name, UserRole $role): void
    {
        User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'role' => $role, 'password' => self::DEVELOPMENT_PASSWORD],
        );
    }
}
