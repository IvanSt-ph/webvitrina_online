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

    #[\PHPUnit\Framework\Attributes\DataProvider('loginPhones')]
    public function test_login_country_normalization(string $input, ?string $country, string $expected): void
    {
        $this->assertSame($expected, $this->normalizer->normalize($input, $country));
    }

    public static function loginPhones(): array
    {
        return [
            ['77111222', 'md', '+37377111222'],
            ['077 111 222', 'md', '+37377111222'],
            ['37377111222', 'md', '+37377111222'],
            ['+373 77 111 222', null, '+37377111222'],
            ['+380 67 123 45 67', 'md', '+380671234567'],
            ['00380 67 123 45 67', 'md', '+380671234567'],
            ['067 123 45 67', 'ua', '+380671234567'],
            ['0721 234 567', 'ro', '+40721234567'],
            ['8 (912) 345-67-89', 'ru', '+79123456789'],
        ];
    }

    public function test_selected_country_does_not_accept_arbitrary_length_national_number(): void
    {
        $this->expectException(ValidationException::class);
        $this->normalizer->normalize('771112', 'md');
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
