<?php

it('planifie le nettoyage des vues de pages chaque semaine', function () {
    expect(scheduledCron('page-views:prune'))->toBe('0 0 * * 0');
});
