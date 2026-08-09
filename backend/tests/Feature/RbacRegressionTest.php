<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Network regression proving the BUG-02 RBAC fix.
 *
 * BEFORE the fix: 14 controllers declared middleware() but did NOT implement
 * HasMiddleware, so the Laravel 13 router silently IGNORED their permission
 * middleware — any authenticated user (even one with NO roles) could access
 * every protected module endpoint. That was a full RBAC bypass.
 *
 * AFTER the fix (Tasks 1-2): every module controller implements
 * HasMiddleware and declares new Middleware('permission:x', ...). A user
 * with no roles now receives HTTP 403 on every protected endpoint.
 *
 * This test locks in the post-fix behavior: it asserts 403 for a role-less
 * user across all modules (dashboard, equipments, inventory, loans,
 * calibrations, verifications, maintenance, reports, users, logs, roles).
 *
 * NOTE: GET /api/v1/roles is intentionally NOT in the 403 list — index/show
 * stay open to any authenticated user (the user screen loads the roles list
 * to render); only mutations (POST /api/v1/roles) require roles.manage.
 */
class RbacRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Authenticate a user with NO roles (worst case for RBAC).
     */
    private function actingAsUnprivilegedUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    public function test_dashboard_requires_dashboard_view_permission(): void
    {
        $this->actingAsUnprivilegedUser();

        $this->getJson('/api/v1/dashboard')->assertStatus(403);
    }

    public function test_equipments_require_permission(): void
    {
        $this->actingAsUnprivilegedUser();

        $this->getJson('/api/v1/equipments')->assertStatus(403);
        $this->postJson('/api/v1/equipments', ['name' => 'Teste'])->assertStatus(403);
    }

    public function test_equipment_support_catalogs_require_permission(): void
    {
        $this->actingAsUnprivilegedUser();

        $this->getJson('/api/v1/categories')->assertStatus(403);
        $this->getJson('/api/v1/manufacturers')->assertStatus(403);
        $this->getJson('/api/v1/suppliers')->assertStatus(403);
    }

    public function test_inventory_requires_permission(): void
    {
        $this->actingAsUnprivilegedUser();

        $this->getJson('/api/v1/inventory-items')->assertStatus(403);
        $this->getJson('/api/v1/inventory-categories')->assertStatus(403);
        $this->getJson('/api/v1/inventory-movements')->assertStatus(403);
    }

    public function test_loans_require_permission(): void
    {
        $this->actingAsUnprivilegedUser();

        $this->getJson('/api/v1/loans')->assertStatus(403);
        $this->postJson('/api/v1/loans', [])->assertStatus(403);
    }

    public function test_calibrations_require_permission(): void
    {
        $this->actingAsUnprivilegedUser();

        $this->getJson('/api/v1/calibrations')->assertStatus(403);
        $this->postJson('/api/v1/calibrations', [])->assertStatus(403);
    }

    public function test_verifications_require_permission(): void
    {
        $this->actingAsUnprivilegedUser();

        $this->getJson('/api/v1/verifications')->assertStatus(403);
        $this->getJson('/api/v1/verifications/pending')->assertStatus(403);
    }

    public function test_maintenance_orders_require_permission(): void
    {
        $this->actingAsUnprivilegedUser();

        $this->getJson('/api/v1/maintenance-orders')->assertStatus(403);
    }

    public function test_reports_require_permission(): void
    {
        $this->actingAsUnprivilegedUser();

        $this->getJson('/api/v1/reports')->assertStatus(403);
        $this->getJson('/api/v1/reports/equipments?format=csv')->assertStatus(403);
    }

    public function test_users_require_permission(): void
    {
        $this->actingAsUnprivilegedUser();

        $this->getJson('/api/v1/users')->assertStatus(403);
    }

    public function test_activity_logs_require_permission(): void
    {
        $this->actingAsUnprivilegedUser();

        // Real route is /api/v1/logs (auditoria.view); /api/v1/activity-logs does not exist
        $this->getJson('/api/v1/logs')->assertStatus(403);
    }

    public function test_role_mutations_require_roles_manage_permission(): void
    {
        $this->actingAsUnprivilegedUser();

        $this->postJson('/api/v1/roles', ['name' => 'Teste', 'slug' => 'teste'])->assertStatus(403);
    }

    public function test_roles_index_stays_open_for_authenticated_users(): void
    {
        // GET /api/v1/roles (index/show) is intentionally open: the user screen
        // loads the roles list to render the role assignment selector.
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/roles')->assertStatus(200);
    }

    public function test_admin_bypasses_permission_checks(): void
    {
        // CheckPermission grants admin a bypass — a regression guard so the
        // middleware does not accidentally block administrators.
        $admin = User::factory()->create();
        $role = \App\Models\Role::where('slug', 'admin')->first();
        $admin->roles()->attach($role);
        Sanctum::actingAs($admin, ['*']);

        $this->getJson('/api/v1/dashboard')->assertStatus(200);
    }
}
