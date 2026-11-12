<?php

// Start the session so the navigation can reflect the user's login status.
session_start();

// Loading database settings that create a database connection
require_once __DIR__ . '/db/db.php';

// Check whether a user ID is stored in the session.
$isLoggedIn = isset($_SESSION['user_id']);

// Connect to the database and get all events in date order.
$pdo = databaseConnection();
$statement = $pdo->query(
    'SELECT event_id, title, description, city, event_date,
            duration_minutes, max_participants
    FROM events
    ORDER BY event_date ASC'
);

// Fetch the event rows as an array for the page to display.
$events = $statement->fetchAll();
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
                            <li class="nav-item"><a class="nav-link" href="#">Omat tapahtumat</a></li>
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
                                <p>Enimmäisosallistujat: <?= (int) $event['max_participants'] ?></p>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>

    </body>
</html>