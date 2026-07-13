<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\LicenseState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LicenseStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_persiste_todos_los_campos_y_castea_payload_a_array(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret',
            'is_admin' => true,
        ]);

        $state = LicenseState::create([
            'raw_token' => 'eyJhbGciOiJFZERTQSJ9.payload.signature',
            'payload' => ['client_id' => 'acme', 'max_users' => 5, 'kid' => 'k1'],
            'valid_from' => '2026-01-01 00:00:00',
            'valid_until' => '2027-01-01 00:00:00',
            'max_users' => 5,
            'last_check_at' => '2026-07-08 10:00:00',
            'last_check_result' => 'active',
            'last_seen_at' => '2026-07-08 10:00:00',
            'activated_at' => '2026-07-08 10:00:00',
            'activated_by' => $admin->id,
        ]);

        $fresh = $state->fresh();

        $this->assertIsArray($fresh->payload);
        $this->assertSame('acme', $fresh->payload['client_id']);
        $this->assertSame(5, $fresh->max_users);
        $this->assertSame('active', $fresh->last_check_result);
        $this->assertSame($admin->id, $fresh->activated_by);
        $this->assertInstanceOf(Carbon::class, $fresh->valid_until);
        $this->assertInstanceOf(Carbon::class, $fresh->last_seen_at);
    }

    public function test_current_devuelve_la_unica_fila_o_null(): void
    {
        $this->assertNull(LicenseState::current());

        $state = LicenseState::create([
            'raw_token' => 'a.b.c',
            'payload' => ['kid' => 'k1'],
            'valid_from' => '2026-01-01 00:00:00',
            'valid_until' => '2027-01-01 00:00:00',
            'max_users' => 3,
        ]);

        $this->assertNotNull(LicenseState::current());
        $this->assertSame($state->id, LicenseState::current()->id);
    }

    public function test_activacion_audita_pero_excluye_raw_token_del_log(): void
    {
        LicenseState::create([
            'raw_token' => 'super.secret.jws',
            'payload' => ['kid' => 'k1'],
            'valid_from' => '2026-01-01 00:00:00',
            'valid_until' => '2027-01-01 00:00:00',
            'max_users' => 1,
        ]);

        $log = ActivityLog::where('log_name', 'LicenseState')->latest('id')->first();

        $this->assertNotNull($log, 'El alta de LicenseState debe auditarse via LogsActivity.');
        $this->assertArrayNotHasKey('raw_token', $log->properties['attributes'] ?? []);
    }
}
