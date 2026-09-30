<?php

it('sends guests to the login screen', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
