<?php
require_once __DIR__ . '/db.php';
// BookAFlight - Flight Results
// Illustrative flight data. Live airline API not connected.

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function queryValue($key, $default = '') {
    $value = $_GET[$key] ?? $default;

    if (is_array($value)) {
        $value = $value[0] ?? $default;
    }

    return (is_string($value) || is_numeric($value))
        ? trim((string)$value)
        : $default;
}

function queryList($key) {
    $values = $_GET[$key] ?? [];

    if (!is_array($values)) {
        $values = [$values];
    }

    $output = [];

    foreach (array_slice(array_values($values), 0, 2) as $value) {
        $output[] = (is_string($value) || is_numeric($value))
            ? trim((string)$value)
            : '';
    }

    return $output;
}

function validDate($value) {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    return $date !== false
        && $date->format('Y-m-d') === $value;
}

// Search settings
$trip = queryValue('trip', 'oneway');

if (!in_array($trip, ['oneway', 'roundtrip', 'multicity'], true)) {
    $trip = 'oneway';
}

$cabin = queryValue('cabin', 'Economy');

$passengers = (int)queryValue('passengers', '1');

if ($passengers < 1 || $passengers > 6) {
    $passengers = 1;
}

$returnDate = queryValue('return_date');

$allFrom = queryList('from');
$allTo = queryList('to');
$allDates = queryList('depart');

$legs = [];

$legCount = $trip === 'multicity' ? 2 : 1;

for ($i = 0; $i < $legCount; $i++) {
    $legs[] = [
        'from' => $allFrom[$i] ?? ($i === 0 ? 'Dhaka' : ''),
        'to' => $allTo[$i] ?? ($i === 0 ? 'Dubai' : ''),
        'date' => $allDates[$i] ?? ''
    ];
}

$from = $legs[0]['from'];
$to = $legs[0]['to'];
$date = $legs[0]['date'];

// Multi-City validation
$errors = [];

if ($trip === 'multicity') {

    foreach ($legs as $i => $leg) {
        $number = $i + 1;

        if (
            $leg['from'] === '' ||
            $leg['to'] === '' ||
            $leg['date'] === ''
        ) {
            $errors[] =
                "Please complete Flight $number route and departure date.";
            continue;
        }

        if (strcasecmp($leg['from'], $leg['to']) === 0) {
            $errors[] =
                "Flight $number cannot have the same From and To airport.";
        }

        if (!validDate($leg['date'])) {
            $errors[] =
                "Flight $number has an invalid departure date.";
        }
    }

    if (
        validDate($legs[0]['date']) &&
        validDate($legs[1]['date']) &&
        $legs[1]['date'] < $legs[0]['date']
    ) {
        $errors[] = 'Flight 2 cannot depart before Flight 1.';
    }
}

