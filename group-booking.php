<?php
require_once __DIR__ . '/db.php';
require_login();

function gbEsc($v): string {
    return htmlspecialchars(
        (string)$v, ENT_QUOTES, 'UTF-8'
    );
}

function gbStr($key, $default = ''): string {
    return is_string($_POST[$key] ?? null)
        ? trim($_POST[$key]) : $default;
}

function gbDate(string $date): bool {
    $d = DateTimeImmutable::createFromFormat(
        '!Y-m-d', $date
    );
    return $d !== false
        && $d->format('Y-m-d') === $date;
}

$errors = [];
$trip = gbStr('trip_type', 'oneway');
$count = (int)gbStr('passenger_count', '7');
$cabin = gbStr('cabin', 'Economy');

$firstNames = is_array(
    $_POST['first_name'] ?? null
) ? $_POST['first_name'] : [];

$lastNames = is_array(
    $_POST['last_name'] ?? null
) ? $_POST['last_name'] : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!valid_csrf(
        $_POST['csrf_token'] ?? null
    )) {
        $errors[] =
            'Session expired. Refresh and try again.';
    }

    if (!in_array(
        $trip,
        ['oneway','roundtrip','multicity'],
        true
    )) {
        $errors[] = 'Invalid trip type.';
    }

    if ($count < 7 || $count > 30) {
        $errors[] =
            'Group size must be between 7 and 30.';
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
        $errors[] = 'Invalid cabin.';
    }

    $name = gbStr('contact_name');
    $email = gbStr('contact_email');
    $phone = gbStr('contact_phone');
    $notes = gbStr('notes');

    if (
        strlen($name) < 2 ||
        strlen($name) > 120
    ) {
        $errors[] = 'Enter a valid contact name.';
    }

    if (
        strlen($email) > 190 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $errors[] = 'Enter a valid email.';
    }

    if (!preg_match(
        '/^\+?[0-9 ()-]{7,30}$/',
        $phone
    )) {
        $errors[] = 'Enter a valid phone number.';
    }

    if (strlen($notes) > 1500) {
        $errors[] =
            'Notes must be under 1500 characters.';
    }

    $from = gbStr('from_airport');
    $to = gbStr('to_airport');
    $depart = gbStr('depart_date');

    $return = gbStr('return_date');

    $from2 = gbStr('from_airport_2');
    $to2 = gbStr('to_airport_2');
    $depart2 = gbStr('depart_date_2');

    $legs = [
        [
            'from' => $from,
            'to' => $to,
            'date' => $depart
        ]
    ];

    if ($trip === 'roundtrip') {

        $legs[] = [
            'from' => $to,
            'to' => $from,
            'date' => $return
        ];

    } elseif ($trip === 'multicity') {

        $legs[] = [
            'from' => $from2,
            'to' => $to2,
            'date' => $depart2
        ];
    }

    foreach ($legs as $i => $leg) {

        if (
            strlen($leg['from']) < 2 ||
            strlen($leg['from']) > 100 ||
            strlen($leg['to']) < 2 ||
            strlen($leg['to']) > 100 ||
            strcasecmp(
                $leg['from'],
                $leg['to']
            ) === 0
        ) {
            $errors[] =
                'Check airports for Flight ' .
                ($i + 1) . '.';
        }

        if (
            !gbDate($leg['date']) ||
            $leg['date'] < date('Y-m-d')
        ) {
            $errors[] =
                'Choose a future date for Flight ' .
                ($i + 1) . '.';
        }
    }

    if (
        count($legs) === 2 &&
        gbDate($legs[0]['date']) &&
        gbDate($legs[1]['date']) &&
        $legs[1]['date'] < $legs[0]['date']
    ) {
        $errors[] =
            'Flight 2 must not be earlier than Flight 1.';
    }

    $people = [];

    if (
        count($firstNames) !== $count ||
        count($lastNames) !== $count
    ) {
        $errors[] =
            'Complete the names for all passengers.';

    } else {

        for ($i = 0; $i < $count; $i++) {

            $fn = is_string(
                $firstNames[$i] ?? null
            ) ? trim($firstNames[$i]) : '';

            $ln = is_string(
                $lastNames[$i] ?? null
            ) ? trim($lastNames[$i]) : '';

            if (
                strlen($fn) < 2 ||
                strlen($fn) > 100 ||
                strlen($ln) < 2 ||
                strlen($ln) > 100
            ) {
                $errors[] =
                    'Check passenger ' .
                    ($i + 1) . ' name.';
            }

            $people[] = [$fn, $ln];
        }
    }

    if (!$errors) {

        try {

            $reference = 'GB' . strtoupper(
                bin2hex(random_bytes(8))
            );

            $pdo->beginTransaction();

            $s = $pdo->prepare(
                'INSERT INTO group_booking_requests
                (
                    request_ref,
                    user_id,
                    contact_name,
                    contact_email,
                    contact_phone,
                    trip_type,
                    cabin,
                    passenger_count,
                    notes
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $s->execute([
                $reference,
                (int)$_SESSION['user_id'],
                $name,
                $email,
                $phone,
                $trip,
                $cabin,
                $count,
                $notes
            ]);

            $requestId =
                (int)$pdo->lastInsertId();

            $sLeg = $pdo->prepare(
                'INSERT INTO group_booking_legs
                (
                    request_id,
                    leg_no,
                    from_airport,
                    to_airport,
                    depart_date
                )
                VALUES (?, ?, ?, ?, ?)'
            );

            foreach ($legs as $i => $leg) {

                $sLeg->execute([
                    $requestId,
                    $i + 1,
                    $leg['from'],
                    $leg['to'],
                    $leg['date']
                ]);
            }

            $sPerson = $pdo->prepare(
                'INSERT INTO group_booking_passengers
                (
                    request_id,
                    passenger_no,
                    first_name,
                    last_name
                )
                VALUES (?, ?, ?, ?)'
            );

            foreach ($people as $i => $person) {

                $sPerson->execute([
                    $requestId,
                    $i + 1,
                    $person[0],
                    $person[1]
                ]);
            }

            $pdo->commit();

            header(
                'Location: group-booking.php?submitted=' .
                rawurlencode($reference)
            );

            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Group booking request failed: ' .
                $e->getMessage()
            );

            $errors[] =
                'Could not save your request. ' .
                'Check the database setup.';
        }
    }
}

