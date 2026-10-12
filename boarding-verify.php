<?php
// Public DEMO QR verification endpoint.
// Not for real airline operations.

require_once __DIR__ . '/db.php';

header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow', true);

function bvEsc($v): string {
    return htmlspecialchars(
        (string)$v,
        ENT_QUOTES,
        'UTF-8'
    );
}

$token = is_string($_GET['token'] ?? null)
    ? $_GET['token']
    : '';

$found = false;

if (preg_match('/\A[a-f0-9]{48}\z/D', $token)) {

    $stmt = $pdo->prepare(
        'SELECT t.id
         FROM demo_boarding_qr_tokens t

         INNER JOIN bookings b
            ON b.id = t.booking_id

         INNER JOIN booking_passengers bp
            ON bp.id = t.passenger_id
            AND bp.booking_id = b.id

         WHERE t.token = ?
         LIMIT 1'
    );

    $stmt->execute([$token]);

    $found = (bool)$stmt->fetchColumn();
}
?>

<!doctype html>
<html lang="en">

<head>
<meta charset="utf-8">

<meta name="viewport"
      content="width=device-width,initial-scale=1">

<title>Demo QR Check | BookAFlight</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #edf3fb;
    color: #16304e;
    font: 16px/1.6 Arial, sans-serif;
}

header {
    background: #082858;
    color: white;
    padding: 18px;
    text-align: center;
}

main {
    max-width: 490px;
    margin: 45px auto;
    padding: 20px;
}

.card {
    border-radius: 14px;
    padding: 25px;
    background: white;
    box-shadow: 0 5px 25px #0001;
    text-align: center;
}

.alert {
    background: #ab1e1e;
    color: white;
    padding: 13px;
    border-radius: 8px;
    font-weight: bold;
}

.small {
    color: #58687a;
}

a {
    color: #086cb2;
}
</style>

</head>

<body>

<header>
    <strong>✈ BookAFlight</strong>
</header>

<main>

<div class="card">

    <h1>Demo QR Check</h1>

    <p class="alert">
        NOT VALID FOR TRAVEL
    </p>

    <?php if ($found): ?>

        <h2>
            Example QR record found ✓
        </h2>

        <p>
            This code exists in BookAFlight's
            local demonstration database only.
        </p>

    <?php else: ?>

        <h2>
            Example QR not found
        </h2>

        <p>
            This token is missing, malformed
            or not available in this database.
        </p>

    <?php endif; ?>

    <p class="small">
        Neither outcome confirms a real airline
        booking, passenger identity, check-in,
        security clearance or boarding authorization.
    </p>

    <a href="index.php">
        Back to BookAFlight
    </a>

</div>

</main>

</body>
</html>