// Sample flight options
$flights = [
    1 => [
        'airline' => 'Airline A',
        'departure' => '08:30',
        'arrival' => '13:10',
        'duration' => '4h 40m',
        'stops' => 0,
        'price' => 32500,
        'baggage' => '20 kg'
    ],
    2 => [
        'airline' => 'Airline B',
        'departure' => '11:45',
        'arrival' => '18:30',
        'duration' => '6h 45m',
        'stops' => 1,
        'price' => 28900,
        'baggage' => '25 kg'
    ],
    3 => [
        'airline' => 'Airline C',
        'departure' => '19:20',
        'arrival' => '23:55',
        'duration' => '4h 35m',
        'stops' => 0,
        'price' => 38700,
        'baggage' => '30 kg'
    ],
    4 => [
        'airline' => 'Airline D',
        'departure' => '06:15',
        'arrival' => '12:45',
        'duration' => '6h 30m',
        'stops' => 1,
        'price' => 26900,
        'baggage' => '30 kg'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>Flight Results | BookAFlight</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f3f7fd;
    color: #172c4d;
}

header {
    background: #082858;
    color: white;
    padding: 22px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

header a {
    color: white;
    text-decoration: none;
}

.logo {
    font-size: 24px;
    font-weight: bold;
}

.logo span {
    color: #ffbb3d;
}

main {
    max-width: 1100px;
    width: 92%;
    margin: 35px auto;
}

.summary {
    color: #61748c;
    line-height: 1.8;
}

.notice {
    background: #fff7df;
    color: #67552d;
    border-radius: 10px;
    padding: 15px;
    margin: 20px 0;
    line-height: 1.6;
}

.error {
    background: #fff0f0;
    color: #8d1a1a;
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
}

.error p {
    margin: 8px 0;
}

.filters,
.flight,
#compare,
#selectionSummary,
.journey-box {
    background: white;
    border-radius: 14px;
    padding: 22px;
    margin: 17px 0;
    box-shadow: 0 4px 18px #0000000b;
}

.filters {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.filters select {
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
}

.leg-group {
    margin: 30px 0;
}

.leg-heading {
    font-size: 23px;
    margin-bottom: 5px;
}

.journey-box {
    margin-top: 22px;
}

.journey-leg {
    padding: 14px 16px;
    background: #f3f7fd;
    border-radius: 9px;
    margin-top: 12px;
    line-height: 1.7;
}

.flight {
    display: grid;
    grid-template-columns: 1fr 1.2fr .85fr;
    gap: 16px;
    align-items: center;
}

.flight[hidden],
#compare[hidden],
.empty[hidden],
.total[hidden] {
    display: none !important;
}

.flight.is-selected {
    border: 2px solid #087cc6;
    background: #f7fbff;
}

.airline {
    font-size: 20px;
    font-weight: bold;
}

.muted {
    color: #6d7f94;
    font-size: 14px;
    line-height: 1.5;
}

.times {
    font-size: 22px;
    font-weight: bold;
}

.price {
    font-size: 27px;
    color: #086db8;
    font-weight: bold;
}

.right {
    text-align: right;
}

.compare-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #086db8;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
}

.btn {
    background: #ffbc43;
    color: #162e4d;
    border: 0;
    border-radius: 8px;
    padding: 13px 18px;
    font-weight: bold;
    cursor: pointer;
    width: 100%;
}

.btn:hover {
    background: #ffcf75;
}

.btn.is-picked {
    background: #087cc6;
    color: #fff;
}

.compare-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-top: 18px;
}

.compare-item {
    border: 2px solid #087cc6;
    border-radius: 10px;
    background: #f8fbff;
    padding: 18px;
    overflow-wrap: anywhere;
}

.empty {
    background: white;
    padding: 20px;
    border-radius: 10px;
}

.selected-leg {
    background: #f3f7fd;
    padding: 14px 16px;
    border-radius: 9px;
    margin: 12px 0;
    line-height: 1.7;
}

.total {
    color: #086db8;
    font-size: 23px;
    font-weight: bold;
}

footer {
    background: #082858;
    color: white;
    text-align: center;
    padding: 26px;
    margin-top: 55px;
}

@media(max-width:700px) {

    .flight {
        grid-template-columns: 1fr;
    }

    .right {
        text-align: left;
    }

    .compare-grid {
        grid-template-columns: 1fr;
    }
}

</style>
</head>

<body>

<header>
    <div class="logo">
        ✈ <span>Book</span>AFlight
    </div>

    <a href="index.php">← Back to Search</a>
</header>

<main>

<h1>✈ Available Flights</h1>
<?php
if (file_exists(__DIR__ . '/fare-calendar.php')) {
    include_once __DIR__ . '/fare-calendar.php';
}
?>
<?php
if (!empty($legs)):
    $lastLeg = $legs[count($legs) - 1];
    $destination = (string)($lastLeg['to'] ?? '');
?>

<a href="travel-guide.php?city=<?php
    echo rawurlencode($destination);
?>">
    🌍 Explore <?php
        echo htmlspecialchars(
            $destination,
            ENT_QUOTES,
            'UTF-8'
        );
    ?> Travel Guide →
</a>

<?php endif; ?>

<?php if ($trip === 'multicity'): ?>

    <p class="summary">
        Multi-City |
        <?php echo $passengers; ?> Passenger(s) |
        <?php echo h($cabin); ?>
    </p>

    <section class="journey-box">

        <h2>✈ Your Multi-City Journey</h2>

        <?php foreach ($legs as $i => $leg): ?>

            <div class="journey-leg">

                <strong>
                    Flight <?php echo $i + 1; ?>:
                </strong>

                <?php echo h($leg['from']); ?>
                →
                <?php echo h($leg['to']); ?>

                <br>

                Departure:
                <?php echo h($leg['date']); ?>

            </div>

        <?php endforeach; ?>

    </section>

<?php else: ?>

    <p class="summary">

        <strong>
            <?php echo h($from); ?>
            →
            <?php echo h($to); ?>
        </strong>

        | <?php echo h($date); ?>

        <?php if (
            $trip === 'roundtrip' &&
            $returnDate !== ''
        ): ?>

            | Return:
            <?php echo h($returnDate); ?>

        <?php endif; ?>

        | <?php echo $passengers; ?> Passenger(s)
        | <?php echo h($cabin); ?>

    </p>

<?php endif; ?>

<?php include __DIR__ . '/route-map.php'; ?>

<div class="notice">
    Preview data only — airline names, schedules,
    fares and baggage are illustrative.
    These are not live seats or confirmed bookings.
    No real airline API is connected yet.
</div>
<?php if (!$errors): ?>

    <?php if (!empty($_SESSION['user_id'])): ?>

        <form
            method="post"
            action="favourites.php"
            style="
                background:white;
                padding:16px;
                border-radius:12px;
                margin:16px 0;
            "
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo h(
                    csrf_token()
                ); ?>"
            >

            <input
                type="hidden"
                name="action"
                value="save"
            >

            <input
                type="hidden"
                name="trip"
                value="<?php echo h($trip); ?>"
            >

            <input
                type="hidden"
                name="cabin"
                value="<?php echo h($cabin); ?>"
            >

            <input
                type="hidden"
                name="passengers"
                value="<?php echo (int)$passengers; ?>"
            >

            <input
                type="hidden"
                name="from1"
                value="<?php echo h(
                    $legs[0]['from']
                ); ?>"
            >

            <input
                type="hidden"
                name="to1"
                value="<?php echo h(
                    $legs[0]['to']
                ); ?>"
            >

            <?php if ($trip === 'multicity'): ?>

                <input
                    type="hidden"
                    name="from2"
                    value="<?php echo h(
                        $legs[1]['from']
                    ); ?>"
                >

                <input
                    type="hidden"
                    name="to2"
                    value="<?php echo h(
                        $legs[1]['to']
                    ); ?>"
                >

            <?php endif; ?>

            <button
                type="submit"
                style="
                    border:0;
                    border-radius:8px;
                    background:#ffbc43;
                    padding:12px 16px;
                    cursor:pointer;
                    font-weight:bold;
                "
            >
                ⭐ Save Favourite Route
            </button>

            <a
                href="favourites.php"
                style="margin-left:12px;"
            >
                My Favourite Routes →
            </a>

        </form>

    <?php else: ?>

        <p>
            <a href="login.php">Log in</a>
            to save your favourite routes.
        </p>

    <?php endif; ?>

