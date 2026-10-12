<?php
// BookAFlight - Live Flight Status & Tracking
// Powered by AirLabs Aviation API.
// This is not a real airline ticket or boarding pass.

$config = require __DIR__ . '/flight-status-config.php';
$apiKey = trim((string)($config['api_key'] ?? ''));

function fsEscape($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fsValue(array $flight, string $key): string {
    $value = $flight[$key] ?? null;
    return $value === null || $value === ''
        ? 'Not available'
        : (string)$value;
}

function fsDisplayTime(array $flight, string $key): string {
    $value = fsValue($flight, $key);
    return $value === 'Not available'
        ? $value
        : $value . ' (airport local time)';
}

function fsPosition(array $flight): ?array {
    $lat = $flight['lat'] ?? null;
    $lng = $flight['lng'] ?? null;

    if (!is_numeric($lat) || !is_numeric($lng)) {
        return null;
    }

    $lat = (float)$lat;
    $lng = (float)$lng;

    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        return null;
    }

    return [
        'lat' => $lat,
        'lng' => $lng
    ];
}

$flightNumber = strtoupper(
    preg_replace(
        '/\s+/',
        '',
        is_string($_GET['flight'] ?? null)
            ? $_GET['flight']
            : ''
    )
);

$flight = null;
$error = '';
$fromCache = false;

if ($flightNumber !== '') {

    if (!preg_match(
        '/\A[A-Z0-9]{2,3}[0-9]{1,4}[A-Z]?\z/D',
        $flightNumber
    )) {

        $error = 'Enter a valid flight number, e.g. BG347 or EK585.';

    } elseif (
        $apiKey === '' ||
        $apiKey === 'PASTE_YOUR_AIRLABS_API_KEY_HERE'
    ) {

        $error = 'AirLabs API Key is not configured.';

    } elseif (!function_exists('curl_init')) {

        $error = 'PHP cURL extension is not enabled.';

    } else {

        // Cache successful responses for 3 minutes.
        $cacheFile = rtrim(sys_get_temp_dir(), '/\\')
            . DIRECTORY_SEPARATOR
            . 'baf_airlabs_'
            . hash('sha256', __DIR__ . '|' . $flightNumber)
            . '.json';

        $body = '';

        $cached = @file_get_contents($cacheFile);

        if (
            $cached !== false &&
            @filemtime($cacheFile) > time() - 180
        ) {

            $body = $cached;
            $fromCache = true;

        } else {

            $url = 'https://airlabs.co/api/v9/flight?'
                . http_build_query([
                    'flight_iata' => $flightNumber,
                    'api_key' => $apiKey
                ], '', '&', PHP_QUERY_RFC3986);

            $curl = curl_init($url);

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 6,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_USERAGENT => 'BookAFlightFlightStatus/1.0',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);

            $response = curl_exec($curl);
            $httpStatus = (int)curl_getinfo(
                $curl,
                CURLINFO_HTTP_CODE
            );

            curl_close($curl);

            if (
                $response === false ||
                $httpStatus < 200 ||
                $httpStatus >= 300
            ) {

                $error = 'Could not connect to AirLabs. Check internet connection or API limits.';

            } else {

                $body = (string)$response;
            }
        }

        if ($body !== '') {

            $decoded = json_decode($body, true);

            if (!is_array($decoded)) {

                $error = 'Invalid response from AirLabs.';

            } elseif (!empty($decoded['error'])) {

                $code = (string)(
                    $decoded['error']['code'] ?? 'unknown'
                );

                if ($code === 'not_found') {

                    $error = 'Flight not found. Try another flight number.';

                } elseif (in_array(
                    $code,
                    ['unknown_api_key', 'expired_api_key'],
                    true
                )) {

                    $error = 'Your AirLabs API Key is invalid or expired.';

                } elseif (strpos(
                    $code,
                    'limit_exceeded'
                ) !== false) {

                    $error = 'AirLabs API request limit reached. Try again later.';

                } else {

                    $error = 'AirLabs could not process this request.';
                }

            } else {

                $payload = $decoded['response'] ?? $decoded;

                if (
                    is_array($payload) &&
                    !empty($payload['flight_iata'])
                ) {

                    $flight = $payload;

                    if (!$fromCache) {
                        @file_put_contents(
                            $cacheFile,
                            $body,
                            LOCK_EX
                        );
                    }

                } else {

                    $error = 'No flight information available now.';
                }
            }
        }
    }
}

$position = $flight
    ? fsPosition($flight)
    : null;

