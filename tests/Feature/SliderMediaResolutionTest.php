<?php

use App\Models\Media;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function actingAsAdminSliderFix(): User
{
    if (! Role::where('name', 'Super Admin')->exists()) {
        Role::create(['name' => 'Super Admin']);
    }
    $user = User::factory()->create();
    $user->assignRole('Super Admin');
    return $user;
}

it('cree un slider via upload direct sans passer par existing_media_id', function () {
    Storage::fake('public');
    $user = actingAsAdminSliderFix();
    $file = UploadedFile::fake()->image('slide.jpg');

    $response = $this->actingAs($user)->post('/admin/sliders', [
        'title' => 'Slider test',
        'image' => $file,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.sliders.index'));
    $this->assertDatabaseHas('sliders', ['title' => 'Slider test']);
});