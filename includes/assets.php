<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Los shortcodes que registra el plugin. Se enumeran para poder saber, antes de
 * pintar nada, si la pagina que se sirve va a necesitar la hoja de estilos.
 */
const SNOWY_WP_SHORTCODES = [
    'snowy_avisos',
    'snowy_estaciones',
    'snowy_estacion',
    'snowy_extremos',
    'snowy_viento',
    'snowy_lluvia',
    'snowy_ranking',
    'snowy_comparador',
    'snowy_aire',
    'snowy_polen',
    'snowy_calima',
    'snowy_embalses',
    'snowy_prevision',
    'snowy_clima',
];

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

    $css = snowy_wp_css();
    if ($css === '') {
        return;
    }

    // El acento se inyecta como variable para que quien instala el plugin pueda
    // vestirlo con su color sin tocar la hoja de estilos.
    $accent = snowy_wp_accent();
    if ($accent) {
        $css = sprintf(':root{--snowy-accent:%s}', $accent) . $css;
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

    $css = snowy_wp_css();
    if ($css === '') {
        return;
    }

    wp_register_style('snowy-wp', false, [], SNOWY_WP_VERSION);
    wp_enqueue_style('snowy-wp');

    $accent = snowy_wp_accent();
    if ($accent) {
        $css = sprintf(':root{--snowy-accent:%s}', $accent) . $css;
    }

    wp_add_inline_style('snowy-wp', $css);
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

    foreach (SNOWY_WP_SHORTCODES as $tag) {
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
