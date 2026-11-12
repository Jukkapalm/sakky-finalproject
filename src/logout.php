<?php

// Starts the session so its data and session cookie can be accessed
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Menetelmä ei ole sallittu.');
}

// Remove all values stored in the current session
$_SESSION = [];

// If the session uses a browser cookie, expire that cookie
if (ini_get('session.use_cookies')) {

    // Read the settings used for the current sesson cookie
    $cookieParameters = session_get_cookie_params();

    // Send an expired cookie with the same settings to remove it from the browser
    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $cookieParameters['path'],
            'domain' => $cookieParameters['domain'],
            'secure' => $cookieParameters['secure'],
            'httponly' => $cookieParameters['httponly'],
            'samesite' => $cookieParameters['samesite'],
        ]
    );
}

// Destroy the session data stored on the server
session_destroy();

// Redirct the user to the home page after logging out
header('Location: index.php');
exit;