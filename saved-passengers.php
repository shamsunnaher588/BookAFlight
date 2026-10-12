<?php
/**
 * BookAFlight - Saved Passenger Profiles
 * Add, edit, delete and list saved passengers.
 *
 * Uses existing db.php, PDO, login and CSRF.
 */

require_once __DIR__ . '/db.php';
require_login();

header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

function spEsc($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function spText($value): string {
    return is_string($value) ? trim($value) : '';
}

function spChars(string $value): int {
    return preg_match_all('/./us', $value, $matches) ?: 0;
}

function spDobValid(string $value): bool {
    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d', $value
    );

    if (!$date || $date->format('Y-m-d') !== $value) {
        return false;
    }

    $now = new DateTimeImmutable('today');

    return $date <= $now &&
           $date >= $now->modify('-120 years');
}

$userId = (int)$_SESSION['user_id'];

$errors = [];

$edit = [
    'id' => 0,
    'first_name' => '',
    'last_name' => '',
    'date_of_birth' => ''
];

/* SAVE, UPDATE AND DELETE */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = spText($_POST['action'] ?? null);

    $id = filter_var(
        $_POST['id'] ?? 0,
        FILTER_VALIDATE_INT
    );

    $id = ($id !== false && $id > 0)
        ? (int)$id
        : 0;

    if (!valid_csrf($_POST['csrf_token'] ?? null)) {

        $errors[] = 'Security check failed. Refresh and try again.';

    } elseif (!in_array(
        $action, ['save', 'delete'], true
    )) {

        $errors[] = 'Invalid request.';

    } elseif ($action === 'delete') {

        if ($id === 0) {

            $errors[] = 'Select a passenger to delete.';

        } else {

            $stmt = $pdo->prepare(
                'DELETE FROM saved_passengers
                 WHERE id = ? AND user_id = ?'
            );

            $stmt->execute([$id, $userId]);

            header(
                'Location: saved-passengers.php?result=deleted'
            );
            exit;
        }

    } else {

        $first = spText(
            $_POST['first_name'] ?? null
        );

        $last = spText(
            $_POST['last_name'] ?? null
        );

        $dob = spText(
            $_POST['date_of_birth'] ?? null
        );

        $edit = [
            'id' => $id,
            'first_name' => $first,
            'last_name' => $last,
            'date_of_birth' => $dob
        ];

        foreach ([$first, $last] as $name) {

            $len = spChars($name);

            if (
                $len < 2 ||
                $len > 120 ||
                preg_match('/[\x00-\x1F\x7F]/u', $name)
            ) {

                $errors[] =
                    'Enter valid names (2-120 characters).';

                break;
            }
        }

        if (!spDobValid($dob)) {

            $errors[] =
                'Enter a valid date of birth.';

        }

        if (!$errors) {

            if ($id > 0) {

                /* UPDATE */

                $stmt = $pdo->prepare(
                    'UPDATE saved_passengers
                     SET first_name = ?,
                         last_name = ?,
                         date_of_birth = ?
                     WHERE id = ? AND user_id = ?'
                );

                $stmt->execute([
                    $first,
                    $last,
                    $dob,
                    $id,
                    $userId
                ]);

                if ($stmt->rowCount() === 0) {

                    $verify = $pdo->prepare(
                        'SELECT id
                         FROM saved_passengers
                         WHERE id = ? AND user_id = ?'
                    );

                    $verify->execute([$id, $userId]);

                    if (!$verify->fetchColumn()) {
                        $errors[] = 'Passenger not found.';
                    }
                }

            } else {

                /* CREATE */

                $countStmt = $pdo->prepare(
                    'SELECT COUNT(*)
                     FROM saved_passengers
                     WHERE user_id = ?'
                );

                $countStmt->execute([$userId]);

                if (
                    (int)$countStmt->fetchColumn() >= 50
                ) {

                    $errors[] =
                        'Maximum 50 saved passengers per account.';

                } else {

                    $stmt = $pdo->prepare(
                        'INSERT INTO saved_passengers
                         (user_id, first_name, last_name,
                          date_of_birth)
                         VALUES (?, ?, ?, ?)'
                    );

                    $stmt->execute([
                        $userId,
                        $first,
                        $last,
                        $dob
                    ]);
                }
            }

            if (!$errors) {

                header(
                    'Location: saved-passengers.php?result=saved'
                );

                exit;
            }
        }
    }
}

