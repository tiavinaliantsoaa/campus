<?php

test('guests are redirected to the login page', function () {
    $this->get('/')->assertRedirect(route('login'));
});
