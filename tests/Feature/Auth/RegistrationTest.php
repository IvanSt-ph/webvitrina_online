<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

public function test_new_users_can_register(): void
{
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'buyer',
        'terms' => '1', // или 'on' или 1 - то что ожидает твоя форма
    ]);
    
    $response->assertSessionHasNoErrors();
    
    $this->assertAuthenticated();
    $response->assertRedirect();
}

    public function test_registration_keeps_selected_international_country_code(): void
    {
        $this->post('/register', [
            'name' => 'Ukraine Phone User',
            'email' => 'ua-phone@example.com',
            'phone' => '+380 67 123 45 67',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'buyer',
            'terms' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('+380671234567', User::where('email', 'ua-phone@example.com')->value('phone'));
    }

    public function test_registration_validation_keeps_phone_for_country_restoration(): void
    {
        $phone = '+40 721 234 567';

        $this->from('/register')
            ->post('/register', [
                'name' => '',
                'email' => 'invalid-registration',
                'phone' => $phone,
                'password' => 'password',
                'password_confirmation' => 'different',
                'role' => 'buyer',
                'terms' => '1',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['name', 'email', 'password'])
            ->assertSessionHasInput('phone', $phone);

        $this->get('/register')
            ->assertOk()
            ->assertSee('value="+40 721 234 567"', false);
    }

    public function test_registration_phone_widget_uses_current_intl_tel_input_api(): void
    {
        $javascript = file_get_contents(resource_path('js/app.js'));
        $styles = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString("loadUtils: window.loadIntlTelInputUtils", $javascript);
        $this->assertStringContainsString('dropdownContainer: document.body', $javascript);
        $this->assertStringContainsString('separateDialCode: true', $javascript);
        $this->assertStringContainsString('strictMode: true', $javascript);
        $this->assertStringContainsString('showFlags: true', $javascript);
        $this->assertStringContainsString('const fullNumber = iti.getNumber()', $javascript);
        $this->assertStringNotContainsString('utilsScript:', $javascript);
        $this->assertStringNotContainsString("input.value = '+'", $javascript);
        $this->assertStringContainsString('.iti--container .iti__dropdown-content', $styles);
        $this->assertStringContainsString('.iti--separate-dial-code .iti__selected-country', $styles);
    }

    public function test_local_csp_allows_vite_phone_flag_images(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $policy = (string) $this->get('/register')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString(
            "img-src 'self' data: blob: https://ui-avatars.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com https://*.tile.openstreetmap.org http://127.0.0.1:5173 http://localhost:5173",
            $policy,
        );
    }


    
}
