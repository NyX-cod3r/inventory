<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/icons.php';

/**
 * Page title band. Left: title. Right: optional actions.
 * Keeps every page on the same baseline.
 */
function page_header(string $title, string $actions = ''): void
{
    echo '<div class="page-header">';
    echo '<div class="page-header__titles">';
    echo '<h1 class="page-title">' . e($title) . '</h1>';
    echo '</div>';
    if ($actions !== '') {
        echo '<div class="page-header__actions">' . $actions . '</div>';
    }
    echo '</div>';
}

/**
 * Opens the right-side drawer. The whole form wraps this, so the footer's
 * submit button posts normally. Works with JS off: the scrim and close
 * button are plain links back to the base page.
 */
function drawer_open(string $closeHref, string $title, string $iconName, bool $isOpen): void
{
    echo '<div class="drawer' . ($isOpen ? ' is-open' : '') . '">';
    echo '<a class="drawer__scrim" href="' . e($closeHref) . '" data-drawer-close aria-label="Close panel"></a>';
    echo '<aside class="drawer__panel" role="dialog" aria-modal="true" aria-label="' . e($title) . '">';
    echo '<header class="drawer__head">';
    echo '<h2 class="drawer__title">' . icon($iconName, 18) . e($title) . '</h2>';
    echo '<a class="icon-button" href="' . e($closeHref) . '" data-drawer-close aria-label="Close panel">' . icon('close', 18) . '</a>';
    echo '</header>';
    echo '<div class="drawer__body">';
}

/**
 * Closes the drawer: ends the body, then the sticky action footer.
 */
function drawer_close(string $cancelHref, string $submitLabel, string $submitIcon = 'check'): void
{
    echo '</div>';
    echo '<footer class="drawer__foot">';
    echo '<a class="button button--ghost" href="' . e($cancelHref) . '" data-drawer-close>Cancel</a>';
    echo '<button class="button" type="submit">' . icon($submitIcon, 16) . e($submitLabel) . '</button>';
    echo '</footer>';
    echo '</aside></div>';
}

/**
 * Table body placeholder when a list is empty.
 */
function empty_state(string $message, string $iconName = 'box'): void
{
    echo '<div class="empty-state">' . icon($iconName, 28) . '<p>' . e($message) . '</p></div>';
}

/**
 * Renders a movement-type indicator (coloured dot + label) for IN / OUT / TRANSFER.
 */
function movement_badge(string $type): string
{
    $map = [
        'IN'       => ['in', 'in'],
        'OUT'      => ['out', 'out'],
        'TRANSFER' => ['transfer', 'transfer'],
    ];

    [$class, $label] = $map[$type] ?? ['transfer', strtolower($type)];

    return '<span class="movement-type movement-type--' . $class . '">'
        . '<span class="movement-type__dot" aria-hidden="true"></span>' . e($label) . '</span>';
}

/**
 * Renders a flash or error alert. Auto-dismisses successes after 4s.
 */
function render_alerts(?array $flash, array $errors): void
{
    if ($flash !== null) {
        echo '<div class="alert alert-' . e($flash['type']) . '" data-auto-dismiss>'
            . icon($flash['type'] === 'success' ? 'check' : 'alert', 18)
            . e($flash['message']) . '</div>';
    }
    foreach ($errors as $error) {
        echo '<div class="alert alert-error">' . icon('alert', 18) . e($error) . '</div>';
    }
}