$my = $pdo->prepare(
    'SELECT
        id,
        request_ref,
        trip_type,
        passenger_count,
        status,
        estimated_total,
        admin_note,
        created_at
     FROM group_booking_requests
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 30'
);

$my->execute([
    (int)$_SESSION['user_id']
]);

$myRequests = $my->fetchAll(PDO::FETCH_ASSOC);

$submitted = is_string(
    $_GET['submitted'] ?? null
) ? $_GET['submitted'] : '';
?>

<!doctype html>
<html lang="en">

<head>
<meta charset="utf-8">
<meta name="viewport"
      content="width=device-width,initial-scale=1">

<title>Group Booking | BookAFlight</title>

<style>
    [hidden] {  
    display: none !important;  
}
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f3f7fc;
    color: #142b4a;
    font-family: Arial, sans-serif;
}

header {
    padding: 20px 6%;
    background: #082858;
    color: white;
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

header a {
    color: white;
}

main {
    width: 94%;
    max-width: 1050px;
    margin: 25px auto;
}

.card {
    background: white;
    padding: 22px;
    border-radius: 14px;
    margin: 18px 0;
    box-shadow: 0 4px 18px #0001;
}

.grid {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.full {
    grid-column: 1/-1;
}

label {
    display: block;
    font-weight: 600;
    font-size: 14px;
}

input,
select,
textarea {
    display: block;
    width: 100%;
    padding: 11px;
    margin-top: 7px;
    border: 1px solid #c9d7e9;
    border-radius: 8px;
    font: inherit;
}

input:focus,
select:focus,
textarea:focus {
    outline: 2px solid #77b9ec;
}

.passenger {
    display: grid;
    grid-template-columns: 55px 1fr 1fr;
    gap: 12px;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #edf1f5;
}

button,
.btn {
    background: #ffbc43;
    border: 0;
    border-radius: 9px;
    padding: 13px 20px;
    font-weight: bold;
    cursor: pointer;
    color: #172b4d;
}

.notice {
    background: #fff4d8;
    padding: 14px;
    border-radius: 10px;
    line-height: 1.6;
}

.error {
    background: #ffeaea;
    color: #912a2a;
    padding: 12px;
    border-radius: 8px;
}

.ok {
    background: #e5f7ed;
    padding: 12px;
    border-radius: 8px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

td,
th {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #e5ebf3;
    vertical-align: top;
}

th {
    background: #eef5fd;
}

@media(max-width:650px) {
    .grid {
        grid-template-columns: 1fr;
    }

    .full {
        grid-column: auto;
    }

    .passenger {
        grid-template-columns: 1fr 1fr;
    }

    .passenger strong {
        grid-column: 1/-1;
    }

    table {
        font-size: 12px;
    }
}
</style>
</head>

<body>

<header>
    <strong>✈ BookAFlight — Group Booking</strong>
    <a href="index.php">Home</a>
    <a href="my-bookings.php">My Bookings</a>
</header>

<main>

<h1>Group Booking — 7 to 30 People</h1>

<p class="notice">
    Request a group travel quote for 7–30 people.
    This is <strong>not</strong> an airline
    reservation or an issued ticket.
    No payment or seat is reserved.
    For 1–6 people, use the normal flight search.
</p>

<?php if ($errors): ?>

<div class="error">
    <strong>Please fix:</strong>

    <ul>
    <?php foreach (array_unique($errors) as $e): ?>
        <li><?php echo gbEsc($e); ?></li>
    <?php endforeach; ?>
    </ul>
</div>

<?php endif; ?>

<?php if ($submitted): ?>

    <?php foreach ($myRequests as $r): ?>

        <?php if ($r['request_ref'] === $submitted): ?>

        <div class="ok">
            ✅ Group request saved.
            Your reference is
            <strong>
                <?php echo gbEsc($r['request_ref']); ?>
            </strong>.

            Status:
            <?php echo gbEsc($r['status']); ?>.
        </div>

        <?php break; ?>

        <?php endif; ?>

    <?php endforeach; ?>

<?php endif; ?>

<section class="card">

<h2>New Group Request</h2>

<form method="post" id="groupForm">

<input
    type="hidden"
    name="csrf_token"
    value="<?php echo gbEsc(csrf_token()); ?>"
>

<div class="grid">

    <label>
        Trip type

        <select name="trip_type" id="trip" required>

        <?php foreach (
            [
                'oneway' => 'One-Way',
                'roundtrip' => 'Round-Trip',
                'multicity' => 'Multi-City (2 flights)'
            ] as $value => $label
        ): ?>

            <option
                value="<?php echo gbEsc($value); ?>"
                <?php echo $trip === $value
                    ? 'selected' : ''; ?>
            >
                <?php echo gbEsc($label); ?>
            </option>

        <?php endforeach; ?>

        </select>
    </label>

    <label>
        People (7–30)

        <input
            type="number"
            name="passenger_count"
            id="count"
            min="7"
            max="30"
            value="<?php echo gbEsc(
                max(7, min(30, $count))
            ); ?>"
            required
        >
    </label>

    <label>
        Cabin

        <select name="cabin">

        <?php foreach (
            [
                'Economy',
                'Premium Economy',
                'Business',
                'First Class'
            ] as $option
        ): ?>

        <option
            value="<?php echo gbEsc($option); ?>"
            <?php echo $cabin === $option
                ? 'selected' : ''; ?>
        >
            <?php echo gbEsc($option); ?>
        </option>

        <?php endforeach; ?>

        </select>
    </label>

    <label>
        Contact full name

        <input
            name="contact_name"
            maxlength="120"
            required
            value="<?php echo gbEsc(
                gbStr('contact_name')
            ); ?>"
        >
    </label>

    <label>
        Email

        <input
            type="email"
            name="contact_email"
            maxlength="190"
            required
            value="<?php echo gbEsc(
                gbStr('contact_email')
            ); ?>"
        >
    </label>

    <label>
        Phone

        <input
            type="tel"
            name="contact_phone"
            maxlength="30"
            required
            value="<?php echo gbEsc(
                gbStr('contact_phone')
            ); ?>"
        >
    </label>

    <label>
        Flight 1 — From

        <input
            name="from_airport"
            required
            maxlength="100"
            placeholder="Dhaka (DAC)"
            value="<?php echo gbEsc(
                gbStr('from_airport')
            ); ?>"
        >
    </label>

    <label>
        Flight 1 — To

        <input
            name="to_airport"
            required
            maxlength="100"
            placeholder="Dubai (DXB)"
            value="<?php echo gbEsc(
                gbStr('to_airport')
            ); ?>"
        >
    </label>

    <label>
        Flight 1 — Date

        <input
            type="date"
            name="depart_date"
            min="<?php echo date('Y-m-d'); ?>"
            required
            value="<?php echo gbEsc(
                gbStr('depart_date')
            ); ?>"
        >
    </label>

    <div id="returnFields" class="full" hidden>

        <label>
            Return date

            <input
                type="date"
                name="return_date"
                id="returnDate"
                min="<?php echo date('Y-m-d'); ?>"
                value="<?php echo gbEsc(
                    gbStr('return_date')
                ); ?>"
            >
        </label>

    </div>

    <div id="multiFields" class="full grid" hidden>

        <label>
            Flight 2 — From

            <input
                name="from_airport_2"
                id="from2"
                maxlength="100"
                value="<?php echo gbEsc(
                    gbStr('from_airport_2')
                ); ?>"
            >
        </label>

        <label>
            Flight 2 — To

            <input
                name="to_airport_2"
                id="to2"
                maxlength="100"
                value="<?php echo gbEsc(
                    gbStr('to_airport_2')
                ); ?>"
            >
        </label>

        <label>
            Flight 2 — Date

            <input
                type="date"
                name="depart_date_2"
                id="depart2"
                min="<?php echo date('Y-m-d'); ?>"
                value="<?php echo gbEsc(
                    gbStr('depart_date_2')
                ); ?>"
            >
        </label>

    </div>

    <label class="full">
        Special requests (optional)

        <textarea
            name="notes"
            rows="3"
            maxlength="1500"
            placeholder="Preferred times, baggage, accessibility needs... (no passport/card details)"
        ><?php echo gbEsc(
            gbStr('notes')
        ); ?></textarea>
    </label>