<?php endif; ?>
<?php if ($errors): ?>

    <div class="error">

        <strong>Please correct your search:</strong>

        <?php foreach ($errors as $error): ?>
            <p><?php echo h($error); ?></p>
        <?php endforeach; ?>

        <a href="index.php">← Return to Search</a>

    </div>

<?php else: ?>

<section class="filters">

    <label>
        Sort By:
        <select id="sort">
            <option value="low">Lowest Price</option>
            <option value="high">Highest Price</option>
            <option value="early">Earliest Departure</option>
            <option value="late">Latest Departure</option>
        </select>
    </label>

    <label>
        <input id="nonstop" type="checkbox">
        Non-stop Only
    </label>

</section>

<section id="compare" hidden>

    <h2 id="compareTitle">Compare Flights</h2>

    <div id="compareCards"
         class="compare-grid"></div>

</section>

<?php if ($trip === 'multicity'): ?>

<section id="selectionSummary">

    <h2>✈ Your Selected Flights</h2>

    <p class="muted">
        Choose one flight for each leg.
        You can change your choices anytime.
    </p>

    <div class="selected-leg">
        <strong>Flight 1:</strong>
        <span id="selected1">
            Not selected yet
        </span>
    </div>

    <div class="selected-leg">
        <strong>Flight 2:</strong>
        <span id="selected2">
            Not selected yet
        </span>
    </div>

    <p id="selectionProgress" class="muted">
        Select both flights to see the combined preview fare.
    </p>

    <p id="selectionTotal" class="total" hidden></p>

    <p class="muted">
        Selection is for planning only.
        Multi-City passenger checkout and airline
        ticketing are not connected.
        No payment or reservation is made here.
    </p>