/* LOAD PASSENGER FOR EDITING */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    $id = filter_var(
        $_GET['edit'] ?? 0,
        FILTER_VALIDATE_INT
    );

    if ($id !== false && $id > 0) {

        $stmt = $pdo->prepare(
            'SELECT id, first_name, last_name,
                    date_of_birth
             FROM saved_passengers
             WHERE id = ? AND user_id = ?
             LIMIT 1'
        );

        $stmt->execute([
            (int)$id,
            $userId
        ]);

        $found = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($found) {
            $edit = $found;
        }
    }
}

/* LOAD CURRENT USER'S PASSENGERS */

$listStmt = $pdo->prepare(
    'SELECT id, first_name, last_name,
            date_of_birth
     FROM saved_passengers
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 50'
);

$listStmt->execute([$userId]);

$passengers = $listStmt->fetchAll(
    PDO::FETCH_ASSOC
);

$result = spText($_GET['result'] ?? null);

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Saved Passengers | BookAFlight</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f3f7fc;
    color: #183352;
    font: 15px/1.6 Arial, sans-serif;
}

header {
    background: #092a57;
    color: white;
    padding: 18px 5%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
}

header a {
    color: white;
    text-decoration: none;
}

main {
    max-width: 1020px;
    margin: 28px auto;
    padding: 0 16px;
}

h1 {
    font-size: 32px;
    margin: 0 0 8px;
}

h2 {
    margin: 0 0 14px;
    font-size: 21px;
}

.muted {
    color: #66798e;
    font-size: 14px;
}

.layout {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 18px;
    align-items: start;
}

.card {
    background: white;
    border: 1px solid #dbe6f1;
    border-radius: 15px;
    padding: 22px;
    box-shadow: 0 6px 22px #102c4d0a;
}

label {
    display: block;
    font-size: 14px;
    font-weight: bold;
    margin: 12px 0;
    color: #344d6a;
}

input {
    display: block;
    width: 100%;
    margin-top: 6px;
    padding: 12px;
    border-radius: 9px;
    border: 1px solid #b6cbdd;
    font: inherit;
    color: #122c48;
}

button,
.btn {
    display: inline-block;
    border: 0;
    border-radius: 9px;
    background: #ffbf4c;
    color: #092a57;
    text-decoration: none;
    font: bold 14px Arial, sans-serif;
    padding: 11px 15px;
    cursor: pointer;
}

.btn-secondary {
    background: #e8f2fb;
}

.btn-danger {
    background: #ffe4e4;
    color: #902727;
}

.actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

.person {
    padding: 14px 0;
    border-bottom: 1px solid #e6edf5;
    display: flex;
    justify-content: space-between;
    gap: 14px;
    align-items: center;
    flex-wrap: wrap;
}

.person:last-child {
    border-bottom: 0;
}

.person strong {
    display: block;
}

.person small {
    color: #60728a;
}

.info {
    background: #fff4d9;
    color: #70511b;
    padding: 14px 18px;
    border-radius: 9px;
    margin: 14px 0;
}

.error {
    background: #ffe7e7;
    color: #882b2b;
    padding: 14px;
    border-radius: 9px;
    margin: 14px 0;
}

.ok {
    background: #e2f5e9;
    color: #166534;
    padding: 14px;
    border-radius: 9px;
    margin: 14px 0;
}

@media(max-width:700px) {

    .layout {
        grid-template-columns: 1fr;
    }

    .card {
        padding: 16px;
    }
}
</style>

