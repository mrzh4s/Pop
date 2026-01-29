<?php

namespace Tests\Unit\Validation\Rules;

use PHPUnit\Framework\TestCase;
use Framework\Validation\Rules\Email;

class EmailTest extends TestCase
{
    private Email $rule;

    protected function setUp(): void
    {
        $this->rule = new Email();
    }

    public function testPassesWithValidEmail(): void
    {
        $this->assertTrue($this->rule->passes('email', 'test@example.com', [], []));
        $this->assertTrue($this->rule->passes('email', 'user.name@domain.co.uk', [], []));
        $this->assertTrue($this->rule->passes('email', 'user+tag@example.com', [], []));
    }

    public function testFailsWithInvalidEmail(): void
    {
        $this->assertFalse($this->rule->passes('email', 'invalid-email', [], []));
        $this->assertFalse($this->rule->passes('email', 'missing@domain', [], []));
        $this->assertFalse($this->rule->passes('email', '@example.com', [], []));
        $this->assertFalse($this->rule->passes('email', 'user@', [], []));
    }

    public function testPassesWithEmptyValue(): void
    {
        // Empty values should pass - 'required' rule handles empty checks
        $this->assertTrue($this->rule->passes('email', '', [], []));
        $this->assertTrue($this->rule->passes('email', null, [], []));
    }

    public function testMessageIsCorrect(): void
    {
        $message = $this->rule->message('email', []);

        $this->assertStringContainsString('email', $message);
        $this->assertStringContainsString('valid', $message);
    }
}
