<?php

// Starts the PHP session, so this page can read and store login information
session_start();

// If the session contains a user ID, and the user is already logged in
// redirect them to the home page instead of showing the login form
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Loads database connection settngs
require_once __DIR__ . '/db/db.php';

// Variable stores an error message to show if login fails
$error = '';

// Variable keeps submitted username or email available for the form
$identifier = '';

// Process the form only, if request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read the username or email and password from the submitted form
    // Use an empty string if either field is was not submitted
    $identifierInput = $_POST['identifier'] ?? '';
    $password = $_POST['password'] ?? '';

    // Checking that both submitted values are strings
    if (!is_string($identifierInput) || !is_string($password)) {
        $error = 'Lomakkeella lähetettiin virheellisiä tietoja.';
    } else {
        $identifier = trim($identifierInput);

        // Both the indentifier and password are required, cannot send empty fields
        if ($identifier === '' || $password === '') {
            $error = 'Täytä molemmat kentät.';
        } else {

            // Connect to the database
            $pdo = databaseConnection();

            // Find the user by username or email address
            // Prepared statement passes the submitted value as a parameter
            $statement = $pdo->prepare(
                'SELECT user_id, username, password, role FROM users WHERE username = :username OR email = :email LIMIT 1'
            );

            // Assing the identifier obtained from the form to the query parameters
            $statement->execute([
                'username' => $identifier,
                'email' => $identifier
            ]);

            // Fetch the user details
            // fetch() function returns false if no matching user is found
            $user = $statement->fetch();

            // Check that the user exists and that the submitted password
            // matches the password hash stored in the database
            if ($user !== false && password_verify($password, $user['password'])) {

                // Generate a new session ID after the successful login
                session_regenerate_id(true);

                // Store the user's ID and information needed by the site in the session
                // Never store the password or its hash in the session
                $_SESSION['user_id'] = (INT) $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Login succeeded, redirect to the home page
                header('Location: index.php');
                exit;
            }

            // Use the same message if the user was not found or the password was incorrect
            $error = 'Virheellinen käyttäjätunnus tai salasana.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Kirjaudu sisään</title>
    </head>
    <body>
    
        <h1>Kirjaudu Sisään</h1>

        <!-- Show an error message only if login failed -->
        <?php if ($error !== ''): ?>
            <p role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <!-- Submit the login details to this same file for processing -->
        <form action="login.php" method="post">
            <label for="identifier">Käyttäjätunnus tai sähköpostiosoite</label>

            <!-- Transforms user input more safety form, before printing it in HTML attribute, if loggin in failed -->
            <input type="text" id="identifier" name="identifier" required value="<?= htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8') ?>">

            <label for="password">Salasana</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Kirjaudu sisään</button>
        </form>

    </body>
</html>
