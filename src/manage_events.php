<?php

session_start();

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Tälle sivulle on pääsy vain ylläpitäjällä.');
}

require_once __DIR__ . '/db/db.php';

if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$pdo = databaseConnection();
$statement = $pdo->query(
    'SELECT e.event_id, e.title, e.city, e.event_date, e.max_participants,
            COUNT(r.registration_id) AS participant_count
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.event_id
    GROUP BY e.event_id, e.title, e.city, e.event_date, e.max_participants
    ORDER BY e.event_date ASC'
);
$events = $statement->fetchAll();

$message = '';
if (($_GET['updated'] ?? '') === '1') {
    $message = 'Tapahtuman tiedot tallennettiin.';
} elseif (($_GET['deleted'] ?? '') === '1') {
    $message = 'Tapahtuma ja sen ilmoittautumiset poistettiin.';
} elseif (($_GET['error'] ?? '') === 'not_found') {
    $message = 'Tapahtumaa ei löytynyt tai se on jo poistettu.';
}
?>

<!DOCTYPE html>
<html lang="fi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Tapahtumien hallinta</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="css/styles.css">
    </head>
    <body>
        <main class="container mt-5 py-5">
            <h1>Tapahtumien hallinta</h1>
            <p>
                <a href="index.php">Takaisin etusivulle</a>
                <a href="add_event.php" class="btn btn-primary ms-3">Lisää tapahtuma</a>
            </p>

            <?php if ($message !== ''): ?>
                <p role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <?php if ($events === []): ?>
                <p>Tapahtumia ei löytynyt.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Tapahtuma</th>
                                <th scope="col">Paikka</th>
                                <th scope="col">Aika</th>
                                <th scope="col">Osallistujat</th>
                                <th scope="col">Toiminnot</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($event['city'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?= htmlspecialchars(
                                            (new DateTimeImmutable($event['event_date']))->format('d.m.Y H:i'),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>
                                    <td>
                                        <?= (int) $event['participant_count'] ?>/<?= (int) $event['max_participants'] ?>
                                    </td>
                                    <td class="d-flex gap-2">
                                        <a
                                            href="add_event.php?event_id=<?= (int) $event['event_id'] ?>"
                                            class="btn btn-secondary btn-sm"
                                        >Muokkaa</a>
                                        <form
                                            action="delete_event.php"
                                            method="post"
                                            onsubmit="return confirm('Haluatko varmasti poistaa tapahtuman ja sen ilmoittautumiset?')"
                                        >
                                            <input type="hidden" name="event_id" value="<?= (int) $event['event_id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Poista</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </body>
</html>