</head>

<body>

<header>

    <strong>✈ BookAFlight · My Travelers</strong>

    <nav>
        <a href="index.php">Home</a>
        ·
        <a href="my-bookings.php">My Bookings</a>
    </nav>

</header>

<main>

    <h1>Saved Passenger Information</h1>

    <p class="muted">
        Save traveler details for faster entry
        into future demo flight bookings.
    </p>

    <p class="info">
        For this local demo, use test information.
        Do not store real passport, national ID or
        payment details here. Saving a passenger
        does not reserve an airline ticket.
    </p>

    <?php if ($errors): ?>

        <div class="error" role="alert">

            <?php foreach ($errors as $message): ?>

                <p>
                    <?= spEsc($message) ?>
                </p>

            <?php endforeach; ?>

        </div>

    <?php elseif ($result === 'saved'): ?>

        <p class="ok">
            ✅ Passenger information saved successfully.
        </p>

    <?php elseif ($result === 'deleted'): ?>

        <p class="ok">
            ✅ Passenger removed.
            Existing bookings are unchanged.
        </p>

    <?php endif; ?>

    <div class="layout">

        <!-- ADD / EDIT FORM -->

        <section class="card">

            <h2>
                <?= $edit['id']
                    ? 'Edit Passenger'
                    : 'Add New Passenger' ?>
            </h2>

            <form
                action="saved-passengers.php"
                method="POST"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= spEsc(csrf_token()) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="save"
                >

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int)$edit['id'] ?>"
                >

                <label>
                    First Name

                    <input
                        type="text"
                        name="first_name"
                        required
                        minlength="2"
                        maxlength="120"
                        autocomplete="off"
                        value="<?= spEsc($edit['first_name']) ?>"
                    >
                </label>

                <label>
                    Last Name

                    <input
                        type="text"
                        name="last_name"
                        required
                        minlength="2"
                        maxlength="120"
                        autocomplete="off"
                        value="<?= spEsc($edit['last_name']) ?>"
                    >
                </label>

                <label>
                    Date of Birth

                    <input
                        type="date"
                        name="date_of_birth"
                        required
                        max="<?= date('Y-m-d') ?>"
                        value="<?= spEsc($edit['date_of_birth']) ?>"
                    >
                </label>

                <div class="actions">

                    <button type="submit">

                        💾 <?= $edit['id']
                            ? 'Update Passenger'
                            : 'Save Passenger' ?>

                    </button>

                    <?php if ($edit['id']): ?>

                        <a
                            href="saved-passengers.php"
                            class="btn btn-secondary"
                        >
                            Cancel Edit
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </section>

        <!-- SAVED PASSENGERS LIST -->

        <section class="card">

            <h2>
                My Saved Passengers
                (<?= count($passengers) ?>)
            </h2>

            <?php if (!$passengers): ?>

                <p class="muted">
                    No passengers saved yet.
                    Add your first traveler.
                </p>

            <?php else: ?>

                <?php foreach ($passengers as $person): ?>

                    <div class="person">

                        <div>

                            <strong>
                                <?= spEsc(
                                    $person['first_name'] . ' ' .
                                    $person['last_name']
                                ) ?>
                            </strong>

                            <small>
                                Date of Birth:
                                <?= spEsc(
                                    $person['date_of_birth']
                                ) ?>
                            </small>

                        </div>

                        <div class="actions">

                            <a
                                class="btn btn-secondary"
                                href="saved-passengers.php?edit=<?= (int)$person['id'] ?>"
                            >
                                Edit
                            </a>

                            <form
                                action="saved-passengers.php"
                                method="POST"
                                onsubmit="return confirm('Delete this saved passenger? Existing bookings will not be deleted.');"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= spEsc(csrf_token()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="delete"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int)$person['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn-danger"
                                >
                                    Delete
                                </button>

                            </form>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </div>

</main>

</body>
</html>