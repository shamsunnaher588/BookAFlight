<?php
require_once __DIR__ . '/db.php';
require_login();

// BookAFlight - Travel Itinerary System
// Uses saved booking information from MySQL

$userId = (int) $_SESSION['user_id'];

$hasId = isset($_GET['id']);

$bookingId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$booking = null;
$passengers = [];
$recent = [];

// HTML output protection
function ticketText($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Format dates
function ticketDate($date) {
    $timestamp = strtotime((string)$date);

    return $timestamp
        ? date('D, d M Y', $timestamp)
        : (string)$date;
}

// Internal booking reference
function makeReference($id) {
    return strtoupper(
        str_pad(
            base_convert(
                (string)((int)$id + 30000000),
                10,
                36
            ),
            6,
            '0',
            STR_PAD_LEFT
        )
    );
}

// Booking status labels
$statusLabels = [
    'saved' => 'Saved Flight Plan',
    'reviewing' => 'Plan Under Review',
    'cancelled' => 'Cancelled Plan'
];

// Payment status labels
$paymentLabels = [
    'not_started' => 'Not Started',
    'pending' => 'Payment Pending',
    'paid' => 'Paid',
    'failed' => 'Payment Failed',
    'refunded' => 'Refunded'
];

// Load selected booking
if ($hasId && $bookingId) {

    $stmt = $pdo->prepare(
        'SELECT
            b.*,
            u.full_name AS account_name,
            u.email AS account_email
         FROM bookings b
         INNER JOIN users u
            ON u.id = b.user_id
         WHERE b.id = ?
         AND b.user_id = ?
         LIMIT 1'
    );

    $stmt->execute([
        $bookingId,
        $userId
    ]);

    $booking = $stmt->fetch();

    if ($booking) {

        $stmt = $pdo->prepare(
            'SELECT
                first_name,
                last_name,
                date_of_birth,
                seat_number
             FROM booking_passengers
             WHERE booking_id = ?
             ORDER BY id ASC'
        );

        $stmt->execute([$bookingId]);

        $passengers = $stmt->fetchAll();
    }
}

// Load booking list
if (!$hasId) {

    $stmt = $pdo->prepare(
        'SELECT
            id,
            booking_reference,
            airline,
            from_city,
            to_city,
            depart_date,
            passenger_count,
            preview_total,
            plan_status,
            payment_status
         FROM bookings
         WHERE user_id = ?
         ORDER BY id DESC
         LIMIT 50'
    );

    $stmt->execute([$userId]);

    $recent = $stmt->fetchAll();
}

// Current booking details
$reference = '';
$planStatus = '';
$paymentStatus = '';

if ($booking) {

    $reference =
        $booking['booking_reference']
        ?: makeReference($booking['id']);

    $planStatus = $statusLabels[
        $booking['plan_status']
    ] ?? 'Saved Flight Plan';

    $paymentStatus = $paymentLabels[
        $booking['payment_status']
    ] ?? 'Not Started';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>Flight Itinerary | BookAFlight</title>

<style>

/* General Design */

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f3f7fd;
    color: #172c4d;
}

a {
    color: #086db8;
    text-decoration: none;
}

header {
    background: #082858;
    color: white;
    padding: 22px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.logo {
    font-weight: bold;
    font-size: 25px;
}

.logo span {
    color: #ffbb43;
}

nav {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
}

nav a {
    color: white;
    font-size: 14px;
}

.container {
    max-width: 1050px;
    width: 93%;
    margin: 35px auto;
}

h1 {
    font-size: 30px;
}

h2 {
    font-size: 21px;
}

.muted {
    color: #708095;
    line-height: 1.6;
}

/* Ticket Container */

.ticket {
    background: white;
    border-radius: 17px;
    overflow: hidden;
    box-shadow: 0 8px 30px #08285817;
    border: 1px solid #e0e9f4;
}

/* Ticket Header */

.ticket-header {
    background: linear-gradient(
        120deg,
        #082858,
        #087fc4
    );
    color: white;
    padding: 33px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.ticket-header h1 {
    margin: 8px 0;
    font-size: 30px;
}

.ticket-header p {
    margin: 0;
    font-size: 13px;
    color: #dceeff;
}

.reference-box {
    background: #ffffff20;
    border: 1px solid #ffffff55;
    padding: 17px 23px;
    border-radius: 12px;
}

.reference-box small {
    display: block;
    color: #dceeff;
    margin-bottom: 7px;
}

.reference {
    font-size: 28px;
    letter-spacing: 3px;
    font-weight: bold;
}

/* Booking and Payment Status */

.status-bar {
    padding: 20px 33px;
    background: #f9fbff;
    border-bottom: 1px solid #e1e8f2;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.status {
    display: inline-block;
    background: #fff0cc;
    color: #795710;
    padding: 9px 15px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: bold;
}

.status.cancelled {
    background: #ffe4e4;
    color: #a12929;
}

.status.reviewing {
    background: #e4f0ff;
    color: #145b9b;
}

.payment-label {
    color: #687b93;
    font-size: 14px;
}

/* Ticket Sections */

.section {
    padding: 28px 33px;
    border-bottom: 1px solid #e2eaf4;
}

.section:last-child {
    border-bottom: 0;
}

.section h2 {
    margin-top: 0;
    margin-bottom: 23px;
}

/* Flight Route */

.route {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 22px;
    align-items: center;
    padding: 15px 0 25px;
}

.city {
    font-size: 29px;
    font-weight: bold;
    overflow-wrap: anywhere;
}

.city-label {
    color: #7d8c9e;
    font-size: 13px;
    margin-top: 8px;
}

.plane-arrow {
    font-size: 28px;
    color: #087ac2;
}

/* Information Grid */

.info-grid {
    display: grid;
    grid-template-columns:
        repeat(3, minmax(0, 1fr));
    gap: 23px;
}

.info-item small {
    display: block;
    color: #79889b;
    margin-bottom: 8px;
    font-size: 13px;
}

.info-item strong {
    font-size: 15px;
    overflow-wrap: anywhere;
}

/* Passenger Table */

.table-wrap {
    overflow-x: auto;
}

table {
    border-collapse: collapse;
    width: 100%;
    min-width: 500px;
    font-size: 14px;
}

th {
    background: #f2f7fd;
    color: #526b85;
}

th,
td {
    padding: 15px 13px;
    border-bottom: 1px solid #e4ebf4;
    text-align: left;
}

/* Fare Summary */

.fare-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.fare {
    color: #076fba;
    font-size: 29px;
    font-weight: bold;
}

/* Buttons */

.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 13px;
    margin: 25px 0;
}

.btn {
    display: inline-block;
    background: #ffbd43;
    color: #182d48;
    border: none;
    border-radius: 9px;
    padding: 14px 22px;
    cursor: pointer;
    font-size: 15px;
    font-weight: bold;
}

.btn.secondary {
    background: #e0ecfa;
    color: #174c7c;
}

/* Booking List */

.booking-card {
    background: white;
    border-radius: 14px;
    padding: 23px;
    margin: 17px 0;
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
    box-shadow: 0 5px 18px #0828580d;
}

.booking-card h2 {
    margin: 0 0 12px;
}

.booking-card p {
    margin: 7px 0;
}

.notice {
    color: #6e5c2e;
    background: #fff6df;
    border-radius: 10px;
    padding: 17px;
    line-height: 1.7;
    font-size: 13px;
}

.empty {
    padding: 30px;
    background: white;
    border-radius: 13px;
}

/* Responsive */

@media(max-width:650px) {

    .ticket-header,
    .status-bar,
    .section {
        padding: 22px;
    }

    .route {
        gap: 10px;
    }

    .city {
        font-size: 21px;
    }

    .info-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .reference {
        font-size: 24px;
    }

    .ticket-header h1 {
        font-size: 25px;
    }

}

/* A4 Print / PDF */

@media print {

    @page {
        size: A4;
        margin: 12mm;
    }

    body {
        background: white;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    header,
    .actions,
    .no-print {
        display: none !important;
    }

    .container {
        width: 100%;
        margin: 0;
    }

    .ticket {
        box-shadow: none;
        border-radius: 0;
    }

    .ticket-header {
        background: #082858 !important;
    }

    .section {
        break-inside: avoid;
    }

}
</style>

</head>

<body>

<header>

    <div class="logo">
        ✈ <span>Book</span>AFlight
    </div>

    <nav>
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="my-bookings.php">My Bookings</a>
    </nav>

</header>

<main class="container">

<?php if ($booking): ?>

<!-- Action Buttons -->

<div class="actions">

    <a
        href="ticket.php"
        class="btn secondary"
    >
        ← All Itineraries
    </a>

    <button
        class="btn"
        type="button"
        onclick="window.print()"
    >
        Download / Print PDF
    </button>

</div>

<!-- Ticket -->

<article class="ticket">

    <!-- Header -->

    <section class="ticket-header">

        <div>
            <p>BookAFlight Travel Documents</p>

            <h1>Flight Itinerary</h1>

            <p>Your Journey Starts Here</p>
        </div>

        <div class="reference-box">

            <small>
                BOOKING REFERENCE
            </small>

            <div class="reference">
                <?php echo ticketText($reference); ?>
            </div>

        </div>

    </section>

    <!-- Status & Payment -->

    <section class="status-bar">

        <span class="status <?php
            echo ticketText(
                $booking['plan_status']
            );
        ?>">

            <?php
            echo ticketText($planStatus);
            ?>

        </span>

        <span class="payment-label">

            Payment:

            <strong>
                <?php
                echo ticketText($paymentStatus);
                ?>
            </strong>

        </span>

    </section>

    <!-- Flight Details -->

    <section class="section">

        <h2>✈ Flight Details</h2>

        <div class="route">

            <div>

                <div class="city">
                    <?php
                    echo ticketText(
                        ucwords(
                            $booking['from_city']
                        )
                    );
                    ?>
                </div>

                <div class="city-label">
                    Departure City
                </div>

            </div>

            <div class="plane-arrow">
                ✈ →
            </div>

            <div>

                <div class="city">
                    <?php
                    echo ticketText(
                        ucwords(
                            $booking['to_city']
                        )
                    );
                    ?>
                </div>

                <div class="city-label">
                    Arrival City
                </div>

            </div>

        </div>

        <div class="info-grid">

            <div class="info-item">

                <small>Airline</small>

                <strong>
                    <?php
                    echo ticketText(
                        $booking['airline']
                    );
                    ?>
                </strong>

            </div>

            <div class="info-item">

                <small>Travel Date</small>

                <strong>
                    <?php
                    echo ticketText(
                        ticketDate(
                            $booking['depart_date']
                        )
                    );
                    ?>
                </strong>

            </div>

            <div class="info-item">

                <small>Cabin Class</small>

                <strong>
                    <?php
                    echo ticketText(
                        $booking['cabin']
                    );
                    ?>
                </strong>

            </div>

            <div class="info-item">

                <small>Departure Time</small>

                <strong>
                    <?php
                    echo ticketText(
                        $booking['departure_time']
                    );
                    ?>
                </strong>

            </div>

            <div class="info-item">

                <small>Arrival Time</small>

                <strong>
                    <?php
                    echo ticketText(
                        $booking['arrival_time']
                    );
                    ?>
                </strong>

            </div>

            <div class="info-item">

                <small>Passengers</small>

                <strong>
                    <?php
                    echo (int)$booking['passenger_count'];
                    ?>
                </strong>

            </div>

        </div>

    </section>

    <!-- Passenger Details -->

    <section class="section">

        <h2>Passenger Details</h2>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Passenger Name</th>
                        <th>Date of Birth</th>
                        <th>Seat</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach (
                    $passengers as $i => $person
                ): ?>

                    <tr>

                        <td>
                            <?php echo $i + 1; ?>
                        </td>

                        <td>
                            <?php
                            echo ticketText(
                                $person['first_name']
                                . ' ' .
                                $person['last_name']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo ticketText(
                                $person['date_of_birth']
                            );
                            ?>
                        </td>

                        <td>
                            <strong>
                                <?php
                                echo ticketText(
                                    $person['seat_number']
                                );
                                ?>
                            </strong>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

    <!-- Contact Information -->

    <section class="section">

        <h2>Booking Contact</h2>

        <div class="info-grid">

            <div class="info-item">

                <small>Account Name</small>

                <strong>
                    <?php
                    echo ticketText(
                        $booking['account_name']
                    );
                    ?>
                </strong>

            </div>

            <div class="info-item">

                <small>Email Address</small>

                <strong>
                    <?php
                    echo ticketText(
                        $booking['account_email']
                    );
                    ?>
                </strong>

            </div>

            <div class="info-item">

                <small>Reference</small>

                <strong>
                    <?php
                    echo ticketText($reference);
                    ?>
                </strong>

            </div>

        </div>

    </section>

    <!-- Fare Summary -->

    <section class="section">

        <h2>Fare Summary</h2>

        <div class="fare-box">

            <div>
                <strong>Estimated Total Fare</strong>

                <p class="muted">
                    <?php
                    echo (int)$booking['passenger_count'];
                    ?>
                    Passenger(s)
                </p>
            </div>

            <div class="fare">
                ৳<?php
                echo number_format(
                    (float)$booking['preview_total'],
                    0
                );
                ?>
            </div>

        </div>

    </section>

    <!-- Reference Notice -->

    <section class="section">

        <div class="notice">
            This itinerary contains a BookAFlight
            internal booking reference.
            It is not an airline-issued PNR
            or a confirmed flight ticket.
            Airline ticket issuance and payment
            verification require live provider
            integration. This document cannot
            be used for boarding.
        </div>

    </section>

</article>

<?php elseif ($hasId): ?>

<div class="empty">

    <h1>Itinerary Not Found</h1>

    <p>
        The requested itinerary does not exist
        or is not accessible to your account.
    </p>

    <a href="ticket.php">
        View My Itineraries
    </a>

</div>

<?php else: ?>

<!-- All Itineraries -->

<h1>My Travel Itineraries</h1>

<p class="muted">
    View your saved flight plans,
    selected seats and booking references.
</p>

<?php if (!$recent): ?>

<div class="empty">

    <h2>No Itineraries Found</h2>

    <p>
        You haven't saved a flight plan yet.
    </p>

    <a href="index.php">
        Search Flights →
    </a>

</div>

<?php endif; ?>

<?php foreach ($recent as $item): ?>

<div class="booking-card">

    <div>

        <h2>
            <?php
            echo ticketText(
                $item['from_city']
                . ' → ' .
                $item['to_city']
            );
            ?>
        </h2>

        <p class="muted">

            <?php
            echo ticketText(
                $item['airline']
            );
            ?>

            |

            <?php
            echo ticketText(
                ticketDate(
                    $item['depart_date']
                )
            );
            ?>

        </p>

        <p>
            <strong>
                Reference:
                <?php
                echo ticketText(
                    $item['booking_reference']
                    ?: makeReference($item['id'])
                );
                ?>
            </strong>
        </p>

        <p class="muted">

            Status:

            <?php
            echo ticketText(
                $statusLabels[
                    $item['plan_status']
                ] ?? 'Saved Flight Plan'
            );
            ?>

        </p>

        <p class="muted">

            Payment:

            <?php
            echo ticketText(
                $paymentLabels[
                    $item['payment_status']
                ] ?? 'Not Started'
            );
            ?>

        </p>

        <p>
            <strong>
                Estimated Fare:
                ৳<?php
                echo number_format(
                    (float)$item['preview_total'],
                    0
                );
                ?>
            </strong>
        </p>

    </div>

    <a
        class="btn"
        href="ticket.php?id=<?php
            echo (int)$item['id'];
        ?>"
    >
        View Itinerary →
    </a>

</div>

<?php endforeach; ?>

<?php endif; ?>

</main>

</body>
</html>