$lastSignal = (
    $flight &&
    is_numeric($flight['updated'] ?? null)
)
    ? gmdate(
        'Y-m-d H:i:s',
        (int)$flight['updated']
    ) . ' UTC'
    : 'Not available';
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>Live Flight Status & Tracking | BookAFlight</title>

<!-- Leaflet Map CSS - Updated CDN -->
<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css"
>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font: 15px/1.55 Arial, sans-serif;
    background: #f3f7fd;
    color: #162c4b;
}

header {
    background: #082858;
    color: white;
    padding: 18px 5%;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

header a {
    color: white;
}

main {
    max-width: 1000px;
    margin: 26px auto;
    padding: 0 14px;
}

.card {
    background: white;
    padding: 22px;
    margin: 16px 0;
    border-radius: 14px;
    box-shadow: 0 5px 18px #0001;
}

h1 {
    font-size: clamp(24px, 4vw, 34px);
}

form {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: end;
}

label {
    display: block;
    font-weight: 700;
    flex: 1;
    min-width: 200px;
}

input[type="text"] {
    display: block;
    width: 100%;
    padding: 12px;
    margin-top: 7px;
    border: 1px solid #b9ccdf;
    border-radius: 8px;
    font: inherit;
    text-transform: uppercase;
}

button,
.btn {
    border: 0;
    background: #ffbc43;
    color: #082858;
    padding: 12px 17px;
    border-radius: 9px;
    font: 700 14px Arial, sans-serif;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.note {
    background: #fff2cf;
    padding: 13px;
    border-radius: 9px;
    line-height: 1.6;
}

.error {
    background: #ffe3e3;
    color: #8e2121;
    padding: 13px;
    border-radius: 9px;
}

.status {
    background: #e2f1ff;
    padding: 12px;
    border-radius: 9px;
    font-size: 18px;
    font-weight: 700;
}

.grid {
    display: grid;
    grid-template-columns:
        repeat(auto-fit, minmax(195px, 1fr));
    gap: 12px;
}

.grid > div {
    background: #f2f7fd;
    border: 1px solid #e0eaf5;
    padding: 13px;
    border-radius: 9px;
    overflow-wrap: anywhere;
}

.grid small {
    display: block;
    color: #526a83;
    margin-bottom: 5px;
}

.grid strong {
    font-size: 16px;
}

/* FIXED LEAFLET MAP CONTAINER */
#flight-map {
    height: 360px;
    width: 100%;
    max-width: 100%;
    position: relative;
    overflow: hidden;
    border-radius: 11px;
    background: #e6eff8;
    z-index: 0;
    isolation: isolate;
}

.muted {
    color: #526a83;
    font-size: 13px;
}

.controls {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 16px;
}

@media (max-width: 600px) {

    .card {
        padding: 15px;
    }

    #flight-map {
        height: 290px;
    }

}

</style>
</head>

<body>

<header>

    <strong>
        ✈ BookAFlight — Flight Tracker
    </strong>

    <a href="index.php">Home</a>

    <a href="my-bookings.php">My Bookings</a>

</header>

<main>

<h1>📡 Live Flight Status & Tracking</h1>

<p class="note">
    Flight status is supplied by AirLabs Aviation API.
    Data may be delayed or unavailable.
    Always confirm flight details with the airline.
</p>

<section class="card">

    <form method="get" action="flight-status.php">

        <label for="flight">
            Published Flight Number (e.g. BG347, EK585)

            <input
                id="flight"
                type="text"
                name="flight"
                maxlength="12"
                required
                autocomplete="off"
                pattern="[a-zA-Z0-9 ]+"
                value="<?= fsEscape($flightNumber) ?>"
                placeholder="e.g. BG347"
            >

        </label>

        <button type="submit">
            🔎 Track Flight
        </button>

    </form>

</section>

<?php if ($error): ?>

    <p class="error" role="alert">
        <?= fsEscape($error) ?>
    </p>

<?php endif; ?>

<?php if ($flight): ?>

