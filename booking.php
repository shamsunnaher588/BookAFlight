<?php
require_once __DIR__ . '/db.php';
require_login();

// Illustrative flight data - not live airline bookings.
$flights = [
    1 => [
        'name' => 'Airline A',
        'out' => '08:30',
        'in' => '13:10',
        'price' => 32500
    ],
    2 => [
        'name' => 'Airline B',
        'out' => '11:45',
        'in' => '18:30',
        'price' => 28900
    ],
    3 => [
        'name' => 'Airline C',
        'out' => '19:20',
        'in' => '23:55',
        'price' => 38700
    ],
    4 => [
        'name' => 'Airline D',
        'out' => '06:15',
        'in' => '12:45',
        'price' => 26900
    ]
];

$blocked = ['1A', '2C', '3E', '5B', '6D', '7F'];

$source = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? $_POST : $_GET;

function field(array $data, string $key): string {
    return is_string($data[$key] ?? null)
        ? trim($data[$key])
        : '';
}

function oldPassenger(string $key, int $i): string {
    $value = $_POST[$key][$i] ?? null;
    return is_string($value) ? $value : '';
}

function validFlightDate(string $value): bool {
    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $value
    );

    return $date !== false &&
        $date->format('Y-m-d') === $value;
}

$trip = field($source, 'trip');
$isMulti = $trip === 'multicity';
$cabin = field($source, 'cabin');
$count = (int)field($source, 'passengers');
$returnDate = field($source, 'return_date');
$errors = [];

if (!in_array(
    $trip,
    ['oneway', 'roundtrip', 'multicity'],
    true
)) {
    $errors[] = 'Select a valid trip type.';
}

if ($count < 1 || $count > 6) {
    $errors[] = 'Choose 1 to 6 passengers.';
}

if (!in_array(
    $cabin,
    [
        'Economy',
        'Premium Economy',
        'Business',
        'First Class'
    ],
    true
)) {
    $errors[] = 'Choose a valid cabin class.';
}

// Multi-City supports two legs.
// One-Way and Round-Trip keep their old URL format.
$legs = [];

for ($n = 1; $n <= ($isMulti ? 2 : 1); $n++) {
    $suffix = $isMulti ? (string)$n : '';

    $id = (int)field(
        $source,
        'flight' . $suffix
    );

    $leg = [
        'id' => $id,
        'flight' => $flights[$id] ?? null,
        'from' => field($source, 'from' . $suffix),
        'to' => field($source, 'to' . $suffix),
        'date' => field($source, 'depart' . $suffix)
    ];

    $legs[$n] = $leg;

    if (!$leg['flight']) {
        $errors[] = "Choose a valid Flight $n.";
    }

    if (
        strlen($leg['from']) < 2 ||
        strlen($leg['from']) > 100 ||
        strlen($leg['to']) < 2 ||
        strlen($leg['to']) > 100 ||
        strcasecmp($leg['from'], $leg['to']) === 0
    ) {
        $errors[] = "Check From and To for Flight $n.";
    }

    if (
        !validFlightDate($leg['date']) ||
        $leg['date'] < date('Y-m-d')
    ) {
        $errors[] =
            "Choose a valid departure date for Flight $n.";
    }
}

if (
    $isMulti &&
    validFlightDate($legs[1]['date']) &&
    validFlightDate($legs[2]['date']) &&
    $legs[2]['date'] < $legs[1]['date']
) {
    $errors[] =
        'Flight 2 cannot depart before Flight 1.';
}

if (
    $trip === 'roundtrip' &&
    $returnDate !== '' &&
    (
        !validFlightDate($returnDate) ||
        $returnDate < $legs[1]['date']
    )
) {
    $errors[] = 'Check the return date.';
}

$selectedSeats = [];
$names = [];