</div>

<h3>Passengers</h3>

<p>
    Enter each traveler's first and last name.
    Do not enter passport or card details here.
</p>

<div id="passengerFields"></div>

<p>
    <button type="submit">
        Submit Group Booking Request →
    </button>
</p>

</form>
</section>

<section class="card">

<h2>My Group Requests</h2>

<?php if (!$myRequests): ?>

    <p>No group requests yet.</p>

<?php else: ?>

<div style="overflow-x:auto">

<table>

<thead>
<tr>
    <th>Reference</th>
    <th>Trip</th>
    <th>People</th>
    <th>Status</th>
    <th>Admin response</th>
</tr>
</thead>

<tbody>

<?php foreach ($myRequests as $r): ?>

<tr>

    <td>
        <?php echo gbEsc($r['request_ref']); ?>

        <small>
            <?php echo gbEsc($r['created_at']); ?>
        </small>
    </td>

    <td>
        <?php echo gbEsc($r['trip_type']); ?>
    </td>

    <td>
        <?php echo (int)$r['passenger_count']; ?>
    </td>

    <td>
        <?php echo gbEsc($r['status']); ?>
    </td>

    <td>

        <?php if (
            $r['estimated_total'] !== null
        ): ?>

            Indicative quote:
            ৳<?php echo number_format(
                (float)$r['estimated_total'],
                2
            ); ?>

        <?php endif; ?>

        <?php echo nl2br(
            gbEsc(
                $r['admin_note']
                ?? 'Awaiting review'
            )
        ); ?>

    </td>

