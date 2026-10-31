<?php

// Database connection settings
// Define create constanst
// Constanst use uppercase letters
define('DB_HOST', 'db');
define('DB_NAME', 'testdb');
define('DB_USER', 'user');
define('DB_PASS', 'pass');
define('DB_CHARSET', 'utf8mb4');


// Function means that database connection opens only when PHP code calling it
// Normally without a function connection opens right away when PHP site run "require_once"
function databaseConnection(): PDO {

    // DSN tells to the PDO database type, server, databse name and charset to be used
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    // These settings determines PDO connection activity
    $settings = [
        // Database error is reported with an exception
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

        // The fetched rows are returned as an array
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // Using MySQL server own prepared statement queries
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    // Creating connection and returns it to the function caller
    // If can't create connection, PDO throws exception 
    return new PDO($dsn, DB_USER, DB_PASS, $settings);


}