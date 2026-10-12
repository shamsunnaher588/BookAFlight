<?php
// BookAFlight - DEMO mobile boarding pass.
// NOT VALID FOR TRAVEL.
// Reads existing bookings and booking_passengers.

require_once __DIR__ . '/db.php';
require_login();

header('Cache-Control: no-store, private');

function bpEsc($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$userId = (int)$_SESSION['user_id'];

$bookingId = filter_var(
    $_GET['booking_id'] ?? null,
    FILTER_VALIDATE_INT
);

$bookingId = ($bookingId && $bookingId > 0)
    ? $bookingId
    : 0;

$error = '';

// Generate demo QR tokens.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postId = filter_var(
        $_POST['booking_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $postId = ($postId && $postId > 0)
        ? $postId
        : 0;

    if (!valid_csrf(
        $_POST['csrf_token'] ?? null
    )) {

        $error =
            'Session expired. Please refresh the page.';

    } elseif (!$postId) {

        $error = 'Invalid flight plan.';

    } else {

        $owner = $pdo->prepare(
            'SELECT id
             FROM bookings
             WHERE id = ? AND user_id = ?
             LIMIT 1'
        );

        $owner->execute([
            $postId,
            $userId
        ]);

        if (!$owner->fetchColumn()) {

            http_response_code(403);

            $error =
                'This flight plan is not accessible.';

        } else {

            try {

                $pdo->beginTransaction();

                $passengersStmt = $pdo->prepare(
                    'SELECT bp.id, t.token
                     FROM booking_passengers bp

                     LEFT JOIN demo_boarding_qr_tokens t
                       ON t.booking_id = bp.booking_id
                       AND t.passenger_id = bp.id

                     WHERE bp.booking_id = ?'
                );

                $passengersStmt->execute([
                    $postId
                ]);

                $people = $passengersStmt->fetchAll(
                    PDO::FETCH_ASSOC
                );

                if (!$people) {
                    throw new RuntimeException(
                        'No passenger records found.'
                    );
                }

                $insert = $pdo->prepare(
                    'INSERT INTO demo_boarding_qr_tokens
                     (
                        booking_id,
                        passenger_id,
                        token
                     )
                     VALUES (?, ?, ?)

                     ON DUPLICATE KEY UPDATE
                         id = id'
                );

                foreach ($people as $person) {

                    if (!empty($person['token'])) {
                        continue;
                    }

                    $insert->execute([
                        $postId,
                        (int)$person['id'],
                        bin2hex(random_bytes(24))
                    ]);
                }

                $pdo->commit();

                header(
                    'Location: boarding-pass.php?booking_id='
                    . $postId
                    . '&generated=1'
                );

                exit;

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log(
                    'Demo boarding pass failed: '
                    . $e->getMessage()
                );

                $error =
                    'Could not generate demo QR. '
                    . 'Check that boarding-pass.sql was imported.';
            }
        }
    }

    $bookingId = $postId;
}

// Load saved bookings.
$booking = null;
$passengers = [];
$bookings = [];

if ($bookingId) {

    $stmt = $pdo->prepare(
        'SELECT
            id,
            booking_reference,
            airline,
            from_city,
            to_city,
            depart_date,
            departure_time,
            arrival_time,
            cabin
         FROM bookings
         WHERE id = ? AND user_id = ?
         LIMIT 1'
    );

    $stmt->execute([
        $bookingId,
        $userId
    ]);

    $booking = $stmt->fetch(
        PDO::FETCH_ASSOC
    ) ?: null;

    if ($booking) {

        $stmt = $pdo->prepare(
            'SELECT
                bp.id,
                bp.first_name,
                bp.last_name,
                bp.seat_number,
                t.token

             FROM booking_passengers bp

             LEFT JOIN demo_boarding_qr_tokens t
               ON t.booking_id = bp.booking_id
               AND t.passenger_id = bp.id

             WHERE bp.booking_id = ?
             ORDER BY bp.id'
        );

        $stmt->execute([
            $bookingId
        ]);

        $passengers = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

    } else {

        http_response_code(404);

        $error =
            'Flight plan not found in your account.';
    }

} else {

    $stmt = $pdo->prepare(
        'SELECT
            id,
            booking_reference,
            from_city,
            to_city,
            depart_date

         FROM bookings

         WHERE user_id = ?
         ORDER BY id DESC
         LIMIT 50'
    );

    $stmt->execute([$userId]);

    $bookings = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );
}
?>

<!doctype html>
<html lang="en">

<head>
<meta charset="utf-8">

<meta name="viewport"
      content="width=device-width,initial-scale=1">

<title>
    Demo Mobile Boarding Pass | BookAFlight
</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font: 15px/1.5 Arial, sans-serif;
    background: #edf3fb;
    color: #16304e;
}

