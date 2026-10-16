<?php

/**
 * Inline SVG icons. Stroke-based, sized by $size, coloured by currentColor.
 * No external font or CDN is required.
 */
function icon(string $name, int $size = 20): string
{
    static $paths = [
        'brand'      => '<path d="m12 2 8.5 4.75v9.5L12 21l-8.5-4.75v-9.5Z"/><path d="m3.5 6.75 8.5 4.75 8.5-4.75"/><path d="M12 11.5V21"/>',
        'dashboard'  => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.6"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.6"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.6"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.6"/>',
        'categories' => '<path d="M20.6 12.9 13 20.5a1.7 1.7 0 0 1-2.4 0l-7-7V3.5h10l7 7a1.7 1.7 0 0 1 0 2.4Z"/><circle cx="7.6" cy="7.6" r="1.3"/>',
        'suppliers'  => '<path d="M3 6.5h11v9H3z"/><path d="M14 9.5h3.6L21 12.6v2.9h-7"/><circle cx="7" cy="18" r="1.7"/><circle cx="17.5" cy="18" r="1.7"/>',
        'products'   => '<path d="m12 2.8 8.2 4.6v9.2L12 21.2l-8.2-4.6V7.4Z"/><path d="m3.8 7.4 8.2 4.6 8.2-4.6"/><path d="M12 12v9.2"/>',
        'warehouses' => '<path d="M3 21V9.2L12 4l9 5.2V21"/><path d="M7.5 21v-6.6h9V21"/><path d="M3 21h18"/>',
        'inventory'  => '<path d="m12 3 8.5 4.6L12 12.2 3.5 7.6Z"/><path d="m3.5 12.4 8.5 4.6 8.5-4.6"/>',
        'movements'  => '<path d="M4 8.5h13.5L14 5"/><path d="M20 15.5H6.5L10 19"/>',
        'reports'    => '<path d="M4 20.5V11"/><path d="M10 20.5V4"/><path d="M16 20.5v-6.8"/><path d="M2.5 20.5h19"/>',
        'plus'       => '<path d="M12 5.5v13"/><path d="M5.5 12h13"/>',
        'edit'       => '<path d="M4 20h4.2L19 9.2 14.8 5 4 15.8Z"/><path d="m13.4 6.4 4.2 4.2"/>',
        'trash'      => '<path d="M4 6.8h16"/><path d="M9.5 6.8V4h5v2.8"/><path d="m6.4 6.8 1 13.2h9.2l1-13.2"/>',
        'download'   => '<path d="M12 3.5v10.5"/><path d="m7.8 10.2 4.2 4.2 4.2-4.2"/><path d="M4.5 19.5h15"/>',
        'close'      => '<path d="m6 6 12 12"/><path d="M18 6 6 18"/>',
        'search'     => '<circle cx="11" cy="11" r="6.2"/><path d="m20 20-3.6-3.6"/>',
        'filter'     => '<path d="M3.5 5h17l-6.6 7.7v5.6l-3.8 1.9v-7.5Z"/>',
        'user'       => '<circle cx="12" cy="8" r="3.6"/><path d="M5 20a7 7 0 0 1 14 0"/>',
        'logout'     => '<path d="M14.5 4H18a1.2 1.2 0 0 1 1.2 1.2v13.6A1.2 1.2 0 0 1 18 20h-3.5"/><path d="m9.8 8.2-3.8 3.8 3.8 3.8"/><path d="M6 12h9.2"/>',
        'check'      => '<path d="m5 12.8 4.4 4.4L19 7.6"/>',
        'clock'      => '<circle cx="12" cy="12" r="8.4"/><path d="M12 7.4V12l3 1.8"/>',
        'alert'      => '<path d="M12 4.2 21 19.5H3Z"/><path d="M12 10v4"/><path d="M12 16.6v.2"/>',
        'box'        => '<path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5Z"/><path d="M4 8.5 12 13l8-4.5"/><path d="M12 13v7"/>',
    ];

    $body = $paths[$name] ?? $paths['box'];

    return '<svg class="icon icon--' . e($name) . '" width="' . $size . '" height="' . $size
        . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7'
        . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . $body . '</svg>';
}

/**
 * Renders the two decorative layers every liquid-glass surface needs.
 */
function glass_layers(): string
{
    return '<span class="liquid-glass__backdrop"></span><span class="liquid-glass__edge"></span>';
}
