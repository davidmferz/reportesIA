<?php

namespace Tests\Feature;

use App\Models\LicenseState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LicenseBannerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    private function license(array $attrs = []): LicenseState
    {
        return LicenseState::create(array_merge([
            'raw_token' => 'a.b.c',
            'payload' => ['kid' => 'k1'],
            'valid_from' => '2026-01-01 00:00:00',
            'valid_until' => '2027-01-01 00:00:00',
            'max_users' => 5,
        ], $attrs));
    }

    public function test_banner_visible_en_periodo_de_gracia(): void
    {
        Carbon::setTestNow('2027-01-05 00:00:00'); // 5 días tras valid_until, grace 14
        $this->license();

        $this->actingAs($this->user())->get('/dashboard')
            ->assertOk()
            ->assertSee('gracia');
    }

    public function test_banner_ausente_con_licencia_valida(): void
    {
        Carbon::setTestNow('2026-06-01 00:00:00');
        $this->license();

        $this->actingAs($this->user())->get('/dashboard')
            ->assertOk()
            ->assertDontSee('gracia');
    }
}