header {
    background: #082858;
    color: white;
    padding: 16px max(14px, 5%);
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

header a {
    color: white;
}

main {
    max-width: 900px;
    margin: 22px auto;
    padding: 0 14px;
}

.notice {
    background: #fff0ca;
    color: #573e0e;
    border: 2px solid #cf962e;
    padding: 13px;
    border-radius: 10px;
    font-weight: 700;
    margin: 16px 0;
}

.error {
    background: #ffdddd;
    color: #841e1e;
    padding: 12px;
    border-radius: 9px;
}

.list-card {
    background: white;
    border-radius: 12px;
    padding: 18px;
    margin: 12px 0;
    box-shadow: 0 4px 18px #0001;
}

.list-card a,
.action {
    display: inline-block;
    background: #ffbc43;
    color: #082858;
    border: 0;
    border-radius: 8px;
    padding: 11px 15px;
    text-decoration: none;
    font-weight: 700;
    cursor: pointer;
}

.pass-grid {
    display: grid;
    grid-template-columns:
        repeat(auto-fit, minmax(270px, 1fr));
    gap: 20px;
}

.pass {
    background: white;
    border: 2px solid #c7d9ea;
    border-radius: 18px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 8px 30px #0001;
}

.pass-head {
    background: #082858;
    color: white;
    padding: 18px;
}

.pass-head strong {
    font-size: 19px;
}

.pass-body {
    padding: 18px;
}

.demo-watermark {
    background: #a12323;
    color: white;
    text-align: center;
    font-weight: 800;
    padding: 9px 10px;
    letter-spacing: .05em;
}

.route {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    font-weight: 800;
    font-size: 20px;
    overflow-wrap: anywhere;
}

.route span {
    flex: 1;
    min-width: 0;
}

.route span:last-child {
    text-align: right;
}

.fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin: 18px 0;
}

.field small {
    display: block;
    color: #67788b;
}

.field strong {
    overflow-wrap: anywhere;
}

.qr-box {
    display: flex;
    justify-content: center;
    align-items: center;
    background: white;
    min-height: 192px;
    padding: 12px;
    border: 1px solid #dae3ed;
    border-radius: 10px;
    margin: 12px auto;
    max-width: 205px;
    direction: ltr;
}

.qr-box img,
.qr-box canvas {
    margin: auto;
}

.helper {
    text-align: center;
    font-size: 12px;
    color: #54657a;
    overflow-wrap: anywhere;
}

.mobile-note {
    font-size: 13px;
    color: #5a6e80;
}

/* Tablet */
@media(min-width:700px) {

    .pass-grid:has(.pass:only-child) {
        grid-template-columns:
            minmax(300px, 430px);
        justify-content: center;
    }
}

/* Mobile */
@media(max-width:600px) {

    .pass-grid {
        grid-template-columns:
            minmax(0, 1fr);
    }

    .pass-body {
        padding: 14px;
    }

    .route {
        font-size: 18px;
    }
}

/* Print / Save PDF */
@media print {

    header,
    .notice,
    .controls,
    .mobile-note,
    .error {
        display: none !important;
    }

    body {
        background: white;
    }

    .pass {
        box-shadow: none;
        break-inside: avoid;
    }

    .pass-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    main {
        margin: 0;
        padding: 0;
    }
}

/* Dark Mode */
html[data-baf-theme="dark"] body {
    background: #0b1220;
    color: #f0f4fa;
}

html[data-baf-theme="dark"] .list-card,
html[data-baf-theme="dark"] .pass {
    background: #172438;
    color: #f0f4fa;
    border-color: #3c5068;
}

html[data-baf-theme="dark"] .field small,
html[data-baf-theme="dark"] .helper {
    color: #c2cfdf;
}
</style>

</head>

<body>

<header>

    <strong>✈ BookAFlight</strong>

    <a href="my-bookings.php">
        My Bookings
    </a>

    <a href="boarding-pass.php">
        All Demo Boarding Passes
    </a>

</header>

<main>

<h1>📱 Mobile Boarding Pass + QR</h1>

<div class="notice">
    DEMO / NOT VALID FOR TRAVEL.

    This is a preview from your saved local
    flight plan.

    It is NOT an airline check-in confirmation,
    real boarding pass, PNR, or permission to board.
</div>

<?php if ($error): ?>

    <p class="error">
        <?php echo bpEsc($error); ?>
    </p>

<?php endif; ?>

<?php if (!$booking): ?>

    <h2>Select a saved flight plan</h2>

    <?php if (!$bookings): ?>

        <p>
            No flight plans found.

            <a href="index.php">
                Search Flights
            </a>

            and save a local plan first.
        </p>

    <?php endif; ?>

    <?php foreach ($bookings as $item): ?>

        <article class="list-card">

            <strong>

                <?php echo bpEsc(
                    $item['from_city']
                ); ?>

                →

                <?php echo bpEsc(
                    $item['to_city']
                ); ?>

            </strong>

            <p>
                <?php echo bpEsc(
                    $item['depart_date']
                ); ?>

                · Plan:

                <?php echo bpEsc(
                    $item['booking_reference']
                ); ?>
            </p>

            <a href="boarding-pass.php?booking_id=<?php
                echo (int)$item['id'];
            ?>">
                View Demo Boarding Pass →
            </a>

        </article>

    <?php endforeach; ?>

