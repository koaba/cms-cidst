<?php

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('ne requete site_settings qu une seule fois pour une page publique', function () {
    SiteSetting::create([]);

    $count = 0;
    DB::listen(function ($query) use (&$count) {
        if (str_contains($query->sql, 'site_settings')) {
            $count++;
        }
    });

    $this->get('/')->assertOk();

    expect($count)->toBe(1);
});
