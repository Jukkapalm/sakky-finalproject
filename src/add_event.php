<?php

// Start the session, this page can read the logged-in user's role
session_start();

// Check permissions on the server, not only by hiding the navigation link.
// Blocking non-admin users who enter the page URL directly.
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Tälle sivulle on pääsy vain ylläpitäjällä.');
}

// Loading database settings
require_once __DIR__ . '/db/db.php';

// Store a validation error
$error = '';

// Form values variables
$title = '';
$description = '';
$city = '';
$eventdate = '';
$duration = '';
$maxParticipants = '';

// Show success message when the browser returns after a successful insert
$success = ($_GET['success'] ?? '') === '1';

// Process the form only when it has been submitted using the POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read each submitted value. Use an empty string if a field is missing
    $titleInput = $_POST['title'] ?? '';
    $descriptionInput = $_POST['description'] ?? '';
    $cityInput = $_POST['city'] ?? '';
    $eventdateInput = $_POST['event_date'] ?? '';
    $durationInput = $_POST['duration_minutes'] ?? '';
    $maxParticipantsInput = $_POST['max_participants'] ?? '';

    // Make sure the submitted values are strings before processing them
    if (
        !is_string($titleInput) ||
        !is_string($descriptionInput) ||
        !is_string($cityInput) ||
        !is_string($eventdateInput) ||
        !is_string($durationInput) ||
        !is_string($maxParticipantsInput)
    ) {
        $error = 'Lomakkeella lähetettiin virheellisiä tietoja.';
    } else {

        // Removing whitespaces
        $title = trim($titleInput);
        $description = trim($descriptionInput);
        $city = trim($cityInput);
        $eventdate = $eventdateInput;
        $duration = $durationInput;
        $maxParticipants = $maxParticipantsInput;

        // Convert the date and time from the datetime-local field's format
        $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $eventdate);

        // Get any warnings or errors produced while parsing the date and time
        $dateErrors = DateTimeImmutable::getLastErrors();

        // Check that every required field has a value
        if ($title === '' || $city === '' || $eventdate === '' || $duration === '' || $maxParticipants === '') {
            $error = 'Täytä kaikki pakolliset kentät.';

        // Reject dates that could not be parsed, produced warnings, or changed during parsing
        } elseif (
            $date === false ||
            ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) ||
            $date->format('Y-m-d\TH:i') !== $eventdate
        ) {
            $error = 'Tarkista tapahtuman päivämäärä ja aika.';

        // Both numeric values must be valid integers greater than zero
        } elseif (
            filter_var($duration, FILTER_VALIDATE_INT) === false ||
            (int) $duration < 1 ||
            filter_var($maxParticipants, FILTER_VALIDATE_INT) === false ||
            (int) $maxParticipants < 1
        ) {
            $error = 'Keston ja osallistujamäärän pitää olla vähintään 1.';
        } else {

            // The input in valid, connecting to the database
            $pdo = databaseConnection();

            // Preparing SQL statement
            $statement = $pdo->prepare(
                'INSERT INTO events
                    (title, description, city, event_date, duration_minutes, max_participants)
                VALUES
                    (:title, :description, :city, :event_date, :duration_minutes, :max_participants)'
            );

            // Attach the form values to the query's named parameters
            $statement->execute([
                'title' => $title,

                // Empty description stored as a NULL in the database
                'description' => $description === '' ? null : $description,
                'city' => $city,

                // Convert the date to the format expected by the datbase
                'event_date' => $date->format('Y-m-d H:i:s'),

                // Convert the validate numeric values to integers
                'duration_minutes' => (int) $duration,
                'max_participants' => (int) $maxParticipants,
            ]);

            // Redirect after saving
            header('Location: add_event.php?success=1');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Lisää tapahtuma</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="css/styles.css">
    </head>
    <main class="container mt-5 py-5">
        <h1>Lisää tapahtuma</h1>
        <p><a href="index.php">Takaisin etusivulle</a></p>

        <!-- Display a confirmation after the event has been saved -->
        <?php if ($success): ?>
            <p role="status">Tapahtuma lisättiin onnistuneesti.</p>
        <?php endif; ?>

        <!-- Display errors if there are any to show -->
        <?php if ($error !== ''): ?>
            <p role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <!-- Send the form values to this page using a POST request -->
        <form action="add_event.php" method="post">
            <label for="title">Tapahtuman nimi</label>
            <input type="text" id="title" name="title" maxlength="100" required value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">

            <label for="description">Kuvaus</label>
            <textarea id="description" name="description"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>

            <label for="city">Kaupunki</label>
            <input type="text" id="city" name="city" maxlength="100" required value="<?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>">

            <label for="event_date">Päivämäärä ja aika</label>
            <input type="datetime-local" id="event_date" name="event_date" required value="<?= htmlspecialchars($eventdate, ENT_QUOTES, 'UTF-8') ?>">

            <label for="duration_minutes">Kesto minuutteina</label>
            <input type="number" id="duration_minutes" name="duration_minutes" min="1" required value="<?= htmlspecialchars($duration, ENT_QUOTES, 'UTF-8') ?>">

            <label for="max_participants">Enimmäisosallistujat</label>
            <input type="number" id="max_participants" name="max_participants" min="1" required value="<?= htmlspecialchars($maxParticipants, ENT_QUOTES, 'UTF-8') ?>">

            <button type="submit">Lisää tapahtuma</button>
        </form>
    </main>
</html>