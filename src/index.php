<?php

// Starts or resume the session, so this page can check the user's login status
session_start();

// Check whether a user ID is stored in the session
// This determines which navigation links are shown
$isLoggedIn = isset($_SESSION['user_id']);

// Loading database settings
require_once __DIR__ . '/db/db.php';

// Connecting to the database
$pdo = databaseConnection();
$currentUserId = $isLoggedIn ? (int) $_SESSION['user_id'] : 0;

$eventStatement = $pdo->prepare(
        'SELECT e.event_id, e.title, e.description, e.city, e.event_date,
            e.max_participants, COUNT(r.registration_id) AS participant_count,
            MAX(CASE WHEN r.user_id = :user_id THEN 1 ELSE 0 END) AS is_registered
        FROM events e
        LEFT JOIN registrations r ON r.event_id = e.event_id
        WHERE e.event_date >= NOW()
        GROUP BY e.event_id, e.title, e.description, e.city, e.event_date, e.max_participants
        ORDER BY e.event_date ASC
        LIMIT 4'
);

$eventStatement->execute(['user_id' => $currentUserId]);
$upcomingEvents = $eventStatement->fetchAll();

$registrationFlash = $_SESSION['registration_flash'] ?? null;
unset($_SESSION['registration_flash']);
?>

<!DOCTYPE html>
<html lang="fi">
    <head>

        <!-- Meta data -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Ikäihmisten yhteisö</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- Own CSS -->
        <link rel="stylesheet" href="css/styles.css">

    </head>
    <body>

        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg fixed-top">
            <div class="container-fluid">

                <!-- Navbar brand -->
                <a class="navbar-brand">
                    <h1>Ikäihmisten<br class="d-sm-none"> Yhteisöpalvelu</h1>
                </a>

                <!-- Hamburger menu -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navigation links -->
                <div class="collapse navbar-collapse" id="collapsibleNavbar">
                    <ul class="navbar-nav ms-auto align-items-lg-center text-center">
                        <li class="nav-item"><a class="nav-link" href="events.php">Kaikki tapahtumat</a></li>

                        <!-- If user role is admin, show add event link -->
                        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="add_event.php">Lisää tapahtuma</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="manage_events.php">Hallitse tapahtumia</a>
                            </li>
                        <?php endif; ?>

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

        <!-- Shared content. Left side about 40% and right side about 60% -->
        <div class="container-fluid">
            <div class="row">

                <!-- Left side -->
                <div class="col-md-5 left-side">
                    <aside class="sidebar">

                        <!-- Left side form/search fields -->
                        <div class="form-side px-3">
                            <h1>Hae tapahtumia</h1>
                            <div class="form-field p-3">
                                <form action="">
                                    <label for="event">Hae tapahtumia tapahtumanimellä</label><br>
                                    <input type="text" id="event" name="event" placeholder="Syötä tapahtuman nimi"><br>
                                    <label for="city">Hae tapahtumia kaupungin perusteella</label><br>
                                    <input type="text" id="city" name="city" placeholder="Syötä kaupunki"><br>
                                    <label for="date">Hae tapahtumia päivämäärän perusteella</label><br>
                                    <input type="date" id="date" name="date"><br>
                                    <input type="submit" value="Hae">
                                    <input type="reset" value="Tyhjennä">
                                </form>
                            </div>
                        </div>

                    </aside>
                </div>

                <!-- Right side -->
                <div class="col-md-7 right-side">
                    <main class="main-content">
                        <div class="event-side">

                            <!-- Incoming events cards -->
                            <h1>Tulevat tapahtumat</h1>

                            <?php if (is_array($registrationFlash) && isset($registrationFlash['message'])): ?>
                                <p role="status">
                                    <?= htmlspecialchars((string) $registrationFlash['message'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            <?php endif; ?>

                            <div class="row g-3 incoming-events">
                                <?php if ($upcomingEvents === []): ?>
                                    <p>Tulevia tapahtumia ei löytynyt.</p>
                                <?php else: ?>
                                    <?php foreach ($upcomingEvents as $event): ?>
                                        <div class="col-12 col-md-6">
                                            <article class="card event-card p-3 h-100">
                                                <h2>
                                                    <?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?>
                                                </h2>
                                                <p class="event-date">
                                                    <?= htmlspecialchars((new DateTimeImmutable($event['event_date']))->format('d.m.Y H:i'), ENT_QUOTES, 'UTF-8') ?>
                                                </p>
                                                <p class="event-desc">
                                                    <?= htmlspecialchars($event['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                                </p>
                                                <p>
                                                    Paikka:
                                                    <?= htmlspecialchars($event['city'], ENT_QUOTES, 'UTF-8') ?>
                                                </p>
                                                <p class="person-number">
                                                    Osallistujat:
                                                    <?= (int) $event['participant_count'] ?>/<?= (int) $event['max_participants'] ?>
                                                </p>
                                                <?php if (!$isLoggedIn): ?>
                                                    <a href="login.php">Kirjaudu sisään ilmoittautuaksesi</a>
                                                <?php elseif ((int) $event['is_registered'] === 1): ?>
                                                    <form action="register_event.php" method="post">
                                                        <input type="hidden" name="event_id" value="<?= (int) $event['event_id'] ?>">
                                                        <input type="hidden" name="return_to" value="index.php">
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
                                                        <input type="hidden" name="return_to" value="index.php">
                                                        <button type="submit" class="btn btn-primary">Ilmoittaudu</button>
                                                    </form>
                                                <?php endif; ?>
                                            </article>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                        </div>
                    </main>
                </div>

            </div>
        </div>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        
    </body>
</html>