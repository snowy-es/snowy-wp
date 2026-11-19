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
        'ink'          => '#0c183b',
        'ink-2'        => '#3d4662',
        'label'        => '#585f74',
        'mute'         => '#6d7489',
        'line'         => '#d3d7e4',
        'line-soft'    => '#e5e7ef',
        'bg'           => '#fff',
        'soft'         => '#f8f9fc',
        'chip'         => '#ebeef4',
        'accent'       => '#2254d3',
        'official-bg'  => '#e9eefb',
        'official-ink' => '#2254d3',
        'up'           => '#2254d3',
        'down'         => '#c24e0a',
        'wet'          => '#7c3aed',
        'shadow'       => '0 1px 2px hsl(225 40% 30% / .06),0 1px 1px hsl(225 40% 30% / .04)',
        'yellow-bg'    => '#fff8d6',
        'yellow-ink'   => '#775103',
        'yellow'       => '#f0c000',
        'orange-bg'    => '#ffefdb',
        'orange-ink'   => '#914308',
        'orange'       => '#ff8c00',
        'red-bg'       => '#fde7e7',
        'red-ink'      => '#8a1418',
        'red'          => '#d8181b',
        'net-official' => '#e87da6',
        'net-other'    => '#8a8f98',
    ],
    'dark' => [
        'ink'          => '#f1f3f9',
        'ink-2'        => '#c5c7cf',
        'label'        => '#a0a7ba',
        'mute'         => '#999ca6',
        'line'         => '#2d3349',
        'line-soft'    => '#23293c',
        'bg'           => '#141929',
        'soft'         => '#171c2b',
        'chip'         => '#1f2538',
        'accent'       => '#6e97f7',
        'official-bg'  => '#18243f',
        'official-ink' => '#a1bbf7',
        'up'           => '#6e97f7',
        'down'         => '#f58747',
        'wet'          => '#a78bfa',
        'shadow'       => '0 1px 2px hsl(224 40% 3% / .5),0 1px 1px hsl(224 40% 3% / .35)',
        'yellow-bg'    => '#392e0e',
        'yellow-ink'   => '#fbde6a',
        'yellow'       => '#f9ce1f',
        'orange-bg'    => '#3d250f',
        'orange-ink'   => '#ffbf70',
        'orange'       => '#ff9f29',
        'red-bg'       => '#3f1212',
        'red-ink'      => '#f98b8b',
        'red'          => '#f04242',
        'net-official' => '#d55383',
        'net-other'    => '#8a8f98',
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

    if (snowy_wp_font() === 'system') {
        $tokens['--snowy-font'] = snowy_wp_font_stack();
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

    return $decls !== '' ? ':root{' . $decls . '}' : '';
}

/**
 * Las fuentes de Snowy van detras de la hoja porque tienen que ganar a la regla
 * base, que con la fuente del tema deja font-family sin valor. Leen las
 * variables publicas con la familia como reserva y no escriben nada en :root:
 * un tema que defina --snowy-font o --snowy-font-heading sigue mandando.
 */
function snowy_wp_font_css()
{
    $font = snowy_wp_font();
    if ($font === 'system') {
        return '.snowy-wp-wrap.snowy-wp-wrap .snowy-wp-head>*,.snowy-wp-card.snowy-wp-card .snowy-wp-card-title{font-family:inherit}';
    }
    if ($font !== 'snowy') {
        return '';
    }

    $base = plugins_url('assets/fonts/', SNOWY_WP_FILE);
    $css  = '';
    foreach (SNOWY_WP_FONT_FILES as $family => $files) {
        foreach ($files as $weight => $file) {
            $css .= sprintf(
                '@font-face{font-family:"%s";src:url("%s") format("woff2");font-weight:%s;font-style:normal;font-display:swap}',
                $family,
                esc_url($base . $file),
                $weight
            );
        }
    }

    $headings = '.snowy-wp-wrap.snowy-wp-wrap .snowy-wp-head>:not(.snowy-wp-tag),.snowy-wp-card.snowy-wp-card .snowy-wp-card-title,'
        . '.snowy-wp-total-figure strong,.snowy-wp-air-index strong,.snowy-wp-card-grid strong,.snowy-wp-card-grid dd';

    return $css
        . '.snowy-wp-wrap.snowy-wp-wrap,.snowy-wp-card.snowy-wp-card{font-family:var(--snowy-font,' . SNOWY_WP_FONTS['snowy'] . ')}'
        . $headings . '{font-family:var(--snowy-font-heading,' . SNOWY_WP_FONT_HEADING . ');font-optical-sizing:auto}'
        . '.snowy-wp-card-grid dd small{font-family:var(--snowy-font,' . SNOWY_WP_FONTS['snowy'] . ')}';
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

    return (string) apply_filters('snowy_wp_css', snowy_wp_token_css() . snowy_wp_scheme_css() . $css . snowy_wp_font_css());
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
