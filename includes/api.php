<?php

if (!defined('ABSPATH')) {
    exit;
}

const SNOWY_WP_CACHE_PREFIX = 'snowy_wp_';
const SNOWY_WP_TTL_STATIONS = 600;
const SNOWY_WP_TTL_HAZARDS  = 900;
const SNOWY_WP_TTL_LIVE     = 300;

/**
 * La clave sale en una cabecera desde PHP, nunca desde el navegador del
 * visitante. Si algun dia estos datos se pidieran con JavaScript desde el
 * cliente habria que quitarla: seria publica.
 */
function snowy_wp_auth_headers()
{
    $key = snowy_wp_api_key();

    return $key ? ['x-api-key' => $key] : [];
}

/**
 * Devuelve el cuerpo decodificado o null. Un fallo de la API nunca es una
 * excepcion aqui: las plantillas tienen que degradar sin romper la pagina.
 */
function snowy_wp_get($path)
{
    $response = wp_remote_get(SNOWY_WP_API . $path, [
        'timeout' => 8,
        'headers' => snowy_wp_auth_headers(),
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return null;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    return is_array($data) ? $data : null;
}

const SNOWY_WP_TTL_FALLBACK = DAY_IN_SECONDS;

/**
 * Cache con red de seguridad.
 *
 * Ademas de la copia de trabajo, se guarda la ultima respuesta buena durante un
 * dia. Si la API deja de responder, el widget sigue pintando ese dato con su
 * antiguedad en vez de desaparecer de la pagina: una caida de media hora no
 * deberia dejar un hueco en un articulo publicado.
 */
function snowy_wp_cached($key, $ttl, callable $fetch)
{
    $name     = SNOWY_WP_CACHE_PREFIX . $key;
    $fallback = SNOWY_WP_CACHE_PREFIX . 'last_' . $key;

    $cached = get_transient($name);
    if (is_array($cached)) {
        return $cached;
    }

    $fresh = $fetch();

    if ($fresh === null || $fresh === []) {
        $last = get_transient($fallback);

        return is_array($last) && isset($last['data']) ? $last['data'] : [];
    }

    set_transient($name, $fresh, $ttl);
    set_transient($fallback, ['data' => $fresh, 'ts' => time()], SNOWY_WP_TTL_FALLBACK);

    return $fresh;
}

/**
 * Momento de la ultima respuesta buena, para no anunciar como "actualizado
 * ahora" un dato que viene de la copia de seguridad.
 */
function snowy_wp_data_time($key)
{
    $last = get_transient(SNOWY_WP_CACHE_PREFIX . 'last_' . $key);

    return is_array($last) && isset($last['ts']) ? (int) $last['ts'] : null;
}

/**
 * Estaciones con dato actual, filtradas por la region configurada. Sin region
 * se devuelve la red entera.
 */
function snowy_wp_stations_key()
{
    return 'stations_' . md5(trim((string) snowy_wp_option('region')));
}

function snowy_wp_stations()
{
    $region = trim((string) snowy_wp_option('region'));

    return snowy_wp_cached(snowy_wp_stations_key(), SNOWY_WP_TTL_STATIONS, static function () use ($region) {
        $all = snowy_wp_get('/stations/markers');
        if ($all === null) {
            return null;
        }
        if ($region === '') {
            return array_values($all);
        }

        return array_values(array_filter($all, static function ($s) use ($region) {
            return isset($s['state']) && $s['state'] === $region;
        }));
    });
}

/**
 * Avisos de AEMET agrupados por dia. La fuente devuelve tres dias para toda
 * España y el filtro por comunidad se aplica aqui.
 */
function snowy_wp_hazards()
{
    $region = trim((string) snowy_wp_option('region'));

    return snowy_wp_cached('hazards_' . md5($region), SNOWY_WP_TTL_HAZARDS, static function () use ($region) {
        $all = snowy_wp_get('/hazards');
        if ($all === null) {
            return null;
        }

        $days = [
            'today'            => __('Hoy', 'snowy-wp'),
            'tomorrow'         => __('Mañana', 'snowy-wp'),
            'dayAfterTomorrow' => __('Pasado mañana', 'snowy-wp'),
        ];

        $out = [];
        foreach ($days as $key => $label) {
            $items = array_values(array_filter($all[$key] ?? [], static function ($a) use ($region) {
                return $region === '' || (isset($a['ccaa']) && $a['ccaa'] === $region);
            }));
            if ($items) {
                $out[] = ['label' => $label, 'items' => $items];
            }
        }

        return $out;
    });
}

/**
 * Lectura en vivo de una estacion.
 *
 * /stations/markers es un snapshot por lotes y puede ir con retraso;
 * /stations-metrics/<red>/<id> es la medida real, la misma que usa la ficha de
 * snowy.es.
 */
function snowy_wp_live_station($network, $id)
{
    return snowy_wp_cached('live_' . md5($network . $id), SNOWY_WP_TTL_LIVE, static function () use ($network, $id) {
        return snowy_wp_get(sprintf(
            '/stations-metrics/%s/%s',
            rawurlencode(strtolower($network)),
            rawurlencode($id)
        ));
    });
}

/**
 * Borra los transients del plugin. Los nombres se buscan en la tabla de
 * opciones porque WordPress no ofrece un listado de transients por prefijo.
 */
function snowy_wp_flush_cache()
{
    global $wpdb;

    $like = $wpdb->esc_like('_transient_' . SNOWY_WP_CACHE_PREFIX) . '%';
    $names = $wpdb->get_col($wpdb->prepare(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
        $like
    ));

    foreach ($names as $name) {
        delete_transient(substr($name, strlen('_transient_')));
    }
}