</section>

<?php endif; ?>
<?php include_once __DIR__ . '/route-map.php'; ?>
<?php include_once __DIR__ . '/price-comparison.php'; ?>
<div id="results">

<?php foreach ($legs as $legIndex => $leg): ?>

<section class="leg-group"
         data-leg="<?php echo $legIndex + 1; ?>">

    <?php if ($trip === 'multicity'): ?>

        <h2 class="leg-heading">
            ✈ Flight <?php echo $legIndex + 1; ?> Options
        </h2>

        <p class="summary">

            <?php echo h($leg['from']); ?>
            →
            <?php echo h($leg['to']); ?>

            | <?php echo h($leg['date']); ?>

        </p>

    <?php endif; ?>

    <div class="leg-results">

    <?php foreach ($flights as $id => $f): ?>

            <article
    class="flight"
    data-flight="<?php echo (int)$id; ?>"
    data-price="<?php echo $f['price']; ?>"
            data-stops="<?php echo $f['stops']; ?>"
            data-time="<?php echo h($f['departure']); ?>"
        >

            <div>

                <div class="airline">
                    <?php echo h($f['airline']); ?>
                </div>

                <p class="muted">
                    Baggage:
                    <span class="baggage">
                        <?php echo h($f['baggage']); ?>
                    </span>
                </p>

                <label class="compare-label">
                    <input
                        type="checkbox"
                        class="compare-check"
                    >
                    Compare Flight
                </label>

            </div>

            <div>

                <div class="times">

                    <span class="departure">
                        <?php echo h($f['departure']); ?>
                    </span>

                    →

                    <span class="arrival">
                        <?php echo h($f['arrival']); ?>
                    </span>

                </div>

                <p class="muted">

                    <span class="duration">
                        <?php echo h($f['duration']); ?>
                    </span>

                    |

                    <span class="stops">

                        <?php
                        echo $f['stops'] === 0
                            ? 'Non-stop'
                            : $f['stops'] . ' Stop';
                        ?>

                    </span>

                </p>

            </div>

            <div class="right">

                <div class="price"
     data-baf-bdt="<?php echo htmlspecialchars((string)$f['price'], ENT_QUOTES, 'UTF-8'); ?>">
                    ৳<?php echo number_format($f['price']); ?>
                </div>

                <p class="muted">
                    Preview fare / passenger
                </p>

                <?php if ($trip === 'multicity'): ?>

                    <button
                        type="button"
                        class="btn multi-pick"
                        data-leg="<?php echo $legIndex + 1; ?>"
                        data-flight="<?php echo $id; ?>"
                        aria-pressed="false"
                    >
                        ✈ Select Flight <?php echo $legIndex + 1; ?>
                    </button>

                <?php else: ?>

                    <form method="GET" action="booking.php">

                        <input
                            type="hidden"
                            name="flight"
                            value="<?php echo $id; ?>"
                        >

                        <input
                            type="hidden"
                            name="from"
                            value="<?php echo h($from); ?>"
                        >

                        <input
                            type="hidden"
                            name="to"
                            value="<?php echo h($to); ?>"
                        >

                        <input
                            type="hidden"
                            name="depart"
                            value="<?php echo h($date); ?>"
                        >

                        <input
                            type="hidden"
                            name="trip"
                            value="<?php echo h($trip); ?>"
                        >

                        <input
                            type="hidden"
                            name="cabin"
                            value="<?php echo h($cabin); ?>"
                        >

                        <input
                            type="hidden"
                            name="passengers"
                            value="<?php echo $passengers; ?>"
                        >

                        <?php if ($trip === 'roundtrip'): ?>

                            <input
                                type="hidden"
                                name="return_date"
                                value="<?php echo h($returnDate); ?>"
                            >

                        <?php endif; ?>

                        <button class="btn" type="submit">
                            Select Seats →
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </article>

    <?php endforeach; ?>

    </div>

    <p class="empty" hidden>
        No flights match your filter.
    </p>

