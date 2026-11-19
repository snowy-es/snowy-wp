<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hoja de estilos, minificada y cacheada.
 *
 * Se sirve como estilo en linea y no como fichero enlazado a proposito. Los
 * optimizadores que generan CSS "unico" por pagina analizan el HTML antes de que
 * exista, deciden que estas reglas no se usan y las tiran: medido en La Rioja
 * Meteo, el CSS del plugin desaparecia entero del que acababa sirviendose y los
 * widgets salian sin formato. En linea viaja con la pagina y no hay analisis que
 * lo pueda descartar.
 */
function snowy_wp_css()
{
    $clave = SNOWY_WP_CACHE_PREFIX . 'css_' . SNOWY_WP_VERSION;
    $css = get_transient($clave);
    if (is_string($css) && $css !== '') {
        return $css;
    }

    $css = (string) @file_get_contents(SNOWY_WP_DIR . 'assets/snowy-wp.css');
    if ($css === '') {
        return '';
    }

    $css = preg_replace('#/\*.*?\*/#s', '', $css);
    $css = preg_replace('/\s*\n\s*/', '', $css);
    $css = preg_replace('/\s{2,}/', ' ', $css);
    $css = trim($css);

    set_transient($clave, $css, WEEK_IN_SECONDS);

    return $css;
}

/**
 * Marca que esta pagina lleva al menos un widget.
 *
 * Los temas de bloques renderizan el contenido antes de la cabecera, asi que un
 * widget puede pedir la hoja cuando todavia no se ha impreso nada.
 */
function snowy_wp_use_styles()
{
    $GLOBALS['snowy_wp_styles_pedidos'] = true;
}

function snowy_wp_styles_pedidos()
{
    return !empty($GLOBALS['snowy_wp_styles_pedidos']);
}

/**
 * Imprime la hoja, una sola vez por peticion.
 *
 * Se escribe directamente en vez de pasar por wp_add_inline_style porque en un
 * tema de bloques el handle acaba registrado pero fuera de la cola —el
 * contenido ya se ha renderizado cuando corre wp_enqueue_scripts— y el estilo
 * se perdia o se duplicaba segun el caso.
 */
function snowy_wp_print_styles()
{
    static $impresa = false;

    if ($impresa) {
        return;
    }

    $css = snowy_wp_css_with_accent();
    if ($css === '') {
        return;
    }

    $impresa = true;

    printf('<style id="snowy-wp-css">%s</style>' . "\n", $css);
}

/**
 * En el editor la hoja si viaja por la cola: alli no hay pasada previa del
 * contenido y ServerSideRender pinta dentro de un iframe que solo recibe lo
 * encolado.
 */
function snowy_wp_enqueue_styles()
{
    if (wp_style_is('snowy-wp', 'enqueued')) {
        return;
    }

    $css = snowy_wp_css_with_accent();
    if ($css === '') {
        return;
    }

    wp_register_style('snowy-wp', false, [], SNOWY_WP_VERSION);
    wp_enqueue_style('snowy-wp');
    wp_add_inline_style('snowy-wp', $css);
}

const SNOWY_WP_PALETTES = [
    'light' => [
        'ink'         => '#0f172a',
        'ink-2'       => '#334155',
        'label'       => '#475569',
        'mute'        => '#64748b',
        'line'        => '#e2e8f0',
        'line-soft'   => '#f1f5f9',
        'bg'          => '#fff',
        'soft'        => '#f8fafc',
        'chip'        => '#f1f5f9',
        'accent'      => '#0369a1',
        'official-bg' => '#e0f2fe',
        'official-ink' => '#075985',
        'up'          => '#0369a1',
        'down'        => '#c2410c',
        'wet'         => '#1d4ed8',
        'shadow'      => '0 1px 2px rgba(15,23,42,.04),0 8px 24px -12px rgba(15,23,42,.12)',
    ],
    'dark' => [
        'ink'         => '#e2e8f0',
        'ink-2'       => '#cbd5e1',
        'label'       => '#a3b1c6',
        'mute'        => '#94a3b8',
        'line'        => '#334155',
        'line-soft'   => '#1e293b',
        'bg'          => '#0f172a',
        'soft'        => '#162033',
        'chip'        => '#1e293b',
        'accent'      => '#38bdf8',
        'official-bg' => '#0c4a6e',
        'official-ink' => '#bae6fd',
        'up'          => '#38bdf8',
        'down'        => '#fb923c',
        'wet'         => '#7dd3fc',
        'shadow'      => '0 1px 2px rgba(0,0,0,.3),0 8px 24px -12px rgba(0,0,0,.5)',
    ],
];

function snowy_wp_palette_decls($scheme)
{
    $decls = '';
    foreach (SNOWY_WP_PALETTES[$scheme] as $name => $value) {
        $decls .= '--sn-def-' . $name . ':' . $value . ';';
    }

    return $decls;
}

