<?php

it('planifie le nettoyage des vues de pages', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('page-views:prune')
        ->assertSuccessful();
});
