<?php

namespace Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Framework\Validation\ErrorBag;

class ErrorBagTest extends TestCase
{
    private ErrorBag $errorBag;

    protected function setUp(): void
    {
        $this->errorBag = new ErrorBag();
    }

    public function testAddError(): void
    {
        $this->errorBag->add('email', 'Email is invalid');

        $this->assertTrue($this->errorBag->has('email'));
    }

    public function testGetFirstError(): void
    {
        $this->errorBag->add('email', 'Email is required');
        $this->errorBag->add('email', 'Email is invalid');

        $first = $this->errorBag->first('email');

        $this->assertEquals('Email is required', $first);
    }

    public function testGetAllErrorsForField(): void
    {
        $this->errorBag->add('email', 'Error 1');
        $this->errorBag->add('email', 'Error 2');

        $errors = $this->errorBag->get('email');

        $this->assertCount(2, $errors);
        $this->assertEquals('Error 1', $errors[0]);
        $this->assertEquals('Error 2', $errors[1]);
    }

    public function testHasReturnsFalseForNonExistentField(): void
    {
        $this->assertFalse($this->errorBag->has('email'));
    }

    public function testCountReturnsCorrectTotal(): void
    {
        $this->errorBag->add('email', 'Error 1');
        $this->errorBag->add('email', 'Error 2');
        $this->errorBag->add('name', 'Error 3');

        $this->assertEquals(3, $this->errorBag->count());
    }

    public function testAnyReturnsTrueWhenErrorsExist(): void
    {
        $this->assertFalse($this->errorBag->any());

        $this->errorBag->add('email', 'Error');

        $this->assertTrue($this->errorBag->any());
    }

    public function testIsEmptyWorks(): void
    {
        $this->assertTrue($this->errorBag->isEmpty());

        $this->errorBag->add('email', 'Error');

        $this->assertFalse($this->errorBag->isEmpty());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $this->errorBag->add('email', 'Email error');
        $this->errorBag->add('name', 'Name error');

        $array = $this->errorBag->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('name', $array);
    }

    public function testToJsonReturnsValidJson(): void
    {
        $this->errorBag->add('email', 'Email error');

        $json = $this->errorBag->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertArrayHasKey('email', $decoded);
    }

    public function testClearRemovesAllErrors(): void
    {
        $this->errorBag->add('email', 'Error');
        $this->errorBag->clear();

        $this->assertTrue($this->errorBag->isEmpty());
    }

    public function testClearFieldRemovesOnlyThatField(): void
    {
        $this->errorBag->add('email', 'Error');
        $this->errorBag->add('name', 'Error');

        $this->errorBag->clearField('email');

        $this->assertFalse($this->errorBag->has('email'));
        $this->assertTrue($this->errorBag->has('name'));
    }
}