</section>

<?php endforeach; ?>

</div>

<?php endif; ?>

</main>

<footer>
    © <?php echo date('Y'); ?>
    BookAFlight · Your Journey Starts Here
</footer>

<script>

const results = document.getElementById('results');

if (results) {

    const sort = document.getElementById('sort');
    const nonstop = document.getElementById('nonstop');
    const compare = document.getElementById('compare');
    const compareTitle = document.getElementById('compareTitle');
    const compareCards = document.getElementById('compareCards');

    const checkboxes = [
        ...document.querySelectorAll('.compare-check')
    ];

    const selected = {
        1: null,
        2: null
    };

    const passengerCount = <?php echo $passengers; ?>;

    const isMultiCity =
        <?php echo $trip === 'multicity' ? 'true' : 'false'; ?>;

    function getText(card, selector) {
        return card.querySelector(selector)
            .textContent.trim();
    }

    function cell(label, value) {

        const p = document.createElement('p');
        const b = document.createElement('b');

        b.textContent = label + ': ';

        p.append(
            b,
            document.createTextNode(value)
        );

        return p;
    }

    // Compare flights
    function updateComparison() {

        const chosen = checkboxes.filter(c => c.checked);

        compareCards.replaceChildren();

        compare.hidden = chosen.length !== 2;

        if (chosen.length !== 2) {
            return;
        }

        const leg = chosen[0]
            .closest('.leg-group')
            .dataset.leg;

        compareTitle.textContent = isMultiCity
            ? 'Compare Flight ' + leg + ' Options'
            : 'Compare Flights';

        chosen.forEach(c => {

            const card = c.closest('.flight');

            const el = document.createElement('div');
            el.className = 'compare-item';

            const title = document.createElement('h3');

            title.textContent = getText(card, '.airline');

            el.append(title);

            el.append(
                cell('Departure', getText(card, '.departure')),
                cell('Arrival', getText(card, '.arrival')),
                cell('Duration', getText(card, '.duration')),
                cell('Stops', getText(card, '.stops')),
                cell('Baggage', getText(card, '.baggage')),
                cell('Price', getText(card, '.price'))
            );

            compareCards.append(el);

        });
    }

    checkboxes.forEach(c => {

        c.addEventListener('change', () => {

            const chosen = checkboxes.filter(x => x.checked);

            if (chosen.length > 2) {

                c.checked = false;

                alert('Select only 2 flights to compare.');

                return;
            }

            if (
                isMultiCity &&
                chosen.length === 2 &&
                chosen[0].closest('.leg-group') !==
                chosen[1].closest('.leg-group')
            ) {

                c.checked = false;

                alert(
                    'Compare flights from the same journey leg.'
                );

                return;
            }

            updateComparison();

            if (
                checkboxes.filter(x => x.checked).length === 2
            ) {
                compare.scrollIntoView({
                    behavior: 'smooth'
                });
            }

        });

    });

    // Selected flights summary
    function renderSelection() {

        if (!isMultiCity) {
            return;
        }

        let perPerson = 0;

        for (const leg of [1, 2]) {

            const card = selected[leg];

            const output = document.getElementById(
                'selected' + leg
            );

            if (card) {

                output.textContent =
                    getText(card, '.airline') + ' | ' +
                    getText(card, '.departure') + ' → ' +
                    getText(card, '.arrival') + ' | ' +
                    getText(card, '.price');

                perPerson += Number(card.dataset.price);

            } else {

                output.textContent = 'Not selected yet';

            }
        }

        const complete =
            !!selected[1] && !!selected[2];

        const total = document.getElementById(
            'selectionTotal'
        );

        const progress = document.getElementById(
            'selectionProgress'
        );

        total.hidden = !complete;

        if (complete) {

            total.textContent =
                'Combined preview: ৳' +
                (perPerson * passengerCount)
                    .toLocaleString('en-BD') +
                ' for ' +
                passengerCount +
                ' passenger(s)';

            progress.textContent =
                'Both flights selected. This is a preview, not a booking.';

        } else {

            progress.textContent =
                'Select both flights to see the combined preview fare.';

        }
    }

    // Multi-City flight selection
    document.querySelectorAll('.multi-pick')
        .forEach(button => {

            button.addEventListener('click', () => {

                const leg = Number(
                    button.dataset.leg
                );

                const card = button.closest('.flight');

                selected[leg] = card;

                document.querySelectorAll('.multi-pick')
                    .forEach(other => {

                        if (
                            Number(other.dataset.leg) !== leg
                        ) {
                            return;
                        }

                        const active = other === button;

                        other.classList.toggle(
                            'is-picked',
                            active
                        );

                        other.closest('.flight')
                            .classList.toggle(
                                'is-selected',
                                active
                            );

                        other.setAttribute(
                            'aria-pressed',
                            String(active)
                        );

                        other.textContent = active
                            ? '✓ Selected Flight ' + leg
                            : '✈ Select Flight ' + leg;

                    });

                renderSelection();

                document.getElementById(
                    'selectionSummary'
                ).scrollIntoView({
                    behavior: 'smooth'
                });

            });

        });

    // Sort and filter
    function filterFlights() {

        document.querySelectorAll('.leg-group')
            .forEach(group => {

                const container = group.querySelector(
                    '.leg-results'
                );

                const cards = [
                    ...container.querySelectorAll('.flight')
                ];

                cards.sort((a, b) => {

                    if (sort.value === 'high') {

                        return Number(b.dataset.price)
                            - Number(a.dataset.price);
                    }

                    if (sort.value === 'early') {

                        return a.dataset.time.localeCompare(
                            b.dataset.time
                        );
                    }

                    if (sort.value === 'late') {

                        return b.dataset.time.localeCompare(
                            a.dataset.time
                        );
                    }

                    return Number(a.dataset.price)
                        - Number(b.dataset.price);

                });

                let count = 0;

                cards.forEach(card => {

                    const visible =
                        !nonstop.checked ||
                        Number(card.dataset.stops) === 0;

                    card.hidden = !visible;

                    if (visible) {
                        count++;
                    }

                    if (!visible) {

                        card.querySelector(
                            '.compare-check'
                        ).checked = false;

                        if (isMultiCity) {

                            const leg = Number(
                                group.dataset.leg
                            );

                            if (selected[leg] === card) {

                                selected[leg] = null;

                                const button = card.querySelector(
                                    '.multi-pick'
                                );

                                button.classList.remove(
                                    'is-picked'
                                );

                                button.setAttribute(
                                    'aria-pressed',
                                    'false'
                                );

                                button.textContent =
                                    '✈ Select Flight ' + leg;

                                card.classList.remove(
                                    'is-selected'
                                );
                            }
                        }
                    }

                    container.append(card);

                });

                group.querySelector('.empty').hidden =
                    count !== 0;

            });

        updateComparison();
        renderSelection();
    }

    sort.addEventListener(
        'change',
        filterFlights
    );

    nonstop.addEventListener(
        'change',
        filterFlights
    );

    filterFlights();
}

</script>
<?php include_once __DIR__ . '/theme-toggle.php'; ?>
<?php include_once __DIR__ . '/responsive-layout.php'; ?>
<?php include_once __DIR__ . '/language-currency.php'; ?>
</body>
</html>