<?php else: ?>

    <p>
        <b>Saved plan:</b>

        <?php echo bpEsc(
            $booking['booking_reference']
            ?: '#' . $booking['id']
        ); ?>

        ·

        <?php echo bpEsc(
            $booking['airline']
        ); ?>
    </p>

    <?php
    $generatedCount = count(
        array_filter(
            $passengers,
            fn($p) => !empty($p['token'])
        )
    );
    ?>

    <?php if (
        $passengers &&
        $generatedCount < count($passengers)
    ): ?>

        <form class="controls" method="post">

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo bpEsc(
                    csrf_token()
                ); ?>"
            >

            <input
                type="hidden"
                name="booking_id"
                value="<?php echo (int)$booking['id']; ?>"
            >

            <button
                class="action"
                type="submit"
            >
                Generate Demo QR for All Passengers
            </button>

        </form>

    <?php endif; ?>

    <div class="pass-grid">

    <?php foreach ($passengers as $p): ?>

        <article class="pass">

            <div class="demo-watermark">
                DEMO — NOT VALID FOR TRAVEL
            </div>

            <div class="pass-head">

                <strong>
                    ✈ BookAFlight · Mobile Pass Preview
                </strong>

                <div>
                    <?php echo bpEsc(
                        $booking['airline']
                    ); ?>
                </div>

            </div>

            <div class="pass-body">

                <div class="route">

                    <span>
                        <?php echo bpEsc(
                            $booking['from_city']
                        ); ?>
                    </span>

                    <b>→</b>

                    <span>
                        <?php echo bpEsc(
                            $booking['to_city']
                        ); ?>
                    </span>

                </div>

                <div class="fields">

                    <div class="field">

                        <small>Passenger</small>

                        <strong>
                            <?php echo bpEsc(
                                trim(
                                    $p['first_name'] . ' ' .
                                    $p['last_name']
                                )
                            ); ?>
                        </strong>

                    </div>

                    <div class="field">

                        <small>Seat (sample)</small>

                        <strong>
                            <?php echo bpEsc(
                                $p['seat_number'] ?: '—'
                            ); ?>
                        </strong>

                    </div>

                    <div class="field">

                        <small>Departure (local plan)</small>

                        <strong>
                            <?php echo bpEsc(
                                $booking['depart_date'] . ' ' .
                                $booking['departure_time']
                            ); ?>
                        </strong>

                    </div>

                    <div class="field">

                        <small>Cabin</small>

                        <strong>
                            <?php echo bpEsc(
                                $booking['cabin']
                            ); ?>
                        </strong>

                    </div>

                    <div class="field">

                        <small>Plan reference</small>

                        <strong>
                            <?php echo bpEsc(
                                $booking['booking_reference']
                                ?: '—'
                            ); ?>
                        </strong>

                    </div>

                    <div class="field">

                        <small>Gate / Boarding</small>

                        <strong>
                            NOT ASSIGNED
                        </strong>

                    </div>

                </div>

                <?php if ($p['token']): ?>

                    <div
                        class="qr-box"
                        data-qr-token="<?php
                            echo bpEsc($p['token']);
                        ?>"
                    >
                        <span>Loading QR…</span>
                    </div>

                    <p class="helper">
                        QR links to DEMO verification only.
                        No private passenger details
                        are encoded.
                    </p>

                <?php else: ?>

                    <div class="helper">
                        Generate the demo QR
                        using the button above.
                    </div>

                <?php endif; ?>

            </div>

        </article>

    <?php endforeach; ?>

    </div>

    <div
        class="controls"
        style="margin:20px 0"
    >

        <button
            class="action"
            type="button"
            onclick="window.print()"
        >
            🖨 Print / Save as PDF
        </button>

    </div>

    <p class="mobile-note">

        📱 To open scanned QR URLs on your phone,
        the website must be accessible from the phone.

        A QR that starts with
        <code>http://localhost/</code>
        points to the scanning phone itself,
        not to your PC.

        For same-Wi-Fi testing, use your PC's
        LAN IP or a test HTTPS host.

        QR generation requires internet
        to load the JavaScript QR library.

    </p>

<?php endif; ?>

</main>

<?php
if (is_file(__DIR__ . '/theme-toggle.php')) {
    include_once __DIR__ . '/theme-toggle.php';
}
?>

<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
(function () {
    'use strict';

    document.querySelectorAll(
        '[data-qr-token]'
    ).forEach(function (el) {

        if (typeof QRCode === 'undefined') {

            el.textContent =
                'QR library unavailable. ' +
                'Connect to the internet and reload.';

            return;
        }

        const verifyUrl = new URL(
            'boarding-verify.php',
            window.location.href
        );

        verifyUrl.searchParams.set(
            'token',
            el.dataset.qrToken
        );

        el.replaceChildren();

        new QRCode(el, {
            text: verifyUrl.href,
            width: 164,
            height: 164,
            correctLevel: QRCode.CorrectLevel.M
        });
    });
})();
</script>

</body>
</html>