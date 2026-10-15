<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;
use ValueError;

class UserTest extends TestCase
{
    public function test_role_enum_has_only_teacher_and_student(): void
    {
        $values = array_map(fn (UserRole $role) => $role->value, UserRole::cases());

        $this->assertEquals(['teacher', 'student'], $values);
    }

    public function test_teacher_is_identified(): void
    {
        $user = new User(['role' => UserRole::Teacher]);

        $this->assertTrue($user->isTeacher());
        $this->assertFalse($user->isStudent());
    }

    public function test_student_is_identified(): void
    {
        $user = new User(['role' => UserRole::Student]);

        $this->assertTrue($user->isStudent());
        $this->assertFalse($user->isTeacher());
    }

    public function test_role_defaults_to_student(): void
    {
        $user = new User;

        $this->assertSame(UserRole::Student, $user->role);
    }

    public function test_role_string_is_cast_to_enum(): void
    {
        $user = new User(['role' => 'teacher']);

        $this->assertSame(UserRole::Teacher, $user->role);
    }

    public function test_invalid_role_is_rejected_by_the_model(): void
    {
        $this->expectException(ValueError::class);

        new User(['role' => 'admin']);
    }

    public function test_role_validation_rejects_invalid_values(): void
    {
        $rules = ['role' => ['required', Rule::enum(UserRole::class)]];

        foreach (['admin', 'professor', 'aluno', ''] as $invalidRole) {
            $validator = Validator::make(['role' => $invalidRole], $rules);

            $this->assertTrue($validator->fails(), "Role [{$invalidRole}] should be rejected.");
        }
    }

    public function test_role_validation_accepts_allowed_values(): void
    {
        $rules = ['role' => ['required', Rule::enum(UserRole::class)]];

        foreach (UserRole::cases() as $role) {
            $validator = Validator::make(['role' => $role->value], $rules);

            $this->assertTrue($validator->passes());
        }
    }

    public function test_password_is_hashed(): void
    {
        $user = new User(['password' => 'secret-password']);

        $this->assertNotEquals('secret-password', $user->password);
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_password_and_remember_token_are_not_serialized(): void
    {
        $user = new User([
            'name' => 'Aluno Fênix',
            'email' => 'student@fenix.local',
            'password' => 'secret-password',
            'role' => UserRole::Student,
        ]);
        $user->forceFill(['remember_token' => 'token']);

        $serialized = $user->toArray();

        $this->assertArrayNotHasKey('password', $serialized);
        $this->assertArrayNotHasKey('remember_token', $serialized);
        $this->assertEquals('student', $serialized['role']);
    }

    public function test_teacher_exams_relation(): void
    {
        $relation = (new User)->exams();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Exam::class, $relation->getRelated());
        $this->assertSame('teacher_id', $relation->getForeignKeyName());
    }

    public function test_student_attempts_relation(): void
    {
        $relation = (new User)->attempts();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Attempt::class, $relation->getRelated());
        $this->assertSame('student_id', $relation->getForeignKeyName());
    }

    public function test_factory_creates_teacher_and_student_without_database(): void
    {
        $this->assertSame(UserRole::Teacher, User::factory()->teacher()->make()->role);
        $this->assertSame(UserRole::Student, User::factory()->student()->make()->role);
        $this->assertSame(UserRole::Student, User::factory()->make()->role);
    }
}