// Process booking form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!valid_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] =
            'Session expired. Refresh and try again.';
    }

    $rawSeats = $_POST['seats'] ?? null;

    foreach ($legs as $n => $leg) {

        $chosen = $isMulti
            ? (
                is_array($rawSeats)
                    ? ($rawSeats[$n] ?? null)
                    : null
            )
            : $rawSeats;

        $selectedSeats[$n] = [];

        if (
            !is_array($chosen) ||
            count($chosen) !== $count
        ) {
            $errors[] =
                "Select $count seat(s) for Flight $n.";
            continue;
        }

        foreach ($chosen as $seat) {

            if (
                !is_string($seat) ||
                !preg_match('/^[1-8][A-F]$/', $seat) ||
                in_array($seat, $blocked, true)
            ) {
                $errors[] =
                    "Invalid seat for Flight $n.";
            } else {
                $selectedSeats[$n][] = $seat;
            }
        }

        if (
            count(array_unique($selectedSeats[$n]))
            !== $count
        ) {
            $errors[] =
                "Seats must be different in Flight $n.";
        }
    }

    foreach (['first', 'last', 'dob'] as $key) {

        if (
            !is_array($_POST[$key] ?? null) ||
            count($_POST[$key]) !== $count
        ) {
            $errors[] =
                'Complete all passenger information.';
            break;
        }
    }

    if (
        is_array($_POST['first'] ?? null) &&
        is_array($_POST['last'] ?? null) &&
        is_array($_POST['dob'] ?? null)
    ) {

        for ($i = 0; $i < $count; $i++) {

            $first = trim(
                oldPassenger('first', $i)
            );

            $last = trim(
                oldPassenger('last', $i)
            );

            $dob = oldPassenger('dob', $i);

            if (
                strlen($first) < 2 ||
                strlen($first) > 120 ||
                strlen($last) < 2 ||
                strlen($last) > 120 ||
                !validFlightDate($dob) ||
                $dob > date('Y-m-d')
            ) {
                $errors[] =
                    'Check passenger ' . ($i + 1);
            }

            $names[] = [
                $first,
                $last,
                $dob
            ];
        }
    }

    // Save each leg as a separate local plan.
    // A transaction ensures all legs save together.
    if (!$errors) {

        try {

            $pdo->beginTransaction();

            $save = $pdo->prepare(
                'INSERT INTO bookings
                (user_id, flight_id, airline,
                 from_city, to_city, depart_date,
                 departure_time, arrival_time,
                 cabin, passenger_count, preview_total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $setRef = $pdo->prepare(
                'UPDATE bookings
                 SET booking_reference = ?
                 WHERE id = ?'
            );

            $savePassenger = $pdo->prepare(
                'INSERT INTO booking_passengers
                (booking_id, first_name, last_name,
                 date_of_birth, seat_number)
                 VALUES (?, ?, ?, ?, ?)'
            );

            foreach ($legs as $n => $leg) {

                $flight = $leg['flight'];

                $save->execute([
                    $_SESSION['user_id'],
                    $leg['id'],
                    $flight['name'],
                    $leg['from'],
                    $leg['to'],
                    $leg['date'],
                    $flight['out'],
                    $flight['in'],
                    $cabin,
                    $count,
                    $count * $flight['price']
                ]);

                $bookingId =
                    (int)$pdo->lastInsertId();

                $reference = strtoupper(
                    str_pad(
                        base_convert(
                            (string)($bookingId + 30000000),
                            10,
                            36
                        ),
                        6,
                        '0',
                        STR_PAD_LEFT
                    )
                );

                $setRef->execute([
                    $reference,
                    $bookingId
                ]);

                foreach ($names as $i => $person) {

                    $savePassenger->execute([
                        $bookingId,
                        $person[0],
                        $person[1],
                        $person[2],
                        $selectedSeats[$n][$i]
                    ]);
                }
            }

            $pdo->commit();

            header(
                'Location: my-bookings.php?saved=1'
            );
            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'BookAFlight save error: ' .
                $e->getMessage()
            );

            $errors[] =
                'Could not save the plan. Check database.';
        }
    }
}

// Calculate illustrative total
$previewTotal = 0;

foreach ($legs as $leg) {

    if ($leg['flight']) {

        $previewTotal +=
            $leg['flight']['price'] * $count;
    }
}

$canShowForm =
    $count >= 1 && $count <= 6;

