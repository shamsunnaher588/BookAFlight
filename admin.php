<?php
require_once __DIR__ . '/db.php';
require_login();

// Check admin permission from database
$account = $pdo->prepare(
    'SELECT id, full_name, role
     FROM users WHERE id = ? LIMIT 1'
);

$account->execute([
    (int)$_SESSION['user_id']
]);

$admin = $account->fetch();

if (!$admin || $admin['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied. Administrators only.');
}

// Analytics summary
$stats = $pdo->query(
    'SELECT
    (SELECT COUNT(*) FROM users) AS users_total,
    (SELECT COUNT(*) FROM bookings) AS plans_total,
    (SELECT COALESCE(SUM(passenger_count),0)
     FROM bookings) AS passengers_total,
    (SELECT COALESCE(SUM(preview_total),0)
     FROM bookings) AS preview_value'
)->fetch();

// Recent saved bookings
$bookings = $pdo->query(
    'SELECT
        b.id, b.airline,
        b.from_city, b.to_city,
        b.depart_date, b.passenger_count,
        b.preview_total, b.created_at,
        u.full_name AS customer_name
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     ORDER BY b.id DESC
     LIMIT 25'
)->fetchAll();

// Registered users
$users = $pdo->query(
    'SELECT id, full_name, email, role, created_at
     FROM users
     ORDER BY id DESC
     LIMIT 25'
)->fetchAll();

// Popular routes
$topRoutes = $pdo->query(
    'SELECT
        from_city, to_city,
        COUNT(*) AS total
     FROM bookings
     GROUP BY from_city, to_city
     ORDER BY total DESC
     LIMIT 5'
)->fetchAll();

// View passenger details for selected booking
$viewId = filter_input(
    INPUT_GET,
    'booking',
    FILTER_VALIDATE_INT
);

$selectedBooking = null;
$selectedPassengers = [];

