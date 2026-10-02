<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicLegalPagesTest extends TestCase
{
    public static function legalRoutes(): array
    {
        return [
            'privacy' => ['legal.privacy'],
            'terms' => ['legal.terms'],
            'seller rules' => ['legal.seller-rules'],
            'buyer rules' => ['legal.buyer-rules'],
            'cookies' => ['legal.cookies'],
            'contacts' => ['contacts'],
            'prohibited products' => ['legal.prohibited-products'],
            'review rules' => ['legal.review-rules'],
        ];
    }

    #[DataProvider('legalRoutes')]
    public function test_legal_page_is_available_to_guests(string $routeName): void
    {
        $this->get(route($routeName))
            ->assertOk();
    }

    public function test_public_footer_contains_primary_legal_links(): void
    {
        $response = $this->get(route('legal.terms'));

        $response->assertOk();

        foreach (self::legalRoutes() as [$routeName]) {
            $response->assertSee(route($routeName), false);
        }
    }
}
