<?php

session_start();
require_once __DIR__ . '/db/db.php';

// Only redirect back to pages that display the event list.
$returnToInput = $_POST['return_to'] ?? '';
$returnTo = is_string($returnToInput) && in_array(
    $returnToInput,
    ['index.php', 'events.php', 'my_events.php'],
    true
)
    ? $returnToInput
    : 'events.php';

// Store a one-time message before returning to the event list.
$redirectWithMessage = static function (string $message) use ($returnTo): void {
    $_SESSION['registration_flash'] = ['message' => $message];
    header('Location: ' . $returnTo);
    exit;
};

// Registration changes database state, so only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Tätä toimintoa ei voi suorittaa tällä menetelmällä.');
}

// Require a valid logged-in user.
$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
if ($userId === false || $userId < 1) {
    $redirectWithMessage('Kirjaudu sisään muuttaaksesi ilmoittautumistasi.');
}

// Existing forms without an action continue to register by default.
$actionInput = $_POST['action'] ?? 'register';
$action = is_string($actionInput) && in_array($actionInput, ['register', 'cancel'], true)
    ? $actionInput
    : '';

if ($action === '') {
    $redirectWithMessage('Virheellinen toiminto.');
}

// Validate the submitted event ID before using it in a database query.
$eventIdInput = $_POST['event_id'] ?? '';
$eventId = is_string($eventIdInput)
    ? filter_var($eventIdInput, FILTER_VALIDATE_INT)
    : false;

if ($eventId === false || $eventId < 1) {
    $redirectWithMessage('Virheellinen tapahtuma.');
}

$pdo = databaseConnection();
$pdo->beginTransaction();

try {
    // Lock the event row so simultaneous registrations cannot exceed capacity.
    $eventStatement = $pdo->prepare(
        'SELECT max_participants
        FROM events
        WHERE event_id = :event_id AND event_date >= NOW()
        FOR UPDATE'
    );
    $eventStatement->execute(['event_id' => $eventId]);
    $event = $eventStatement->fetch();

    if ($event === false) {
        $pdo->rollBack();
        $redirectWithMessage('Tapahtumaa ei löytynyt tai se on jo mennyt.');
    }

    // Check whether this user has already registered for this event.
    $existingStatement = $pdo->prepare(
        'SELECT registration_id
        FROM registrations
        WHERE user_id = :user_id AND event_id = :event_id'
    );
    $existingStatement->execute([
        'user_id' => $userId,
        'event_id' => $eventId,
    ]);

    if ($action === 'cancel') {
        if ($existingStatement->fetch() === false) {
            $pdo->rollBack();
            $redirectWithMessage('Et ole ilmoittautunut tähän tapahtumaan.');
        }

        $deleteStatement = $pdo->prepare(
            'DELETE FROM registrations
            WHERE user_id = :user_id AND event_id = :event_id'
        );
        $deleteStatement->execute([
            'user_id' => $userId,
            'event_id' => $eventId,
        ]);

        $pdo->commit();
        $redirectWithMessage('Ilmoittautuminen peruttiin.');
    }

    if ($existingStatement->fetch() !== false) {
        $pdo->rollBack();
        $redirectWithMessage('Olet jo ilmoittautunut tähän tapahtumaan.');
    }

    // Count current registrations while the event row is locked.
    $countStatement = $pdo->prepare(
        'SELECT COUNT(*)
        FROM registrations
        WHERE event_id = :event_id'
    );
    $countStatement->execute(['event_id' => $eventId]);
    $participantCount = (int) $countStatement->fetchColumn();

    if ($participantCount >= (int) $event['max_participants']) {
        $pdo->rollBack();
        $redirectWithMessage('Tapahtuma on täynnä.');
    }

    // Insert the registration after all checks have passed.
    $insertStatement = $pdo->prepare(
        'INSERT INTO registrations (user_id, event_id)
        VALUES (:user_id, :event_id)'
    );
    $insertStatement->execute([
        'user_id' => $userId,
        'event_id' => $eventId,
    ]);

    $pdo->commit();
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // The database unique constraint is a final safeguard against duplicate registrations.
    if (($exception->errorInfo[1] ?? null) === 1062) {
        $redirectWithMessage('Olet jo ilmoittautunut tähän tapahtumaan.');
    }

    throw $exception;
}

$redirectWithMessage('Ilmoittautuminen onnistui.');
