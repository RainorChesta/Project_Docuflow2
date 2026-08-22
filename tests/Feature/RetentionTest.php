<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetentionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['system_role' => 'admin']);
    }

    public function test_non_admin_cannot_access_retention_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/retention')->assertForbidden();
    }

    public function test_admin_can_view_and_update_retention_setting(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/retention')->assertOk()->assertSee('Version Retention');

        $this->put('/admin/retention', [
            'retention_days' => 30,
            'document_retention_years' => 3,
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('30', Setting::get('version_retention_days'));
        $this->assertSame('3', Setting::get('document_retention_years'));
    }

    public function test_retention_days_requires_valid_integer(): void
    {
        $this->actingAs($this->admin());

        $this->put('/admin/retention', ['retention_days' => 0])
            ->assertSessionHasErrors('retention_days');
    }
}
