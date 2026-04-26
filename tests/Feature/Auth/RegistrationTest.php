<?php

namespace Tests\Feature\Auth;

use App\Models\City;
use App\Models\Province;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $province = Province::create([
            'name' => 'Bagmati Province',
            'slug' => 'bagmati-province',
        ]);

        $city = City::create([
            'province_id' => $province->id,
            'name' => 'Kathmandu',
            'slug' => 'kathmandu',
            'is_active' => true,
            'is_serviceable' => true,
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));
    }
}
