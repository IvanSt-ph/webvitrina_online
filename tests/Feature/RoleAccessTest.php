<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: покупатель не может зайти в панель продавца
     */
    public function test_buyer_cannot_access_seller_pages(): void
    {
        $buyer = User::factory()->create(); // по умолчанию buyer
        
        $response = $this->actingAs($buyer)->get('/seller/products');
        $response->assertStatus(403);
    }

    /**
     * Тест: продавец не может зайти в админку
     */
    public function test_seller_cannot_access_admin_pages(): void
    {
        $seller = User::factory()->seller()->create();
        
        $response = $this->actingAs($seller)->get('/admin/users');
        $response->assertStatus(403);
    }

    /**
     * Тест: гость не может зайти на защищенные страницы
     */
    public function test_guest_cannot_access_protected_pages(): void
    {
        $response = $this->get('/favorites');
        $response->assertRedirect('/login');
        
        $response = $this->get('/cart');
        $response->assertRedirect('/login');
        
        $response = $this->get('/orders');
        $response->assertRedirect('/login');
    }

    /**
     * Тест: админ может зайти в админку
     */
    public function test_admin_can_access_admin_pages(): void
    {
        $admin = User::factory()->admin()->create();
        
        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertStatus(200);
    }

    /**
     * Тест: покупатель может зайти в свой профиль
     */
    public function test_buyer_can_access_buyer_profile(): void
    {
        $buyer = User::factory()->create();
        
        $response = $this->actingAs($buyer)->get('/buyer/profile');
        $response->assertStatus(200);
    }

    /**
     * Тест: продавец может зайти в свой профиль
     */
    public function test_seller_can_access_seller_profile(): void
    {
        $seller = User::factory()->seller()->create();
        
        $response = $this->actingAs($seller)->get('/profile/edit');
        $response->assertStatus(200);
    }

    /**
     * Тест: покупатель не может зайти на страницу редактирования продавца
     */
    public function test_buyer_cannot_access_seller_edit_profile(): void
    {
        $buyer = User::factory()->create();
        
        $response = $this->actingAs($buyer)->get('/profile/edit');
        $response->assertStatus(403);
    }

    /**
     * Тест: продавец не может зайти в профиль покупателя
     */
    public function test_seller_cannot_access_buyer_profile(): void
    {
        $seller = User::factory()->seller()->create();
        
        $response = $this->actingAs($seller)->get('/buyer/profile');
        $response->assertStatus(403);
    }

    /**
     * Тест: редирект /profile работает по ролям
     */
    public function test_profile_redirect_works_by_role(): void
    {
        // Покупатель
        $buyer = User::factory()->create();
        $response = $this->actingAs($buyer)->get('/profile');
        $response->assertRedirect('/buyer/profile');
        
        // Продавец
        $seller = User::factory()->seller()->create();
        $response = $this->actingAs($seller)->get('/profile');
        $response->assertRedirect('/profile/edit');
        
        // Админ
        $admin = User::factory()->admin()->create();
        $response = $this->actingAs($admin)->get('/profile');
        $response->assertRedirect('/admin/profile/edit');
    }
}