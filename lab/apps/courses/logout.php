<?php
// Sign out (POST only, with CSRF, so a link on another site can't sign someone out).
require __DIR__ . '/lib/auth.php';

if (!is_post()) {
    redirect(url(''));
}
check_csrf();
sign_out();
flash('You\'re signed out.');
redirect(url('login.php'));
