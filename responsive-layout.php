<?php
// BookAFlight - Responsive Layout
// Mobile, Tablet and Desktop
// No database changes required.
?>

<style>

/* ===== GLOBAL RESPONSIVE SAFETY ===== */

*, *::before, *::after {
    box-sizing: border-box;
}

html {
    -webkit-text-size-adjust: 100%;
}

body {
    max-width: 100%;
}

img,
video,
canvas {
    max-width: 100%;
    height: auto;
}

iframe {
    max-width: 100%;
}

main,
section,
article,
aside,
.grid,
.layout,
.chat-layout,
.card,
.panel,
.flight {
    min-width: 0;
}

h1, h2, h3, p {
    overflow-wrap: break-word;
}

/* ===== ACCESSIBILITY ===== */

button:focus-visible,
a:focus-visible,
input:focus-visible,
select:focus-visible,
textarea:focus-visible {
    outline: 3px solid #58aaf0;
    outline-offset: 2px;
}

.baf-mobile-menu {
    display: none;
}

/* ===== TABLET ===== */

@media (min-width: 768px) and (max-width: 1100px) {

    main {
        max-width: 96%;
    }

    .layout,
    .chat-layout {
        grid-template-columns:
            minmax(225px, 30%)
            minmax(0, 1fr) !important;
    }

    .flight {
        grid-template-columns:
            minmax(0, 1fr)
            minmax(0, 1fr) !important;
    }

    .flight > .right {
        grid-column: 1 / -1;
        text-align: right;
    }

    .grid {
        gap: 12px;
    }

    .card,
    .panel {
        max-width: 100%;
    }

    #bafFlightMap,
    #baf-route-map {
        width: 100% !important;
        height: 350px !important;
    }
}

/* ===== MOBILE & SMALL TABLETS ===== */

@media (max-width: 767px) {

    html,
    body {
        width: 100%;
        overflow-x: clip;
    }

    main {
        width: calc(100% - 24px) !important;
        max-width: 100% !important;
        margin-left: auto !important;
        margin-right: auto !important;
    }

    header,
    .topbar,
    .navbar {
        flex-wrap: wrap !important;
        gap: 10px !important;
        padding: 14px 12px !important;
    }

    header a,
    nav a {
        overflow-wrap: anywhere;
    }

    /* Page headings */

    h1 {
        font-size: clamp(
            23px,
            6vw,
            30px
        ) !important;

        line-height: 1.25;
    }

    h2 {
        font-size: 20px !important;
        line-height: 1.35;
    }

    h3 {
        font-size: 17px !important;
    }

    /* Common panels */

    .card,
    .panel,
    .filters,
    .flight,
    .journey-box,
    .baf-route-card,
    .baf-map-card,
    #compare,
    #selectionSummary {
        max-width: 100% !important;
        min-width: 0 !important;
        padding: 15px !important;
    }

    /* Flight result cards */

    .flight {
        display: grid !important;

        grid-template-columns:
            minmax(0, 1fr) !important;

        gap: 15px !important;
    }

    .flight > div {
        min-width: 0;
    }

    .flight .right,
    .right {
        text-align: left !important;
    }

    .price {
        font-size: 24px !important;
    }

    .times {
        font-size: 19px !important;
    }

    /* Filters */

    .filters {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 14px !important;
    }

    .filters label,
    .filters select {
        max-width: 100%;
    }

    /* Grid and admin layouts */

    .grid,
    .layout,
    .chat-layout,
    .compare-grid {
        display: grid !important;

        grid-template-columns:
            minmax(0, 1fr) !important;
    }

    .full {
        grid-column: auto !important;
    }

    /* Form controls */

    input:not(
        [type="checkbox"]
    ):not(
        [type="radio"]
    ),
    select,
    textarea {
        max-width: 100%;
        min-width: 0;
    }

    button[type="submit"],
    .btn {
        min-height: 44px;
        max-width: 100%;
    }

    /* Group booking */

    #groupForm .grid {
        grid-template-columns:
            minmax(0, 1fr) !important;
    }

    .passenger {
        max-width: 100%;
        min-width: 0;
    }

    /* Tables: horizontal scrolling */

    table {
        display: block;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    th,
    td {
        white-space: normal;
        overflow-wrap: anywhere;
    }

    /* Seat selection map */

    .seat-holder {
        max-width: 100% !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 14px;
    }

    .seat-map {
        max-width: none;
        margin-left: auto;
        margin-right: auto;
    }

    .seat-row {
        flex-shrink: 0;
    }

    /* Flight route maps */

    #bafFlightMap,
    #baf-route-map {
        width: 100% !important;
        height: 290px !important;
    }

    /* Admin Support */

    .threads {
        max-height: 230px;
        overflow-y: auto;
    }

    #messages,
    .chat {
        max-width: 100%;
        min-width: 0;
        height: 340px;
    }

    /* Header navigation */

    header.baf-responsive-nav {
        position: relative;
    }

    header.baf-responsive-nav
    .baf-mobile-menu {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        margin-left: auto;
        padding: 10px 13px;

        border: 1px solid #c5d5e8;
        border-radius: 9px;

        background: #ffffff;
        color: #082858;

        font-weight: bold;
        min-height: 44px;
    }

    header.baf-responsive-nav nav {
        display: none !important;

        flex-direction: column !important;

        width: 100% !important;
        flex-basis: 100% !important;

        gap: 5px !important;
        padding: 10px !important;

        background: #f0f6ff;
        border-radius: 10px;
    }

    header.baf-responsive-nav
    nav.baf-nav-open {
        display: flex !important;
    }

    header.baf-responsive-nav nav a {
        display: block;
        padding: 11px;
        border-radius: 6px;

        text-decoration: none;
        color: #082858;
    }

    html[data-baf-theme="dark"]
    header.baf-responsive-nav nav {
        background: #162235 !important;
    }

    html[data-baf-theme="dark"]
    header.baf-responsive-nav nav a {
        color: #ffffff !important;
    }

    /* Floating Live Chat */

    #baf-support,
    #baf-help {
        right: 8px !important;
        bottom: 8px !important;
        max-width: calc(100vw - 16px);
    }

    #baf-support-panel,
    #baf-panel {
        position: fixed !important;

        top: 8px !important;
        left: 8px !important;
        right: 8px !important;
        bottom: 70px !important;

        width: auto !important;
        height: auto !important;
        max-height: none !important;

        margin: 0 !important;
        border-radius: 14px !important;
    }

    #baf-support-messages,
    #baf-chat {
        min-height: 0;
        flex: 1;
        overflow-y: auto !important;
    }

    #baf-support-quick {
        max-height: 78px;
        overflow-y: auto;
    }

    #baf-support-actions {
        max-height: 85px;
        overflow-y: auto;
    }

    #baf-support-form {
        flex-shrink: 0;
    }

    #baf-support-text {
        min-width: 0;
    }

    #baf-support-toggle,
    #baf-toggle {
        padding: 12px !important;
        font-size: 12px !important;
    }

    /* Dark / Light toggle */

    #baf-theme-toggle {
        left: 8px !important;
        bottom: 8px !important;

        padding: 12px !important;
        font-size: 12px !important;
    }

    /* Footer */

    footer {
        text-align: center;
        padding: 20px 12px !important;
        max-width: 100%;
    }
}

