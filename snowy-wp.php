<?php
/**
 * Plugin Name:       Snowy — datos meteorológicos en vivo
 * Plugin URI:        https://github.com/snowy-es/snowy-wp
 * Description:       Shortcodes y bloques con datos en vivo de la red de estaciones de Snowy: extremos del día, rachas de viento, avisos de AEMET y fichas de estación.
 * Version:           2.4.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Snowy
 * Author URI:        https://snowy.es
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       snowy-wp
 * Domain Path:       /languages
 * Update URI:        https://github.com/snowy-es/snowy-wp
 */

if (!defined('ABSPATH')) {
    exit;
}

const SNOWY_WP_VERSION = '2.4.0';
const SNOWY_WP_SITE    = 'https://snowy.es';
const SNOWY_WP_API     = 'https://api.snowy.es';
const SNOWY_WP_CONTACT = 'hola@snowy.es';

define('SNOWY_WP_FILE', __FILE__);
define('SNOWY_WP_DIR', plugin_dir_path(__FILE__));

/**
 * El plugin del que procede este registraba los mismos shortcodes y los mismos
 * bloques. Con los dos activos, el ultimo en cargar pisaba al otro y los
 * bloques avisaban de registro duplicado, asi que aqui se cede el paso: mientras
 * el antiguo siga activo, este no registra nada y lo dice en el admin.
 *
 * Es lo que permite instalar el nuevo y desactivar el viejo en cualquier orden,
 * sin una ventana en la que la web quede a medias.
 */
const SNOWY_WP_LEGACY_PLUGIN = 'snowy-datos-larioja/snowy-datos-larioja.php';

function snowy_wp_legacy_is_active()
{
    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    return is_plugin_active(SNOWY_WP_LEGACY_PLUGIN);
}

function snowy_wp_legacy_notice()
{
    if (!snowy_wp_legacy_is_active() || !current_user_can('activate_plugins')) {
        return;
    }
    ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php esc_html_e('Snowy', 'snowy-wp'); ?></strong> —
            <?php esc_html_e('el plugin antiguo «Snowy — datos de La Rioja» sigue activo y es el que está pintando los widgets. Desactívalo para que tome el relevo esta versión; los shortcodes y los bloques son los mismos y el contenido publicado no hay que tocarlo.', 'snowy-wp'); ?>
        </p>
    </div>
    <?php
}
add_action('admin_notices', 'snowy_wp_legacy_notice');

if (snowy_wp_legacy_is_active()) {
    return;
}

require_once SNOWY_WP_DIR . 'includes/options.php';
require_once SNOWY_WP_DIR . 'includes/api.php';
require_once SNOWY_WP_DIR . 'includes/render.php';
require_once SNOWY_WP_DIR . 'includes/sparkline.php';
require_once SNOWY_WP_DIR . 'includes/shortcodes.php';
require_once SNOWY_WP_DIR . 'includes/environment.php';
require_once SNOWY_WP_DIR . 'includes/rankings.php';
require_once SNOWY_WP_DIR . 'includes/blocks.php';
require_once SNOWY_WP_DIR . 'includes/settings.php';
require_once SNOWY_WP_DIR . 'includes/admin.php';
require_once SNOWY_WP_DIR . 'includes/health.php';
require_once SNOWY_WP_DIR . 'includes/patterns.php';
require_once SNOWY_WP_DIR . 'includes/updates.php';

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
    wp_clear_scheduled_hook(SNOWY_WP_HEALTH_CRON);
}
register_deactivation_hook(__FILE__, 'snowy_wp_deactivate');