function snowy_wp_scheme_css()
{
    $light = snowy_wp_palette_decls('light');
    $dark  = snowy_wp_palette_decls('dark');

    $css = sprintf(
        '.snowy-wp--light{%1$s}.snowy-wp--dark{%2$s}.snowy-wp--auto{%1$s}@media(prefers-color-scheme:dark){.snowy-wp--auto{%2$s}}',
        $light,
        $dark
    );

    $scheme = snowy_wp_scheme();
    if ($scheme === 'dark') {
        $css = ':root{' . $dark . '}' . $css;
    } elseif ($scheme === 'auto') {
        $css = '@media(prefers-color-scheme:dark){:root{' . $dark . '}}' . $css;
    }

    return $css;
}

function snowy_wp_token_css()
{
    $tokens = [];

    $accent = snowy_wp_accent();
    if ($accent) {
        $tokens['--snowy-accent'] = $accent;
    }

    $radius = snowy_wp_radius();
    if ($radius !== null) {
        $tokens['--snowy-radius'] = $radius . 'px';
    }

    $font = snowy_wp_font_stack();
    if ($font !== '') {
        $tokens['--snowy-font'] = $font;
    }

    $tokens = (array) apply_filters('snowy_wp_css_tokens', $tokens);

    $decls = '';
    foreach ($tokens as $name => $value) {
        $name  = (string) $name;
        $value = (string) $value;
        if (!preg_match('/^--snowy-[a-z0-9-]+$/', $name) || preg_match('/[;{}<>]/', $value)) {
            continue;
        }
        $decls .= $name . ':' . $value . ';';
    }

    $css = $decls !== '' ? ':root{' . $decls . '}' : '';
    if ($font !== '') {
        $css .= '.snowy-wp-wrap.snowy-wp-wrap .snowy-wp-head>*,.snowy-wp-card.snowy-wp-card .snowy-wp-card-title{font-family:inherit}';
    }

    return $css;
}

function snowy_wp_css_with_accent()
{
    if (!snowy_wp_css_enabled()) {
        return '';
    }

    $css = snowy_wp_css();
    if ($css === '') {
        return '';
    }

    return (string) apply_filters('snowy_wp_css', snowy_wp_token_css() . snowy_wp_scheme_css() . $css);
}

/**
 * Los shortcodes del plugin, preguntados al registro de WordPress en vez de
 * enumerados: una lista escrita a mano se queda vieja el dia que alguien añade
 * un widget y no se acuerda de apuntarlo aqui, y el sintoma seria un widget sin
 * estilos.
 */
function snowy_wp_shortcodes()
{
    return array_filter(array_keys($GLOBALS['shortcode_tags'] ?? []), static function ($tag) {
        return strpos($tag, 'snowy_') === 0;
    });
}

/**
 * Marcas que delatan un widget dentro de un contenido.
 */
function snowy_wp_content_has_widget($content)
{
    if (!is_string($content) || stripos($content, 'snowy') === false) {
        return false;
    }

    foreach (array_keys(SNOWY_WP_BLOCKS) as $block) {
        if (has_block($block, $content)) {
            return true;
        }
    }

    foreach (snowy_wp_shortcodes() as $tag) {
        if (has_shortcode($content, $tag)) {
            return true;
        }
    }

    return false;
}

/**
 * Si la pagina que se esta sirviendo lleva algun widget.
 *
 * Se mira el contenido de la consulta principal en vez de imprimir siempre: el
 * CSS viajaba en las mil paginas del sitio, widget o no. Lo que no se puede
 * saber aqui —una plantilla que llama al shortcode a mano— lo resuelve la
 * impresion en el pie.
 */
function snowy_wp_query_has_widget()
{
    if (snowy_wp_option('css_siempre') || snowy_wp_styles_pedidos()) {
        return true;
    }

    if (snowy_wp_placement_rules_apply()) {
        return true;
    }

    $query = $GLOBALS['wp_query'] ?? null;
    if (!$query instanceof WP_Query) {
        return false;
    }

    foreach ((array) $query->posts as $post) {
        if ($post instanceof WP_Post && snowy_wp_content_has_widget($post->post_content)) {
            return true;
        }
    }

    return false;
}

/**
 * Va despues de wp_print_styles, que corre en la prioridad 8: si se imprimiera
 * antes, la hoja del tema ganaria a igualdad de especificidad.
 */
function snowy_wp_head_styles()
{
    if (is_admin() || !snowy_wp_query_has_widget()) {
        return;
    }

    snowy_wp_print_styles();
}
add_action('wp_head', 'snowy_wp_head_styles', 20);

/**
 * Red para el widget que aparece fuera del contenido: una barra lateral, una
 * plantilla del tema o la colocacion automatica.
 */
function snowy_wp_footer_styles()
{
    if (is_admin() || !snowy_wp_styles_pedidos()) {
        return;
    }

    snowy_wp_print_styles();
}
add_action('wp_footer', 'snowy_wp_footer_styles', 1);

/**
 * wp_enqueue_scripts no corre dentro del editor: sin esto la previsualizacion
 * de los bloques saldria sin formato. En el front no hace falta, que es donde
 * duplicaba el estilo.
 */
function snowy_wp_editor_styles()
{
    if (!is_admin()) {
        return;
    }

    snowy_wp_enqueue_styles();
}
add_action('enqueue_block_assets', 'snowy_wp_editor_styles');
