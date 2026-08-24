<?php

if (!defined('ABSPATH')) {
    exit;
}

const SNOWY_WP_REPO = 'snowy-es/snowy-wp';
const SNOWY_WP_RELEASES = 'https://api.github.com/repos/' . SNOWY_WP_REPO . '/releases/latest';
const SNOWY_WP_TTL_RELEASE = 21600;

/**
 * Actualizaciones fuera del directorio de WordPress.
 *
 * Un plugin que se instala desde un zip no recibe avisos de version, asi que
 * cada instalacion se queda congelada donde la dejaron y un fallo corregido no
 * llega a quien lo sufre. Aqui se consulta la ultima publicacion del repositorio
 * y se le entrega a WordPress en el formato que espera, de modo que la
 * actualizacion aparece en Plugins como la de cualquier otro.
 */
function snowy_wp_latest_release()
{
    $cached = get_transient(SNOWY_WP_CACHE_PREFIX . 'release');
    if (is_array($cached)) {
        return $cached;
    }

    $res = wp_remote_get(SNOWY_WP_RELEASES, [
        'timeout' => 8,
        'headers' => [
            'Accept'     => 'application/vnd.github+json',
            'User-Agent' => 'snowy-wp/' . SNOWY_WP_VERSION,
        ],
    ]);

    if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
        // Se cachea el fallo un rato: si el repositorio no responde, no tiene
        // sentido reintentarlo en cada carga del listado de plugins.
        set_transient(SNOWY_WP_CACHE_PREFIX . 'release', [], HOUR_IN_SECONDS);

        return [];
    }

    $data = json_decode(wp_remote_retrieve_body($res), true);
    if (!is_array($data) || empty($data['tag_name'])) {
        return [];
    }

    $zip = '';
    foreach ($data['assets'] ?? [] as $asset) {
        if (($asset['content_type'] ?? '') === 'application/zip' && !empty($asset['browser_download_url'])) {
            $zip = $asset['browser_download_url'];
            break;
        }
    }

    $release = [
        'version' => ltrim((string) $data['tag_name'], 'vV'),
        'zip'     => $zip ?: ($data['zipball_url'] ?? ''),
        'url'     => $data['html_url'] ?? '',
        'notas'   => (string) ($data['body'] ?? ''),
        'fecha'   => substr((string) ($data['published_at'] ?? ''), 0, 10),
    ];

    set_transient(SNOWY_WP_CACHE_PREFIX . 'release', $release, SNOWY_WP_TTL_RELEASE);

    return $release;
}

function snowy_wp_update_available()
{
    $release = snowy_wp_latest_release();
    if (empty($release['version']) || empty($release['zip'])) {
        return null;
    }

    return version_compare($release['version'], SNOWY_WP_VERSION, '>') ? $release : null;
}

function snowy_wp_check_for_update($transient)
{
    if (!is_object($transient)) {
        return $transient;
    }

    $slug = plugin_basename(SNOWY_WP_FILE);
    $release = snowy_wp_update_available();

    if (!$release) {
        return $transient;
    }

    $transient->response[$slug] = (object) [
        'slug'        => dirname($slug),
        'plugin'      => $slug,
        'new_version' => $release['version'],
        'package'     => $release['zip'],
        'url'         => $release['url'],
        'tested'      => get_bloginfo('version'),
    ];

    return $transient;
}
add_filter('site_transient_update_plugins', 'snowy_wp_check_for_update');

/**
 * Ficha de la actualizacion, la que se abre con "Ver detalles de la version".
 */
function snowy_wp_plugin_info($result, $action, $args)
{
    if ($action !== 'plugin_information' || ($args->slug ?? '') !== dirname(plugin_basename(SNOWY_WP_FILE))) {
        return $result;
    }

    $release = snowy_wp_latest_release();
    if (empty($release['version'])) {
        return $result;
    }

    return (object) [
        'name'          => 'Snowy — datos meteorológicos en vivo',
        'slug'          => dirname(plugin_basename(SNOWY_WP_FILE)),
        'version'       => $release['version'],
        'author'        => '<a href="' . esc_url(SNOWY_WP_SITE) . '">Snowy</a>',
        'homepage'      => SNOWY_WP_SITE,
        'download_link' => $release['zip'],
        'last_updated'  => $release['fecha'],
        'sections'      => [
            'description' => wpautop(esc_html__('Shortcodes y bloques con datos en vivo de la red de estaciones de Snowy.', 'snowy-wp')),
            'changelog'   => wpautop(esc_html($release['notas'])),
        ],
    ];
}
add_filter('plugins_api', 'snowy_wp_plugin_info', 10, 3);

/**
 * El zip de una release de GitHub se descomprime en una carpeta con el nombre
 * del tag, no con el del plugin, y WordPress lo instalaria como un plugin
 * distinto dejando dos copias.
 */
function snowy_wp_fix_update_folder($source, $remote_source, $upgrader, $args = [])
{
    global $wp_filesystem;

    $slug = dirname(plugin_basename(SNOWY_WP_FILE));
    if (($args['plugin'] ?? '') !== plugin_basename(SNOWY_WP_FILE)) {
        return $source;
    }

    $destino = trailingslashit($remote_source) . $slug;
    if ($source === trailingslashit($destino)) {
        return $source;
    }

    if ($wp_filesystem && $wp_filesystem->move($source, $destino)) {
        return trailingslashit($destino);
    }

    return $source;
}
add_filter('upgrader_source_selection', 'snowy_wp_fix_update_folder', 10, 4);
