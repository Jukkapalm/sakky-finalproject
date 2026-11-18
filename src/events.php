<?php

// Start the session so the navigation can reflect the user's login status.
session_start();

// Loading database settings that create a database connection
require_once __DIR__ . '/db/db.php';

// Check whether a user ID is stored in the session.
$isLoggedIn = isset($_SESSION['user_id']);

// Connect to the database and get all events in date order.
$pdo = databaseConnection();
$currentUserId = $isLoggedIn ? (int) $_SESSION['user_id'] : 0;

$statement = $pdo->prepare(
    'SELECT e.event_id, e.title, e.description, e.city, e.event_date,
            e.duration_minutes, e.max_participants,
            COUNT(r.registration_id) AS participant_count,
            MAX(CASE WHEN r.user_id = :user_id THEN 1 ELSE 0 END) AS is_registered
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.event_id
    WHERE e.event_date >= NOW()
    GROUP BY e.event_id, e.title, e.description, e.city, e.event_date,
             e.duration_minutes, e.max_participants
    ORDER BY e.event_date ASC'
);
$statement->execute(['user_id' => $currentUserId]);

// Fetch the event rows as an array for the page to display.
$events = $statement->fetchAll();

$registrationFlash = $_SESSION['registration_flash'] ?? null;
unset($_SESSION['registration_flash']);
?>

<!DOCTYPE html>
<html lang="fi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Kaikki tapahtumat</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="css/styles.css">
    </head>
    <body>

        <nav class="navbar navbar-expand-lg fixed-top">
            <div class="container-fluid">
                <a class="navbar-brand">
                    <h1>Ikäihmisten yhteisöpalvelu</h1>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="collapsibleNavbar">
                    <ul class="navbar-nav ms-auto align-items-lg-center text-center">
                        <li class="nav-item"><a class="nav-link" href="index.php">Etusivu</a></li>

                        <!-- If user logged in, show these links  -->
                        <?php if ($isLoggedIn): ?>
                            <li class="nav-item"><a class="nav-link" href="my_events.php">Omat tapahtumat</a></li>
                            <li class="nav-item">
                                <form action="logout.php" method="post">
                                    <button type="submit" class="nav-link border-0 bg-transparent">Kirjaudu ulos</button>
                                </form>
                            </li>

                        <!-- If not logged in, show these links -->
                        <!-- Condition needed, if want to show these links only if not logged in -->    
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="login.php">Kirjaudu sisään</a></li>
                            <li class="nav-item"><a class="nav-link" href="register.php">Rekisteröidy</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

            </div>
        </nav>

        <main class="container mt-5 py-5">
            <h1>Kaikki tapahtumat</h1>

            <?php if (is_array($registrationFlash) && isset($registrationFlash['message'])): ?>
                <p role="status">
                    <?= htmlspecialchars((string) $registrationFlash['message'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <!-- Show a message when the database contains no events. -->
            <?php if ($events === []): ?>
                <p>Tapahtumia ei löytynyt.</p>
            <?php else: ?>
                <div class="row g-3">

                    <!-- Create event cards -->
                    <?php foreach ($events as $event): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <article class="card event-card p-3 h-100">
                                <h2>
                                    <?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?>
                                </h2>
                                <p>
                                    <?= htmlspecialchars($event['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <p>
                                    Paikka:
                                    <?= htmlspecialchars($event['city'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <p>
                                    Aika:
                                    <?= htmlspecialchars(
                                        (new DateTimeImmutable($event['event_date']))->format('d.m.Y H:i'),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>
                                <p>Kesto: <?= (int) $event['duration_minutes'] ?> min</p>
                                <p class="person-number">
                                    Osallistujat:
                                    <?= (int) $event['participant_count'] ?>/<?= (int) $event['max_participants'] ?>
                                </p>
                                <?php if (!$isLoggedIn): ?>
                                    <a href="login.php">Kirjaudu sisään ilmoittautuaksesi</a>
                                <?php elseif ((int) $event['is_registered'] === 1): ?>
                                    <form action="register_event.php" method="post">
                                        <input type="hidden" name="event_id" value="<?= (int) $event['event_id'] ?>">
                                        <input type="hidden" name="return_to" value="events.php">
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit" class="btn btn-outline-danger">Peru ilmoittautuminen</button>
                                    </form>
                                <?php elseif ((int) $event['participant_count'] >= (int) $event['max_participants']): ?>
                                    <button type="button" class="btn btn-secondary" disabled>
                                        Ilmoittautuminen täynnä
                                    </button>
                                <?php else: ?>
                                    <form action="register_event.php" method="post">
                                        <input type="hidden" name="event_id" value="<?= (int) $event['event_id'] ?>">
                                        <input type="hidden" name="return_to" value="events.php">
                                        <button type="submit" class="btn btn-primary">Ilmoittaudu</button>
                                    </form>
                                <?php endif; ?>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>

    </body>
</html>