<?php

if (!defined('ABSPATH')) {
    exit;
}

const SNOWY_WP_TTL_AIR = 1800;

/**
 * Niveles del indice europeo tal y como los nombra la API, con el texto que ve
 * el lector y la clase de color.
 */
const SNOWY_WP_AIR_LEVELS = [
    'good'           => ['label' => 'Buena',             'class' => 'is-good'],
    'fair'           => ['label' => 'Aceptable',         'class' => 'is-fair'],
    'moderate'       => ['label' => 'Moderada',          'class' => 'is-moderate'],
    'poor'           => ['label' => 'Mala',              'class' => 'is-poor'],
    'very_poor'      => ['label' => 'Muy mala',          'class' => 'is-very-poor'],
    'extremely_poor' => ['label' => 'Extremadamente mala', 'class' => 'is-extreme'],
];

/**
 * Contaminantes que se pintan, con su unidad. El indice por si solo no dice
 * gran cosa: lo que se busca al leer esto es cual esta alto.
 */
const SNOWY_WP_POLLUTANTS = [
    'pm25'            => ['label' => 'PM2,5', 'unit' => 'µg/m³'],
    'pm10'            => ['label' => 'PM10',  'unit' => 'µg/m³'],
    'ozone'           => ['label' => 'Ozono', 'unit' => 'µg/m³'],
    'nitrogenDioxide' => ['label' => 'NO₂',   'unit' => 'µg/m³'],
];

const SNOWY_WP_POLLEN = [
    'grassPollen'   => 'Gramíneas',
    'olivePollen'   => 'Olivo',
    'birchPollen'   => 'Abedul',
    'alderPollen'   => 'Aliso',
    'mugwortPollen' => 'Artemisa',
    'ragweedPollen' => 'Ambrosía',
];

/**
 * Umbrales de grano por metro cubico. Son los que usan los servicios de
 * aerobiologia para hablar de riesgo, no una escala inventada.
 */
const SNOWY_WP_POLLEN_LEVELS = [
    ['max' => 1,   'label' => 'Nulo',  'class' => 'is-good'],
    ['max' => 15,  'label' => 'Bajo',  'class' => 'is-fair'],
    ['max' => 50,  'label' => 'Medio', 'class' => 'is-moderate'],
    ['max' => 200, 'label' => 'Alto',  'class' => 'is-poor'],
];

/**
 * Punto de referencia para consultar el aire: el centro de las estaciones de la
 * region configurada. Evita pedir coordenadas a quien instala el plugin, que es
 * justo el dato que nadie sabe de memoria.
 */
function snowy_wp_reference_point()
{
    $stations = snowy_wp_stations();
    if (!$stations) {
        return null;
    }

    $lat = $lon = 0;
    $n = 0;
    foreach ($stations as $s) {
        if (isset($s['latitude'], $s['longitude'])) {
            $lat += (float) $s['latitude'];
            $lon += (float) $s['longitude'];
            $n++;
        }
    }

    return $n ? ['lat' => round($lat / $n, 4), 'lon' => round($lon / $n, 4)] : null;
}

function snowy_wp_air_quality($lat, $lon)
{
    return snowy_wp_cached('air_' . md5("$lat|$lon"), SNOWY_WP_TTL_AIR, static function () use ($lat, $lon) {
        return snowy_wp_get(sprintf('/air-quality?lat=%s&lon=%s', rawurlencode($lat), rawurlencode($lon)));
    });
}

function snowy_wp_air_point($atts)
{
    if ($atts['lat'] !== '' && $atts['lon'] !== '') {
        return ['lat' => (float) $atts['lat'], 'lon' => (float) $atts['lon']];
    }

    return snowy_wp_reference_point();
}

function snowy_wp_pollen_level($value)
{
    foreach (SNOWY_WP_POLLEN_LEVELS as $level) {
        if ($value < $level['max']) {
            return $level;
        }
    }

    return ['label' => __('Muy alto', 'snowy-wp'), 'class' => 'is-very-poor'];
}

/**
 * [snowy_aire] — indice de calidad del aire y contaminantes en el punto de
 * referencia.
 */
function snowy_wp_shortcode_aire($atts = [])
{
    $atts = snowy_wp_atts(['lat' => '', 'lon' => '', 'nivel' => ''], $atts, 'snowy_aire');
    snowy_wp_use_styles();

    $point = snowy_wp_air_point($atts);
    if (!$point) {
        return '';
    }

    $data = snowy_wp_air_quality($point['lat'], $point['lon']);
    $now  = $data['current'] ?? null;
    if (!$now || !isset($now['aqi'])) {
        return '';
    }

    return snowy_wp_render_widget('aire', [
        'title'      => sprintf(
            /* translators: %s: nombre de la region configurada */
            __('Calidad del aire en %s', 'snowy-wp'),
            snowy_wp_region_label()
        ),
        'now'        => $now,
        'level'      => SNOWY_WP_AIR_LEVELS[$now['level'] ?? ''] ?? null,
        'pollutants' => SNOWY_WP_POLLUTANTS,
    ], $atts);
}
add_shortcode('snowy_aire', 'snowy_wp_shortcode_aire');

