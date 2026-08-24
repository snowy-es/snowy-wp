<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cada bloque reutiliza el render_callback del shortcode equivalente: la logica
 * vive en un solo sitio. La previsualizacion dentro del editor la resuelve
 * ServerSideRender, que llama a ese mismo PHP por la REST API.
 */
const SNOWY_WP_BLOCK_MODE = ['type' => 'string', 'default' => 'vivo'];

const SNOWY_WP_BLOCKS = [
    'snowy/avisos' => [
        'cb'    => 'snowy_wp_shortcode_avisos',
        'attrs' => ['modo' => SNOWY_WP_BLOCK_MODE],
    ],
    'snowy/estaciones' => [
        'cb'    => 'snowy_wp_shortcode_estaciones',
        'attrs' => [
            'modo'   => SNOWY_WP_BLOCK_MODE,
            'ids'    => ['type' => 'string', 'default' => ''],
            'titulo' => ['type' => 'string', 'default' => ''],
        ],
    ],
    'snowy/extremos' => [
        'cb'    => 'snowy_wp_shortcode_extremos',
        'attrs' => ['limite' => ['type' => 'number', 'default' => 8]],
    ],
    'snowy/viento' => [
        'cb'    => 'snowy_wp_shortcode_viento',
        'attrs' => ['limite' => ['type' => 'number', 'default' => 8]],
    ],
    'snowy/estacion' => [
        'cb'    => 'snowy_wp_shortcode_estacion',
        'attrs' => ['id' => ['type' => 'string', 'default' => '']],
    ],
    'snowy/aire' => [
        'cb'    => 'snowy_wp_shortcode_aire',
        'attrs' => [
            'lat' => ['type' => 'string', 'default' => ''],
            'lon' => ['type' => 'string', 'default' => ''],
        ],
    ],
    'snowy/polen' => [
        'cb'    => 'snowy_wp_shortcode_polen',
        'attrs' => [
            'lat'   => ['type' => 'string', 'default' => ''],
            'lon'   => ['type' => 'string', 'default' => ''],
            'todos' => ['type' => 'string', 'default' => 'no'],
        ],
    ],
];

function snowy_wp_register_blocks()
{
    if (!function_exists('register_block_type')) {
        return;
    }

    wp_register_script(
        'snowy-wp-blocks',
        plugins_url('assets/blocks.js', SNOWY_WP_FILE),
        ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n'],
        SNOWY_WP_VERSION,
        true
    );

    foreach (SNOWY_WP_BLOCKS as $name => $conf) {
        register_block_type($name, [
            'api_version'     => 2,
            'editor_script'   => 'snowy-wp-blocks',
            'attributes'      => $conf['attrs'],
            'render_callback' => static function ($attributes) use ($conf) {
                return call_user_func($conf['cb'], $attributes);
            },
        ]);
    }
}
add_action('init', 'snowy_wp_register_blocks');
