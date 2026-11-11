<?php

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/db/db.php';

$error = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifierInput = $_POST['identifier'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!is_string($identifierInput) || !is_string($password)) {
        $error = 'Lomakkeella lähetettiin virheellisiä tietoja.';
    } else {
        $identifier = trim($identifierInput);

        if ($identifier === '' || $password === '') {
            $error = 'Täytä molemmat kentät.';
        } else {
            $pdo = databaseConnection();
            $statement = $pdo->prepare(
                'SELECT user_id, username, password, role FROM users WHERE username = :username OR email = :email LIMIT 1'
            );
            $statement->execute([
                'username' => $identifier,
                'email' => $identifier
            ]);

            $user = $statement->fetch();

            if ($user !== false && password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = (INT) $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                header('Location: index.php');
                exit;
            }

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

        <?php if ($error !== ''): ?>
            <p role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form action="login.php" method="post">
            <label for="identiefier">Käyttäjätunnus</label>
            <input type="text" id="identifier" name="identifier" required>

            <label for="password">Salasana</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Kirjaudu sisään</button>
        </form>

    </body>
</html>