<section class="card">

    <h2>
        <?= fsEscape(fsValue($flight, 'flight_iata')) ?>
        — Flight Information
    </h2>

    <p class="status">
        Status:
        <?= fsEscape(
            ucwords(
                str_replace(
                    '-',
                    ' ',
                    fsValue($flight, 'status')
                )
            )
        ) ?>
    </p>

    <div class="grid">

        <div>
            <small>From</small>
            <strong>
                <?= fsEscape(fsValue($flight, 'dep_iata')) ?>
            </strong>
        </div>

        <div>
            <small>To</small>
            <strong>
                <?= fsEscape(fsValue($flight, 'arr_iata')) ?>
            </strong>
        </div>

        <div>
            <small>Departure (Scheduled)</small>
            <strong>
                <?= fsEscape(
                    fsDisplayTime($flight, 'dep_time')
                ) ?>
            </strong>
        </div>

        <div>
            <small>Departure (Estimated)</small>
            <strong>
                <?= fsEscape(
                    fsDisplayTime($flight, 'dep_estimated')
                ) ?>
            </strong>
        </div>

        <div>
            <small>Arrival (Scheduled)</small>
            <strong>
                <?= fsEscape(
                    fsDisplayTime($flight, 'arr_time')
                ) ?>
            </strong>
        </div>

        <div>
            <small>Arrival (Estimated)</small>
            <strong>
                <?= fsEscape(
                    fsDisplayTime($flight, 'arr_estimated')
                ) ?>
            </strong>
        </div>

        <div>
            <small>Departure Gate / Terminal</small>
            <strong>
                <?= fsEscape(fsValue($flight, 'dep_gate')) ?>
                /
                <?= fsEscape(fsValue($flight, 'dep_terminal')) ?>
            </strong>
        </div>

        <div>
            <small>Arrival Gate / Terminal</small>
            <strong>
                <?= fsEscape(fsValue($flight, 'arr_gate')) ?>
                /
                <?= fsEscape(fsValue($flight, 'arr_terminal')) ?>
            </strong>
        </div>

        <div>
            <small>Departure Delay (Minutes)</small>
            <strong>
                <?= fsEscape(
                    fsValue($flight, 'dep_delayed')
                ) ?>
            </strong>
        </div>

        <div>
            <small>Arrival Delay (Minutes)</small>
            <strong>
                <?= fsEscape(
                    fsValue($flight, 'arr_delayed')
                ) ?>
            </strong>
        </div>

        <div>
            <small>Altitude (Meters)</small>
            <strong>
                <?= fsEscape(fsValue($flight, 'alt')) ?>
            </strong>
        </div>

        <div>
            <small>Speed (km/h)</small>
            <strong>
                <?= fsEscape(fsValue($flight, 'speed')) ?>
            </strong>
        </div>

    </div>

    <p class="muted">
        Last Aircraft Signal:
        <?= fsEscape($lastSignal) ?>.
        <?= $fromCache
            ? 'Cached response (up to 3 minutes old).'
            : 'Provider response fetched now.'
        ?>
        The provider may return the closest available
        flight instance rather than a selected date.
    </p>

    <div class="controls">

        <a
            class="btn"
            href="flight-status.php?flight=<?= rawurlencode($flightNumber) ?>"
        >
            ↻ Refresh Status
        </a>

        <label style="font-weight:normal">

            <input
                type="checkbox"
                id="auto-refresh"
            >

            Auto Refresh Every 3 Minutes

        </label>

    </div>

</section>

<section class="card">

    <h2>🗺️ Aircraft Location</h2>

    <?php if ($position): ?>

        <div
            id="flight-map"
            role="img"
            aria-label="Last reported aircraft location"
        ></div>

        <p class="muted">
            The marker shows the latest aircraft
            coordinates reported by AirLabs.
            This is not a continuously measured
            flight path.
        </p>

    <?php else: ?>

        <p class="note">
            No aircraft coordinates available now.
            This is common for scheduled,
            landed or out-of-coverage flights.
        </p>

    <?php endif; ?>

</section>

<?php endif; ?>

<p class="muted">
    This service does not issue airline tickets,
    boarding passes or real booking confirmations.
</p>

</main>

<!-- Leaflet JavaScript - Updated CDN -->
<?php if ($position): ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

<script>
if (window.L) {

    const lat = <?= json_encode($position['lat']) ?>;
    const lng = <?= json_encode($position['lng']) ?>;

    const map = L.map('flight-map', {
        scrollWheelZoom: false
    }).setView([lat, lng], 6);

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);

    L.marker([lat, lng])
        .addTo(map)
        .bindPopup('Last reported aircraft position');

    window.addEventListener('load', () => {
        map.invalidateSize();
    });

}
</script>

<?php endif; ?>

<!-- Auto Refresh -->
<?php if ($flight): ?>

<script>
(() => {

    const checkbox = document.getElementById(
        'auto-refresh'
    );

    const saved = localStorage.getItem(
        'baf_flight_auto_refresh'
    ) === '1';

    checkbox.checked = saved;

    checkbox.addEventListener('change', () => {

        localStorage.setItem(
            'baf_flight_auto_refresh',
            checkbox.checked ? '1' : '0'
        );

    });

    setInterval(() => {

        if (
            checkbox.checked &&
            document.visibilityState === 'visible'
        ) {
            location.reload();
        }

    }, 180000);

})();
</script>

<?php endif; ?>

</body>
</html>