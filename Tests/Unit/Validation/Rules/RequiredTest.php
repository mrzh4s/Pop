<?php

namespace Tests\Unit\Validation\Rules;

use PHPUnit\Framework\TestCase;
use Framework\Validation\Rules\Required;

class RequiredTest extends TestCase
{
    private Required $rule;

    protected function setUp(): void
    {
        $this->rule = new Required();
    }

    public function testFailsWithNull(): void
    {
        $this->assertFalse($this->rule->passes('field', null, [], []));
    }

    public function testFailsWithEmptyString(): void
    {
        $this->assertFalse($this->rule->passes('field', '', [], []));
        $this->assertFalse($this->rule->passes('field', '   ', [], []));
    }

    public function testFailsWithEmptyArray(): void
    {
        $this->assertFalse($this->rule->passes('field', [], [], []));
    }

    public function testPassesWithNonEmptyString(): void
    {
        $this->assertTrue($this->rule->passes('field', 'value', [], []));
        $this->assertTrue($this->rule->passes('field', '0', [], []));
    }

    public function testPassesWithNonEmptyArray(): void
    {
        $this->assertTrue($this->rule->passes('field', ['item'], [], []));
    }

    public function testPassesWithZero(): void
    {
        $this->assertTrue($this->rule->passes('field', 0, [], []));
        $this->assertTrue($this->rule->passes('field', '0', [], []));
    }

    public function testPassesWithFalse(): void
    {
        $this->assertTrue($this->rule->passes('field', false, [], []));
    }

    public function testMessageIsCorrect(): void
    {
        $message = $this->rule->message('field', []);

        $this->assertStringContainsString('field', $message);
        $this->assertStringContainsString('required', $message);
    }
}