/* ===== SMALL PHONES ===== */

@media (max-width: 420px) {

    .passenger {
        grid-template-columns:
            minmax(0, 1fr) !important;
    }

    .passenger strong {
        grid-column: auto !important;
    }

    .card,
    .panel {
        padding: 12px !important;
    }

    .airline {
        font-size: 18px !important;
    }

    .price {
        font-size: 22px !important;
    }

    #baf-support-actions button,
    #baf-support-quick button {
        font-size: 11px !important;
        padding: 7px !important;
    }

    #baf-support-header {
        padding: 10px !important;
    }
}

/* ===== LANDSCAPE MOBILE ===== */

@media (max-height: 500px)
and (max-width: 950px) {

    #baf-support-panel,
    #baf-panel {
        top: 4px !important;
        bottom: 55px !important;
    }

    #baf-support-quick {
        max-height: 50px;
    }

    #baf-support-actions {
        max-height: 50px;
    }
}

/* ===== REDUCED MOTION ===== */

@media (
    prefers-reduced-motion: reduce
) {

    *,
    *::before,
    *::after {
        scroll-behavior: auto !important;
        animation-duration: .01ms !important;
        transition-duration: .01ms !important;
    }
}

</style>

<script>
(function () {
    'use strict';

    if (window.__bafResponsiveLoaded) {
        return;
    }

    window.__bafResponsiveLoaded = true;

    // Ensure proper device viewport.
    let viewport = document.querySelector(
        'meta[name="viewport"]'
    );

    if (!viewport) {

        viewport = document.createElement('meta');

        viewport.name = 'viewport';

        viewport.content =
            'width=device-width, initial-scale=1';

        document.head.appendChild(viewport);
    }

    // Create a mobile menu when the page has
    // at least 3 navigation links.

    const nav = document.querySelector(
        'header nav'
    );

    if (!nav) return;

    const header = nav.closest('header');

    if (!header) return;

    if (nav.querySelectorAll('a[href]').length < 3) {
        return;
    }

    // Avoid interfering with existing mobile menus.
    if (
        header.querySelector(
            '.hamburger, .navbar-toggler, .menu-toggle, .baf-mobile-menu'
        )
    ) {
        return;
    }

    header.classList.add(
        'baf-responsive-nav'
    );

    if (!nav.id) {
        nav.id = 'baf-mobile-navigation';
    }

    const button = document.createElement('button');

    button.type = 'button';
    button.className = 'baf-mobile-menu';
    button.textContent = '☰ Menu';

    button.setAttribute(
        'aria-controls',
        nav.id
    );

    button.setAttribute(
        'aria-expanded',
        'false'
    );

    button.setAttribute(
        'aria-label',
        'Open navigation menu'
    );

    header.insertBefore(
        button,
        nav
    );

    function closeMenu() {

        nav.classList.remove(
            'baf-nav-open'
        );

        button.textContent = '☰ Menu';

        button.setAttribute(
            'aria-expanded',
            'false'
        );

        button.setAttribute(
            'aria-label',
            'Open navigation menu'
        );
    }

    button.addEventListener(
        'click',
        function () {

            const isOpen = nav.classList.toggle(
                'baf-nav-open'
            );

            button.textContent = isOpen
                ? '✕ Close'
                : '☰ Menu';

            button.setAttribute(
                'aria-expanded',
                String(isOpen)
            );

            button.setAttribute(
                'aria-label',
                isOpen
                    ? 'Close navigation menu'
                    : 'Open navigation menu'
            );
        }
    );

    nav.addEventListener(
        'click',
        function (event) {

            if (event.target.closest('a')) {
                closeMenu();
            }
        }
    );

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {
                closeMenu();
            }
        }
    );

    document.addEventListener(
        'click',
        function (event) {

            if (!header.contains(event.target)) {
                closeMenu();
            }
        }
    );

    window.addEventListener(
        'resize',
        function () {

            if (window.innerWidth > 767) {
                closeMenu();
            }
        }
    );

})();
</script>