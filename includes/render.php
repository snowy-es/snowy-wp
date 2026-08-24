<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * La red americana no se nombra nunca por su nombre comercial de cara al
 * lector: se identifica por lo que es, una estacion de aficionado.
 */
const SNOWY_WP_NETWORK_LABELS = [
    'AEMET' => 'AEMET',
];

function snowy_wp_network_label($network)
{
    return SNOWY_WP_NETWORK_LABELS[$network] ?? __('estación de aficionado', 'snowy-wp');
}

function snowy_wp_network_badge($network)
{
    return sprintf(
        '<span class="snowy-wp-net%s">%s</span>',
        isset(SNOWY_WP_NETWORK_LABELS[$network]) ? ' is-official' : '',
        esc_html(snowy_wp_network_label($network))
    );
}

/**
 * Enlace a la ficha de la estacion en snowy.es. Es lo que convierte el widget
 * en puerta de entrada: el lector encuentra el dato aqui y sigue alli.
 */
function snowy_wp_station_link($station)
{
    $name = $station['stationName'] ?? '';
    $id   = $station['stationId'] ?? '';

    if (!$id) {
        return esc_html($name);
    }

    return sprintf(
        '<a href="%s/stations/%s" target="_blank" rel="noopener" class="snowy-wp-link">%s</a>',
        SNOWY_WP_SITE,
        rawurlencode($id),
        esc_html($name)
    );
}

function snowy_wp_temp($value)
{
    return $value === null ? '—' : number_format((float) $value, 1, ',', '.') . ' °C';
}

function snowy_wp_speed($value, $decimals = 1)
{
    return $value === null ? '—' : number_format((float) $value, $decimals, ',', '.') . ' km/h';
}

/**
 * Credito de la fuente. No es decorativo: es la condicion de uso de los datos,
 * por eso se pinta siempre aunque la atribucion ampliada este desactivada.
 */
function snowy_wp_credit()
{
    $count  = count(snowy_wp_stations());
    $region = snowy_wp_region_label();
    $link   = sprintf('<a href="%s" target="_blank" rel="noopener"><strong>Snowy</strong></a>', SNOWY_WP_SITE);

    $text = sprintf(
        /* translators: 1: numero de estaciones, 2: nombre de la region, 3: enlace a Snowy */
        _n(
            'Dato de %1$s estación de %2$s de la red de %3$s.',
            'Datos de %1$s estaciones de %2$s de la red de %3$s.',
            $count,
            'snowy-wp'
        ),
        esc_html($count),
        esc_html($region),
        $link
    );

    // La hora que se anuncia es la de la medida, no la de la visita: con la
    // copia de seguridad en juego el dato puede tener horas, y decir "ahora"
    // seria falso.
    $ts  = snowy_wp_data_time(snowy_wp_stations_key());
    $age = $ts ? time() - $ts : 0;

    if ($ts && $age > HOUR_IN_SECONDS) {
        $when = sprintf(
            /* translators: %s: tiempo transcurrido, por ejemplo "2 horas" */
            __('Última actualización hace %s.', 'snowy-wp'),
            esc_html(human_time_diff($ts))
        );
    } else {
        $when = sprintf(
            /* translators: %s: hora de actualizacion */
            __('Actualizado a las %s.', 'snowy-wp'),
            esc_html(wp_date('H:i', $ts ?: null))
        );
    }

    return '<p class="snowy-wp-credit">' . $text . ' ' . $when . '</p>';
}

/**
 * Congela los datos de un widget dentro del post.
 *
 * Un widget en una pagina de datos debe ir siempre en vivo; dentro de un post
 * de actualidad, no: si el aviso caduca o la temperatura cambia, el articulo se
 * queda hablando de algo que ya no se ve.
 *
 * Devuelve ts null cuando no ha llegado a congelar nada. Quien pinta debe
 * mirarlo: anunciar "datos congelados" sobre una lectura en vivo es mentirle al
 * lector, y es justo lo que pasaba fuera del bucle, donde no hay post al que
 * asociar el snapshot.
 */
function snowy_wp_snapshot($key, callable $load)
{
    $post_id = get_the_ID();
    if (!$post_id) {
        return ['data' => $load(), 'ts' => null];
    }

    $meta  = '_snowy_wp_snap_' . md5($key);
    $saved = get_post_meta($post_id, $meta, true);

    if (is_array($saved) && isset($saved['data'])) {
        return $saved;
    }

    $data = $load();

    // Un resultado vacio puede ser la API caida, y congelar una caida deja el
    // widget muerto para siempre. Tampoco se congela mientras se edita: el
    // primer render suele ser una previsualizacion, con el dato de antes de
    // publicar.
    if (!$data || !snowy_wp_can_freeze($post_id)) {
        return ['data' => $data, 'ts' => null];
    }

    $snap = ['data' => $data, 'ts' => time()];
    update_post_meta($post_id, $meta, $snap);

    return $snap;
}

/**
 * Solo se congela sobre un post ya publico y en una peticion de lectura real.
 */
function snowy_wp_can_freeze($post_id)
{
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return false;
    }
    if (is_admin() || is_preview() || is_customize_preview()) {
        return false;
    }

    return get_post_status($post_id) === 'publish';
}

function snowy_wp_snapshot_note($ts)
{
    return sprintf(
        '<p class="snowy-wp-credit snowy-wp-frozen">%s</p>',
        sprintf(
            /* translators: %s: fecha y hora en que se congelaron los datos */
            __('Datos congelados del <strong>%s</strong>, tal y como estaban cuando se publicó este artículo. No se actualizan.', 'snowy-wp'),
            esc_html(wp_date('j \d\e F \d\e Y \a \l\a\s H:i', $ts))
        )
    );
}

/**
 * Filtra por una lista de identificadores separados por comas, para mostrar
 * solo unas estaciones concretas en vez de toda la red.
 */
function snowy_wp_filter_ids($stations, $ids)
{
    $ids = array_filter(array_map('trim', explode(',', (string) $ids)));
    if (!$ids) {
        return $stations;
    }
    $ids = array_map('strtolower', $ids);

    return array_values(array_filter($stations, static function ($s) use ($ids) {
        return in_array(strtolower($s['stationId'] ?? ''), $ids, true);
    }));
}

function snowy_wp_risk_badge($level)
{
    $level = strtolower((string) $level);
    $known = ['amarillo', 'naranja', 'rojo'];

    return sprintf(
        '<span class="snowy-wp-risk %s">%s</span>',
        esc_attr(in_array($level, $known, true) ? 'is-' . $level : ''),
        esc_html(ucfirst($level))
    );
}

function snowy_wp_hazard_window($hazard)
{
    $start = isset($hazard['startTime']) ? strtotime($hazard['startTime']) : null;
    $end   = isset($hazard['endTime']) ? strtotime($hazard['endTime']) : null;
    if (!$start || !$end) {
        return '';
    }

    return sprintf(
        /* translators: 1: hora de inicio, 2: hora de fin */
        __('de %1$s a %2$s', 'snowy-wp'),
        wp_date('H:i', $start),
        wp_date('H:i', $end)
    );
}

function snowy_wp_styles()
{
    wp_enqueue_style('snowy-wp', plugins_url('assets/snowy-wp.css', SNOWY_WP_FILE), [], SNOWY_WP_VERSION);
}
add_action('wp_enqueue_scripts', 'snowy_wp_styles');

/**
 * wp_enqueue_scripts no corre dentro del editor: sin esto la previsualizacion
 * de los bloques saldria sin formato.
 */
add_action('enqueue_block_assets', 'snowy_wp_styles');
