<?php
// BookAFlight Dark / Light Theme Toggle
// No database connection needed.
// Include once before </body> on each page.
?>

<style>
:root {
    --baf-dark-bg: #0b1220;
    --baf-dark-card: #162235;
    --baf-dark-card2: #1d2d44;
    --baf-dark-text: #f1f5fc;
    --baf-dark-muted: #b4c2d6;
    --baf-dark-border: #34475f;
}

/* Fixed theme button - bottom left */
#baf-theme-toggle {
    position: fixed;
    left: 18px;
    bottom: 18px;
    z-index: 99998;

    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;

    padding: 12px 17px;
    border-radius: 30px;
    border: 1px solid #cbd8e6;

    background: #ffffff;
    color: #082858;

    font: 600 14px Arial, sans-serif;
    cursor: pointer;
    box-shadow: 0 5px 20px #00000025;
    transition: background .2s, color .2s;
}

#baf-theme-toggle:hover {
    transform: translateY(-2px);
}

#baf-theme-toggle:focus-visible {
    outline: 3px solid #64b5ff;
    outline-offset: 3px;
}

/* DARK MODE */
html[data-baf-theme="dark"] {
    color-scheme: dark;
}

html[data-baf-theme="dark"] body {
    background: var(--baf-dark-bg) !important;
    color: var(--baf-dark-text) !important;
}

/* Cards and page sections */
html[data-baf-theme="dark"] :is(
    .card,
    .panel,
    .flight,
    .filters,
    .journey-box,
    #compare,
    #selectionSummary,
    .baf-route-card,
    .baf-map-card,
    .baf-page-main,
    .passenger,
    .seat-map
) {
    background: var(--baf-dark-card) !important;
    color: var(--baf-dark-text) !important;
    border-color: var(--baf-dark-border) !important;
}

/* Secondary boxes */
html[data-baf-theme="dark"] :is(
    .route,
    .journey-leg,
    .selected-leg,
    .leg-heading,
    .compare-item,
    .thread,
    .item,
    .chat,
    #messages,
    .flight.is-selected
) {
    background: var(--baf-dark-card2) !important;
    color: var(--baf-dark-text) !important;
    border-color: var(--baf-dark-border) !important;
}

/* Text */
html[data-baf-theme="dark"] :is(
    h1,
    h2,
    h3,
    h4,
    p,
    label
) {
    color: inherit;
}

html[data-baf-theme="dark"] :is(
    .muted,
    .summary,
    .status,
    .baf-map-info,
    #baf-route-status
) {
    color: var(--baf-dark-muted) !important;
}

/* Form inputs */
html[data-baf-theme="dark"] :is(
    input:not([type="checkbox"]):not([type="radio"]),
    select,
    textarea
) {
    background: #101b2d !important;
    color: #f1f5fc !important;
    border-color: #47617f !important;
}

html[data-baf-theme="dark"] input::placeholder,
html[data-baf-theme="dark"] textarea::placeholder {
    color: #a9b7c9 !important;
}

html[data-baf-theme="dark"] option {
    background: #17243a;
    color: white;
}

/* Tables */
html[data-baf-theme="dark"] table {
    color: var(--baf-dark-text);
}

html[data-baf-theme="dark"] th {
    background: #22344c !important;
    color: white !important;
}

html[data-baf-theme="dark"] td,
html[data-baf-theme="dark"] th {
    border-color: var(--baf-dark-border) !important;
}

/* Alerts */
html[data-baf-theme="dark"] :is(
    .notice,
    .note
) {
    background: #413419 !important;
    color: #ffe3ab !important;
}

html[data-baf-theme="dark"] .error {
    background: #491f29 !important;
    color: #ffccd1 !important;
}

html[data-baf-theme="dark"] :is(
    .ok,
    .success,
    .good
) {
    background: #143e31 !important;
    color: #baf4d7 !important;
}

/* Admin Support Inbox */
html[data-baf-theme="dark"] .msg {
    background: #253a55 !important;
    color: #f1f5fc !important;
}

html[data-baf-theme="dark"] .thread.active {
    background: #215c91 !important;
}

/* Customer Support Widget */
html[data-baf-theme="dark"] #baf-support {
    color: white;
}

html[data-baf-theme="dark"] #baf-support-panel {
    background: #162235 !important;
    border-color: #34475f !important;
}

html[data-baf-theme="dark"] #baf-support-presence,
html[data-baf-theme="dark"] #baf-support-messages,
html[data-baf-theme="dark"] #baf-support-quick,
html[data-baf-theme="dark"] #baf-support-actions {
    background: #101b2d !important;
    color: #f1f5fc !important;
}

html[data-baf-theme="dark"] #baf-support .baf-msg {
    background: #253a55 !important;
    color: white !important;
}

html[data-baf-theme="dark"] #baf-support .baf-msg.baf-user {
    background: #087cc6 !important;
}

html[data-baf-theme="dark"] #baf-support-quick button,
html[data-baf-theme="dark"] #baf-support-actions button {
    background: #253a55 !important;
    color: white !important;
    border-color: #46617d !important;
}

html[data-baf-theme="dark"] #baf-support-rating {
    background: #413419 !important;
    color: #ffe3ab !important;
}

/* Theme toggle in dark mode */
html[data-baf-theme="dark"] #baf-theme-toggle {
    background: #ffbc43;
    color: #082858;
    border-color: #ffbc43;
}

@media (max-width: 500px) {
    #baf-theme-toggle {
        left: 8px;
        bottom: 8px;
        padding: 12px;
        font-size: 12px;
    }
}
</style>

<button
    id="baf-theme-toggle"
    type="button"
    aria-label="Switch color theme"
    aria-pressed="false"
>
    🌙 Dark Mode
</button>

<script>
(function () {
    'use strict';

    // Prevent duplicate theme initialization.
    if (window.__bafThemeLoaded) return;
    window.__bafThemeLoaded = true;

    const key = 'bookaflight_theme';
    const root = document.documentElement;
    const button = document.getElementById(
        'baf-theme-toggle'
    );

    let saved = null;

    try {
        saved = localStorage.getItem(key);
    } catch (e) {
        // Use default if browser storage is unavailable.
    }

    const systemDark = window.matchMedia
        ? window.matchMedia(
            '(prefers-color-scheme: dark)'
        ).matches
        : false;

    let current =
        saved === 'dark' || saved === 'light'
            ? saved
            : (systemDark ? 'dark' : 'light');

    function applyTheme(theme) {
        current = theme;

        root.setAttribute(
            'data-baf-theme',
            theme
        );

        const isDark = theme === 'dark';

        button.textContent = isDark
            ? '☀️ Light Mode'
            : '🌙 Dark Mode';

        button.setAttribute(
            'aria-label',
            isDark
                ? 'Switch to Light Mode'
                : 'Switch to Dark Mode'
        );

        button.setAttribute(
            'aria-pressed',
            String(isDark)
        );

        button.title = isDark
            ? 'Switch to Light Mode'
            : 'Switch to Dark Mode';
    }

    applyTheme(current);

    button.addEventListener('click', function () {

        const next = current === 'dark'
            ? 'light'
            : 'dark';

        applyTheme(next);

        try {
            localStorage.setItem(key, next);
        } catch (e) {
            // Theme still works for the current page.
        }
    });

    // Keep theme synchronized in other tabs.
    window.addEventListener('storage', function (event) {

        if (event.key !== key) return;

        if (
            event.newValue === 'dark' ||
            event.newValue === 'light'
        ) {
            applyTheme(event.newValue);
        }
    });
})();
</script>