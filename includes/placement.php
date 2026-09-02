<?php

if (!defined('ABSPATH')) {
    exit;
}

const SNOWY_WP_PLACEMENT_POSITIONS = ['despues' => 'despues', 'antes' => 'antes'];
const SNOWY_WP_PLACEMENT_TARGETS   = ['post' => 'post', 'page' => 'page', 'ambos' => 'ambos'];

function snowy_wp_placement()
{
    $shortcode = trim((string) snowy_wp_option('auto_shortcode'));

    return [
        'shortcode'  => $shortcode,
        'donde'      => SNOWY_WP_PLACEMENT_TARGETS[(string) snowy_wp_option('auto_donde')] ?? 'post',
        'posicion'   => SNOWY_WP_PLACEMENT_POSITIONS[(string) snowy_wp_option('auto_posicion')] ?? 'despues',
        'categorias' => array_filter(array_map('trim', explode(',', (string) snowy_wp_option('auto_categorias')))),
    ];
}

/**
 * Si la regla de colocacion alcanza a esta entrada.
 *
 * La restriccion por categoria es lo que separa "todas las entradas del sitio"
 * de "las de nieve": sin ella, colocar automaticamente en un blog de novecientas
 * entradas mete el mismo widget en las novecientas.
 */
function snowy_wp_placement_matches($post)
{
    $regla = snowy_wp_placement();

    if ($regla['shortcode'] === '' || !$post instanceof WP_Post) {
        return false;
    }

    if ($regla['donde'] !== 'ambos' && $post->post_type !== $regla['donde']) {
        return false;
    }

    if ($regla['categorias'] && !has_category($regla['categorias'], $post)) {
        return false;
    }

    return true;
}

/**
 * Lo que necesita saber el encolado de estilos antes de que se pinte nada.
 */
function snowy_wp_placement_rules_apply()
{
    if (!is_singular()) {
        return false;
    }

    return snowy_wp_placement_matches(get_post());
}

/**
 * Inserta el widget configurado alrededor del contenido.
 *
 * Nunca sobre un contenido que ya trae un widget puesto a mano: la colocacion
 * automatica es para lo que nadie va a editar entrada por entrada, no para
 * duplicar lo que ya hay.
 */
function snowy_wp_placement_content($content)
{
    if (is_admin() || is_feed() || !is_singular() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    // Se mira el contenido crudo del post y no el que llega al filtro: aqui los
    // shortcodes ya se han convertido en HTML y buscarlos no encontraria nada.
    $post = get_post();
    if (!snowy_wp_placement_matches($post) || snowy_wp_content_has_widget($post->post_content)) {
        return $content;
    }

    $regla  = snowy_wp_placement();
    $widget = do_shortcode($regla['shortcode']);

    if (trim($widget) === '' || $widget === $regla['shortcode']) {
        return $content;
    }

    return $regla['posicion'] === 'antes' ? $widget . $content : $content . $widget;
}
add_filter('the_content', 'snowy_wp_placement_content', 20);
