<?php
/**
 * BookAFlight Maintenance Mode
 * true = Maintenance ON
 * false = Maintenance OFF
 */

define('BAF_MAINTENANCE_ON', false);

// Keep command-line scripts usable.
if (
    !BAF_MAINTENANCE_ON ||
    PHP_SAPI === 'cli'
) {
    return;
}

// Return correct maintenance response.
http_response_code(503);

header('Content-Type: text/html; charset=UTF-8');

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<meta name="robots" content="noindex,nofollow">

<title>We'll Be Back Soon | BookAFlight</title>

<style>
:root {
    color-scheme: dark;
}

* {
    box-sizing: border-box;
}

body {
    min-height: 100vh;
    margin: 0;

    display: grid;
    place-items: center;

    padding: 22px;

    font: 16px/1.65 Arial, sans-serif;
    color: #f2f7ff;

    background:
        radial-gradient(
            circle at 75% 15%,
            #205586 0,
            transparent 37%
        ),
        linear-gradient(
            145deg,
            #061629,
            #082f57 70%,
            #0a4068
        );
}

.shell {
    width: min(100%, 650px);
}

.brand {
    text-align: center;
    font-size: 23px;
    font-weight: 800;
    margin-bottom: 20px;
}

.brand span {
    color: #ffcb68;
}

.panel {
    background: rgba(255,255,255,.08);

    border: 1px solid rgba(255,255,255,.19);

    padding: clamp(25px,6vw,48px);

    border-radius: 24px;

    text-align: center;

    box-shadow:
        0 20px 60px rgba(0,0,0,.2);

    backdrop-filter: blur(12px);
}

.symbol {
    font-size: 56px;
    line-height: 1.2;
    margin-bottom: 18px;
}

h1 {
    font-size: clamp(30px,5vw,43px);
    line-height: 1.2;
    margin: 0 0 15px;
}

p {
    margin: 0 auto 24px;
    max-width: 470px;
    color: #d8e7f8;
}

.status {
    display: inline-block;

    background: rgba(255,204,104,.12);

    border: 1px solid rgba(255,204,104,.4);

    color: #ffdf9e;

    padding: 7px 14px;

    border-radius: 999px;

    font-size: 13px;
    font-weight: 700;

    margin-bottom: 20px;
}

button {
    background: #ffc75c;
    color: #092e56;

    border: 0;
    border-radius: 10px;

    padding: 13px 22px;

    font: 700 15px Arial,sans-serif;

    cursor: pointer;
}

button:hover {
    background: #ffdb98;
}

button:focus-visible {
    outline: 3px solid white;
    outline-offset: 4px;
}

.small {
    font-size: 13px;
    color: #abc5de;
    margin-top: 20px;
}

footer {
    margin-top: 18px;
    text-align: center;
    color: #9fbed9;
    font-size: 13px;
}
</style>

</head>

<body>

<main class="shell">

    <div class="brand">
        ✈ Book<span>AFlight</span>
    </div>

    <section class="panel">

        <div class="symbol">
            🛠️
        </div>

        <div class="status">
            ● MAINTENANCE MODE
        </div>

        <h1>
            We'll Be Back Soon!
        </h1>

        <p>
            BookAFlight is temporarily unavailable
            while we perform maintenance
            and improvements.

            Please check back shortly.
        </p>

        <button
            type="button"
            onclick="window.location.reload()"
        >
            ↻ Try Again
        </button>

        <div class="small">
            Thank you for your patience.
        </div>

    </section>

    <footer>
        © <?= date('Y') ?> BookAFlight
        · Flight Booking Management System
    </footer>

</main>

</body>
</html>

<?php exit; ?>