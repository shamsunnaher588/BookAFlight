<?php
// BookAFlight - Price Comparison Chart
// Requires $flights and $legs from results.php.
// Illustrative prices only, not live airline fares.

if (!isset($flights) || !is_array($flights)) {
    $flights = [];
}

$pcLegs = isset($legs) && is_array($legs)
    ? array_values($legs)
    : [];

$pcPassengers = max(
    1,
    min(30, (int)($passengers ?? 1))
);

$pcOptions = [];

foreach ($flights as $id => $flight) {

    if (!is_array($flight)) continue;

    $price = (float)($flight['price'] ?? 0);

    if ($price <= 0) continue;

    $pcOptions[] = [
        'id' => (string)$id,
        'airline' => (string)(
            $flight['airline']
            ?? $flight['name']
            ?? 'Airline'
        ),
        'price' => $price,
        'stops' => (int)($flight['stops'] ?? 0),
        'departure' => (string)(
            $flight['departure']
            ?? $flight['out']
            ?? ''
        )
    ];
}

usort($pcOptions, static function ($a, $b) {
    return $a['price'] <=> $b['price'];
});

$pcPrices = array_column($pcOptions, 'price');

$pcMin = $pcPrices
    ? min($pcPrices)
    : 0;

$pcMax = $pcPrices
    ? max($pcPrices)
    : 1;

