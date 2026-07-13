<?php

namespace Tests\Feature;

use App\Models\AIGeneration;
use App\Models\AITraining;
use App\Models\Chapter;
use App\Models\LicenseState;
use App\Models\ReportType;
use App\Models\ReportTypeFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\Concerns\SeedsValidLicense;
use Tests\TestCase;

class AdminRouteScopingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsValidLicense;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedValidLicense();
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_generation_detail_cannot_be_opened_under_another_report_type(): void
    {
        $admin = $this->admin();
        [$ownerReportType, $generation] = $this->generationForReportType($admin);
        $otherReportType = ReportType::create(['nombre' => 'Otro reporte']);

        $this->actingAs($admin)
            ->get(route('admin.ai-training.generation.show', [$otherReportType, $generation]))
            ->assertNotFound();
    }

    public function test_generation_download_cannot_be_opened_under_another_report_type(): void
    {
        $admin = $this->admin();
        [$ownerReportType, $generation] = $this->generationForReportType($admin);
        $otherReportType = ReportType::create(['nombre' => 'Otro reporte']);

        $this->actingAs($admin)
            ->get(route('admin.ai-training.generation.download', [$otherReportType, $generation]))
            ->assertNotFound();
    }

    public function test_generation_delete_cannot_delete_generation_from_another_report_type(): void
    {
        $admin = $this->admin();
        [$ownerReportType, $generation] = $this->generationForReportType($admin);
        $otherReportType = ReportType::create(['nombre' => 'Otro reporte']);

        $this->actingAs($admin)
            ->delete(route('admin.ai-training.generation.destroy', [$otherReportType, $generation]))
            ->assertNotFound();

        $this->assertDatabaseHas('ai_generations', ['id' => $generation->id]);
    }

    public function test_generation_download_handles_missing_generated_at(): void
    {
        $admin = $this->admin();
        [$reportType, $generation] = $this->generationForReportType($admin);
        $generation->update(['generated_at' => null]);

        $this->actingAs($admin)
            ->get(route('admin.ai-training.generation.download', [$reportType, $generation]))
            ->assertOk()
            ->assertSee('No disponible', false);
    }

    public function test_report_files_show_uses_portable_sql_ordering(): void
    {
        $admin = $this->admin();
        $reportType = ReportType::create(['nombre' => 'Reporte portable']);
        $grupoId = (string) Str::uuid();

        ReportTypeFile::create([
            'report_type_id' => $reportType->id,
            'tipo_archivo' => 'entrada',
            'grupo_id' => $grupoId,
            'nombre_original' => 'entrada.txt',
            'nombre_archivo' => 'entrada.txt',
            'ruta' => 'report_files/test/entrada.txt',
            'extension' => 'txt',
            'tamano' => 10,
            'created_by' => $admin->id,
        ]);

        ReportTypeFile::create([
            'report_type_id' => $reportType->id,
            'tipo_archivo' => 'salida',
            'grupo_id' => $grupoId,
            'nombre_original' => 'salida.txt',
            'nombre_archivo' => 'salida.txt',
            'ruta' => 'report_files/test/salida.txt',
            'extension' => 'txt',
            'tamano' => 10,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.report-files.show', $reportType))
            ->assertOk();
    }

    // ------------------------------------------------------------------
    // 8.1 — Bloqueo total: el estado bloqueado (gracia agotada) es
    // airtight. En vez de hardcodear una lista de rutas (que se pudre en
    // cuanto alguien agrega una ruta nueva), se enumeran las rutas GET
    // registradas en el router real y se filtran por las que llevan el
    // middleware `license` — exactamente el criterio que usa
    // EnsureLicenseIsValid para decidir qué protege.
    // ------------------------------------------------------------------

    public function test_licencia_bloqueada_redirige_todas_las_rutas_get_protegidas_sin_parametros_para_admin(): void
    {
        $this->blockLicense();
        $admin = $this->admin();

        $all = $this->licenseProtectedGetRoutes();
        $routes = $this->withoutRouteParams($all);

        // Guarda contra un filtro roto que deje la lista vacía: si esto
        // pasara, el foreach de abajo no ejecutaría ninguna aserción y el
        // test "pasaría" sin probar nada.
        $this->assertNotEmpty($routes, 'No se encontró ninguna ruta GET protegida por `license`; revisar el filtro.');

        // Rutas protegidas por `license` que SÍ tienen parámetros de ruta
        // ({user}, {reportType}, {chapter}, {file}, {generation}...). Un GET
        // a ciegas no las puede satisfacer de forma genérica sin fixtures
        // específicas por recurso (y ese escenario ya está cubierto, para
        // los casos de scoping, por los demás tests de esta clase). Se
        // documentan acá de forma explícita: si alguien agrega una ruta
        // protegida nueva con parámetros, esta aserción se rompe y obliga a
        // decidir conscientemente si sumarla a esta lista o no.
        $skippedNames = collect($all)
            ->filter(fn (RoutingRoute $route) => str_contains($route->uri(), '{'))
            ->map(fn (RoutingRoute $route) => $route->getName())
            ->values()
            ->all();

        $this->assertEqualsCanonicalizing([
            'admin.users.show', 'admin.users.edit',
            'admin.report-types.show', 'admin.report-types.edit',
            'admin.chapters.index', 'admin.chapters.create', 'admin.chapters.edit',
            'admin.report-files.prompt', 'admin.report-files.show',
            'admin.report-files.create', 'admin.report-files.download',
            'admin.ai-training.show', 'admin.ai-training.generate.create',
            'admin.ai-training.generations', 'admin.ai-training.generation.show',
            'admin.ai-training.generation.download',
        ], $skippedNames, 'La lista de rutas con parámetros omitidas cambió; revisar y actualizar a propósito.');

        foreach ($routes as $route) {
            $this->actingAs($admin)
                ->get('/' . ltrim($route->uri(), '/'))
                ->assertRedirect(route('license.activation.show'));
        }
    }

    public function test_licencia_bloqueada_redirige_las_rutas_no_admin_protegidas_para_no_admin(): void
    {
        $this->blockLicense();
        $user = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        // Solo las rutas SIN middleware `admin`: un no-admin sobre una ruta
        // /admin/* ya recibe 403 de EnsureUserIsAdmin (que corre antes que
        // `license` en el stack) — eso es un test de autorización, no de
        // licenciamiento, y ya está cubierto en otro lado.
        $routes = array_values(array_filter(
            $this->withoutRouteParams($this->licenseProtectedGetRoutes()),
            fn (RoutingRoute $route) => ! in_array('admin', $route->gatherMiddleware(), true)
        ));

        $this->assertNotEmpty($routes, 'No se encontró ninguna ruta GET protegida por `license` sin `admin`; revisar el filtro.');

        foreach ($routes as $route) {
            $this->actingAs($user)
                ->get('/' . ltrim($route->uri(), '/'))
                ->assertRedirect(route('license.blocked.show'));
        }
    }

    public function test_licencia_bloqueada_no_afecta_el_health_check_ni_las_rutas_de_invitado_de_auth(): void
    {
        $this->blockLicense();

        // /up (health check, sin middleware `auth`/`license`) y las rutas de
        // invitado de routes/auth.php: si estas dejaran de funcionar con la
        // licencia bloqueada, un admin nunca podría loguearse para
        // activarla.
        $this->get('/up')->assertOk();
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
        $this->get(route('password.request'))->assertOk();
        $this->get(route('password.reset', ['token' => 'un-token-cualquiera']))->assertOk();
    }

    public function test_login_sigue_funcionando_con_la_licencia_bloqueada(): void
    {
        $this->blockLicense();
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        // Esta es LA aserción más importante del archivo: `login` no lleva
        // el middleware `license` (routes/auth.php queda afuera de los
        // grupos protegidos), así que un admin bloqueado puede autenticarse
        // igual. Si esto se rompiera, nadie podría llegar nunca a la
        // pantalla de activación.
        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($admin);

        // Ya logueado, el resto de la app sigue bloqueada: cae en la
        // pantalla de activación, no en el dashboard.
        $this->get('/dashboard')->assertRedirect(route('license.activation.show'));
    }

    public function test_las_pantallas_de_licencia_siguen_alcanzables_sin_loop_de_redireccion(): void
    {
        $this->blockLicense();

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('license.activation.show'))->assertOk();

        $user = User::factory()->create(['is_admin' => false, 'is_active' => true]);
        $this->actingAs($user)->get(route('license.blocked.show'))->assertOk();
    }

    /**
     * Reemplaza la licencia vigente sembrada en setUp() por un estado
     * bloqueado real: `valid_until` muy vencido, más allá de cualquier
     * `grace_days` configurado, con `last_seen_at` en el pasado (para no
     * disparar la detección de rollback de reloj de resolveStatus()).
     */
    private function blockLicense(): LicenseState
    {
        return LicenseState::create([
            'raw_token' => 'blocked.token.x',
            'payload' => ['kid' => 'k1'],
            'valid_from' => now()->subDays(400),
            'valid_until' => now()->subDays(60),
            'max_users' => 999,
            'last_check_at' => now()->subDays(60),
            'last_check_result' => 'active',
            'last_seen_at' => now()->subDays(60),
            'enforcement_status' => 'grace',
        ]);
    }

    /**
     * Todas las rutas GET registradas en la app cuyo stack de middleware
     * incluye el alias `license` — es decir, exactamente las que
     * EnsureLicenseIsValid protege. Se deriva del router real en vez de
     * mantenerse a mano, para que este test no pueda quedar desactualizado
     * si se agrega o se saca una ruta.
     *
     * @return list<RoutingRoute>
     */
    private function licenseProtectedGetRoutes(): array
    {
        return collect(Route::getRoutes())
            ->filter(fn (RoutingRoute $route) => in_array('GET', $route->methods(), true))
            ->filter(fn (RoutingRoute $route) => in_array('license', $route->gatherMiddleware(), true))
            ->values()
            ->all();
    }

    /**
     * @param  list<RoutingRoute>  $routes
     * @return list<RoutingRoute>
     */
    private function withoutRouteParams(array $routes): array
    {
        return array_values(array_filter(
            $routes,
            fn (RoutingRoute $route) => ! str_contains($route->uri(), '{')
        ));
    }

    /** @return array{0: ReportType, 1: AIGeneration} */
    private function generationForReportType(User $admin): array
    {
        $reportType = ReportType::create(['nombre' => 'Reporte dueño']);
        $chapter = Chapter::create([
            'report_type_id' => $reportType->id,
            'nombre' => 'Capítulo',
            'orden' => 1,
        ]);
        $training = AITraining::create([
            'report_type_id' => $reportType->id,
            'status' => 'ready',
            'system_prompt' => 'Prompt',
            'examples_count' => 1,
        ]);
        $generation = AIGeneration::create([
            'ai_training_id' => $training->id,
            'user_id' => $admin->id,
            'chapter_id' => $chapter->id,
            'titulo' => 'Generación',
            'input_content' => 'input',
            'output_content' => 'output',
            'status' => 'completed',
            'generated_at' => now(),
        ]);

        return [$reportType, $generation];
    }
}
