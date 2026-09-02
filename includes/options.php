<?php

if (!defined('ABSPATH')) {
    exit;
}

const SNOWY_WP_OPTION = 'snowy_wp_settings';

/**
 * Region vacia significa "toda la red": el plugin sirve igual a un medio
 * nacional que a uno local, y filtrar es decision de quien lo instala.
 */
const SNOWY_WP_DEFAULTS = [
    'api_key'         => '',
    'region'          => '',
    'stations_url'    => '',
    'attribution'     => true,
    'heading_level'   => 'h3',
    'accent'          => '',
    'css_siempre'     => false,
    'auto_shortcode'  => '',
    'auto_donde'      => 'post',
    'auto_posicion'   => 'despues',
    'auto_categorias' => '',
];

/**
 * Niveles admitidos para el encabezado de los widgets. Se limita la lista
 * porque el valor se interpola en el HTML.
 */
const SNOWY_WP_HEADING_LEVELS = ['h2' => 'h2', 'h3' => 'h3', 'h4' => 'h4', 'p' => 'p'];

function snowy_wp_options()
{
    $saved = get_option(SNOWY_WP_OPTION, []);

    return is_array($saved) ? array_merge(SNOWY_WP_DEFAULTS, $saved) : SNOWY_WP_DEFAULTS;
}

function snowy_wp_option($key)
{
    $options = snowy_wp_options();

    return $options[$key] ?? null;
}

/**
 * La constante de wp-config.php gana a la opcion guardada en base de datos.
 *
 * Es lo que permite que una instalacion que ya definia SNOWY_API_KEY siga
 * funcionando sin entrar en los ajustes, y deja la puerta abierta a gestionar
 * la clave por entorno en vez de por interfaz.
 */
function snowy_wp_api_key()
{
    if (defined('SNOWY_API_KEY') && SNOWY_API_KEY) {
        return (string) SNOWY_API_KEY;
    }

    return (string) snowy_wp_option('api_key');
}

function snowy_wp_api_key_is_constant()
{
    return defined('SNOWY_API_KEY') && SNOWY_API_KEY;
}

/**
 * Nombre de la region para los textos. Sin region configurada se habla de la
 * red entera en vez de dejar un hueco raro en mitad de la frase.
 */
function snowy_wp_region_label()
{
    $region = trim((string) snowy_wp_option('region'));

    return $region !== '' ? $region : __('España', 'snowy-wp');
}

/**
 * Etiqueta del encabezado de un widget.
 *
 * Un widget insertado en mitad de un articulo no sabe a que profundidad esta, y
 * fijar h3 rompia el orden de encabezados de la pagina. Se puede elegir en los
 * ajustes y por atributo en cada insercion.
 */
function snowy_wp_heading_tag($override = '')
{
    $tag = $override !== '' ? $override : (string) snowy_wp_option('heading_level');

    return SNOWY_WP_HEADING_LEVELS[$tag] ?? 'h3';
}

/**
 * Color de acento efectivo. Vacio deja el azul de Snowy.
 */
function snowy_wp_accent()
{
    $accent = trim((string) snowy_wp_option('accent'));

    return preg_match('/^#[0-9a-fA-F]{6}$/', $accent) ? $accent : '';
}