foreach ($legs as $leg) {

    if (!$leg['flight']) {
        $canShowForm = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>Seat Selection | BookAFlight</title>

<link rel="stylesheet" href="auth.css">

<style>

.wrap {
    max-width: 1050px;
    width: 94%;
    margin: 30px auto;
}

.notice {
    background: #fff4d8;
    padding: 14px;
    border-radius: 10px;
    margin: 15px 0;
    line-height: 1.6;
}

.grid {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.seat-map {
    width: max-content;
    max-width: 100%;
    margin: auto;
    padding: 30px 15px 18px;
    border: 2px solid #dce6f4;
    border-radius: 80px 80px 18px 18px;
    background: #f5f9fe;
}

.seat-row {
    display: grid;
    grid-template-columns:
        repeat(3, 39px) 25px repeat(3, 39px);
    gap: 5px;
    margin: 7px 0;
    align-items: center;
}

.seat {
    cursor: pointer;
    position: relative;
}

.seat input {
    position: absolute;
    opacity: 0;
    width: 1px;
    height: 1px;
}

.seat span {
    display: grid;
    place-items: center;
    height: 36px;
    background: #dcefff;
    border: 1px solid #7ebce8;
    border-radius: 7px;
    font-size: 12px;
}

.seat input:checked + span {
    background: #0879ca;
    color: white;
}

.seat input:focus-visible + span {
    outline: 3px solid #162d71;
}

.seat.off {
    cursor: not-allowed;
}

.seat.off span {
    background: #e0e3e7;
    color: #9a9fa7;
    border-color: #bec5cc;
}

.aisle {
    text-align: center;
    color: #8797aa;
    font-size: 12px;
}

.passenger {
    border: 1px solid #e3e9ef;
    border-radius: 10px;
    padding: 15px;
    margin: 15px 0;
}

.seat-holder {
    overflow-x: auto;
    padding: 10px 0;
}

.selected-label {
    text-align: center;
    font-weight: bold;
    color: #075ca1;
}

.error ul {
    margin: 5px 0;
}

.panel {
    margin: 18px 0;
}

.btn {
    max-width: 350px;
}

.muted {
    color: #6d7f94;
    line-height: 1.6;
}

.leg-heading {
    background: #eaf4ff;
    border-radius: 9px;
    padding: 10px;
}

.total {
    color: #086db8;
    font-size: 22px;
}

@media(max-width:620px) {

    .grid {
        grid-template-columns: 1fr;
    }

    .panel {
        padding: 19px;
    }
}

</style>
</head>

<body>

<header class="topbar">

    <a class="brand" href="index.php">
        ✈ <span>Book</span>AFlight
    </a>

    <a href="my-bookings.php">
        My Bookings
    </a>

</header>

<main class="wrap">

<h1>Choose Seats & Passenger Details</h1>

<div class="notice">
    Seat maps and prices are illustrative.
    Saving a plan does not reserve an airline
    seat, issue a ticket or PNR, or charge payment.

    <?php if ($isMulti): ?>
        Both flight legs will be saved as
        separate entries in My Bookings.
    <?php endif; ?>
</div>

<?php if ($errors): ?>

<div class="alert error">
    <ul>

        <?php foreach (
            array_unique($errors) as $error
        ): ?>

            <li>
                <?php echo esc($error); ?>
            </li>

        <?php endforeach; ?>

    </ul>
</div>

<?php endif; ?>

<?php if ($canShowForm): ?>

<section class="panel">

    <h2>
        <?php echo $isMulti
            ? 'Multi-City Journey'
            : 'Your Flight'; ?>
    </h2>

    <?php foreach ($legs as $n => $leg): ?>

        <div class="leg-heading">

            <strong>
                Flight <?php echo $n; ?>:
            </strong>

            <?php echo esc(
                $leg['flight']['name']
            ); ?>

            —

            <?php echo esc($leg['from']); ?>
            →
            <?php echo esc($leg['to']); ?>

        </div>

        <p>
            <?php echo esc($leg['date']); ?>

            ·

            <?php echo esc(
                $leg['flight']['out']
            ); ?>

            →

            <?php echo esc(
                $leg['flight']['in']
            ); ?>

            · <?php echo esc($cabin); ?>

            · ৳<?php echo number_format(
                $leg['flight']['price']
            ); ?> / passenger
        </p>

    <?php endforeach; ?>

    <p class="total">

        <strong>
            Illustrative total:
            ৳<?php echo number_format(
                $previewTotal
            ); ?>
        </strong>

        for <?php echo $count; ?> passenger(s)

    </p>

    <?php if ($trip === 'roundtrip'): ?>

        <p class="muted">
            Only the outbound segment is saved
            in this version; the return flight
            is not included in this saved plan.
        </p>

    <?php endif; ?>

</section>

<form method="POST"
      action="booking.php"
      id="saveForm">

<?php
$hidden = [
    'trip' => $trip,
    'cabin' => $cabin,
    'passengers' => $count,
    'return_date' => $returnDate
];

foreach ($legs as $n => $leg) {

    $suffix = $isMulti
        ? (string)$n : '';

    $hidden['flight' . $suffix] =
        $leg['id'];

    $hidden['from' . $suffix] =
        $leg['from'];

    $hidden['to' . $suffix] =
        $leg['to'];

    $hidden['depart' . $suffix] =
        $leg['date'];
}

foreach ($hidden as $key => $value):
?>

<input
    type="hidden"
    name="<?php echo esc($key); ?>"
    value="<?php echo esc($value); ?>"
>

<?php endforeach; ?>

<input
    type="hidden"
    name="csrf_token"
    value="<?php echo esc(csrf_token()); ?>"
>

<?php foreach ($legs as $n => $leg): ?>

<section class="panel seat-panel"
         data-leg="<?php echo $n; ?>">

<h2>
    1. Select <?php echo $count; ?> seat(s)
    — Flight <?php echo $n; ?>
</h2>

<p class="muted">
    A/F = Window, C/D = Aisle.
    Grey seats are unavailable in this preview.
</p>

<div class="seat-holder">

<div class="seat-map">

    <p style="text-align:center">
        ✈ FRONT
    </p>

    <?php for ($r = 1; $r <= 8; $r++): ?>

    <div class="seat-row">

    <?php foreach (
        ['A','B','C','-','D','E','F'] as $col
    ): ?>

    <?php if ($col === '-'): ?>

        <div class="aisle">
            <?php echo $r; ?>
        </div>

    <?php else:

        $seat = $r . $col;

        $off = in_array(
            $seat,
            $blocked,
            true
        );

        $seatInput = $isMulti
            ? 'seats[' . $n . '][]'
            : 'seats[]';
    ?>

    <label class="seat <?php
        echo $off ? 'off' : '';
    ?>">

        <?php if (!$off): ?>

        <input
            type="checkbox"
            name="<?php echo $seatInput; ?>"
            value="<?php echo esc($seat); ?>"
            <?php
            echo in_array(
                $seat,
                $selectedSeats[$n] ?? [],
                true
            ) ? 'checked' : '';
            ?>
            aria-label="Flight <?php
                echo $n;
            ?> seat <?php echo esc($seat); ?>"
        >

        <?php endif; ?>

        <span>
            <?php echo esc($seat); ?>
        </span>

    </label>

    <?php endif; ?>
    <?php endforeach; ?>

    </div>

    <?php endfor; ?>

</div>
</div>

<p class="selected-label">
    0 / <?php echo $count; ?> selected
</p>

</section>

<?php endforeach; ?>

<section class="panel">

<h2>2. Passenger Details</h2>

<p class="muted">
    The same passengers will be included
    in each saved flight leg.
</p>

<?php for ($i = 0; $i < $count; $i++): ?>

<div class="passenger">

<h3>
    Passenger <?php echo $i + 1; ?>
</h3>

<div class="grid">

<label>
    First Name

    <input
        type="text"
        name="first[]"
        required
        minlength="2"
        maxlength="120"
        value="<?php echo esc(
            oldPassenger('first', $i)
        ); ?>"
    >
</label>

<label>
    Last Name

    <input
        type="text"
        name="last[]"
        required
        minlength="2"
        maxlength="120"
        value="<?php echo esc(
            oldPassenger('last', $i)
        ); ?>"
    >
</label>

<label>
    Date of Birth

    <input
        type="date"
        name="dob[]"
        required
        max="<?php echo date('Y-m-d'); ?>"
        value="<?php echo esc(
            oldPassenger('dob', $i)
        ); ?>"
    >
