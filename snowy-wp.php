<?php
/**
 * Plugin Name:       Snowy — datos meteorológicos en vivo
 * Plugin URI:        https://github.com/snowy-es/snowy-wp
 * Description:       Shortcodes y bloques con datos en vivo de la red de estaciones de Snowy: extremos del día, rachas de viento, avisos de AEMET y fichas de estación.
 * Version:           2.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Snowy
 * Author URI:        https://snowy.es
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       snowy-wp
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

const SNOWY_WP_VERSION = '2.1.0';
const SNOWY_WP_SITE    = 'https://snowy.es';
const SNOWY_WP_API     = 'https://api.snowy.es';
const SNOWY_WP_CONTACT = 'hola@snowy.es';

define('SNOWY_WP_FILE', __FILE__);
define('SNOWY_WP_DIR', plugin_dir_path(__FILE__));

require_once SNOWY_WP_DIR . 'includes/options.php';
require_once SNOWY_WP_DIR . 'includes/api.php';
require_once SNOWY_WP_DIR . 'includes/render.php';
require_once SNOWY_WP_DIR . 'includes/shortcodes.php';
require_once SNOWY_WP_DIR . 'includes/environment.php';
require_once SNOWY_WP_DIR . 'includes/blocks.php';
require_once SNOWY_WP_DIR . 'includes/settings.php';
require_once SNOWY_WP_DIR . 'includes/admin.php';

function snowy_wp_load_textdomain()
{
    load_plugin_textdomain('snowy-wp', false, dirname(plugin_basename(SNOWY_WP_FILE)) . '/languages');
}
add_action('init', 'snowy_wp_load_textdomain');

/**
 * Los transients de la API se borran al desactivar: si alguien desactiva el
 * plugin porque los datos venían mal, reactivarlo debe pedirlos de nuevo en vez
 * de devolver la misma caché.
 */
function snowy_wp_deactivate()
{
    snowy_wp_flush_cache();
}
register_deactivation_hook(__FILE__, 'snowy_wp_deactivate');
