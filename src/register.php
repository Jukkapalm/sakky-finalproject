<?php

// Loading database connection settings
// Require_once doing that only once per request
require_once __DIR__ . '/db/db.php';

// Initializing messages and form values
$error = '';
$username = '';
$email = '';

// Checking if the form has been sent and method is post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Reading values from the form
    $usernameInput = $_POST['username'] ?? '';
    $emailInput = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';

    // Check that inputs is the form of string
    if (
        !is_string($usernameInput) || !is_string($emailInput) || !is_string($password) || !is_string($passwordConfirmation)
    ) {
        $error = 'Lomakkeella lähetettiin virheellisiä tietoja.';
    } else {

    // Remove the spaces from the beginning and end
        $username = trim($usernameInput);
        $email = trim($emailInput);

        // Server check that all fields is filled
        if ($username === '' || $email === '' || $password === '' || $passwordConfirmation === '') {
            $error = 'Täytä kaikki kentät';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Tarkista sähköpostiosoite.';
        } elseif ($password !== $passwordConfirmation) {
            $error = 'Salasanat eivät täsmää.';
        }
    }

    // Only continue to saving database if no error messages
    if ($error === '') {
        try {
            // Open database connection
            $pdo = databaseConnection();

            // Password hashing
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Preparing SQL statement
            // Names which starts with colon, is parameters
            $statement = $pdo->prepare(
                'INSERT INTO users (username, email, password)
                VALUES (:username, :email, :password)'
            );

            // Give to prepared query a values
            $statement->execute([
                'username' => $username,
                'email' => $email,
                'password' => $passwordHash,
            ]);

            // Redirect to the login page after successful registration.
            header('Location: login.php');
            exit;

            // PDOExceprion tells the error with database
        } catch (PDOException $exception) {

        // SQLSTATE 23000 means an error related to database integrity
            if ($exception->getCode() === '23000') {
                $error = 'Käyttäjänimi tai sähköpostiosoite on jo käytössä.';
            } else {
                throw $exception;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Rekisteröidy</title>
    </head>
    <body>
        <h1>Rekisteröidy</h1>

        <!-- Only show error message if $error is not empty -->
        <?php if ($error !== ''): ?>
            <p role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form action="register.php" method="post">
            <label for="username">Käyttäjänimi</label>
            <input type="text" id="username" name="username" required value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>">

            <label for="email">Sähköposti</label>
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">

            <label for="password">Salasana</label>
            <input type="password" id="password" name="password" autocomplete="new-password" required>

            <label for="password_confirmation">Vahvista salasana</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>

            <button type="submit">Rekisteröidy</button>
        </form>
    </body>
</html>