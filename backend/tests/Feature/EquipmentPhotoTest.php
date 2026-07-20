<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EquipmentPhotoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('slug', 'admin')->value('id'));

        $this->equipment = Equipment::factory()->create();

        Storage::fake('public');
    }

    public function test_can_upload_equipment_photo(): void
    {
        $file = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/equipments/{$this->equipment->id}/photos", [
                'photo' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('original_name', 'photo.jpg');
    }

    public function test_upload_rejects_invalid_file(): void
    {
        $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/equipments/{$this->equipment->id}/photos", [
                'photo' => $file,
            ]);

        $response->assertStatus(422);
    }

    public function test_upload_rejects_large_file(): void
    {
        $file = UploadedFile::fake()->create('large.jpg', 6000, 'image/jpeg');

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/equipments/{$this->equipment->id}/photos", [
                'photo' => $file,
            ]);

        $response->assertStatus(422);
    }

    public function test_can_list_equipment_photos(): void
    {
        $file = UploadedFile::fake()->create('photo1.jpg', 100, 'image/jpeg');
        $this->actingAs($this->user)
            ->postJson("/api/v1/equipments/{$this->equipment->id}/photos", ['photo' => $file]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/equipments/{$this->equipment->id}/photos");

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_can_delete_equipment_photo(): void
    {
        $file = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');
        $upload = $this->actingAs($this->user)
            ->postJson("/api/v1/equipments/{$this->equipment->id}/photos", ['photo' => $file]);

        $photoId = $upload->json('id');

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/equipments/{$this->equipment->id}/photos/{$photoId}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('equipment_photos', ['id' => $photoId]);
    }

    public function test_can_reorder_photos(): void
    {
        $file1 = UploadedFile::fake()->create('first.jpg', 100, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('second.jpg', 100, 'image/jpeg');

        $first = $this->actingAs($this->user)
            ->postJson("/api/v1/equipments/{$this->equipment->id}/photos", ['photo' => $file1]);
        $second = $this->actingAs($this->user)
            ->postJson("/api/v1/equipments/{$this->equipment->id}/photos", ['photo' => $file2]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/equipments/{$this->equipment->id}/photos/reorder", [
                'photo_ids' => [$second->json('id'), $first->json('id')],
            ]);

        $response->assertStatus(200);
        $this->assertEquals($second->json('id'), $response->json('0.id'));
        $this->assertEquals($first->json('id'), $response->json('1.id'));
    }
}
