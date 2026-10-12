<?php
// BookAFlight - Visual Flight Route Map
// This is not live aircraft tracking.

$mapRoutes = [];

foreach (($legs ?? []) as $leg) {
    if (!is_array($leg)) continue;

    $mapRoutes[] = [
        'from' => (string)($leg['from'] ?? ''),
        'to'   => (string)($leg['to'] ?? ''),
        'date' => (string)($leg['date'] ?? '')
    ];
}
?>

<link rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
.baf-route-card {
    background: #fff;
    border-radius: 14px;
    padding: 18px;
    margin: 20px 0;
    box-shadow: 0 4px 18px #00000013;
}

.baf-route-card h2 {
    margin-top: 0;
    color: #082858;
}

#baf-route-map {
    height: 360px;
    width: 100%;
    border-radius: 10px;
    z-index: 0;
}

#baf-route-status {
    font-size: 13px;
    color: #4c6077;
    line-height: 1.5;
}

@media(max-width:600px) {
    #baf-route-map {
        height: 280px;
    }
}
</style>

<section class="baf-route-card"
         aria-label="Flight route map">

    <h2>🌍 Flight Route Map</h2>

    <div id="baf-route-map"></div>

    <p id="baf-route-status">
        Loading map...
    </p>

    <p style="font-size:12px;color:#677b8c">
        Illustrative airport-to-airport connection only.
        This is not live aircraft tracking.
        Internet is required to load map tiles.
    </p>

</section>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
(() => {
    'use strict';

    const legs = <?php echo json_encode(
        $mapRoutes,
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_QUOT |
        JSON_HEX_APOS |
        JSON_INVALID_UTF8_SUBSTITUTE
    ); ?>;

    const status = document.getElementById(
        'baf-route-status'
    );

    if (typeof L === 'undefined') {
        status.textContent =
            'Map unavailable. Check internet connection.';
        return;
    }

    const airports = {
        DAC: [23.8433, 90.3978],
        CGP: [22.2496, 91.8133],
        ZYL: [24.9632, 91.8668],
        CXB: [21.4522, 91.9639],
        SPD: [25.7592, 88.9089],
        RJH: [24.4372, 88.6165],
        JSR: [23.1838, 89.1608],

        CCU: [22.6547, 88.4467],
        DEL: [28.5562, 77.1000],
        BOM: [19.0896, 72.8656],

        DXB: [25.2532, 55.3657],
        AUH: [24.4330, 54.6511],
        SHJ: [25.3286, 55.5172],
        DOH: [25.2731, 51.6081],
        JED: [21.6796, 39.1565],
        RUH: [24.9576, 46.6988],
        MCT: [23.5933, 58.2844],

        KUL: [2.7456, 101.7072],
        SIN: [1.3644, 103.9915],
        BKK: [13.6900, 100.7501],

        IST: [41.2753, 28.7519],
        LHR: [51.4700, -0.4543],
        JFK: [40.6413, -73.7781],
        YYZ: [43.6777, -79.6248]
    };

    const cities = {
        'dhaka': 'DAC',
        'chattogram': 'CGP',
        'chittagong': 'CGP',
        'sylhet': 'ZYL',
        "cox's bazar": 'CXB',
        'saidpur': 'SPD',
        'rajshahi': 'RJH',
        'jashore': 'JSR',
        'jessore': 'JSR',
        'kolkata': 'CCU',
        'delhi': 'DEL',
        'mumbai': 'BOM',
        'dubai': 'DXB',
        'abu dhabi': 'AUH',
        'sharjah': 'SHJ',
        'doha': 'DOH',
        'jeddah': 'JED',
        'riyadh': 'RUH',
        'muscat': 'MCT',
        'kuala lumpur': 'KUL',
        'singapore': 'SIN',
        'bangkok': 'BKK',
        'istanbul': 'IST',
        'london': 'LHR',
        'london heathrow': 'LHR',
        'new york': 'JFK',
        'new york jfk': 'JFK',
        'toronto': 'YYZ'
    };

    function find(place) {
        const raw = String(place || '').trim();

        const match = raw.match(
            /\(([A-Za-z]{3})\)/
        );

        const direct = raw.toUpperCase();

        let code = match
            ? match[1].toUpperCase()
            : (airports[direct] ? direct : null);

        if (!code) {
            const plain = raw
                .replace(/\([^)]*\)/g, '')
                .split(',')[0]
                .trim()
                .toLowerCase();

            code = cities[plain];
        }

        return code && airports[code]
            ? { code, point: airports[code] }
            : null;
    }

    const map = L.map('baf-route-map', {
        scrollWheelZoom: false
    }).setView([22, 65], 3);

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);

    const bounds = L.latLngBounds([]);
    const marked = new Set();
    const missing = [];

    let drawn = 0;

    legs.forEach((leg, i) => {
        const a = find(leg.from);
        const b = find(leg.to);

        if (!a || !b) {
            missing.push(
                `Flight ${i + 1}: ${leg.from} → ${leg.to}`
            );
            return;
        }

        [a, b].forEach(p => {
            bounds.extend(p.point);

            if (!marked.has(p.code)) {
                L.marker(p.point)
                    .addTo(map)
                    .bindPopup(p.code);

                marked.add(p.code);
            }
        });

        const bend = 1;

        const aLat = a.point[0];
        const aLng = a.point[1];
        const bLat = b.point[0];
        const bLng = b.point[1];

        const dx = bLng - aLng;
        const dy = bLat - aLat;

        const norm = Math.hypot(dx, dy) || 1;

        const height = Math.min(
            4,
            Math.max(.3, norm * .08)
        );

        const points = [];

        for (let s = 0; s <= 40; s++) {
            const t = s / 40;

            const offset =
                4 * t * (1 - t) * height * bend;

            points.push([
                aLat + dy * t + (dx / norm) * offset,
                aLng + dx * t - (dy / norm) * offset
            ]);
        }

        L.polyline(points, {
            color: i % 2 === 0
                ? '#087cc6'
                : '#e49616',
            weight: 4
        })
        .addTo(map)
        .bindPopup(
            `Flight ${i + 1}: ${a.code} → ${b.code}`
        );

        drawn++;
    });

    if (bounds.isValid()) {
        map.fitBounds(bounds, {
            padding: [30, 30],
            maxZoom: 6
        });
    }

    status.textContent =
        `${drawn} route(s) shown.` +
        (
            missing.length
                ? ' Not mapped: ' + missing.join('; ')
                : ''
        );

    setTimeout(() => map.invalidateSize(), 100);
})();
</script>