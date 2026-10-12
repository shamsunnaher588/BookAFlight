<?php
// BookAFlight - Favourite Routes
// Saves route templates, not airline reservations.

require_once __DIR__ . '/db.php';
require_login();

function favEsc($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function favText(string $name): string {
    return is_string($_POST[$name] ?? null)
        ? trim($_POST[$name])
        : '';
}

function favValidAirport(string $value): bool {
    return strlen($value) >= 2 &&
           strlen($value) <= 100;
}

$userId = (int)$_SESSION['user_id'];
$error = '';

$cabins = [
    'Economy',
    'Premium Economy',
    'Business',
    'First Class'
];

// Save or delete favourite
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!valid_csrf(
        $_POST['csrf_token'] ?? null
    )) {

        $error =
            'Security session expired. Refresh and try again.';

    } else {

        $action = favText('action');

        if ($action === 'save') {

            $trip = favText('trip');
            $from1 = favText('from1');
            $to1 = favText('to1');

            $from2 = $trip === 'multicity'
                ? favText('from2')
                : '';

            $to2 = $trip === 'multicity'
                ? favText('to2')
                : '';

            $cabin = favText('cabin');
            $passengers = (int)favText('passengers');

            if (!in_array(
                $trip,
                ['oneway','roundtrip','multicity'],
                true
            )) {

                $error = 'Invalid trip type.';

            } elseif (
                !favValidAirport($from1) ||
                !favValidAirport($to1) ||
                strcasecmp($from1, $to1) === 0
            ) {

                $error =
                    'Enter different and valid From/To airports.';

            } elseif (
                $trip === 'multicity' &&
                (
                    !favValidAirport($from2) ||
                    !favValidAirport($to2) ||
                    strcasecmp($from2, $to2) === 0
                )
            ) {

                $error = 'Check the second flight route.';

            } elseif (
                !in_array($cabin, $cabins, true) ||
                $passengers < 1 ||
                $passengers > 6
            ) {

                $error =
                    'Choose 1–6 passengers and a valid cabin.';

            } else {

                // Identify the same route uniquely.
                $identity = array_map(
                    'strtolower',
                    [$trip, $from1, $to1, $from2, $to2]
                );

                $hash = hash(
                    'sha256',
                    json_encode(
                        $identity,
                        JSON_UNESCAPED_UNICODE
                    )
                );

                $stmt = $pdo->prepare(
                    'INSERT INTO favourite_routes
                     (
                        user_id,
                        route_hash,
                        trip_type,
                        from_airport,
                        to_airport,
                        second_from,
                        second_to,
                        cabin,
                        passengers
                     )
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                       cabin = VALUES(cabin),
                       passengers = VALUES(passengers),
                       updated_at = CURRENT_TIMESTAMP'
                );

                $stmt->execute([
                    $userId,
                    $hash,
                    $trip,
                    $from1,
                    $to1,
                    $from2 !== '' ? $from2 : null,
                    $to2 !== '' ? $to2 : null,
                    $cabin,
                    $passengers
                ]);

                header(
                    'Location: favourites.php?saved=1'
                );
                exit;
            }

        } elseif ($action === 'delete') {

            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {

                $error = 'Invalid favourite route.';

            } else {

                $stmt = $pdo->prepare(
                    'DELETE FROM favourite_routes
                     WHERE id = ? AND user_id = ?'
                );

                $stmt->execute([
                    $id,
                    $userId
                ]);

                header(
                    'Location: favourites.php?deleted=1'
                );
                exit;
            }

        } else {

            $error = 'Unknown action.';
        }
    }
}

// Load only the current user's routes.
$stmt = $pdo->prepare(
    'SELECT
        id,
        trip_type,
        from_airport,
        to_airport,
        second_from,
        second_to,
        cabin,
        passengers,
        updated_at
     FROM favourite_routes
     WHERE user_id = ?
     ORDER BY updated_at DESC, id DESC
     LIMIT 100'
);

