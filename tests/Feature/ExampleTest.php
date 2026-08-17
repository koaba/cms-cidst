<?php

it('retourne une réponse réussie', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
