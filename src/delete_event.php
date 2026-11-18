<?php

session_start();

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Tälle toiminnolle on pääsy vain ylläpitäjällä.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Tätä toimintoa ei voi suorittaa tällä menetelmällä.');
}

$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';
if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    !hash_equals($sessionToken, $submittedToken)
) {
    http_response_code(403);
    exit('Virheellinen lomaketunniste.');
}

$eventIdInput = $_POST['event_id'] ?? '';
$eventId = is_string($eventIdInput)
    ? filter_var($eventIdInput, FILTER_VALIDATE_INT)
    : false;

if ($eventId === false || $eventId < 1) {
    http_response_code(400);
    exit('Virheellinen tapahtuma.');
}

require_once __DIR__ . '/db/db.php';
$pdo = databaseConnection();
$pdo->beginTransaction();

try {
    $eventStatement = $pdo->prepare(
        'SELECT event_id
        FROM events
        WHERE event_id = :event_id
        FOR UPDATE'
    );
    $eventStatement->execute(['event_id' => $eventId]);

    if ($eventStatement->fetch() === false) {
        $pdo->rollBack();
        header('Location: manage_events.php?error=not_found');
        exit;
    }

    $registrationStatement = $pdo->prepare(
        'DELETE FROM registrations
        WHERE event_id = :event_id'
    );
    $registrationStatement->execute(['event_id' => $eventId]);

    $deleteStatement = $pdo->prepare(
        'DELETE FROM events
        WHERE event_id = :event_id'
    );
    $deleteStatement->execute(['event_id' => $eventId]);
    $pdo->commit();
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $exception;
}

header('Location: manage_events.php?deleted=1');
exit;
