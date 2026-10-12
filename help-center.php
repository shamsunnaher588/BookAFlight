<?php
/**
 * BookAFlight FAQ & Help Center
 * Standalone PHP page
 * No database or additional API required.
 */

function hcEsc($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$categories = [
    'flights' => [
        'icon' => '✈️',
        'name' => 'Flights & Search'
    ],
    'bookings' => [
        'icon' => '🎫',
        'name' => 'Bookings & Seats'
    ],
    'tracking' => [
        'icon' => '📍',
        'name' => 'Flight Tracking'
    ],
    'payment' => [
        'icon' => '💳',
        'name' => 'Payments & Refunds'
    ],
    'account' => [
        'icon' => '👤',
        'name' => 'Account & Privacy'
    ],
    'extras' => [
        'icon' => '🏨',
        'name' => 'Travel Extras'
    ]
];

$faqs = [
    [
        'flights',
        'How do I search for a flight?',
        'Go to the homepage, choose your trip type, enter your departure city, destination and dates, then select Search Flights.'
    ],
    [
        'flights',
        'Can I search for one-way, round-trip or multi-city flights?',
        'Yes. BookAFlight supports searching for one-way, round-trip and multi-city journeys. Combined multi-leg ticket issuance is not connected to a real airline reservation system.'
    ],
    [
        'flights',
        'Are the flight fares and seats live?',
        'Not currently. Flight search fares, available seats and some schedules are project/demo data. Do not treat them as live airline offers.'
    ],
    [
        'flights',
        'What is the Fare Calendar?',
        'The Fare Calendar displays illustrative prices across nearby dates so you can compare flexible travel days. Prices are examples rather than confirmed airline quotes.'
    ],
    [
        'bookings',
        'How can I see my saved bookings?',
        'Sign in and open My Bookings or My Journeys. These records are saved inside the project database and are not airline-issued tickets.'
    ],
    [
        'bookings',
        'Does selecting a seat issue a real airline ticket?',
        'No. Seat selection and boarding pass features are demonstrations. They do not reserve inventory with an airline or authorize travel.'
    ],
    [
        'bookings',
        'How does Booking Share work?',
        'Open Booking Share for an eligible saved demo booking, create a share link and copy it. The public page shows limited itinerary information, not passenger personal details.'
    ],
    [
        'bookings',
        'Can I stop or expire a shared booking link?',
        'Yes. Booking Share supports stopping a link and its links are designed to expire after seven days.'
    ],
    [
        'tracking',
        'How do I track a flight?',
        'Open Live Flight Tracking, enter a published flight number such as BG305 or EK585, and search. Available flight status information is supplied by AirLabs.'
    ],
    [
        'tracking',
        'Why is the live aircraft map sometimes missing?',
        'A flight provider may not have coordinates for scheduled, landed or untracked flights. The map appears only when usable location data is available.'
    ],
    [
        'tracking',
        'Is the displayed flight status guaranteed to be current?',
        'No. Third-party status data can be delayed, incomplete or unavailable. Confirm flight times and changes with the operating airline or airport.'
    ],
    [
        'payment',
        'Can I pay for a real airline ticket on BookAFlight?',
        'Not yet. Real airline payment, ticket issuance and airline reservation APIs are not connected. Do not enter payment-card details into demo forms.'
    ],
    [
        'payment',
        'Can I request a real refund or cancellation here?',
        'BookAFlight does not currently handle actual airline refunds or ticket cancellations. For real reservations, contact the provider that issued your ticket.'
    ],
    [
        'account',
        'How do I create an account?',
        'Select Sign Up, enter the details requested and complete registration. If you already have an account, use Login.'
    ],
    [
        'account',
        'What information is visible through Booking Share?',
        'The public demo itinerary is intended to show limited route, airline, dates, cabin and traveler-count information. Avoid sharing links with people you do not trust.'
    ],
    [
        'account',
        'Can I change language or currency?',
        'The project includes language and currency controls on supported pages. Currency conversion is for display and does not make demo fares into bookable airline prices.'
    ],
    [
        'extras',
        'Can I book a hotel or rental car?',
        'The Hotel and Car Rental pages include previews and provider-integration code. Live prices and checkout depend on approved partner API access.'
    ],
    [
        'extras',
        'Is the Sky Weather animation real flight tracking?',
        'No. Any animated sky or decorative airplane is a visual simulation and must not be confused with actual aircraft positions from Live Flight Tracking.'
    ]
];

$links = [
    [
        'index.php',
        '✈️',
        'Search Flights',
        'Explore flight results'
    ],
    [
        'my-bookings.php',
        '🎫',
        'My Bookings',
        'View saved demo bookings'
    ],
    [
        'flight-status.php',
        '📡',
        'Flight Tracker',
        'Check flight status'
    ],
    [
        'booking-share.php',
        '🔗',
        'Booking Share',
        'Manage itinerary links'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Help Center & FAQ | BookAFlight</title>

<style>
:root {
    --navy: #0a2851;
    --blue: #1467b0;
    --ink: #18314f;
    --muted: #5d7088;
    --line: #dce5f0;
    --bg: #f3f7fd;
    --gold: #ffbd46;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--bg);
    color: var(--ink);
    font: 15px/1.65 Arial, sans-serif;
}

a {
    color: inherit;
}

button, input {
    font: inherit;
}

/* Header */

header {
    background: var(--navy);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding: 17px 5%;
    flex-wrap: wrap;
}

.brand {
    font-size: 23px;
    font-weight: 850;
    text-decoration: none;
}

.brand span {
    color: var(--gold);
}

header nav {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
}

header nav a {
    color: #e7f2ff;
    text-decoration: none;
    font-size: 14px;
}

header nav a:hover {
    text-decoration: underline;
}

/* Hero */

.hero {
    background: linear-gradient(
        120deg,
        #092856 10%,
        #12589e 75%,
        #297bba
    );

    color: white;
    padding: 62px 16px 85px;
    text-align: center;
}

.hero .eyebrow {
    color: #f6d597;
    font-weight: bold;
    letter-spacing: 1.4px;
    font-size: 12px;
}

.hero h1 {
    font-size: clamp(29px, 4vw, 47px);
    line-height: 1.18;
    margin: 12px 0;
}

.hero p {
    color: #d5eaff;
    margin: 0 0 24px;
}

/* Search */

.search-box {
    position: relative;
    max-width: 690px;
    margin: 0 auto;
}

.search-box input {
    width: 100%;
    min-height: 55px;
    border: 0;
    border-radius: 12px;
    padding: 14px 52px 14px 20px;
    background: white;
    color: var(--ink);
    outline-offset: 4px;
    box-shadow: 0 8px 24px #001c3f38;
}

.search-icon {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: #487093;
    font-size: 20px;
    pointer-events: none;
}

/* Main */

main {
    max-width: 1120px;
    margin: -38px auto 50px;
    padding: 0 18px;
    position: relative;
}

/* Quick Links */

.quick-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}

.quick-card {
    display: block;
    background: white;
    border: 1px solid var(--line);
    padding: 21px;
    border-radius: 15px;
    text-decoration: none;
    box-shadow: 0 6px 20px #11314f0a;
    transition: transform .15s;
}

.quick-card:hover {
    transform: translateY(-3px);
    border-color: #84b1db;
}

.quick-icon {
    font-size: 24px;
}

.quick-card strong {
    display: block;
    margin: 8px 0 3px;
}

.quick-card span:last-child {
    font-size: 13px;
    color: var(--muted);
}

/* FAQ Section */

.section-head {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    margin: 35px 0 15px;
}

.section-head h2 {
    font-size: 25px;
    margin: 0;
}

.section-head p {
    margin: 3px 0 0;
    color: var(--muted);
}

/* Categories */

.category-list {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    margin: 15px 0 19px;
}

.category-list button {
    cursor: pointer;
    background: white;
    color: #234967;
    border: 1px solid #cfdfed;
    border-radius: 50px;
    padding: 9px 14px;
    font-weight: bold;
}

.category-list button[aria-pressed="true"] {
    background: var(--navy);
    color: white;
    border-color: var(--navy);
}

/* FAQ Questions */

.faq-list {
    display: grid;
    gap: 10px;
}

.faq-item {
    border: 1px solid var(--line);
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

.faq-item summary {
    list-style: none;
    cursor: pointer;
    padding: 17px 20px;
    font-size: 15px;
    font-weight: bold;

    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.faq-item summary::-webkit-details-marker {
    display: none;
}

.faq-item summary::after {
    content: '+';
    color: var(--blue);
    font-size: 24px;
    font-weight: normal;
}

.faq-item[open] summary::after {
    content: '−';
}

.faq-answer {
    padding: 0 20px 18px;
    color: #496079;
    max-width: 850px;
}

.faq-answer p {
    margin: 0;
}

.faq-category {
    color: #68819b;
    letter-spacing: .4px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    display: block;
    margin-bottom: 4px;
}

/* Empty Search */

.empty {
    background: white;
    border: 1px dashed #b7c9dc;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    color: #526981;
}

/* Notice */

.note {
    margin-top: 28px;
    background: #fff6df;
    border-left: 4px solid #e5a52b;
    padding: 15px 18px;
    border-radius: 10px;
    color: #70521b;
}

/* Contact Support Section */

.contact {
    margin-top: 25px;
    background: #0c2c56;
    color: white;
    border-radius: 16px;
    padding: 25px;

    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 17px;
    flex-wrap: wrap;
}

.contact h2 {
    margin: 0 0 5px;
}

.contact p {
    margin: 0;
    color: #d0e2f5;
    max-width: 700px;
}

.contact a {
    background: var(--gold);
    color: #102d4c;
    padding: 12px 18px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
}

footer {
    text-align: center;
    color: #687f96;
    font-size: 13px;
    padding: 0 15px 28px;
}

[hidden] {
    display: none !important;
}

/* Mobile Responsive */

@media(max-width:830px) {
    .quick-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width:480px) {
    .hero {
        padding-top: 42px;
    }

    .quick-grid {
        grid-template-columns: 1fr;
    }

    .quick-card {
        padding: 14px 16px;
    }

    .section-head h2 {
        font-size: 22px;
    }

    .faq-item summary {
        padding: 15px;
    }

    .faq-answer {
        padding: 0 15px 15px;
    }
}
</style>

</head>

<body>

<header>

    <a class="brand" href="index.php">
        ✈ Book<span>AFlight</span>
    </a>

    <nav>
        <a href="index.php">Home</a>

        <?php if (
            is_file(__DIR__ . '/my-bookings.php')
        ): ?>
            <a href="my-bookings.php">
                My Bookings
            </a>
        <?php endif; ?>

        <?php if (
            is_file(__DIR__ . '/flight-status.php')
        ): ?>
            <a href="flight-status.php">
                Flight Tracker
            </a>
        <?php endif; ?>

    </nav>

</header>

<!-- HERO -->

<section class="hero">

    <div class="eyebrow">
        BOOKAFLIGHT SUPPORT
    </div>

    <h1>
        How can we help you?
    </h1>

    <p>
        Find clear answers about flights,
        bookings, tracking and more.
    </p>

    <div class="search-box">

        <label for="faqSearch"
               style="position:absolute;width:1px;height:1px;overflow:hidden">
            Search FAQs
        </label>

        <input
            id="faqSearch"
            type="search"
            placeholder="Search for booking, refund, tracking..."
            autocomplete="off"
            maxlength="120"
        >

        <span class="search-icon"
              aria-hidden="true">⌕</span>

    </div>

</section>

<main>

<!-- QUICK ACCESS CARDS -->

<section class="quick-grid"
         aria-label="Quick links">

    <?php foreach (
        $links as [$file, $icon, $name, $description]
    ): ?>

        <?php if (
            !is_file(__DIR__ . '/' . $file)
        ) continue; ?>

        <a class="quick-card"
           href="<?= hcEsc($file) ?>">

            <span class="quick-icon">
                <?= hcEsc($icon) ?>
            </span>

            <strong>
                <?= hcEsc($name) ?>
            </strong>

            <span>
                <?= hcEsc($description) ?>
            </span>

        </a>

    <?php endforeach; ?>

</section>

<!-- FAQ -->

<section aria-labelledby="faqHeading">

    <div class="section-head">

        <div>
            <h2 id="faqHeading">
                Frequently Asked Questions
            </h2>

            <p>
                Choose a topic or find your
                answer instantly.
            </p>
        </div>

        <span
            id="faqCount"
            role="status"
            aria-live="polite"
        >
            <?= count($faqs) ?> questions
        </span>

    </div>

    <!-- CATEGORY FILTERS -->

    <div
        class="category-list"
        role="group"
        aria-label="FAQ categories"
    >

        <button
            type="button"
            data-category="all"
            aria-pressed="true"
        >
            All topics
        </button>

        <?php foreach (
            $categories as $key => $category
        ): ?>

            <button
                type="button"
                data-category="<?= hcEsc($key) ?>"
                aria-pressed="false"
            >
                <?= hcEsc(
                    $category['icon'] . ' ' .
                    $category['name']
                ) ?>
            </button>

        <?php endforeach; ?>

    </div>

    <!-- FAQ ACCORDION -->

    <div class="faq-list" id="faqList">

        <?php foreach (
            $faqs as $i => [
                $category,
                $question,
                $answer
            ]
        ): ?>

            <details
                class="faq-item"
                data-category="<?= hcEsc($category) ?>"
                data-search="<?= hcEsc(
                    $question . ' ' .
                    $answer . ' ' .
                    $categories[$category]['name']
                ) ?>"
                id="faq-<?= $i + 1 ?>"
            >

                <summary>

                    <span>

                        <span class="faq-category">
                            <?= hcEsc(
                                $categories[$category]['name']
                            ) ?>
                        </span>

                        <?= hcEsc($question) ?>

                    </span>

                </summary>

                <div class="faq-answer">
                    <p><?= hcEsc($answer) ?></p>
                </div>

            </details>

        <?php endforeach; ?>

    </div>

    <div
        class="empty"
        id="faqEmpty"
        hidden
    >
        No matching questions.
        Try another keyword or choose All topics.
    </div>

</section>

<!-- DEMO NOTICE -->

<aside class="note">

    <strong>Important:</strong>

    BookAFlight currently includes demo
    flight fares, bookings and boarding passes.

    They are not real airline tickets.

    Live Flight Tracking uses third-party
    data and may be delayed or incomplete.

</aside>

<!-- CONTACT SECTION -->

<section class="contact">

    <div>

        <h2>Still need help?</h2>

        <p>
            You can return to flight search
            or check your demo booking details.

            Human support agents and support-ticket
            submission are not connected to
            this FAQ page yet.
        </p>

    </div>

    <a href="index.php">
        Back to Home →
    </a>

</section>

</main>

<footer>
    © <?= date('Y') ?> BookAFlight
    · FAQ & Help Center
</footer>

<!-- JAVASCRIPT SEARCH AND FILTER -->

<script>
(() => {
    'use strict';

    const input = document.getElementById(
        'faqSearch'
    );

    const items = Array.from(
        document.querySelectorAll('.faq-item')
    );

    const buttons = Array.from(
        document.querySelectorAll(
            '.category-list button'
        )
    );

    const count = document.getElementById(
        'faqCount'
    );

    const empty = document.getElementById(
        'faqEmpty'
    );

    let category = 'all';

    /* Search + Category Filter */

    function filterFAQ() {

        const query = input.value
            .trim()
            .toLocaleLowerCase();

        let shown = 0;

        items.forEach(item => {

            const categoryMatch =
                category === 'all' ||
                item.dataset.category === category;

            const textMatch =
                item.dataset.search
                    .toLocaleLowerCase()
                    .includes(query);

            item.hidden = !(
                categoryMatch && textMatch
            );

            if (!item.hidden) {
                shown++;
            }

        });

        count.textContent =
            shown +
            (shown === 1
                ? ' question'
                : ' questions');

        empty.hidden = shown !== 0;
    }

    /* Category Buttons */

    buttons.forEach(button => {

        button.addEventListener('click', () => {

            category = button.dataset.category;

            buttons.forEach(b => {
                b.setAttribute(
                    'aria-pressed',
                    b === button ? 'true' : 'false'
                );
            });

            filterFAQ();

        });

    });

    /* Instant Search */

    input.addEventListener(
        'input',
        filterFAQ
    );

    /* Clear search on Escape */

    input.addEventListener('keydown', event => {

        if (event.key === 'Escape') {

            input.value = '';

            filterFAQ();

            input.blur();
        }

    });

    /* Keyboard Shortcut / */

    document.addEventListener('keydown', event => {

        const tag =
            document.activeElement.tagName;

        if (
            event.key === '/' &&
            !['INPUT','TEXTAREA'].includes(tag)
        ) {

            event.preventDefault();

            input.focus();
        }

    });

})();
</script>

</body>
</html>