</label>

</div>
</div>

<?php endfor; ?>

<button class="btn" type="submit">
    Save to My Bookings →
</button>

<p class="muted">
    Use test passenger details on localhost.
    No passport or card details are needed.
</p>

</section>

</form>

<?php endif; ?>

</main>

<script>

const limit = <?php
    echo max(0, min(6, $count));
?>;

const panels = [
    ...document.querySelectorAll('.seat-panel')
];

function updateSeatCounts() {

    panels.forEach(panel => {

        const boxes = [
            ...panel.querySelectorAll(
                'input[type="checkbox"]'
            )
        ];

        panel.querySelector(
            '.selected-label'
        ).textContent =
            boxes.filter(box => box.checked).length +
            ' / ' + limit + ' selected';
    });
}

panels.forEach(panel => {

    const boxes = [
        ...panel.querySelectorAll(
            'input[type="checkbox"]'
        )
    ];

    boxes.forEach(box => {

        box.addEventListener('change', () => {

            if (
                boxes.filter(
                    item => item.checked
                ).length > limit
            ) {
                box.checked = false;

                alert(
                    'Select only ' + limit +
                    ' seat(s) per flight.'
                );
            }

            updateSeatCounts();
        });
    });
});

const form = document.getElementById('saveForm');

if (form) {

    form.addEventListener('submit', event => {

        for (const panel of panels) {

            const checked =
                panel.querySelectorAll(
                    'input[type="checkbox"]:checked'
                ).length;

            if (checked !== limit) {

                event.preventDefault();

                alert(
                    'Select ' + limit +
                    ' seat(s) for Flight ' +
                    panel.dataset.leg + '.'
                );

                panel.scrollIntoView({
                    behavior: 'smooth'
                });

                return;
            }
        }
    });
}

updateSeatCounts();

</script>
<?php include_once __DIR__ . '/theme-toggle.php'; ?>
<?php include_once __DIR__ . '/responsive-layout.php'; ?>
<!-- Saved Passenger Auto-Fill -->
<?php
if (is_file(__DIR__ . '/passenger-autofill.php')) {
    include_once __DIR__ . '/passenger-autofill.php';
}
?>
</body>
</html>