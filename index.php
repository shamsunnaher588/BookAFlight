<?php
require_once __DIR__ . '/maintenance-mode.php';
?>
<?php
$searched = isset($_GET['search']);

function clean($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<?php
require_once __DIR__ . '/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>BookAFlight | Your Journey Starts Here</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    color: #172b4d;
    background: #f3f7fc;
}

header {
    background: #082858;
    color: white;
    padding: 20px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.logo {
    font-size: 27px;
    font-weight: bold;
}

.logo span {
    color: #ffb733;
}

nav {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

nav a {
    color: white;
    text-decoration: none;
    font-size: 14px;
}

.hero {
    background: linear-gradient(120deg,#082858,#087ec2);
    padding: 75px 20px 125px;
    text-align: center;
    color: white;
}

.hero h1 {
    font-size: clamp(30px, 5vw, 50px);
    margin: 0 0 15px;
}

.hero p {
    font-size: 17px;
    color: #d9ecff;
}

.container {
    width: 90%;
    max-width: 1100px;
    margin: auto;
}

.search-card {
    background: white;
    margin-top: -72px;
    position: relative;
    padding: 30px;
    border-radius: 18px;
    box-shadow: 0 12px 35px #12345622;
}

.trip-options {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 25px;
}

.trip-options label {
    cursor: pointer;
    font-weight: bold;
}

.fields {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
}

.field label {
    display: block;
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 9px;
}

.field input,
.field select {
    width: 100%;
    padding: 15px;
    border: 1px solid #d4deea;
    border-radius: 9px;
    font-size: 15px;
    background: white;
}

.field input:focus,
.field select:focus {
    outline: 2px solid #0686d4;
}

.search-btn {
    background: #ffb733;
    padding: 16px 40px;
    border: 0;
    border-radius: 10px;
    font-size: 16px;
    font-weight: bold;
    margin-top: 25px;
    cursor: pointer;
    color: #12223b;
}

.search-btn:hover {
    background: #ffc85b;
}

.hidden {
    display: none;
}

.section-title {
    text-align: center;
    margin: 55px 0 25px;
    font-size: 28px;
}

.features {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 50px;
}

.feature {
    background: white;
    padding: 30px;
    border-radius: 14px;
    text-align: center;
    box-shadow: 0 4px 16px #12345610;
}

.feature .symbol {
    font-size: 38px;
}

.feature h3 {
    margin: 16px 0 10px;
}

.feature p {
    color: #68768a;
    line-height: 1.6;
}

.notice {
    background: #e8f4ff;
    padding: 22px;
    margin-top: 25px;
    border-radius: 12px;
    line-height: 1.8;
    overflow-wrap: anywhere;
}

footer {
    background: #082858;
    color: #d9e6f7;
    text-align: center;
    padding: 30px;
}

@media(max-width: 700px) {
    .fields, .features {
        grid-template-columns: 1fr;
    }

    header {
        justify-content: center;
        text-align: center;
    }

    nav {
        justify-content: center;
    }

    .search-card {
        padding: 20px;
    }
}
</style>
</head>

<body>

<header>
    <div class="logo">✈ <span>Book</span>AFlight</div>
<nav>
    <a href="saved-passengers.php">👤 My Passengers</a>
    <a href="index.php">Home</a>
    <a href="#flights">Flights</a>
    <a href="#features">Services</a>
    <a href="dashboard.php">My Dashboard</a>
    <a href="login.php">Login</a>
    <a href="register.php">Register</a>
    <a href="saved-passengers.php">👤 My Passengers</a>
    <a href="help-center.php">❓ Help Center</a>
    <a href="profile.php">👤 My Profile</a>
    <a href="flight-status.php">
    📡 Live Flight Status & Tracking
</a>
</nav>

</header>

<section class="hero">
    <h1>Discover Your Next Destination</h1>
    <p>Search smarter. Travel better. Fly anywhere.</p>
</section>

<div class="container">

<section class="search-card" id="flights">
    <h2>Find Your Perfect Flight</h2>

    <form method="GET" action="results.php">

        <div class="trip-options">
            <label>
                <input type="radio" name="trip"
                       value="roundtrip" checked>
                Round Trip
            </label>

            <label>
                <input type="radio" name="trip"
                       value="oneway">
                One Way
            </label>

            <label>
                <input type="radio" name="trip"
                       value="multicity">
                Multi-City
            </label>
        </div>
        <button type="button" id="swapAirports" class="search-btn">
    ⇄ Swap Airports
</button>
        <div class="fields">

            <div class="field">
                <label>From</label>
                <input name="from[]" type="text"
                       placeholder="Dhaka (DAC)"
                       required>
            </div>

            <div class="field">
                <label>To</label>
                <input name="to[]" type="text"
                       placeholder="Dubai (DXB)"
                       required>
            </div>

            <div class="field">
                <label>Departure Date</label>
                <input name="depart[]" type="date"
                       id="departDate" required>
            </div>

            <div class="field" id="returnField">
                <label>Return Date</label>
                <input name="return_date" type="date"
                       id="returnDate" required>
            </div>

        </div>

        <div id="multiCityFields" class="hidden">
            <h3>Second Flight</h3>
            <div class="fields">

                <div class="field">
                    <label>From</label>
                    <input name="from[]" type="text"
                           class="extra-field"
                           placeholder="Dubai (DXB)"
                           disabled>
                </div>

                <div class="field">
                    <label>To</label>
                    <input name="to[]" type="text"
                           class="extra-field"
                           placeholder="London (LHR)"
                           disabled>
                </div>

                <div class="field">
                    <label>Departure Date</label>
                    <input name="depart[]" type="date"
                           class="extra-field extra-date"
                           disabled>
                </div>

            </div>
        </div>

        <div class="fields" style="margin-top:18px">

            <div class="field">
                <label>Passengers</label>
                <select name="passengers">
                    <option value="1">1 Passenger</option>
                    <option value="2">2 Passengers</option>
                    <option value="3">3 Passengers</option>
                    <option value="4">4 Passengers</option>
                    <option value="5">5 Passengers</option>
                    <option value="6">6 Passengers</option>
                </select>
            </div>

            <div class="field">
                <label>Cabin Class</label>
                <select name="cabin">
                    <option value="Economy">Economy</option>
                    <option value="Premium Economy">
                        Premium Economy
                    </option>
                    <option value="Business">Business</option>
                    <option value="First Class">First Class</option>
                </select>
            </div>

        </div>

        <button class="search-btn" type="submit"
                name="search" value="1">
            🔍 Search Flights
        </button>

    </form>
    <a href="travel-guide.php"
    data-baf-i18n="travel_guide"
   style="display:inline-block;
          background:#ffbc43;
          color:#082858;
          padding:13px 20px;
          border-radius:9px;
          text-decoration:none;
          font-weight:bold;
          margin:12px 0;">
    🌍 Travel Guide & Destinations
</a>
    <a href="group-booking.php"
       data-baf-i18n="group_booking"
       style="
       display:inline-block;
       background:#ffbc43;
       color:#082858;
       padding:13px 20px;
       border-radius:9px;
       text-decoration:none;
       font-weight:bold;
       margin:12px 0;
   ">
    👥 Group Booking — 7 to 30 People
</a>
</section>

<?php if ($searched): ?>
<section class="notice">
    <h3>✈ Flight Search Submitted</h3>

    <p>
        <strong>Trip Type:</strong>
        <?php echo clean($_GET['trip'] ?? ''); ?>
    </p>

    <?php
    $from = $_GET['from'] ?? [];
    $to = $_GET['to'] ?? [];
    $depart = $_GET['depart'] ?? [];

    if (is_array($from) && is_array($to)
        && is_array($depart)):

        foreach ($from as $i => $city):
    ?>
        <p>
            <strong>Flight <?php echo $i + 1; ?>:</strong>
            <?php echo clean($city); ?>
            →
            <?php echo clean($to[$i] ?? ''); ?>
            |
            <?php echo clean($depart[$i] ?? ''); ?>
        </p>
    <?php
        endforeach;
    endif;
    ?>

    <p>
        <strong>Passengers:</strong>
        <?php echo clean($_GET['passengers'] ?? '1'); ?>
    </p>

    <p>
        <strong>Class:</strong>
        <?php echo clean($_GET['cabin'] ?? 'Economy'); ?>
    </p>

    <p>
        This is a demo search.
        Live flight prices and availability
        will be connected through a Flight API.
    </p>
</section>
<?php endif; ?>

<h2 class="section-title" id="features">
    Why Choose BookAFlight?
</h2>

<section class="features">

    <div class="feature">
        <div class="symbol">✈️</div>
        <h3>Flight Search</h3>
        <p>
            Search flights across different
            airlines and destinations.
        </p>
    </div>

    <div class="feature">
        <div class="symbol">🔔</div>
        <h3>Price Alerts</h3>
        <p>
            Get notified when flight prices
            drop after alerts are enabled.
        </p>
    </div>

    <div class="feature">
        <div class="symbol">🔒</div>
        <h3>Secure Booking</h3>
        <p>
            Secure payment and booking
            services coming in later phases.
        </p>
    </div>

</section>

</div>

<footer>
    <strong>✈ BookAFlight</strong>
    <p>Your Journey Starts Here</p>
    <p>© <?php echo date('Y'); ?> BookAFlight</p>
</footer>

<script>
const tripRadios = document.querySelectorAll(
    'input[name="trip"]'
);

const returnField = document.getElementById('returnField');
const returnDate = document.getElementById('returnDate');
const multiCity = document.getElementById('multiCityFields');
const extraFields = document.querySelectorAll('.extra-field');
const departure = document.getElementById('departDate');

const today = new Date();
const todayString =
    today.getFullYear() + '-' +
    String(today.getMonth() + 1).padStart(2, '0') + '-' +
    String(today.getDate()).padStart(2, '0');

departure.min = todayString;
returnDate.min = todayString;

document.querySelectorAll('.extra-date').forEach(input => {
    input.min = todayString;
});

function updateTrip() {
    const selected = document.querySelector(
        'input[name="trip"]:checked'
    ).value;

    const isRoundTrip = selected === 'roundtrip';
    const isMultiCity = selected === 'multicity';

    returnField.classList.toggle('hidden', !isRoundTrip);
    returnDate.disabled = !isRoundTrip;
    returnDate.required = isRoundTrip;

    multiCity.classList.toggle('hidden', !isMultiCity);

    extraFields.forEach(field => {
        field.disabled = !isMultiCity;
        field.required = isMultiCity;
    });
}

tripRadios.forEach(radio => {
    radio.addEventListener('change', updateTrip);
});

departure.addEventListener('change', () => {
    returnDate.min = departure.value || todayString;
    if (returnDate.value && returnDate.value < departure.value) {
        returnDate.value = '';
    }
});

updateTrip();
const airports = [
    "Dhaka (DAC)",
    "Chattogram (CGP)",
    "Sylhet (ZYL)",
    "Cox's Bazar (CXB)",
    "Saidpur (SPD)",
    "Rajshahi (RJH)",
    "Jashore (JSR)",
    "Kolkata (CCU)",
    "Delhi (DEL)",
    "Mumbai (BOM)",
    "Dubai (DXB)",
    "Abu Dhabi (AUH)",
    "Sharjah (SHJ)",
    "Doha (DOH)",
    "Jeddah (JED)",
    "Riyadh (RUH)",
    "Muscat (MCT)",
    "Kuala Lumpur (KUL)",
    "Singapore (SIN)",
    "Bangkok (BKK)",
    "Istanbul (IST)",
    "London Heathrow (LHR)",
    "New York JFK (JFK)",
    "Toronto (YYZ)"
];

const airportList = document.createElement("datalist");
airportList.id = "airportSuggestions";

airports.forEach(airport => {
    const option = document.createElement("option");
    option.value = airport;
    airportList.appendChild(option);
});

document.body.appendChild(airportList);

document.querySelectorAll(
    'input[name="from[]"], input[name="to[]"]'
).forEach(input => {
    input.setAttribute("list", "airportSuggestions");
});
document.getElementById('swapAirports').addEventListener('click', function () {
    const from = document.querySelector('input[name="from[]"]');
    const to = document.querySelector('input[name="to[]"]');

    const temp = from.value;
    from.value = to.value;
    to.value = temp;
});
document.querySelector('form[action="results.php"]')
.addEventListener('submit', function(event) {
    const from = this.querySelectorAll('input[name="from[]"]');
    const to = this.querySelectorAll('input[name="to[]"]');

    for (let i = 0; i < from.length; i++) {
        if (from[i].disabled || to[i].disabled) continue;

        if (from[i].value.trim().toLowerCase() ===
            to[i].value.trim().toLowerCase()) {
            event.preventDefault();
            alert("From and To cannot be the same airport!");
            to[i].focus();
            return;
        }
    }
});
const secondDeparture = document.querySelector(
    '#multiCityFields .extra-date'
);

function updateSecondDeparture() {
    secondDeparture.min = departure.value || todayString;

    if (secondDeparture.value &&
        secondDeparture.value < secondDeparture.min) {
        secondDeparture.value = '';
    }
}

departure.addEventListener('change', updateSecondDeparture);
updateSecondDeparture();
const firstDestination = document.querySelector(
    'input[name="to[]"]'
);

const secondOrigin = document.querySelector(
    '#multiCityFields input[name="from[]"]'
);

function autoFillSecondFlight() {
    secondOrigin.value = firstDestination.value;
}

firstDestination.addEventListener(
    'input', autoFillSecondFlight
);

document.querySelector(
    'input[name="trip"][value="multicity"]'
).addEventListener('change', autoFillSecondFlight);
</script>

<a href="support.php"
   style="
       position:fixed;
       right:20px;
       bottom:20px;
       z-index:9999;
       background:#087cc6;
       color:white;
       padding:15px 20px;
       border-radius:30px;
       text-decoration:none;
       font-weight:bold;
       box-shadow:0 5px 20px #0003;
   ">
    💬 Live Support
</a>
<?php include_once __DIR__ . '/support.php'; ?>
<?php include_once __DIR__ . '/theme-toggle.php'; ?>
<?php include_once __DIR__ . '/responsive-layout.php'; ?>
<?php include_once __DIR__ . '/language-currency.php'; ?>
<script>
(function () {
    'use strict';

    function addTranslation(element, key) {
        if (!element) return;

        if (element.hasAttribute('data-baf-i18n')) {
            return;
        }

        // Do not remove form inputs or selects.
        if (element.querySelector('input, select, textarea')) {
            return;
        }

        element.setAttribute('data-baf-i18n', key);
    }

    // Homepage navigation and feature buttons
    const links = {
        'group-booking.php': 'group_booking',
        'travel-guide.php': 'travel_guide',
        'favourites.php': 'favourite_routes',
        'my-bookings.php': 'my_bookings',
        'dashboard.php': 'dashboard',
        'login.php': 'login',
        'logout.php': 'logout'
    };

    for (const [file, key] of Object.entries(links)) {
        document.querySelectorAll('a[href]').forEach(link => {
            const href = link.getAttribute('href') || '';

            if (href === file || href.startsWith(file + '?')) {
                addTranslation(link, key);
            }
        });
    }

    // Flight Search form labels
    const labelKeys = {
        'from': 'from',
        'to': 'to',
        'departure': 'departure',
        'departure date': 'departure',
        'return': 'return',
        'return date': 'return',
        'passengers': 'passengers'
    };

    document.querySelectorAll('label').forEach(label => {
        const text = label.textContent.trim().toLowerCase();
        const key = labelKeys[text];

        if (key) {
            addTranslation(label, key);
        }
    });

    // Search Flights button
    document.querySelectorAll('button').forEach(button => {
        const text = button.textContent.trim();

        if (/search flights|show flights/i.test(text)) {
            addTranslation(button, 'search_flights');
        }
    });

    // Supported section headings
    const headingKeys = {
        'search flights': 'search_flights',
        'price comparison': 'price_comparison',
        'my bookings': 'my_bookings',
        'dashboard': 'dashboard'
    };

    document.querySelectorAll('h1, h2, h3').forEach(heading => {
        const text = heading.textContent.trim().toLowerCase();

        if (headingKeys[text]) {
            addTranslation(heading, headingKeys[text]);
        }
    });

    // Apply currently selected language
    if (typeof window.bafRefreshLocale === 'function') {
        window.bafRefreshLocale();
    }
})();
</script>
</body>
</html>