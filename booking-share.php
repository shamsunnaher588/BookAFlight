<?php
// BookAFlight - Secure DEMO Booking Share
// Not a real airline ticket or PNR.

require_once __DIR__ . '/db.php';

header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow', true);

function bsEsc($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$shareToken = $_GET['share'] ?? null;

$isPublic = is_string($shareToken)
    && $shareToken !== '';

$publicBooking = null;

// PUBLIC SHARED VIEW
if ($isPublic) {

    if (preg_match('/\A[a-f0-9]{64}\z/D', $shareToken)) {

        $s = $pdo->prepare(
            'SELECT
                b.airline,
                b.from_city,
                b.to_city,
                b.depart_date,
                b.departure_time,
                b.arrival_time,
                b.cabin,
                b.passenger_count
             FROM booking_share_links s
             INNER JOIN bookings b
                ON b.id = s.booking_id
             WHERE s.token_hash = ?
               AND s.revoked_at IS NULL
               AND s.expires_at > UTC_TIMESTAMP()
             LIMIT 1'
        );

        $s->execute([
            hash('sha256', $shareToken)
        ]);

        $publicBooking = $s->fetch(PDO::FETCH_ASSOC)
            ?: null;
    }

    if (!$publicBooking) {
        http_response_code(404);
    }

} else {

    // PRIVATE CUSTOMER VIEW
    require_login();

    $userId = (int)$_SESSION['user_id'];
    $errors = [];

    $notice = (string)(
        $_SESSION['bs_notice'] ?? ''
    );

    $newToken = (string)(
        $_SESSION['bs_new_token'] ?? ''
    );

    $newBookingId = (int)(
        $_SESSION['bs_new_booking_id'] ?? 0
    );

    unset(
        $_SESSION['bs_notice'],
        $_SESSION['bs_new_token'],
        $_SESSION['bs_new_booking_id']
    );

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $action = is_string(
            $_POST['action'] ?? null
        ) ? $_POST['action'] : '';

        $id = filter_var(
            $_POST['booking_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $id = $id && $id > 0
            ? (int)$id
            : 0;

        if (!valid_csrf($_POST['csrf_token'] ?? null)) {

            $errors[] =
                'Session expired. Refresh this page and try again.';

        } elseif (
            !$id ||
            !in_array($action, ['create', 'revoke'], true)
        ) {

            $errors[] = 'Invalid action.';

        } else {

            // Ensure booking belongs to logged-in user.
            $owner = $pdo->prepare(
                'SELECT id
                 FROM bookings
                 WHERE id = ? AND user_id = ?
                 LIMIT 1'
            );

            $owner->execute([
                $id,
                $userId
            ]);

            if (!$owner->fetchColumn()) {

                http_response_code(403);

                $errors[] =
                    'That flight plan does not belong to your account.';

            } else {

                try {

                    if ($action === 'create') {

                        // Generate a random private token.
                        $token = bin2hex(
                            random_bytes(32)
                        );

                        // Store only a hash of the token.
                        $insert = $pdo->prepare(
                            'INSERT INTO booking_share_links
                             (
                                booking_id,
                                token_hash,
                                created_at,
                                expires_at,
                                revoked_at
                             )
                             VALUES (
                                ?,
                                ?,
                                UTC_TIMESTAMP(),
                                DATE_ADD(
                                    UTC_TIMESTAMP(),
                                    INTERVAL 7 DAY
                                ),
                                NULL
                             )
                             ON DUPLICATE KEY UPDATE
                                token_hash = VALUES(token_hash),
                                created_at = UTC_TIMESTAMP(),
                                expires_at = DATE_ADD(
                                    UTC_TIMESTAMP(),
                                    INTERVAL 7 DAY
                                ),
                                revoked_at = NULL'
                        );

                        $insert->execute([
                            $id,
                            hash('sha256', $token)
                        ]);

                        $_SESSION['bs_new_token'] = $token;
                        $_SESSION['bs_new_booking_id'] = $id;

                        $_SESSION['bs_notice'] =
                            'New share link created. Older links for this flight plan no longer work.';

                    } else {

                        // Revoke existing share link.
                        $stmt = $pdo->prepare(
                            'UPDATE booking_share_links
                             SET revoked_at = UTC_TIMESTAMP()
                             WHERE booking_id = ?'
                        );

                        $stmt->execute([$id]);

                        $_SESSION['bs_notice'] =
                            'Sharing stopped for this flight plan.';
                    }

                    header(
                        'Location: booking-share.php?booking_id='
                        . $id,
                        true,
                        303
                    );

                    exit;

                } catch (Throwable $e) {

                    error_log(
                        'BookAFlight booking share: '
                        . $e->getMessage()
                    );

                    $errors[] =
                        'Could not update share link. Check that booking-share.sql was imported.';
                }
            }
        }
    }

    // Load only bookings owned by the customer.
    $stmt = $pdo->prepare(
        'SELECT
            b.id,
            b.airline,
            b.from_city,
            b.to_city,
            b.depart_date,
            b.departure_time,
            b.cabin,
            b.passenger_count,
            s.expires_at,
            CASE
                WHEN s.revoked_at IS NULL
                 AND s.expires_at > UTC_TIMESTAMP()
                THEN 1
                ELSE 0
            END AS share_active
         FROM bookings b
         LEFT JOIN booking_share_links s
            ON s.booking_id = b.id
         WHERE b.user_id = ?
         ORDER BY b.id DESC
         LIMIT 50'
    );

    $stmt->execute([$userId]);

    $bookings = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

    $newPreview = '';

    foreach ($bookings as $b) {

        if ((int)$b['id'] === $newBookingId) {

            $newPreview =
                $b['from_city'] . ' → ' .
                $b['to_city'] . ' | ' .
                $b['depart_date'] . ' ' .
                $b['departure_time'] . ' | ' .
                $b['airline'] . ' | ' .
                $b['cabin'];

            break;
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
<meta charset="utf-8">
<meta name="viewport"
      content="width=device-width,initial-scale=1">

<title>Share Demo Booking | BookAFlight</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f0f5fb;
    color: #183251;
    font: 15px/1.6 Arial, sans-serif;
}

header {
    background: #082858;
    color: white;
    padding: 18px 5%;
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
    align-items: center;
}

header a {
    color: white;
}

main {
    max-width: 940px;
    margin: 26px auto;
    padding: 0 14px;
}

.card {
    background: white;
    border-radius: 14px;
    padding: 20px;
    margin: 16px 0;
    box-shadow: 0 4px 20px #0001;
}

.notice {
    background: #fff1cc;
    border-left: 4px solid #d59a29;
    padding: 14px;
    border-radius: 8px;
    margin: 14px 0;
}

.success {
    background: #e2f6e9;
    padding: 12px;
    border-radius: 9px;
}

.error {
    background: #ffe6e6;
    color: #882c2c;
    padding: 12px;
    border-radius: 9px;
}

.btn,
button {
    display: inline-block;
    border: 0;
    border-radius: 9px;
    padding: 11px 15px;
    background: #ffbc43;
    color: #082858;
    font-weight: bold;
    text-decoration: none;
    cursor: pointer;
    font: 700 14px Arial, sans-serif;
    min-height: 42px;
}

.btn-outline {
    background: #e8f1fa;
}

.actions {
    display: flex;
    gap: 9px;
    flex-wrap: wrap;
    margin-top: 12px;
}

.actions form {
    display: inline;
}

.meta {
    color: #5e7188;
    overflow-wrap: anywhere;
}

.share-link {
    width: 100%;
    padding: 12px;
    border: 1px solid #c3d3e5;
    border-radius: 8px;
    font: inherit;
    overflow-wrap: anywhere;
}

small {
    font-size: 13px;
    color: #586b80;
}

@media(max-width:600px) {
    .card {
        padding: 15px;
    }

    .actions > * {
        max-width: 100%;
    }
}

/* Dark mode */
html[data-baf-theme="dark"] body {
    background: #0c1421;
    color: #ecf4ff;
}

html[data-baf-theme="dark"] .card {
    background: #172639;
    color: #ecf4ff;
}
</style>
</head>

<body>

<header>
    <strong>
        ✈ BookAFlight — Booking Share
    </strong>

    <a href="index.php">Home</a>
    <a href="my-bookings.php">My Bookings</a>
</header>

<main>

<h1>🔗 Share Demo Flight Plan</h1>

<p class="notice">
    <strong>
        DEMO — NOT A REAL AIRLINE TICKET OR PNR.
    </strong>

    Shared pages only show flight route,
    airline, date, time, cabin and passenger count.

    Passenger names, dates of birth,
    seat numbers and contact details
    are never shown.
</p>

<?php if ($isPublic): ?>

    <!-- Public shared itinerary -->

    <?php if (!$publicBooking): ?>

        <section class="card">
            <h2>Link unavailable</h2>

            <p>
                This share link is invalid,
                expired or turned off.
            </p>
        </section>

    <?php else: ?>

        <section class="card">

            <h2>
                <?php echo bsEsc(
                    $publicBooking['from_city']
                ); ?>
                →
                <?php echo bsEsc(
                    $publicBooking['to_city']
                ); ?>
            </h2>

            <p>
                <strong>Airline (sample):</strong>
                <?php echo bsEsc(
                    $publicBooking['airline']
                ); ?>
            </p>

            <p>
                <strong>Departure date:</strong>
                <?php echo bsEsc(
                    $publicBooking['depart_date']
                ); ?>
            </p>

            <p>
                <strong>Departure time:</strong>
                <?php echo bsEsc(
                    $publicBooking['departure_time']
                ); ?>
            </p>

            <p>
                <strong>Arrival time:</strong>
                <?php echo bsEsc(
                    $publicBooking['arrival_time']
                ); ?>
            </p>

            <p>
                <strong>Cabin:</strong>
                <?php echo bsEsc(
                    $publicBooking['cabin']
                ); ?>
            </p>

            <p>
                <strong>Travelers:</strong>
                <?php echo (int)
                    $publicBooking['passenger_count'];
                ?>
            </p>

            <p class="notice">
                This is a read-only local
                travel-plan preview.

                It does not verify any passenger,
                reserve airline seats, issue a
                ticket, or authorize boarding.
            </p>

        </section>

    <?php endif; ?>

<?php else: ?>

    <!-- Logged-in customer share management -->

    <?php if ($notice): ?>

        <p class="success">
            <?php echo bsEsc($notice); ?>
        </p>

    <?php endif; ?>

    <?php foreach ($errors as $error): ?>

        <p class="error">
            <?php echo bsEsc($error); ?>
        </p>

    <?php endforeach; ?>

    <?php if (
        $newToken !== '' &&
        $newBookingId > 0
    ): ?>

        <section
            class="card"
            id="new-share"
            data-token="<?php
                echo bsEsc($newToken);
            ?>"
            data-summary="<?php
                echo bsEsc($newPreview);
            ?>"
        >

            <h2>✅ Your new share link</h2>

            <p class="meta">
                Anyone with this link can view
                the limited demo itinerary for 7 days.

                Keep it private if you don't want
                others to see the route and dates.
            </p>

            <input
                class="share-link"
                id="shareUrl"
                type="text"
                readonly
                aria-label="Shareable URL"
            >

            <div class="actions">

                <button
                    type="button"
                    id="copyShare"
                >
                    📋 Copy Link
                </button>

                <a
                    class="btn"
                    id="whatsappShare"
                    href="#"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    🟢 Share on WhatsApp
                </a>

                <button
                    type="button"
                    class="btn-outline"
                    id="nativeShare"
                >
                    ↗ Share
                </button>

            </div>

            <p
                id="shareResult"
                class="meta"
                role="status"
            ></p>

        </section>

    <?php endif; ?>

    <?php if (!$bookings): ?>

        <section class="card">
            No saved flight plans yet.

            <a href="index.php">
                Search Flights
            </a>

            and save a demo plan first.
        </section>

    <?php endif; ?>

    <?php foreach ($bookings as $b): ?>

        <section class="card">

            <h2>
                <?php echo bsEsc(
                    $b['from_city']
                ); ?>
                →
                <?php echo bsEsc(
                    $b['to_city']
                ); ?>
            </h2>

            <p>
                <?php echo bsEsc($b['airline']); ?>
                ·
                <?php echo bsEsc($b['depart_date']); ?>
                ·
                <?php echo bsEsc(
                    $b['departure_time']
                ); ?>
            </p>

            <p class="meta">
                Cabin:
                <?php echo bsEsc($b['cabin']); ?>

                · Travelers:
                <?php echo (int)$b['passenger_count']; ?>
            </p>

            <p>
                <small>
                    Share status:

                    <?php
                    echo (int)$b['share_active'] === 1
                        ? 'Active until ' .
                          bsEsc($b['expires_at']) .
                          ' (UTC)'
                        : 'Not active';
                    ?>
                </small>
            </p>

            <div class="actions">

                <form method="post">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php
                            echo bsEsc(csrf_token());
                        ?>"
                    >

                    <input
                        type="hidden"
                        name="booking_id"
                        value="<?php
                            echo (int)$b['id'];
                        ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="create"
                    >

                    <button type="submit">
                        <?php
                        echo (int)$b['share_active'] === 1
                            ? '🔁 Create New Link'
                            : '🔗 Create Share Link';
                        ?>
                    </button>

                </form>

                <?php if (
                    (int)$b['share_active'] === 1
                ): ?>

                    <form method="post">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php
                                echo bsEsc(csrf_token());
                            ?>"
                        >

                        <input
                            type="hidden"
                            name="booking_id"
                            value="<?php
                                echo (int)$b['id'];
                            ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="revoke"
                        >

                        <button
                            class="btn-outline"
                            type="submit"
                        >
                            Stop Sharing
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </section>

    <?php endforeach; ?>

<?php endif; ?>

</main>

<?php
if (is_file(__DIR__ . '/theme-toggle.php')) {
    include_once __DIR__ . '/theme-toggle.php';
}

if (is_file(__DIR__ . '/responsive-layout.php')) {
    include_once __DIR__ . '/responsive-layout.php';
}
?>

<script>
(() => {
    'use strict';

    const card = document.getElementById(
        'new-share'
    );

    if (!card) return;

    const url = new URL(
        'booking-share.php',
        location.href
    );

    url.search = '';

    url.searchParams.set(
        'share',
        card.dataset.token
    );

    const link = url.href;

    document.getElementById(
        'shareUrl'
    ).value = link;

    const localOnly = [
        'localhost',
        '127.0.0.1'
    ].includes(location.hostname);

    const message =
        '✈ BookAFlight DEMO travel plan ' +
        '(NOT a ticket or PNR):\n' +
        (
            localOnly
                ? card.dataset.summary +
                  '\n(Website is localhost, ' +
                  'so this link works only on ' +
                  'the hosting computer.)'
                : link
        );

    document.getElementById(
        'whatsappShare'
    ).href =
        'https://api.whatsapp.com/send?text=' +
        encodeURIComponent(message);

    const result = document.getElementById(
        'shareResult'
    );

    const copy = async () => {

        try {

            if (
                navigator.clipboard &&
                window.isSecureContext
            ) {

                await navigator.clipboard.writeText(
                    link
                );

            } else {

                const input = document.getElementById(
                    'shareUrl'
                );

                input.focus();
                input.select();

                if (!document.execCommand('copy')) {
                    throw new Error(
                        'Copy not supported'
                    );
                }
            }

            result.textContent =
                'Share link copied.';

        } catch (_) {

            result.textContent =
                'Select the link above and press Ctrl+C to copy.';
        }
    };

    document.getElementById(
        'copyShare'
    ).addEventListener(
        'click',
        copy
    );

    document.getElementById(
        'nativeShare'
    ).addEventListener(
        'click',
        () => {

            if (navigator.share) {

                const payload = localOnly
                    ? {
                        title:
                            'BookAFlight demo travel plan',
                        text: message
                    }
                    : {
                        title:
                            'BookAFlight demo travel plan',
                        text:
                            'Demo itinerary — not an airline ticket',
                        url: link
                    };

                navigator.share(payload).catch(
                    () => {}
                );

            } else {

                copy();
            }
        }
    );
})();
</script>

</body>
</html>