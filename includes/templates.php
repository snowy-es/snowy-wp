<?php

if (!defined('ABSPATH')) {
    exit;
}

const SNOWY_WP_DISPLAY_DEFAULTS = [
    'cabecera' => 'si',
    'variante' => '',
    'esquema'  => '',
    'clase'    => '',
];

const SNOWY_WP_VARIANTS = ['compacta' => 'snowy-wp--compact'];

const SNOWY_WP_SCHEME_CLASSES = [
    'claro'  => 'snowy-wp--light',
    'oscuro' => 'snowy-wp--dark',
    'auto'   => 'snowy-wp--auto',
];

function snowy_wp_atts($defaults, $atts, $shortcode)
{
    return shortcode_atts(array_merge($defaults, SNOWY_WP_DISPLAY_DEFAULTS), (array) $atts, $shortcode);
}

function snowy_wp_show_head($atts)
{
    return ($atts['cabecera'] ?? 'si') !== 'no';
}

function snowy_wp_widget_classes($widget, $atts, $base = ['snowy-wp-wrap'])
{
    $classes = $base;
    $classes[] = 'snowy-wp--' . $widget;

    $variant = SNOWY_WP_VARIANTS[$atts['variante'] ?? ''] ?? '';
    if ($variant !== '') {
        $classes[] = $variant;
    }

    $scheme = SNOWY_WP_SCHEME_CLASSES[$atts['esquema'] ?? ''] ?? '';
    if ($scheme !== '') {
        $classes[] = $scheme;
    }

    foreach (preg_split('/\s+/', (string) ($atts['clase'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $extra) {
        $extra = sanitize_html_class($extra);
        if ($extra !== '') {
            $classes[] = $extra;
        }
    }

    $classes = (array) apply_filters('snowy_wp_widget_classes', $classes, $widget, $atts);

    return implode(' ', array_unique(array_filter(array_map('sanitize_html_class', $classes))));
}

function snowy_wp_locate_template($name)
{
    $name = sanitize_file_name($name);
    $file = $name . '.php';

    $candidates = [
        trailingslashit(get_stylesheet_directory()) . 'snowy-wp/' . $file,
        trailingslashit(get_template_directory()) . 'snowy-wp/' . $file,
        SNOWY_WP_DIR . 'templates/' . $file,
    ];

    $found = '';
    foreach ($candidates as $path) {
        if (is_readable($path)) {
            $found = $path;
            break;
        }
    }

    $found = (string) apply_filters('snowy_wp_locate_template', $found, $name);

    return $found !== '' && is_readable($found) ? $found : '';
}

function snowy_wp_include_template($__snowy_template, $__snowy_vars)
{
    extract($__snowy_vars, EXTR_SKIP);
    include $__snowy_template;
}

function snowy_wp_render_widget($widget, $vars, $atts, $base_classes = ['snowy-wp-wrap'])
{
    $vars = (array) apply_filters('snowy_wp_widget_data', $vars, $widget, $atts);
    $vars = (array) apply_filters('snowy_wp_widget_data_' . $widget, $vars, $atts);

    $template = snowy_wp_locate_template($widget);
    if ($template === '') {
        return '';
    }

    $vars += [
        'atts'      => $atts,
        'widget'    => $widget,
        'tag'       => snowy_wp_heading_tag($atts['nivel'] ?? ''),
        'show_head' => snowy_wp_show_head($atts),
        'classes'   => snowy_wp_widget_classes($widget, $atts, $base_classes),
    ];

    ob_start();
    do_action('snowy_wp_before_widget', $widget, $vars);
    snowy_wp_include_template($template, $vars);
    do_action('snowy_wp_after_widget', $widget, $vars);
    $html = (string) ob_get_clean();

    $html = (string) apply_filters('snowy_wp_widget_html', $html, $widget, $vars, $atts);

    return (string) apply_filters('snowy_wp_widget_html_' . $widget, $html, $vars, $atts);
}
