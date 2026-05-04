<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function test_required_passes_when_present(): void
    {
        $v = Validator::make(['email' => 'a@b.com'], ['email' => 'required|email']);
        $this->assertTrue($v->passes());
    }

    public function test_required_fails_when_blank(): void
    {
        $v = Validator::make(['email' => ''], ['email' => 'required']);
        $this->assertFalse($v->passes());
        $this->assertNotEmpty($v->firstError());
    }

    public function test_email_rule_rejects_invalid_addresses(): void
    {
        $v = Validator::make(['email' => 'not-an-email'], ['email' => 'email']);
        $this->assertTrue($v->fails());
    }

    public function test_min_max_rules(): void
    {
        $this->assertTrue(Validator::make(['p' => 'abcdef'], ['p' => 'min:6|max:20'])->passes());
        $this->assertFalse(Validator::make(['p' => 'abc'], ['p' => 'min:6'])->passes());
        $this->assertFalse(Validator::make(['p' => str_repeat('x', 30)], ['p' => 'max:20'])->passes());
    }

    public function test_in_rule(): void
    {
        $rule = ['role' => 'in:admin,staff,customer'];
        $this->assertTrue(Validator::make(['role' => 'staff'], $rule)->passes());
        $this->assertFalse(Validator::make(['role' => 'guest'], $rule)->passes());
    }

    public function test_confirmed_rule(): void
    {
        $ok = Validator::make(['password' => 'secret', 'password_confirmation' => 'secret'], ['password' => 'confirmed']);
        $this->assertTrue($ok->passes());

        $bad = Validator::make(['password' => 'secret', 'password_confirmation' => 'mismatch'], ['password' => 'confirmed']);
        $this->assertFalse($bad->passes());
    }

    public function test_collects_errors_per_field(): void
    {
        $v = Validator::make(
            ['email' => '', 'name' => ''],
            ['email' => 'required|email', 'name' => 'required'],
        );
        $this->assertFalse($v->passes());
        $errors = $v->errors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('name', $errors);
    }
}
