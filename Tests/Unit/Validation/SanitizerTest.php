<?php

namespace Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Framework\Validation\Sanitizer;

class SanitizerTest extends TestCase
{
    private Sanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = Sanitizer::getInstance();
    }

    public function testSanitizerIsSingleton(): void
    {
        $instance1 = Sanitizer::getInstance();
        $instance2 = Sanitizer::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function testSanitizeString(): void
    {
        $input = '<script>alert("xss")</script>Hello';
        $result = $this->sanitizer->string($input);

        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testSanitizeEmail(): void
    {
        $input = 'test@example.com<script>';
        $result = $this->sanitizer->email($input);

        $this->assertEquals('test@example.com', $result);
    }

    public function testSanitizeUrl(): void
    {
        $input = 'https://example.com<script>';
        $result = $this->sanitizer->url($input);

        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testSanitizeInteger(): void
    {
        $this->assertEquals(123, $this->sanitizer->int('123'));
        $this->assertEquals(123, $this->sanitizer->int('123abc'));
        $this->assertEquals(-123, $this->sanitizer->int('-123'));
    }

    public function testSanitizeFloat(): void
    {
        $this->assertEquals(123.45, $this->sanitizer->float('123.45'));
        $this->assertEquals(123.45, $this->sanitizer->float('123.45abc'));
    }

    public function testSanitizeBoolean(): void
    {
        $this->assertTrue($this->sanitizer->boolean('true'));
        $this->assertTrue($this->sanitizer->boolean('1'));
        $this->assertTrue($this->sanitizer->boolean('yes'));
        $this->assertTrue($this->sanitizer->boolean('on'));
        $this->assertTrue($this->sanitizer->boolean(true));

        $this->assertFalse($this->sanitizer->boolean('false'));
        $this->assertFalse($this->sanitizer->boolean('0'));
        $this->assertFalse($this->sanitizer->boolean('no'));
        $this->assertFalse($this->sanitizer->boolean(false));
    }

    public function testSanitizeArray(): void
    {
        $input = [
            'name' => '<script>alert("xss")</script>John',
            'email' => 'test@example.com',
        ];

        $result = $this->sanitizer->array($input);

        $this->assertIsArray($result);
        $this->assertStringNotContainsString('<script>', $result['name']);
    }

    public function testEscapeHtml(): void
    {
        $input = '<div>Hello & "World"</div>';
        $result = $this->sanitizer->escapeHtml($input);

        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&gt;', $result);
        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('&quot;', $result);
    }

    public function testStripTags(): void
    {
        $input = '<div><p>Hello</p><script>alert("xss")</script></div>';
        $result = $this->sanitizer->stripTags($input);

        $this->assertEquals('Helloalert("xss")', $result);
    }

    public function testStripTagsWithAllowedTags(): void
    {
        $input = '<div><p>Hello</p><script>alert("xss")</script></div>';
        $result = $this->sanitizer->stripTags($input, '<p>');

        $this->assertStringContainsString('<p>', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testFilename(): void
    {
        $this->assertEquals('hello_world.txt', $this->sanitizer->filename('hello world.txt'));
        $this->assertEquals('test_file.pdf', $this->sanitizer->filename('test@#$file.pdf'));
        $this->assertEquals('document.docx', $this->sanitizer->filename('../../../document.docx'));
    }

    public function testDeepSanitize(): void
    {
        $input = [
            'user' => [
                'name' => '<script>alert("xss")</script>John',
                'profile' => [
                    'bio' => '<script>XSS</script>Hello',
                ],
            ],
        ];

        $result = $this->sanitizer->deepSanitize($input);

        $this->assertStringNotContainsString('<script>', $result['user']['name']);
        $this->assertStringNotContainsString('<script>', $result['user']['profile']['bio']);
    }
}
