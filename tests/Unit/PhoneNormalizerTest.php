<?php

namespace Tests\Unit;

use App\Services\PhoneNormalizer;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhoneNormalizerTest extends TestCase
{
    private PhoneNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = app(PhoneNormalizer::class);
    }

    public function test_canonical_phone_is_accepted(): void
    {
        $this->assertSame('+37377811161', $this->normalizer->normalize('+37377811161'));
    }

    public function test_allowed_formatting_is_removed(): void
    {
        $this->assertSame('+37377811161', $this->normalizer->normalize('+373 (778) 11-161'));
    }

    public function test_blank_phone_becomes_null(): void
    {
        $this->assertNull($this->normalizer->normalize(" \t\n"));
    }

    public function test_letters_and_mixed_garbage_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->normalizer->normalize('+373 abc 77811161');
    }

    public function test_too_short_phone_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->normalizer->normalize('+123456');
    }

    public function test_too_long_phone_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->normalizer->normalize('+1234567890123456');
    }
}
