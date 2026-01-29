<?php

namespace Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Framework\Validation\Validator;
use Framework\Validation\ValidationException;

class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = Validator::getInstance();
    }

    public function testValidatorIsSingleton(): void
    {
        $instance1 = Validator::getInstance();
        $instance2 = Validator::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function testMakeReturnsValidatorInstance(): void
    {
        $validator = $this->validator->make(['email' => 'test@example.com'], ['email' => 'required|email']);

        $this->assertInstanceOf(Validator::class, $validator);
    }

    public function testValidationPassesWithValidData(): void
    {
        $data = [
            'email' => 'test@example.com',
            'name' => 'John Doe',
            'age' => 25,
        ];

        $rules = [
            'email' => 'required|email',
            'name' => 'required|string',
            'age' => 'required|numeric|min:18',
        ];

        $validator = $this->validator->make($data, $rules);

        $this->assertTrue($validator->passes());
        $this->assertFalse($validator->fails());
    }

    public function testValidationFailsWithInvalidData(): void
    {
        $data = [
            'email' => 'invalid-email',
            'age' => 15,
        ];

        $rules = [
            'email' => 'required|email',
            'name' => 'required',
            'age' => 'required|numeric|min:18',
        ];

        $validator = $this->validator->make($data, $rules);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->fails());
    }

    public function testValidateThrowsExceptionOnFailure(): void
    {
        $this->expectException(ValidationException::class);

        $data = ['email' => 'invalid'];
        $rules = ['email' => 'required|email'];

        $this->validator->validate($data, $rules);
    }

    public function testValidateReturnsDataOnSuccess(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'required|email'];

        $validated = $this->validator->validate($data, $rules);

        $this->assertIsArray($validated);
        $this->assertEquals('test@example.com', $validated['email']);
    }

    public function testErrorsAreCollected(): void
    {
        $data = [
            'email' => 'invalid',
            'age' => 'not-a-number',
        ];

        $rules = [
            'email' => 'email',
            'age' => 'numeric',
        ];

        $validator = $this->validator->make($data, $rules);

        $errors = $validator->errors();

        $this->assertTrue($errors->has('email'));
        $this->assertTrue($errors->has('age'));
    }

    public function testStringRulesAreParsed(): void
    {
        $data = ['name' => 'John'];

        $validator = $this->validator->make($data, [
            'name' => 'required|string|max:10',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function testArrayRulesWork(): void
    {
        $data = ['name' => 'John'];

        $validator = $this->validator->make($data, [
            'name' => ['required', 'string', 'max:10'],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function testCustomRuleCanBeRegistered(): void
    {
        $this->validator->extend('uppercase', function ($field, $value) {
            return $value === strtoupper($value);
        }, 'The :field must be uppercase.');

        $data = ['code' => 'ABC'];
        $validator = $this->validator->make($data, ['code' => 'uppercase']);

        $this->assertTrue($validator->passes());

        $data = ['code' => 'abc'];
        $validator = $this->validator->make($data, ['code' => 'uppercase']);

        $this->assertFalse($validator->passes());
    }
}
