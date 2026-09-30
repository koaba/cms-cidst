<?php

use App\Models\Page;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

it('affiche le formulaire de creation de chaque type de bloc', function () {
    $page = Page::factory()->create();
    $echecs = [];

    foreach (array_keys(config('page_blocks.types')) as $type) {
        $status = $this->actingAs($this->admin)
            ->get(route('admin.pages.blocks.create', [$page, $type]))
            ->status();

        if ($status !== 200) {
            $echecs[$type] = $status;
        }
    }

    expect($echecs)->toBe([]);
});