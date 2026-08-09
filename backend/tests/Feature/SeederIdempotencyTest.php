<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryCategory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_clean_seed_creates_admin_roles_and_permissions(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Admin user must exist with the documented credentials
        $this->assertDatabaseHas('users', ['email' => 'admin@labcontrol.com']);
        $this->assertSame(1, User::where('email', 'admin@labcontrol.com')->count());

        // All 6 system roles must exist
        $roleSlugs = ['admin', 'supervisor', 'laboratorista', 'tecnico', 'consulta', 'auditor'];
        foreach ($roleSlugs as $slug) {
            $this->assertDatabaseHas('roles', ['slug' => $slug]);
        }

        $this->assertSame(6, Role::count());

        // Permissions must be populated
        $this->assertGreaterThan(0, DB::table('permissions')->count());
    }

    public function test_seed_twice_does_not_throw_or_duplicate_records(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        // Stable counts after the second run (no duplicates, no exception)
        $this->assertSame(5, Category::count());
        $this->assertSame(5, InventoryCategory::count());
        $this->assertSame(1, User::where('email', 'admin@labcontrol.com')->count());
        $this->assertSame(6, Role::count());
    }
}