function pcEsc($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<style>
.baf-pc {
    margin: 25px 0;
    padding: 22px;
    border-radius: 15px;
    background: #ffffff;
    color: #172b4d;
    box-shadow: 0 4px 20px #00000010;
}

.baf-pc * {
    box-sizing: border-box;
}

.baf-pc-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.baf-pc h2 {
    margin: 0 0 8px;
    color: inherit;
}

.baf-pc-subtitle {
    color: #61748c;
    line-height: 1.6;
}

.baf-pc-switch {
    display: flex;
    gap: 5px;
    background: #eaf1fa;
    padding: 5px;
    border-radius: 10px;
}

.baf-pc-switch button {
    border: 0;
    background: transparent;
    color: #24415f;
    padding: 10px 12px;
    border-radius: 7px;
    cursor: pointer;
    font-weight: 600;
}

.baf-pc-switch button.active {
    background: #087cc6;
    color: white;
}

.baf-pc-leg {
    padding: 18px 0;
    border-top: 1px solid #e5ebf3;
}

.baf-pc-leg h3 {
    margin: 4px 0 15px;
    font-size: 18px;
}

.baf-pc-option {
    display: grid;
    grid-template-columns: 160px minmax(0,1fr) 130px;
    gap: 14px;
    align-items: center;
    padding: 13px 10px;
    margin: 8px 0;
    border: 1px solid #e5ebf3;
    border-radius: 10px;
}

.baf-pc-option.cheapest {
    border-color: #43a879;
    background: #f1faf5;
}

.baf-pc-airline {
    font-weight: bold;
    margin-bottom: 4px;
}

.baf-pc-detail {
    color: #667b90;
    font-size: 12px;
    line-height: 1.6;
}

.baf-pc-track {
    width: 100%;
    height: 22px;
    border-radius: 6px;
    overflow: hidden;
    background: #e7eef7;
}

.baf-pc-bar {
    height: 100%;
    background: #1681ce;
    border-radius: 6px;
    transition: width .25s ease;
}

.baf-pc-option.cheapest .baf-pc-bar {
    background: #269a67;
}

.baf-pc-cost {
    text-align: right;
}

.baf-pc-price {
    display: block;
    font-weight: bold;
    font-size: 18px;
    color: #0874bb;
    white-space: nowrap;
}

.baf-pc-option.cheapest .baf-pc-price {
    color: #198452;
}

.baf-pc-view {
    background: transparent;
    border: 0;
    padding: 6px 0;
    color: #0874bb;
    cursor: pointer;
    text-decoration: underline;
    font-size: 12px;
}

.baf-pc-summary {
    background: #eef7ff;
    padding: 15px;
    border-radius: 10px;
    margin-top: 12px;
    line-height: 1.8;
}

.baf-pc-summary strong {
    font-size: 21px;
    color: #0874bb;
}

.baf-pc-note {
    font-size: 12px;
    color: #65798d;
    line-height: 1.6;
    margin-top: 12px;
}

/* Dark Mode */
html[data-baf-theme="dark"] .baf-pc {
    background: #162235 !important;
    color: #f1f5fc !important;
}

html[data-baf-theme="dark"] .baf-pc-leg {
    border-color: #34475f;
}

html[data-baf-theme="dark"] .baf-pc-option {
    background: #1d2d44 !important;
    border-color: #34475f;
}

html[data-baf-theme="dark"]
.baf-pc-option.cheapest {
    background: #153b32 !important;
    border-color: #329d74;
}

html[data-baf-theme="dark"] .baf-pc-track {
    background: #34475f;
}

html[data-baf-theme="dark"] .baf-pc-detail,
html[data-baf-theme="dark"] .baf-pc-note,
html[data-baf-theme="dark"] .baf-pc-subtitle {
    color: #b4c2d6;
}

html[data-baf-theme="dark"] .baf-pc-summary {
    background: #1d2d44;
}

html[data-baf-theme="dark"] .baf-pc-switch {
    background: #24364d;
}

html[data-baf-theme="dark"]
.baf-pc-switch button {
    color: #f1f5fc;
}

html[data-baf-theme="dark"]
.baf-pc-switch button.active {
    background: #087cc6;
    color: white;
}

html[data-baf-theme="dark"]
.baf-pc-price,
html[data-baf-theme="dark"]
.baf-pc-view,
html[data-baf-theme="dark"]
.baf-pc-summary strong {
    color: #8ad1ff;
}

/* Mobile */
@media(max-width:650px) {
    .baf-pc {
        padding: 14px;
    }

    .baf-pc-option {
        grid-template-columns: 1fr;
        gap: 9px;
    }

    .baf-pc-cost {
        text-align: left;
    }

    .baf-pc-switch {
        width: 100%;
    }

    .baf-pc-switch button {
        flex: 1;
        font-size: 12px;
    }
}

@media(prefers-reduced-motion:reduce) {
    .baf-pc-bar {
        transition: none;
    }
}
</style>

<?php if ($pcOptions && $pcLegs): ?>

<section class="baf-pc" id="priceComparison">

    <div class="baf-pc-top">

        <div>
            <h2>📊 Flight Price Comparison</h2>

            <p class="baf-pc-subtitle">
                Compare sample fares by airline,
                including stops and total travel cost.
            </p>
        </div>

        <div
            class="baf-pc-switch"
            role="group"
            aria-label="Price display mode"
        >

            <button
                type="button"
                class="active"
                data-price-mode="single"
                aria-pressed="true"
            >
                1 Passenger
            </button>

            <button
                type="button"
                data-price-mode="all"
                aria-pressed="false"
            >
                All <?php echo $pcPassengers; ?>
            </button>

        </div>

    </div>

    <?php foreach ($pcLegs as $legIndex => $leg): ?>

    <div
        class="baf-pc-leg"
        data-leg="<?php echo $legIndex + 1; ?>"
    >

        <h3>
            Flight <?php echo $legIndex + 1; ?>:
            <?php echo pcEsc($leg['from'] ?? ''); ?>
            →
            <?php echo pcEsc($leg['to'] ?? ''); ?>
        </h3>

        <?php foreach ($pcOptions as $option): ?>

        <?php
            $cheapest =
                $option['price'] === $pcMin;

            $percentage = max(
                3,
                min(
                    100,
                    ($option['price'] / max(1, $pcMax)) * 100
                )
            );
        ?>

        <div class="baf-pc-option <?php
            echo $cheapest ? 'cheapest' : '';
        ?>">

            <div>

                <div class="baf-pc-airline">
                    <?php echo pcEsc(
                        $option['airline']
                    ); ?>
                </div>

                <div class="baf-pc-detail">

                    <?php echo $option['stops'] === 0
                        ? 'Non-stop'
                        : $option['stops'] . ' Stop(s)'; ?>

                    <?php if ($option['departure']): ?>
                        · <?php echo pcEsc(
                            $option['departure']
                        ); ?>
                    <?php endif; ?>

                </div>

                <?php if ($cheapest): ?>
                    <div class="baf-pc-detail">
                        ✓ Lowest sample fare
                    </div>
                <?php endif; ?>

            </div>

            <div
                class="baf-pc-track"
                role="img"
                aria-label="<?php
                    echo pcEsc(
                        $option['airline'] .
                        ': ' .
                        number_format(
                            $option['price'],
                            0
                        ) . ' BDT per person'
                    );
                ?>"
            >

                <div
                    class="baf-pc-bar"
                    style="width:<?php
                        echo round($percentage, 2);
                    ?>%"
                ></div>

            </div>

            <div class="baf-pc-cost">

                <span
                    class="baf-pc-price"
                    data-base-price="<?php
                        echo pcEsc(
                            $option['price']
                        );
                    ?>"
                >
                    ৳<?php echo number_format(
                        $option['price'],
                        0
                    ); ?>
                </span>

                <button
                    type="button"
                    class="baf-pc-view"
                    data-flight="<?php
                        echo pcEsc(
                            $option['id']
                        );
                    ?>"
                    data-leg="<?php
                        echo $legIndex + 1;
                    ?>"
                >
                    View Flight ↓
                </button>

            </div>

        </div>

        <?php endforeach; ?>

    </div>

    <?php endforeach; ?>

    <div class="baf-pc-summary">

        <div>
            Lowest illustrative fare across
            <?php echo count($pcLegs); ?>
            flight leg(s):
        </div>

        <strong
            id="baf-pc-total"
            data-base-total="<?php
                echo pcEsc(
                    $pcMin * count($pcLegs)
                );
            ?>"
        >
            ৳<?php echo number_format(
                $pcMin * count($pcLegs),
                0
            ); ?>
        </strong>

        <div class="baf-pc-detail">
            Sum of the cheapest sample option
            for each leg, before any additional fees.
        </div>

    </div>

    <p class="baf-pc-note">
        These are illustrative fares, not live
        airline quotations. Actual ticket price,
        taxes, baggage, availability and fees
        may differ. Selecting a sample flight
        does not reserve an airline seat.
    </p>

</section>

<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {
        'use strict';

        const chart = document.getElementById(
            'priceComparison'
        );

        if (!chart) return;

        const passengers = <?php echo $pcPassengers; ?>;

        const currency = {
    format: function (bdt) {
        return window.bafFormatCurrency
            ? window.bafFormatCurrency(bdt)
            : '৳' + Number(bdt).toLocaleString('en-BD');
    }
};

        function updatePrices(mode) {

            const multiplier =
                mode === 'all' ? passengers : 1;

            chart.querySelectorAll(
                '.baf-pc-price'
            ).forEach(label => {

                const base = Number(
                    label.dataset.basePrice
                );

                label.textContent =
                    currency.format(base * multiplier);
            });

            const total = document.getElementById(
                'baf-pc-total'
            );

            total.textContent = currency.format(
                Number(total.dataset.baseTotal) *
                multiplier
            );

            chart.querySelectorAll(
                '[data-price-mode]'
            ).forEach(button => {

                const active =
                    button.dataset.priceMode === mode;

                button.classList.toggle(
                    'active',
                    active
                );

                button.setAttribute(
                    'aria-pressed',
                    String(active)
                );
            });
        }
        window.addEventListener('baf:currency-change', () => {
    const selected = chart.querySelector(
        '[data-price-mode].active'
    );

    updatePrices(
        selected
            ? selected.dataset.priceMode
            : 'single'
    );
});

        chart.querySelectorAll(
            '[data-price-mode]'
        ).forEach(button => {

            button.addEventListener(
                'click',
                () => updatePrices(
                    button.dataset.priceMode
                )
            );
        });

        // Scroll to the corresponding Flight Card.
        chart.querySelectorAll(
            '.baf-pc-view'
        ).forEach(button => {

            button.addEventListener(
                'click',
                function () {

                    const leg = button.dataset.leg;
                    const flight = button.dataset.flight;

                    const group = document.querySelector(
                        '.leg-group[data-leg="' +
                        leg + '"]'
                    );

                    if (!group) return;

                    const card = [
                        ...group.querySelectorAll(
                            '.flight'
                        )
                    ].find(item =>
                        item.dataset.flight === flight
                    );

                    if (!card) return;

                    if (card.hidden) {
                        alert(
                            'This flight is hidden by ' +
                            'your current filter. ' +
                            'Disable the filter to view it.'
                        );

                        return;
                    }

                    card.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    card.style.outline =
                        '3px solid #ffbc43';

                    setTimeout(() => {
                        card.style.outline = '';
                    }, 1800);
                }
            );
        });

        updatePrices('single');
    }
);
</script>

<?php endif; ?>