if ($viewId && $viewId > 0) {

    $stmt = $pdo->prepare(
        'SELECT
            b.*,
            u.full_name AS customer_name,
            u.email AS customer_email
         FROM bookings b
         JOIN users u ON u.id = b.user_id
         WHERE b.id = ?
         LIMIT 1'
    );

    $stmt->execute([$viewId]);

    $selectedBooking = $stmt->fetch();

    if ($selectedBooking) {

        $stmt = $pdo->prepare(
            'SELECT
                first_name, last_name,
                date_of_birth, seat_number
             FROM booking_passengers
             WHERE booking_id = ?
             ORDER BY id'
        );

        $stmt->execute([$viewId]);

        $selectedPassengers = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>Admin Dashboard | BookAFlight</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f3f6fc;
    color: #152b48;
    font-family: Arial, sans-serif;
}

a {
    color: #096bb4;
    text-decoration: none;
}

header {
    background: #092b60;
    color: white;
    padding: 21px 6%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

header a {
    color: white;
}

.brand {
    font-size: 23px;
    font-weight: 800;
}

.brand span {
    color: #ffc14f;
}

nav {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
    font-size: 14px;
}

main {
    max-width: 1220px;
    width: 92%;
    margin: 30px auto 55px;
}

h1 {
    font-size: 30px;
    margin-bottom: 7px;
}

h2 {
    font-size: 21px;
    margin: 0 0 20px;
}

.muted {
    color: #667b93;
    line-height: 1.6;
}

/* Dashboard Statistics */

.grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin: 27px 0;
}

.stat, .panel {
    background: white;
    border: 1px solid #e6edf6;
    border-radius: 14px;
    box-shadow: 0 5px 20px #092b6009;
    padding: 23px;
}

.stat strong {
    display: block;
    font-size: 28px;
    color: #096bb4;
    margin-top: 12px;
}

.stat span {
    font-size: 14px;
    color: #60758e;
}

.panel {
    margin: 22px 0;
}

/* Tables */

.scroll {
    overflow-x: auto;
}

table {
    border-collapse: collapse;
    width: 100%;
    min-width: 650px;
    font-size: 14px;
}

th, td {
    text-align: left;
    border-bottom: 1px solid #e9eff6;
    padding: 13px 12px;
    vertical-align: middle;
}

th {
    background: #f8faff;
    color: #526982;
}

.tag {
    font-size: 12px;
    border-radius: 20px;
    padding: 5px 9px;
    background: #edf4ff;
    color: #065ca0;
}

.note {
    border-radius: 10px;
    background: #fff4d8;
    padding: 14px 17px;
    line-height: 1.7;
    color: #6b5523;
}

.details {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
}

.details p {
    margin: 7px 0;
    line-height: 1.6;
}

.empty {
    color: #667b93;
}

/* Responsive */

@media(max-width:850px) {
    .grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media(max-width:500px) {
    .grid, .details {
        grid-template-columns: 1fr;
    }

    main {
        width: 95%;
    }

    h1 {
        font-size: 25px;
    }

    .stat, .panel {
        padding: 17px;
    }
}
</style>
</head>

<body>

<header>

    <div class="brand">
        ✈ <span>Book</span>AFlight · Admin
    </div>

    <nav>
        <a href="admin.php">Admin Panel</a>
        <a href="dashboard.php">User Dashboard</a>
        <a href="my-bookings.php">My Bookings</a>
        <a href="index.php">Homepage</a>
        <a href="admin-group-bookings.php">
    👥 Group Booking Requests
</a>
    </nav>

</header>

<main>

<h1>Admin Dashboard</h1>

<p class="muted">
    Welcome,
    <?php echo esc($admin['full_name']); ?>.
    Manage your travel platform.
</p>

<div class="note">
    Internal development dashboard.
    Saved plans are not confirmed airline
    reservations. Estimated plan values
    are not sales or revenue.
</div>

<!-- Statistics -->

<section class="grid">

    <div class="stat">
        <span>Registered Users</span>

        <strong>
            <?php
            echo number_format(
                (int)$stats['users_total']
            );
            ?>
        </strong>
    </div>

    <div class="stat">
        <span>Saved Plans</span>

        <strong>
            <?php
            echo number_format(
                (int)$stats['plans_total']
            );
            ?>
        </strong>
    </div>

    <div class="stat">
        <span>Passenger Entries</span>

        <strong>
            <?php
            echo number_format(
                (int)$stats['passengers_total']
            );
            ?>
        </strong>
    </div>

    <div class="stat">
        <span>Total Preview Value</span>

        <strong>
            ৳<?php
            echo number_format(
                (float)$stats['preview_value'],
                0
            );
            ?>
        </strong>
    </div>

</section>

<!-- Selected Booking Details -->

<?php if ($viewId): ?>

<section class="panel" id="details">

<h2>
    Saved Plan Details
    #<?php echo (int)$viewId; ?>
</h2>

<?php if ($selectedBooking): ?>

<div class="details">

    <div>

        <p>
            <b>Customer:</b>
            <?php
            echo esc(
                $selectedBooking['customer_name']
            );
            ?>
        </p>

        <p>
            <b>Email:</b>
            <?php
            echo esc(
                $selectedBooking['customer_email']
            );
            ?>
        </p>

        <p>
            <b>Airline:</b>
            <?php
            echo esc($selectedBooking['airline']);
            ?>
        </p>

        <p>
            <b>Route:</b>
            <?php
            echo esc(
                $selectedBooking['from_city']
                . ' → ' .
                $selectedBooking['to_city']
            );
            ?>
        </p>

    </div>

    <div>

        <p>
            <b>Date:</b>
            <?php
            echo esc(
                $selectedBooking['depart_date']
            );
            ?>
        </p>

        <p>
            <b>Time:</b>
            <?php
            echo esc(
                $selectedBooking['departure_time']
                . ' → ' .
                $selectedBooking['arrival_time']
            );
            ?>
        </p>

        <p>
            <b>Cabin:</b>
            <?php
            echo esc($selectedBooking['cabin']);
            ?>
        </p>

        <p>
            <b>Preview Value:</b>
            ৳<?php
            echo number_format(
                (float)$selectedBooking['preview_total'],
                0
            );
            ?>
        </p>

    </div>

</div>

<h3>Passengers & Selected Seats</h3>

<div class="scroll">

<table>

<thead>
<tr>
    <th>Name</th>
    <th>Date of Birth</th>
    <th>Seat</th>
</tr>
</thead>

<tbody>

<?php foreach ($selectedPassengers as $p): ?>

<tr>

    <td>
        <?php
        echo esc(
            $p['first_name'] . ' ' .
            $p['last_name']
        );
        ?>
    </td>

    <td>
        <?php echo esc($p['date_of_birth']); ?>
    </td>

    <td>
        <?php echo esc($p['seat_number']); ?>
    </td>

</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>

<?php else: ?>

<p class="empty">
    Saved plan not found.
</p>

<?php endif; ?>

<p>
    <a href="admin.php">Close Details</a>
</p>

</section>

<?php endif; ?>

<!-- Booking Management -->

<section class="panel" id="bookings">

<h2>Recent Saved Plans</h2>

<div class="scroll">

<table>

<thead>
<tr>
    <th>ID</th>
    <th>Customer</th>
    <th>Route</th>
    <th>Departure</th>
    <th>Passengers</th>
    <th>Preview Value</th>
    <th>Details</th>
</tr>
</thead>

<tbody>

<?php foreach ($bookings as $b): ?>

<tr>

    <td>
        #<?php echo (int)$b['id']; ?>
    </td>

    <td>
        <?php echo esc($b['customer_name']); ?>
    </td>

    <td>
        <?php
        echo esc(
            $b['from_city'] . ' → ' .
            $b['to_city']
        );
        ?>
    </td>

    <td>
        <?php echo esc($b['depart_date']); ?>
    </td>

    <td>
        <?php echo (int)$b['passenger_count']; ?>
    </td>

    <td>
        ৳<?php
        echo number_format(
            (float)$b['preview_total'],
            0
        );
        ?>
    </td>

    <td>
        <a href="admin.php?booking=<?php
            echo (int)$b['id'];
        ?>#details">
            View
        </a>
    </td>

</tr>

<?php endforeach; ?>

<?php if (!$bookings): ?>

<tr>
    <td colspan="7">
        No saved plans yet.
    </td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>

<p class="muted">
    Showing up to 25 latest plans.
</p>

</section>

<!-- User Management -->

<section class="panel" id="users">

<h2>Registered Users</h2>

<div class="scroll">

<table>

<thead>
<tr>
    <th>ID</th>
    <th>Full Name</th>
    <th>Email</th>
    <th>Role</th>
    <th>Joined</th>
</tr>
</thead>

<tbody>

<?php foreach ($users as $u): ?>

<tr>

    <td>
        #<?php echo (int)$u['id']; ?>
    </td>

    <td>
        <?php echo esc($u['full_name']); ?>
    </td>

    <td>
        <?php echo esc($u['email']); ?>
    </td>

    <td>
        <span class="tag">
            <?php
            echo esc(ucfirst($u['role']));
            ?>
        </span>
    </td>

    <td>
        <?php echo esc($u['created_at']); ?>
    </td>

</tr>

<?php endforeach; ?>

<?php if (!$users): ?>

<tr>
    <td colspan="5">
        No registered users.
    </td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>

<p class="muted">
    Showing up to 25 latest accounts.
    User roles are read-only on this page.
</p>

</section>

<!-- Route Analytics -->

<section class="panel" id="routes">

<h2>Most Saved Routes</h2>

<div class="scroll">

<table>

<thead>
<tr>
    <th>Route</th>
    <th>Saved Plans</th>
</tr>
</thead>

<tbody>

<?php foreach ($topRoutes as $r): ?>

<tr>

    <td>
        <?php
        echo esc(
            $r['from_city'] . ' → ' .
            $r['to_city']
        );
        ?>
    </td>

    <td>
        <?php echo (int)$r['total']; ?>
    </td>

</tr>

<?php endforeach; ?>

<?php if (!$topRoutes): ?>

<tr>
    <td colspan="2">
        No route data yet.
    </td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>

</section>

</main>

</body>
</html>