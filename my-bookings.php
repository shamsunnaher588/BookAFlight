<?php
require_once __DIR__ . '/db.php';
require_login();

$userId = (int)$_SESSION['user_id'];

// Delete saved plan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!valid_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid request. Refresh the page.');
    }

    $deleteId = filter_var(
        $_POST['delete_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$deleteId || $deleteId < 1) {
        http_response_code(400);
        exit('Invalid plan ID.');
    }

    $del = $pdo->prepare(
        'DELETE FROM bookings
         WHERE id = ? AND user_id = ?'
    );

    $del->execute([$deleteId, $userId]);

    header('Location: my-bookings.php?removed=1');
    exit;
}

// Get user's bookings
$stmt = $pdo->prepare(
    'SELECT
        id, airline, from_city, to_city,
        depart_date, departure_time,
        arrival_time, cabin, passenger_count,
        preview_total, created_at
     FROM bookings
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 50'
);

$stmt->execute([$userId]);

$bookings = $stmt->fetchAll();

// Load one booking's details
$viewId = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT
);

$detail = null;
$people = [];

if ($viewId && $viewId > 0) {

    $stmt = $pdo->prepare(
        'SELECT *
         FROM bookings
         WHERE id = ? AND user_id = ?'
    );

    $stmt->execute([$viewId, $userId]);

    $detail = $stmt->fetch();

    if ($detail) {

        $stmt = $pdo->prepare(
            'SELECT
                first_name, last_name,
                date_of_birth, seat_number
             FROM booking_passengers
             WHERE booking_id = ?
             ORDER BY id'
        );

        $stmt->execute([$viewId]);

        $people = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>My Bookings | BookAFlight</title>

<link rel="stylesheet" href="auth.css">

<style>
.container {
    max-width: 1050px;
    width: 93%;
    margin: 35px auto;
}

.plan {
    background: white;
    border-radius: 13px;
    padding: 20px;
    margin: 15px 0;
    box-shadow: 0 5px 18px #08285812;
}

.plan h3 {
    margin: 0 0 8px;
}

.meta {
    color: #68798e;
    line-height: 1.7;
}

.price {
    color: #096ab2;
    font-size: 23px;
    font-weight: 700;
}

.actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.actions .btn {
    margin: 0;
    width: auto;
    text-decoration: none;
}

.remove {
    background: white;
    border: 1px solid #b53b3b;
    color: #992c2c;
    padding: 12px 15px;
    border-radius: 9px;
    cursor: pointer;
}

.note {
    background: #fff3d9;
    border-radius: 9px;
    padding: 15px;
    line-height: 1.6;
}

.passenger-row {
    padding: 12px;
    border-bottom: 1px solid #e2e9f1;
}

.passenger-row:last-child {
    border: 0;
}

.status {
    font-size: 12px;
    color: #085d9c;
    background: #e4f2ff;
    border-radius: 20px;
    padding: 6px 10px;
}

.notice {
    padding: 14px;
    background: #e8f8ed;
    border-radius: 9px;
    color: #195f38;
}

.back {
    margin-bottom: 15px;
    display: inline-block;
}
</style>
</head>

<body>

<header class="topbar">

    <a class="brand" href="index.php">
        ✈ <span>Book</span>AFlight
    </a>

    <a href="dashboard.php">
        My Dashboard
    </a>

</header>

<main class="container">

<h1>My Bookings</h1>

<p class="muted">
    Your saved flight plans and passenger selections.
</p>

<div class="note">
    These are saved plans only, not airline
    reservations or ticket confirmations.
    Seat availability has not been checked
    with an airline.
</div>

<?php if (isset($_GET['saved'])): ?>

<p class="notice">
    Your flight plan was saved successfully.
</p>

<?php endif; ?>

<?php if (isset($_GET['removed'])): ?>

<p class="notice">
    Saved plan removed.
</p>

<?php endif; ?>

<!-- Selected Booking Details -->

<?php if ($viewId): ?>

<a class="back" href="my-bookings.php">
    ← All saved plans
</a>

<?php if ($detail): ?>

<section class="plan">

    <h2>
        <?php echo esc($detail['airline']); ?>
    </h2>

    <p>
        <strong>
            <?php echo esc($detail['from_city']); ?>
            →
            <?php echo esc($detail['to_city']); ?>
        </strong>
    </p>

    <p class="meta">
        Departure:
        <?php echo esc($detail['depart_date']); ?>
        ·
        <?php echo esc($detail['departure_time']); ?>
        →
        <?php echo esc($detail['arrival_time']); ?>
    </p>

    <p class="meta">
        Cabin:
        <?php echo esc($detail['cabin']); ?>
        |
        Passengers:
        <?php echo (int)$detail['passenger_count']; ?>
    </p>

    <p class="price">
        Illustrative total:
        ৳<?php
        echo number_format(
            (float)$detail['preview_total'],
            0
        );
        ?>
    </p>

    <h3>Passenger Details</h3>

    <?php foreach ($people as $i => $p): ?>

    <div class="passenger-row">

        <strong>
            <?php echo $i + 1; ?>.
            <?php
            echo esc(
                $p['first_name'] . ' ' . $p['last_name']
            );
            ?>
        </strong>

        <p class="meta">
            Seat:
            <?php echo esc($p['seat_number']); ?>
            |
            Date of Birth:
            <?php echo esc($p['date_of_birth']); ?>
        </p>

    </div>

    <?php endforeach; ?>

</section>

<?php else: ?>

<div class="plan">
    Saved plan not found.
</div>

<?php endif; ?>
<?php endif; ?>

<!-- All Booking Plans -->

<h2>
    Saved Plans
    (<?php
    echo count($bookings);
    echo count($bookings) === 50 ? '+' : '';
    ?>)
</h2>

<?php if (!$bookings): ?>

<section class="plan">

    <p>No saved plans yet.</p>

    <a href="index.php">
        Search Flights →
    </a>

</section>

<?php endif; ?>

<?php foreach ($bookings as $b): ?>

<section class="plan">

    <h3>
        <?php echo esc($b['from_city']); ?>
        →
        <?php echo esc($b['to_city']); ?>

        <span class="status">
            Saved plan
        </span>
    </h3>

    <p class="meta">
        <?php echo esc($b['airline']); ?>
        ·
        <?php echo esc($b['depart_date']); ?>
        ·
        <?php echo esc($b['departure_time']); ?>
        →
        <?php echo esc($b['arrival_time']); ?>
    </p>

    <p class="meta">
        <?php echo (int)$b['passenger_count']; ?>
        passenger(s)
        ·
        <?php echo esc($b['cabin']); ?>
    </p>

    <p class="price">
        ৳<?php
        echo number_format(
            (float)$b['preview_total'],
            0
        );
        ?>
    </p>

    <div class="actions">

        <a
            class="btn"
            href="my-bookings.php?id=<?php
                echo (int)$b['id'];
            ?>"
        >
            View Details
        </a>

        <form
            method="POST"
            action="my-bookings.php"
            onsubmit="return confirm(
                'Remove this saved plan? No airline booking is affected.'
            );"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo esc(csrf_token()); ?>"
            >

            <input
                type="hidden"
                name="delete_id"
                value="<?php echo (int)$b['id']; ?>"
            >

            <button
                class="remove"
                type="submit"
            >
                Remove Saved Plan
            </button>

        </form>

    </div>

</section>

<?php endforeach; ?>
<a href="boarding-pass.php"
   style="display:inline-block;
          padding:12px 18px;
          background:#ffbc43;
          color:#082858;
          border-radius:9px;
          text-decoration:none;
          font-weight:bold;
          margin:12px 0;">
    📱 Mobile Boarding Pass & QR Code
</a>
<a href="booking-share.php"
   style="display:inline-block;
          padding:12px 18px;
          background:#ffbc43;
          color:#082858;
          border-radius:9px;
          text-decoration:none;
          font-weight:bold;
          margin:12px 0;">
    🔗 Share Booking / WhatsApp
</a>
</main>

</body>
</html>