$stmt->execute([$userId]);

$routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>My Favourite Routes | BookAFlight</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font: 15px Arial, sans-serif;
    background: #f3f7fc;
    color: #172b4d;
}

header {
    background: #082858;
    color: white;
    padding: 20px 6%;
    display: flex;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

header a {
    color: white;
    text-decoration: none;
}

main {
    max-width: 1050px;
    margin: 28px auto;
    padding: 0 15px;
}

.fav-card {
    background: white;
    border-radius: 14px;
    padding: 20px;
    margin: 15px 0;
    box-shadow: 0 4px 18px #0001;
}

.fav-grid {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 13px;
}

.fav-grid label {
    display: block;
    font-weight: bold;
    font-size: 13px;
}

.fav-grid input {
    display: block;
    width: 100%;
    padding: 11px;
    margin-top: 6px;
    border: 1px solid #c6d5e6;
    border-radius: 8px;
    font: inherit;
}

.fav-route {
    font-size: 19px;
    font-weight: bold;
    overflow-wrap: anywhere;
}

.fav-muted {
    color: #687b91;
    font-size: 13px;
    line-height: 1.6;
}

.fav-primary,
.fav-delete {
    border: 0;
    border-radius: 9px;
    padding: 12px 16px;
    cursor: pointer;
    font-weight: bold;
    margin-top: 12px;
}

.fav-primary {
    background: #ffbc43;
    color: #082858;
}

.fav-delete {
    background: #fff1f1;
    color: #a52a2a;
    border: 1px solid #e6b2b2;
}

.fav-notice {
    background: #fff4d8;
    padding: 12px;
    border-radius: 9px;
    margin: 15px 0;
}

.fav-success {
    background: #e2f7eb;
    padding: 12px;
    border-radius: 9px;
}

.fav-error {
    background: #ffe3e3;
    padding: 12px;
    border-radius: 9px;
}

/* Dark Mode */

html[data-baf-theme="dark"] .fav-card {
    background: #162235;
    color: #f1f5fc;
}

html[data-baf-theme="dark"] .fav-muted {
    color: #b4c2d6;
}

html[data-baf-theme="dark"] .fav-grid input {
    background: #101b2d;
    color: white;
    border-color: #47617f;
}

html[data-baf-theme="dark"] .fav-notice {
    background: #413419;
    color: #ffe3ab;
}

/* Mobile */

@media(max-width:650px) {
    .fav-grid {
        grid-template-columns: 1fr;
    }

    .fav-card {
        padding: 15px;
    }

    .fav-route {
        font-size: 17px;
    }
}
</style>

</head>

<body>

<header>
    <strong>
        ✈ BookAFlight — My Favourite Routes
    </strong>

    <a href="index.php">
        ← Search Flights
    </a>
</header>

<main>

<h1>⭐ Saved Favourite Routes</h1>

<p class="fav-notice">
    Save your favourite airport routes once.
    Choose fresh travel dates every time you search.
    Favourites are not airline tickets or reservations.
</p>

<?php if ($error !== ''): ?>

<p class="fav-error">
    <?php echo favEsc($error); ?>
</p>

<?php endif; ?>

<?php if (isset($_GET['saved'])): ?>

<p class="fav-success">
    ✅ Route saved to your favourites.
</p>

<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>

<p class="fav-success">
    ✅ Route removed from your favourites.
</p>

<?php endif; ?>

<?php if (!$routes): ?>

<section class="fav-card">

    <h2>No favourite routes yet</h2>

    <p>
        Search a flight and click
        "⭐ Save Favourite Route"
        on the Results page.
    </p>

    <a href="index.php">
        Search Flights →
    </a>

</section>

<?php endif; ?>

<?php foreach ($routes as $route): ?>

<section class="fav-card">

    <div class="fav-route">

        <?php echo favEsc(
            $route['from_airport']
        ); ?>

        →

        <?php echo favEsc(
            $route['to_airport']
        ); ?>

        <?php if (
            $route['trip_type'] === 'roundtrip'
        ): ?>

            → <?php echo favEsc(
                $route['from_airport']
            ); ?>

        <?php endif; ?>

        <?php if (
            $route['trip_type'] === 'multicity'
        ): ?>

            |

            <?php echo favEsc(
                $route['second_from']
            ); ?>

            →

            <?php echo favEsc(
                $route['second_to']
            ); ?>

        <?php endif; ?>

    </div>

    <p class="fav-muted">

        <?php echo favEsc(
            ucfirst($route['trip_type'])
        ); ?>

        · <?php echo favEsc(
            $route['cabin']
        ); ?>

        · <?php echo (int)$route['passengers']; ?>
        passenger(s)

    </p>

    <!-- Search again with new dates -->

    <form method="get" action="results.php">

        <input
            type="hidden"
            name="trip"
            value="<?php echo favEsc(
                $route['trip_type']
            ); ?>"
        >

        <input
            type="hidden"
            name="cabin"
            value="<?php echo favEsc(
                $route['cabin']
            ); ?>"
        >

        <input
            type="hidden"
            name="passengers"
            value="<?php echo (int)$route['passengers']; ?>"
        >

        <input
            type="hidden"
            name="from[]"
            value="<?php echo favEsc(
                $route['from_airport']
            ); ?>"
        >

        <input
            type="hidden"
            name="to[]"
            value="<?php echo favEsc(
                $route['to_airport']
            ); ?>"
        >

        <?php if (
            $route['trip_type'] === 'multicity'
        ): ?>

        <input
            type="hidden"
            name="from[]"
            value="<?php echo favEsc(
                $route['second_from']
            ); ?>"
        >

        <input
            type="hidden"
            name="to[]"
            value="<?php echo favEsc(
                $route['second_to']
            ); ?>"
        >

        <?php endif; ?>

        <div class="fav-grid">

            <label>

                <?php echo
                    $route['trip_type'] === 'multicity'
                    ? 'Flight 1 Date'
                    : 'Departure Date';
                ?>

                <input
                    type="date"
                    name="depart[]"
                    required
                    min="<?php echo $today; ?>"
                >

            </label>

            <?php if (
                $route['trip_type'] === 'roundtrip'
            ): ?>

            <label>
                Return Date

                <input
                    type="date"
                    name="return_date"
                    required
                    min="<?php echo $today; ?>"
                >
            </label>

            <?php elseif (
                $route['trip_type'] === 'multicity'
            ): ?>

            <label>
                Flight 2 Date

                <input
                    type="date"
                    name="depart[]"
                    required
                    min="<?php echo $today; ?>"
                >
            </label>

            <?php endif; ?>

        </div>

        <button class="fav-primary" type="submit">
            🔎 Search This Route
        </button>

    </form>

    <!-- Delete saved route -->

    <form
        method="post"
        action="favourites.php"
        onsubmit="return confirm('Remove this favourite route?');"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo favEsc(
                csrf_token()
            ); ?>"
        >

        <input
            type="hidden"
            name="action"
            value="delete"
        >

        <input
            type="hidden"
            name="id"
            value="<?php echo (int)$route['id']; ?>"
        >

        <button
            type="submit"
            class="fav-delete"
        >
            🗑 Remove Favourite
        </button>

    </form>

</section>

<?php endforeach; ?>

</main>

<?php
if (is_file(__DIR__ . '/support.php')) {
    include_once __DIR__ . '/support.php';
}

if (is_file(__DIR__ . '/theme-toggle.php')) {
    include_once __DIR__ . '/theme-toggle.php';
}

if (is_file(__DIR__ . '/responsive-layout.php')) {
    include_once __DIR__ . '/responsive-layout.php';
}
?>

</body>
</html>