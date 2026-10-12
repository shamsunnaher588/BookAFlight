<?php
// BookAFlight - Travel Guide & Destination Information
// Static editorial examples; not real-time travel advisories.

require_once __DIR__ . '/db.php';

function tgEscape($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$destinations = [
    'DXB' => [
        'city' => 'Dubai',
        'country' => 'United Arab Emirates',
        'region' => 'Middle East',
        'emoji' => '🏙️',
        'airport' => 'Dubai International (DXB)',
        'intro' => 'Skyscrapers, traditional markets, desert scenery and waterfront neighbourhoods.',
        'best_time' => 'November to March is generally more comfortable for outdoor sightseeing.',
        'currency' => 'UAE Dirham (AED)',
        'language' => 'Arabic; English is widely used',
        'transport' => 'Dubai Metro, public buses and licensed taxis.',
        'places' => [
            'Burj Khalifa',
            'Al Fahidi Historical Neighbourhood',
            'Dubai Creek',
            'Dubai Marina'
        ],
        'food' => 'Try regional dishes such as machboos, shawarma and luqaimat.',
        'tips' => [
            'Summer can be extremely hot.',
            'Dress respectfully at religious and cultural sites.',
            'Check attraction opening hours before visiting.'
        ],
        'lat' => 25.2048,
        'lon' => 55.2708
    ],

    'BKK' => [
        'city' => 'Bangkok',
        'country' => 'Thailand',
        'region' => 'Asia',
        'emoji' => '🛕',
        'airport' => 'Suvarnabhumi Airport (BKK)',
        'intro' => 'Temple architecture, lively markets, riverside sights and street food.',
        'best_time' => 'November to February often has less heat and humidity.',
        'currency' => 'Thai Baht (THB)',
        'language' => 'Thai',
        'transport' => 'BTS Skytrain, MRT, airport rail and river boats.',
        'places' => [
            'Grand Palace',
            'Wat Arun',
            'Chatuchak Weekend Market',
            'Chao Phraya River'
        ],
        'food' => 'Popular dishes include pad thai, tom yum and mango sticky rice.',
        'tips' => [
            'Cover shoulders and knees at many temples.',
            'Allow extra time for road traffic.',
            'Check food ingredients if you have dietary restrictions.'
        ],
        'lat' => 13.7563,
        'lon' => 100.5018
    ],

    'SIN' => [
        'city' => 'Singapore',
        'country' => 'Singapore',
        'region' => 'Asia',
        'emoji' => '🌿',
        'airport' => 'Singapore Changi Airport (SIN)',
        'intro' => 'Modern architecture, green spaces, multicultural neighbourhoods and food centres.',
        'best_time' => 'Year-round destination; expect warmth, humidity and occasional heavy rain.',
        'currency' => 'Singapore Dollar (SGD)',
        'language' => 'English, Malay, Mandarin and Tamil',
        'transport' => 'MRT trains and public buses are convenient for many attractions.',
        'places' => [
            'Gardens by the Bay',
            'Marina Bay',
            'Sentosa',
            'Chinatown'
        ],
        'food' => 'Explore hawker centres for local dishes; check dietary requirements.',
        'tips' => [
            'Carry rain protection.',
            'Follow local public-space rules.',
            'Public transit is often convenient.'
        ],
        'lat' => 1.3521,
        'lon' => 103.8198
    ],

    'KUL' => [
        'city' => 'Kuala Lumpur',
        'country' => 'Malaysia',
        'region' => 'Asia',
        'emoji' => '🌇',
        'airport' => 'Kuala Lumpur International (KUL)',
        'intro' => 'A mix of modern towers, heritage districts and varied Malaysian cuisine.',
        'best_time' => 'Warm all year with periodic rain; plan indoor and outdoor activities flexibly.',
        'currency' => 'Malaysian Ringgit (MYR)',
        'language' => 'Malay; English is common in tourist areas',
        'transport' => 'Urban rail lines, airport trains and ride-hailing services.',
        'places' => [
            'Petronas Twin Towers',
            'Batu Caves',
            'KLCC Park',
            'Central Market'
        ],
        'food' => 'Try nasi lemak, roti canai and other Malaysian dishes.',
        'tips' => [
            'Carry water in the humid weather.',
            'Use official transport services.',
            'Dress respectfully when visiting religious sites.'
        ],
        'lat' => 3.1390,
        'lon' => 101.6869
    ],

    'LHR' => [
        'city' => 'London',
        'country' => 'United Kingdom',
        'region' => 'Europe',
        'emoji' => '🎡',
        'airport' => 'London Heathrow (LHR)',
        'intro' => 'Museums, historic landmarks, theatres, parks and neighbourhood markets.',
        'best_time' => 'May to September is often milder for outdoor activities.',
        'currency' => 'Pound Sterling (GBP)',
        'language' => 'English',
        'transport' => 'Underground, Elizabeth line, buses and suburban trains.',
        'places' => [
            'British Museum',
            'Tower of London',
            'Hyde Park',
            'South Bank'
        ],
        'food' => 'Explore diverse international food alongside traditional British dishes.',
        'tips' => [
            'Prepare for changing weather.',
            'Check transport fares and service changes.',
            'Reserve popular timed-entry attractions ahead.'
        ],
        'lat' => 51.5074,
        'lon' => -0.1278
    ],

    'IST' => [
        'city' => 'Istanbul',
        'country' => 'Türkiye',
        'region' => 'Europe / Asia',
        'emoji' => '🕌',
        'airport' => 'Istanbul Airport (IST)',
        'intro' => 'Historic architecture, bazaars and Bosphorus waterfront views.',
        'best_time' => 'April–June and September–October often suit walking and sightseeing.',
        'currency' => 'Turkish Lira (TRY)',
        'language' => 'Turkish',
        'transport' => 'Metro, tram, ferries and buses.',
        'places' => [
            'Hagia Sophia',
            'Blue Mosque',
            'Grand Bazaar',
            'Bosphorus'
        ],
        'food' => 'Explore kebabs, simit, meze and Turkish breakfast.',
        'tips' => [
            'Be considerate of prayer times at mosques.',
            'Expect hills and walking.',
            'Check museum and site entry rules.'
        ],
        'lat' => 41.0082,
        'lon' => 28.9784
    ],

    'DOH' => [
        'city' => 'Doha',
        'country' => 'Qatar',
        'region' => 'Middle East',
        'emoji' => '🏛️',
        'airport' => 'Hamad International Airport (DOH)',
        'intro' => 'Museums, traditional souqs, seafront walks and modern architecture.',
        'best_time' => 'November to March is generally better for outdoor activities.',
        'currency' => 'Qatari Riyal (QAR)',
        'language' => 'Arabic; English is widely used',
        'transport' => 'Doha Metro, public transport and taxis.',
        'places' => [
            'Souq Waqif',
            'Museum of Islamic Art',
            'Doha Corniche',
            'National Museum of Qatar'
        ],
        'food' => 'Explore regional rice dishes, grilled foods and sweets.',
        'tips' => [
            'Summer heat can be intense.',
            'Check museum hours.',
            'Respect local customs and dress expectations.'
        ],
        'lat' => 25.2854,
        'lon' => 51.5310
    ],

    'CCU' => [
        'city' => 'Kolkata',
        'country' => 'India',
        'region' => 'Asia',
        'emoji' => '🏛️',
        'airport' => 'Netaji Subhas Chandra Bose Airport (CCU)',
        'intro' => 'Colonial-era architecture, cultural institutions and Bengali food.',
        'best_time' => 'October to February often offers more comfortable sightseeing weather.',
        'currency' => 'Indian Rupee (INR)',
        'language' => 'Bengali; Hindi and English are also used',
        'transport' => 'Metro, local trains, buses and taxis.',
        'places' => [
            'Victoria Memorial',
            'Indian Museum',
            'Howrah Bridge',
            'Park Street'
        ],
        'food' => 'Try regional Bengali dishes and well-known local sweets.',
        'tips' => [
            'Plan around peak traffic.',
            'Check museum closure days.',
            'Allow extra time during major festivals.'
        ],
        'lat' => 22.5726,
        'lon' => 88.3639
    ],

    'DAC' => [
        'city' => 'Dhaka',
        'country' => 'Bangladesh',
        'region' => 'Asia',
        'emoji' => '🏰',
        'airport' => 'Hazrat Shahjalal International (DAC)',
        'intro' => 'Historic old-city landmarks, museums, markets and lively local food.',
        'best_time' => 'November to February is often more comfortable for sightseeing.',
        'currency' => 'Bangladeshi Taka (BDT)',
        'language' => 'Bangla',
        'transport' => 'Metro rail on operating routes, buses, ride-hailing and rickshaws.',
        'places' => [
            'Lalbagh Fort',
            'Ahsan Manzil',
            'Hatirjheel',
            'National Parliament Complex (exterior)'
        ],
        'food' => 'Explore biryani, bhorta and traditional Bangladeshi sweets.',
        'tips' => [
            'Allow extra time for traffic.',
            'Check site opening days.',
            'Use safe drinking water.'
        ],
        'lat' => 23.8103,
        'lon' => 90.4125
    ],

    'JFK' => [
        'city' => 'New York',
        'country' => 'United States',
        'region' => 'North America',
        'emoji' => '🗽',
        'airport' => 'John F. Kennedy International (JFK)',
        'intro' => 'World-famous city parks, museums, theatre and neighbourhood food.',
        'best_time' => 'April–June and September–October often have pleasant walking weather.',
        'currency' => 'US Dollar (USD)',
        'language' => 'English; many other languages are spoken',
        'transport' => 'Subway, buses and commuter trains.',
        'places' => [
            'Central Park',
            'Times Square',
            'Metropolitan Museum of Art',
            'Brooklyn Bridge'
        ],
        'food' => 'Try classic New York pizza, bagels and international cuisine.',
        'tips' => [
            'Budget for taxes and tips where applicable.',
            'Check museum entry policies.',
            'Use official public transportation information.'
        ],
        'lat' => 40.7128,
        'lon' => -74.0060
    ]
];

// Search destination or open a specific city.
$query = trim(substr(
    is_string($_GET['q'] ?? null) ? $_GET['q'] : '',
    0,
    80
));

$cityInput = trim(substr(
    is_string($_GET['city'] ?? null) ? $_GET['city'] : '',
    0,
    100
));

$selectedCode = null;

if ($cityInput !== '') {

    // Allows "Dubai", "DXB", or "Dubai (DXB)".
    $clean = trim((string)preg_replace(
        '/\s*\([^)]*\)\s*/',
        '',
        $cityInput
    ));

    foreach ($destinations as $code => $destination) {

        if (
            strcasecmp($cityInput, $code) === 0 ||
            strcasecmp($clean, $destination['city']) === 0 ||
            preg_match(
                '/\(' . preg_quote($code, '/') . '\)/i',
                $cityInput
            )
        ) {
            $selectedCode = $code;
            break;
        }
    }
}

$visible = [];

foreach ($destinations as $code => $destination) {

    $haystack = implode(' ', [
        $code,
        $destination['city'],
        $destination['country'],
        $destination['region'],
        $destination['airport']
    ]);

    if (
        $query === '' ||
        stripos($haystack, $query) !== false
    ) {
        $visible[$code] = $destination;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Travel Guide & Destinations | BookAFlight</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f3f7fc;
    color: #172b4d;
    font: 15px/1.6 Arial, sans-serif;
}

.tg-header {
    background: #082858;
    color: white;
    padding: 18px 4%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.tg-header a {
    color: white;
    text-decoration: none;
    font-weight: 600;
}

.tg-main {
    max-width: 1100px;
    margin: 30px auto;
    padding: 0 16px;
}

.tg-hero {
    background: linear-gradient(115deg, #0b3973, #087fc4);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 20px;
}

.tg-hero h1 {
    margin: 0 0 8px;
    font-size: clamp(25px, 4vw, 36px);
}

.tg-hero p {
    margin: 0 0 16px;
    color: #e9f5ff;
}

.tg-search {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.tg-search input {
    flex: 1;
    min-width: 170px;
    padding: 12px;
    border-radius: 9px;
    border: 1px solid #bed3e5;
    font: inherit;
}

.tg-btn {
    display: inline-block;
    border: 0;
    border-radius: 9px;
    background: #ffbc43;
    color: #172b4d;
    text-decoration: none;
    font: 700 14px Arial, sans-serif;
    padding: 13px 17px;
    cursor: pointer;
}

.tg-grid {
    display: grid;
    grid-template-columns:
        repeat(auto-fit, minmax(235px, 1fr));
    gap: 15px;
}

.tg-card,
.tg-section {
    background: white;
    border-radius: 13px;
    padding: 20px;
    box-shadow: 0 4px 17px #0000000a;
}

.tg-card h2,
.tg-section h2 {
    margin: 4px 0 7px;
    font-size: 21px;
}

.tg-muted {
    color: #62768d;
    font-size: 13px;
}

.tg-emoji {
    font-size: 37px;
}

.tg-card p {
    min-height: 50px;
}

.tg-card a {
    color: #0869af;
    font-weight: bold;
}

.tg-detail-title {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.tg-back {
    display: inline-block;
    margin-bottom: 14px;
    color: #075f9f;
}

.tg-info {
    display: grid;
    grid-template-columns:
        repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    margin: 17px 0;
}

.tg-info div {
    background: #edf5fd;
    border-radius: 10px;
    padding: 13px;
}

.tg-info small {
    display: block;
    color: #5b6c80;
}

.tg-section {
    margin: 14px 0;
}

.tg-section ul {
    padding-left: 22px;
}

.tg-section li {
    margin: 9px 0;
}

.tg-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin: 16px 0;
}

.tg-note {
    background: #fff4d8;
    border-radius: 10px;
    padding: 15px;
    margin: 20px 0;
    color: #664b16;
}

.tg-empty {
    padding: 25px;
    background: white;
    border-radius: 12px;
}

/* Dark Mode */

html[data-baf-theme="dark"] body {
    background: #0b1220;
    color: #f1f5fc;
}

html[data-baf-theme="dark"] .tg-card,
html[data-baf-theme="dark"] .tg-section {
    background: #162235;
    color: #f1f5fc;
}

html[data-baf-theme="dark"] .tg-info div {
    background: #203249;
}

html[data-baf-theme="dark"] .tg-muted,
html[data-baf-theme="dark"] .tg-info small {
    color: #bcc9da;
}

html[data-baf-theme="dark"] .tg-card a,
html[data-baf-theme="dark"] .tg-back {
    color: #8acfff;
}

html[data-baf-theme="dark"] .tg-note {
    background: #43361a;
    color: #ffe4ac;
}

/* Mobile */

@media(max-width:600px) {
    .tg-main {
        margin-top: 16px;
        padding: 0 12px;
    }

    .tg-hero {
        padding: 20px;
    }

    .tg-card p {
        min-height: 0;
    }
}
</style>

</head>

<body>

<header class="tg-header">

    <strong>✈ BookAFlight</strong>

    <nav>
        <a href="index.php">Search Flights</a>
        ·
        <a href="travel-guide.php">All Destinations</a>
    </nav>

</header>

<main class="tg-main">

<?php if ($selectedCode !== null): ?>

    <?php $d = $destinations[$selectedCode]; ?>

    <a class="tg-back" href="travel-guide.php">
        ← Explore all destinations
    </a>

    <section class="tg-hero">

        <div class="tg-detail-title">

            <span class="tg-emoji">
                <?php echo tgEscape($d['emoji']); ?>
            </span>

            <h1>
                <?php echo tgEscape($d['city']); ?>
                Travel Guide
            </h1>

        </div>

        <p>
            <?php echo tgEscape($d['country']); ?>
            ·
            <?php echo tgEscape($d['airport']); ?>
        </p>

        <p>
            <?php echo tgEscape($d['intro']); ?>
        </p>

    </section>

    <section class="tg-info">

        <div>
            <small>Best time to visit (general)</small>
            <strong>
                <?php echo tgEscape($d['best_time']); ?>
            </strong>
        </div>

        <div>
            <small>Currency</small>
            <strong>
                <?php echo tgEscape($d['currency']); ?>
            </strong>
        </div>

        <div>
            <small>Language</small>
            <strong>
                <?php echo tgEscape($d['language']); ?>
            </strong>
        </div>

        <div>
            <small>Local transport</small>
            <strong>
                <?php echo tgEscape($d['transport']); ?>
            </strong>
        </div>

    </section>

    <div class="tg-grid">

        <section class="tg-section">

            <h2>📍 Popular places</h2>

            <ul>
                <?php foreach ($d['places'] as $place): ?>
                    <li>
                        <?php echo tgEscape($place); ?>
                    </li>
                <?php endforeach; ?>
            </ul>

        </section>

        <section class="tg-section">

            <h2>💡 Travel tips</h2>

            <ul>
                <?php foreach ($d['tips'] as $tip): ?>
                    <li>
                        <?php echo tgEscape($tip); ?>
                    </li>
                <?php endforeach; ?>
            </ul>

        </section>

    </div>

    <section class="tg-section">

        <h2>🍽️ Food to explore</h2>

        <p>
            <?php echo tgEscape($d['food']); ?>
        </p>

    </section>

    <section class="tg-section">

        <h2>🧳 Before you travel</h2>

        <ul>
            <li>
                Check passport validity and visa/entry
                requirements using official government
                sources for your nationality.
            </li>

            <li>
                Verify current airline baggage and
                ticket rules.
            </li>

            <li>
                Check accommodation, transport,
                attraction hours and travel insurance.
            </li>

            <li>
                Review current health and safety
                advice before departure.
            </li>
        </ul>

    </section>

    <div class="tg-actions">

        <a class="tg-btn" href="index.php">
            ✈ Search Flights
        </a>

        <a
            class="tg-btn"
            target="_blank"
            rel="noopener noreferrer"
            href="https://www.openstreetmap.org/#map=12/<?php
                echo rawurlencode((string)$d['lat']);
            ?>/<?php
                echo rawurlencode((string)$d['lon']);
            ?>"
        >
            🗺️ Open City Map
        </a>

    </div>

<?php else: ?>

    <section class="tg-hero">

        <h1>
            🌍 Travel Guide & Destination Info
        </h1>

        <p>
            Explore cities, attractions, local
            transport and travel tips before
            choosing your flight.
        </p>

        <form
            class="tg-search"
            action="travel-guide.php"
            method="get"
        >

            <input
                type="search"
                name="q"
                maxlength="80"
                placeholder="Search Dubai, Bangkok, DXB..."
                value="<?php echo tgEscape($query); ?>"
                aria-label="Search destinations"
            >

            <button class="tg-btn" type="submit">
                Search Destinations
            </button>

        </form>

    </section>

    <?php if ($cityInput !== ''): ?>

        <p class="tg-note">

            Guide for
            "<?php echo tgEscape($cityInput); ?>"
            is not available yet.

            Browse the destinations below.

        </p>

    <?php endif; ?>

    <p class="tg-muted">

        <?php echo count($visible); ?>
        destination(s) found.

        Informational examples,
        not live travel advice.

    </p>

    <?php if (!$visible): ?>

        <div class="tg-empty">

            No matches. Try a city name or airport code.

            <a href="travel-guide.php">Show all</a>.

        </div>

    <?php endif; ?>

    <div class="tg-grid">

        <?php foreach ($visible as $code => $d): ?>

        <article class="tg-card">

            <div class="tg-emoji">
                <?php echo tgEscape($d['emoji']); ?>
            </div>

            <h2>
                <?php echo tgEscape($d['city']); ?>
            </h2>

            <div class="tg-muted">

                <?php echo tgEscape($d['country']); ?>
                ·
                <?php echo tgEscape($code); ?>

            </div>

            <p>
                <?php echo tgEscape($d['intro']); ?>
            </p>

            <a
                href="travel-guide.php?city=<?php
                    echo rawurlencode($code);
                ?>"
            >
                Read Travel Guide →
            </a>

        </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<p class="tg-note">

    <strong>Important:</strong>

    This is a sample destination guide.
    Entry rules, visas, prices, weather,
    attraction hours and safety information
    can change.

    Verify with official sources before
    making travel decisions.

    No live travel advisory API is connected.

</p>

</main>

<?php
if (is_file(__DIR__ . '/support.php')) {
    include_once __DIR__ . '/support.php';
}

if (is_file(__DIR__ . '/theme-toggle.php')) {
    include_once __DIR__ . '/theme-toggle.php';
}

if (is_file(__DIR__ . '/responsive-layout.php')) {
    include_once __DIR__ . '/responsive-layout.php';
}
?>

</body>
</html>