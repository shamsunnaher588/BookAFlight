<?php
// BookAFlight - Fare Calendar / Flexible Dates
// Demo fares only, not real airline prices.

// This file is included from results.php.

if (
    !isset($trip, $legs, $flights) ||
    !is_array($legs) ||
    !$legs
) {
    return;
}

$fcEsc = static function ($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$fcParseDate = static function ($value): ?DateTimeImmutable {
    if (
        !is_string($value) ||
        !preg_match(
            '/\A\d{4}-\d{2}-\d{2}\z/D',
            $value
        )
    ) {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $value
    );

    return $date &&
        $date->format('Y-m-d') === $value
        ? $date
        : null;
};

$fcTrip = (string)$trip;

$fcOriginal = $fcParseDate(
    (string)($legs[0]['date'] ?? '')
);

$fcReturn = $fcTrip === 'roundtrip'
    ? $fcParseDate((string)($returnDate ?? ''))
    : null;

$fcToday = new DateTimeImmutable('today');

$fcCenterInput = $_GET['fc_center'] ?? null;

$fcRequestedCenter = $fcParseDate(
    is_string($fcCenterInput)
        ? $fcCenterInput
        : ''
);

$fcCenter = $fcRequestedCenter ?? $fcOriginal;

$fcBaseFare = $flights
    ? min(
        array_map(
            'floatval',
            array_column($flights, 'price')
        )
    )
    : 0;

if ($fcTrip === 'multicity') {

    echo '<section class="fc-wrap">
        <p>
            📅 Flexible Dates is currently
            available for One-Way and
            Round-Trip searches.
        </p>
    </section>';

} elseif (
    $fcOriginal &&
    $fcCenter &&
    $fcBaseFare > 0 &&
    (
        $fcTrip !== 'roundtrip' ||
        ($fcReturn && $fcReturn >= $fcOriginal)
    )
) {

    // Keep all search details while changing dates.
    $fcBaseParams = [
        'trip' => $fcTrip,
        'from' => (string)($legs[0]['from'] ?? ''),
        'to' => (string)($legs[0]['to'] ?? ''),
        'depart' => $fcOriginal->format('Y-m-d'),
        'cabin' => (string)($cabin ?? 'Economy'),
        'passengers' => (int)($passengers ?? 1)
    ];

    if ($fcReturn) {
        $fcBaseParams['return_date'] =
            $fcReturn->format('Y-m-d');
    }

    $fcUrl = static function (array $params): string {
        return 'results.php?' .
            http_build_query(
                $params,
                '',
                '&',
                PHP_QUERY_RFC3986
            ) .
            '#fare-calendar';
    };

    $fcTiles = [];
    $fcMinFare = null;

    // Fictional weekday price multipliers.
    // These are NOT real airline prices.
    $fcFactors = [
        1 => 1.00,
        2 => 0.97,
        3 => 0.95,
        4 => 0.98,
        5 => 1.03,
        6 => 1.08,
        7 => 1.04
    ];

    // Seven dates: 3 before, selected, 3 after.
    for ($offset = -3; $offset <= 3; $offset++) {

        $d = $fcCenter->modify(
            ($offset >= 0 ? '+' : '') .
            $offset .
            ' days'
        );

        $eligible = $d >= $fcToday;

        $daysShift = (int)$fcOriginal
            ->diff($d)
            ->format('%r%a');

        $params = $fcBaseParams;

        $params['depart'] = $d->format('Y-m-d');

        // For Round-Trip, move return date too.
        if ($fcReturn) {

            $newReturn = $fcReturn->modify(
                ($daysShift >= 0 ? '+' : '') .
                $daysShift .
                ' days'
            );

            $params['return_date'] =
                $newReturn->format('Y-m-d');
        }

        // Estimated demo price.
        $estimate = (int)(
            round(
                (
                    $fcBaseFare *
                    $fcFactors[(int)$d->format('N')]
                ) / 100
            ) * 100
        );

        if ($fcTrip === 'roundtrip') {
            $estimate *= 2;
        }

        if ($eligible) {

            $fcMinFare = $fcMinFare === null
                ? $estimate
                : min($fcMinFare, $estimate);
        }

        $fcTiles[] = [
            'date' => $d,
            'eligible' => $eligible,
            'price' => $estimate,
            'url' => $fcUrl($params),
            'selected' =>
                $d->format('Y-m-d') ===
                $fcOriginal->format('Y-m-d')
        ];
    }

    $fcPrevParams = $fcBaseParams;

    $fcPrevParams['fc_center'] =
        $fcCenter->modify('-7 days')
            ->format('Y-m-d');

    $fcNextParams = $fcBaseParams;

    $fcNextParams['fc_center'] =
        $fcCenter->modify('+7 days')
            ->format('Y-m-d');
?>

<style>
.fc-wrap {
    background: #fff;
    color: #173052;
    padding: 20px;
    border-radius: 14px;
    margin: 20px 0;
    box-shadow: 0 4px 18px #0000000b;
}

.fc-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.fc-head h2 {
    margin: 0;
    font-size: 22px;
}

.fc-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.fc-nav a {
    padding: 9px 12px;
    text-decoration: none;
    border: 1px solid #c9d9ed;
    border-radius: 8px;
    color: #075d9f;
    font-weight: 700;
}

.fc-days {
    display: grid;
    grid-template-columns:
        repeat(7, minmax(0, 1fr));
    gap: 9px;
    margin-top: 16px;
}

.fc-day {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    min-width: 0;
    gap: 5px;
    min-height: 125px;
    padding: 11px 6px;
    background: #f4f8fd;
    border: 2px solid #d7e5f5;
    border-radius: 12px;
    text-decoration: none;
    color: #183558;
}

.fc-day strong {
    font-size: 19px;
}

.fc-day small {
    color: #526a85;
    font-size: 12px;
}

.fc-price {
    font-size: 15px;
    font-weight: 800;
    color: #0871b7;
    overflow-wrap: anywhere;
}

.fc-day.fc-cheapest {
    border-color: #15803d;
    background: #eaf8f0;
}

.fc-day.fc-selected {
    outline: 3px solid #f2b943;
    outline-offset: 0;
}

.fc-day.fc-disabled {
    color: #8997a8;
    background: #f1f2f4;
    border-color: #e1e4e8;
}

.fc-note {
    margin: 15px 0 0;
    line-height: 1.5;
    font-size: 13px;
    color: #5b6f85;
}

.fc-tag {
    font-size: 11px;
    color: #12733d;
    font-weight: 700;
}

/* Dark mode */
html[data-baf-theme="dark"] .fc-wrap {
    background: #172438;
    color: #f2f6ff;
}

html[data-baf-theme="dark"] .fc-day {
    background: #20344b;
    color: #f2f6ff;
    border-color: #425d7a;
}

html[data-baf-theme="dark"] .fc-day small,
html[data-baf-theme="dark"] .fc-note {
    color: #c5d3e4;
}

html[data-baf-theme="dark"] .fc-price {
    color: #8ad1ff;
}

html[data-baf-theme="dark"] .fc-day.fc-cheapest {
    border-color: #47ba76;
}

/* Tablet */
@media (max-width: 760px) {
    .fc-days {
        grid-template-columns:
            repeat(4, minmax(0, 1fr));
    }
}

/* Mobile */
@media (max-width: 450px) {
    .fc-wrap {
        padding: 14px;
    }

    .fc-days {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }
}
</style>

<section
    id="fare-calendar"
    class="fc-wrap"
    aria-label="Flexible dates fare calendar"
>

    <div class="fc-head">

        <h2>📅 Fare Calendar · Flexible Dates</h2>

        <nav
            class="fc-nav"
            aria-label="Change displayed week"
        >

            <a href="<?php
                echo $fcEsc(
                    $fcUrl($fcPrevParams)
                );
            ?>">
                ← Previous Week
            </a>

            <a href="<?php
                echo $fcEsc(
                    $fcUrl($fcNextParams)
                );
            ?>">
                Next Week →
            </a>

        </nav>

    </div>

    <p class="fc-note">

        <?php
            echo $fcEsc($legs[0]['from']);
        ?>
        →
        <?php
            echo $fcEsc($legs[0]['to']);
        ?>

        ·

        <?php
            echo $fcTrip === 'roundtrip'
                ? 'Example round-trip total'
                : 'Example one-way fare';
        ?>

        per passenger.

        Click a date to search it.

        <?php if ($fcTrip === 'roundtrip'): ?>

            Both outbound and return dates move together.

        <?php endif; ?>

    </p>

    <div class="fc-days">

        <?php foreach ($fcTiles as $tile): ?>

            <?php

                $classes = 'fc-day';

                if (!$tile['eligible']) {
                    $classes .= ' fc-disabled';
                }

                if (
                    $tile['eligible'] &&
                    $tile['price'] === $fcMinFare
                ) {
                    $classes .= ' fc-cheapest';
                }

                if ($tile['selected']) {
                    $classes .= ' fc-selected';
                }

            ?>

            <?php if ($tile['eligible']): ?>

                <a
                    class="<?php
                        echo $fcEsc($classes);
                    ?>"

                    href="<?php
                        echo $fcEsc($tile['url']);
                    ?>"

                    <?php
                        echo $tile['selected']
                            ? 'aria-current="date"'
                            : '';
                    ?>
                >

            <?php else: ?>

                <div
                    class="<?php
                        echo $fcEsc($classes);
                    ?>"
                    aria-disabled="true"
                >

            <?php endif; ?>

                <small>
                    <?php
                        echo $fcEsc(
                            $tile['date']->format('D')
                        );
                    ?>
                </small>

                <strong>
                    <?php
                        echo $fcEsc(
                            $tile['date']->format('d M')
                        );
                    ?>
                </strong>

                <?php if ($tile['eligible']): ?>

                    <span
                        class="fc-price"
                        data-baf-bdt="<?php
                            echo (int)$tile['price'];
                        ?>"
                    >
                        ৳<?php
                            echo number_format(
                                $tile['price']
                            );
                        ?>
                    </span>

                    <?php if (
                        $tile['price'] === $fcMinFare
                    ): ?>

                        <span class="fc-tag">
                            Lowest example
                        </span>

                    <?php endif; ?>

                    <?php if ($tile['selected']): ?>

                        <small>
                            Selected date
                        </small>

                    <?php endif; ?>

                <?php else: ?>

                    <small>
                        Past date
                    </small>

                <?php endif; ?>

            <?php if ($tile['eligible']): ?>

                </a>

            <?php else: ?>

                </div>

            <?php endif; ?>

        <?php endforeach; ?>

    </div>

    <p class="fc-note">

        <strong>DEMO only:</strong>

        These date-specific fares are simulated,
        not real airline prices.

        Flight Results currently uses fixed sample
        fares, so the prices there may differ
        from these calendar estimates.

        No airline seats or tickets are reserved.

    </p>

</section>

<?php

} else {

    echo '<section class="fc-wrap">
        <p>
            📅 Flexible Dates is unavailable until
            a valid future departure date is selected.
        </p>
    </section>';

}
?>