/**
 * [snowy_polen] — niveles de polen por tipo.
 *
 * Solo se listan los tipos con presencia: en agosto el abedul es cero y una
 * fila de ceros no informa de nada.
 */
function snowy_wp_shortcode_polen($atts = [])
{
    $atts = snowy_wp_atts(['lat' => '', 'lon' => '', 'todos' => 'no', 'nivel' => ''], $atts, 'snowy_polen');
    snowy_wp_use_styles();

    $point = snowy_wp_air_point($atts);
    if (!$point) {
        return '';
    }

    $data = snowy_wp_air_quality($point['lat'], $point['lon']);
    $now  = $data['current'] ?? null;
    if (!$now) {
        return '';
    }

    $all  = $atts['todos'] === 'si';
    $rows = [];
    foreach (SNOWY_WP_POLLEN as $key => $label) {
        $value = $now[$key] ?? null;
        if ($value === null) {
            continue;
        }
        if (!$all && (float) $value < 1) {
            continue;
        }
        $rows[] = ['label' => $label, 'value' => (float) $value, 'level' => snowy_wp_pollen_level((float) $value)];
    }

    usort($rows, static fn($a, $b) => $b['value'] <=> $a['value']);

    return snowy_wp_render_widget('polen', ['rows' => $rows], $atts);
}
add_shortcode('snowy_polen', 'snowy_wp_shortcode_polen');

const SNOWY_WP_TTL_DUST = 3600;

/**
 * Umbrales de polvo en suspension. La API devuelve una serie horaria en
 * microgramos por metro cubico; estos cortes son los que separan un cielo
 * turbio de una calima que se nota al respirar.
 */
const SNOWY_WP_DUST_LEVELS = [
    ['max' => 10,  'label' => 'Sin calima',  'class' => 'is-good'],
    ['max' => 50,  'label' => 'Ligera',      'class' => 'is-fair'],
    ['max' => 150, 'label' => 'Moderada',    'class' => 'is-moderate'],
    ['max' => 350, 'label' => 'Intensa',     'class' => 'is-poor'],
];

function snowy_wp_dust_level($value)
{
    foreach (SNOWY_WP_DUST_LEVELS as $level) {
        if ($value < $level['max']) {
            return $level;
        }
    }

    return ['label' => __('Muy intensa', 'snowy-wp'), 'class' => 'is-very-poor'];
}

function snowy_wp_dust($lat, $lon)
{
    return snowy_wp_cached('dust_' . md5("$lat|$lon"), SNOWY_WP_TTL_DUST, static function () use ($lat, $lon) {
        return snowy_wp_get(sprintf('/air-quality/dust?lat=%s&lon=%s', rawurlencode($lat), rawurlencode($lon)));
    });
}

/**
 * [snowy_calima] — polvo sahariano ahora y en los proximos dias.
 *
 * Se resume por dias en vez de listar las ciento veinte horas que devuelve la
 * fuente: lo que se pregunta es que dia llega y cuanto aprieta.
 */
function snowy_wp_shortcode_calima($atts = [])
{
    $atts = snowy_wp_atts(['lat' => '', 'lon' => '', 'dias' => 4, 'nivel' => ''], $atts, 'snowy_calima');
    snowy_wp_use_styles();

    $point = snowy_wp_air_point($atts);
    if (!$point) {
        return '';
    }

    $data = snowy_wp_dust($point['lat'], $point['lon']);
    $times = $data['times'] ?? [];
    $dust  = $data['dust'] ?? [];
    if (!$times || !$dust || count($times) !== count($dust)) {
        return '';
    }

    // Se agrupa por dia local quedandose con el pico, que es lo que se percibe.
    $dias = [];
    foreach ($times as $i => $t) {
        $valor = $dust[$i] ?? null;
        if ($valor === null) {
            continue;
        }
        $ts = strtotime($t);
        if (!$ts) {
            continue;
        }
        $clave = wp_date('Y-m-d', $ts);
        $dias[$clave] = max($dias[$clave] ?? 0, (float) $valor);
    }

    if (!$dias) {
        return '';
    }

    $dias = array_slice($dias, 0, max(1, min(5, (int) $atts['dias'])), true);
    return snowy_wp_render_widget('calima', [
        'days' => $dias,
        'peak' => max($dias),
    ], $atts);
}
add_shortcode('snowy_calima', 'snowy_wp_shortcode_calima');
