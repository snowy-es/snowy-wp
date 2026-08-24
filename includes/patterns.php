<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Composiciones listas para insertar.
 *
 * Colocar los widgets uno a uno es la friccion del primer dia: quien acaba de
 * instalar el plugin no sabe todavia cuales combinan bien. Estas tres son las
 * que funcionan en una web meteorologica.
 */
const SNOWY_WP_PATTERNS = [
    'portada' => [
        'title'       => 'Snowy: portada meteorológica',
        'description' => 'Avisos vigentes, extremos del día y rachas de viento, en ese orden.',
        'content'     => "<!-- wp:snowy/avisos /-->\n<!-- wp:snowy/extremos {\"limite\":6} /-->\n<!-- wp:snowy/viento {\"limite\":6} /-->",
    ],
    'pagina-datos' => [
        'title'       => 'Snowy: página de datos en tiempo real',
        'description' => 'Tabla completa de estaciones con los avisos encima.',
        'content'     => "<!-- wp:snowy/avisos /-->\n<!-- wp:snowy/estaciones /-->",
    ],
    'ambiental' => [
        'title'       => 'Snowy: calidad del aire y polen',
        'description' => 'Índice de calidad del aire con los niveles de polen debajo.',
        'content'     => "<!-- wp:snowy/aire /-->\n<!-- wp:snowy/polen /-->",
    ],
];

function snowy_wp_register_patterns()
{
    if (!function_exists('register_block_pattern_category') || !function_exists('register_block_pattern')) {
        return;
    }

    register_block_pattern_category('snowy', ['label' => __('Snowy', 'snowy-wp')]);

    foreach (SNOWY_WP_PATTERNS as $slug => $p) {
        register_block_pattern('snowy/' . $slug, [
            'title'       => $p['title'],
            'description' => $p['description'],
            'categories'  => ['snowy'],
            'content'     => $p['content'],
        ]);
    }
}
add_action('init', 'snowy_wp_register_patterns', 20);