</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>

<?php endif; ?>

</section>

</main>

<?php
if (is_file(__DIR__ . '/support.php')) {
    include_once __DIR__ . '/support.php';
}
?>

<script>
(() => {
    'use strict';

    const $ = id => document.getElementById(id);

    const oldFirst = <?php echo json_encode(
        array_values(
            array_filter($firstNames, 'is_string')
        ),
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_INVALID_UTF8_SUBSTITUTE
    ); ?>;

    const oldLast = <?php echo json_encode(
        array_values(
            array_filter($lastNames, 'is_string')
        ),
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_INVALID_UTF8_SUBSTITUTE
    ); ?>;

    function route() {

        const type = $('trip').value;

        $('returnFields').hidden =
            type !== 'roundtrip';

        $('multiFields').hidden =
            type !== 'multicity';

        $('returnDate').required =
            type === 'roundtrip';

        for (
            const id of [
                'from2',
                'to2',
                'depart2'
            ]
        ) {
            $(id).required =
                type === 'multicity';
        }
    }

    function passengers(initial = false) {

        const root = $('passengerFields');

        const previous = initial
            ? []
            : [
                ...root.querySelectorAll('.passenger')
            ].map(div => [
                div.querySelector(
                    '[name="first_name[]"]'
                ).value,
                div.querySelector(
                    '[name="last_name[]"]'
                ).value
            ]);

        const count = Math.min(
            30,
            Math.max(
                7,
                Number($('count').value) || 7
            )
        );

        root.replaceChildren();

        for (let i = 0; i < count; i++) {

            const row =
                document.createElement('div');

            row.className = 'passenger';

            const heading =
                document.createElement('strong');

            heading.textContent = '#' + (i + 1);

            row.append(heading);

            for (
                const [k, label] of [
                    ['first', 'First name'],
                    ['last', 'Last name']
                ]
            ) {

                const holder =
                    document.createElement('label');

                holder.textContent = label;

                const input =
                    document.createElement('input');

                input.name = k + '_name[]';
                input.required = true;
                input.minLength = 2;
                input.maxLength = 100;

                input.value = (
                    initial
                        ? (
                            k === 'first'
                                ? oldFirst[i]
                                : oldLast[i]
                        )
                        : previous[i]?.[
                            k === 'first' ? 0 : 1
                        ]
                ) || '';

                holder.append(input);
                row.append(holder);
            }

            root.append(row);
        }
    }

    $('trip').addEventListener(
        'change',
        route
    );

    $('count').addEventListener(
        'change',
        () => passengers(false)
    );

    route();
    passengers(true);
})();
</script>
<?php include_once __DIR__ . '/theme-toggle.php'; ?>
<?php include_once __DIR__ . '/responsive-layout.php'; ?>
</body>
</html>