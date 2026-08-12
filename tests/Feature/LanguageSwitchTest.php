<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_switch_language_to_english(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('language.switch', 'en'));

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
    }

    public function test_user_can_switch_language_to_indonesian(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('language.switch', 'id'));

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'id');
    }

    public function test_invalid_language_is_ignored(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('language.switch', 'fr'));

        $response->assertRedirect();
        $this->assertNull(session('locale'));
    }
}
