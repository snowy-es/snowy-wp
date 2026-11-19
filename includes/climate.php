<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * El cubo climatico se reconstruye pocas veces al año: la unica razon para
 * volver a pedirlo antes es que caduque la copia.
 */
const SNOWY_WP_TTL_CLIMATE = 604800;

/**
 * La celda entera del reanalisis pesa cientos de kilobytes —lleva climatologia
 * diaria y percentiles de setenta años— y tarda mas que una lectura de
 * estacion.
 */
const SNOWY_WP_CLIMATE_TIMEOUT = 25;

/**
 * Media de extremos: es la comparable con lo que publican las estaciones, que
 * solo dan maxima y minima. La media de 24 h del reanalisis no lo es.
 */
const SNOWY_WP_CLIMATE_VARIABLE = 'temperature_mid';

const SNOWY_WP_CLIMATE_INDICES = [
    'summer_days'     => 'Días de más de 30 °C',
    'hot_days'        => 'Días de más de 35 °C',
    'tropical_nights' => 'Noches tropicales',
    'frost_days'      => 'Días de helada',
];

const SNOWY_WP_CLIMATE_RECORDS = [
    'warmestDay' => ['label' => 'Día más cálido', 'unit' => '°C'],
    'coldestDay' => ['label' => 'Día más frío',   'unit' => '°C'],
    'wettestDay' => ['label' => 'Día más lluvioso', 'unit' => 'mm'],
];

/**
 * Resumen de la celda, no la celda.
 *
 * Se guarda en cache lo que se pinta y no la respuesta completa: cachear la
 * celda entera llenaria la tabla de opciones del sitio con megabytes que nadie
 * lee.
 */
function snowy_wp_climate($lat, $lon)
{
    return snowy_wp_cached('climate_' . md5("$lat|$lon"), SNOWY_WP_TTL_CLIMATE, static function () use ($lat, $lon) {
        $cell = snowy_wp_get(
            sprintf('/climate/cell?lat=%s&lon=%s', rawurlencode($lat), rawurlencode($lon)),
            SNOWY_WP_CLIMATE_TIMEOUT
        );

        if (!$cell) {
            return null;
        }

        return snowy_wp_climate_summary($cell);
    });
}

function snowy_wp_climate_summary($cell)
{
    $variable = isset($cell['annual'][SNOWY_WP_CLIMATE_VARIABLE])
        ? SNOWY_WP_CLIMATE_VARIABLE
        : 'temperature_mean';

    // El año en curso llega hasta donde ERA5-Land haya consolidado, que van dos
    // meses de retraso: contar sus dias de helada o su media anual junto a los
    // de años completos compara medio año con doce meses.
    $annual = snowy_wp_drop_current_year($cell['annual'][$variable] ?? []);
    if (!$annual) {
        return null;
    }

    $years = array_map(static fn($p) => (int) $p[0], $annual);

    $indices = [];
    foreach (SNOWY_WP_CLIMATE_INDICES as $key => $label) {
        $serie = snowy_wp_drop_current_year($cell['indices'][$key]['annual'] ?? []);
        if (!$serie) {
            continue;
        }
        $indices[$key] = [
            'antes'  => snowy_wp_series_mean($serie, min($years), min($years) + 29),
            'ahora'  => snowy_wp_series_mean($serie, max($years) - 9, max($years)),
            'decada' => $cell['indices'][$key]['slopePerDecade'] ?? null,
            'firme'  => !empty($cell['indices'][$key]['significant']),
        ];
    }

    return [
        'variable'  => $variable,
        'desde'     => min($years),
        'hasta'     => max($years),
        'tendencia' => $cell['trends'][$variable] ?? null,
        'antes'     => snowy_wp_series_mean($annual, min($years), min($years) + 29),
        'ahora'     => snowy_wp_series_mean($annual, max($years) - 9, max($years)),
        'indices'   => $indices,
        'records'   => $cell['records'] ?? [],
    ];
}

function snowy_wp_drop_current_year($serie)
{
    $ahora = (int) wp_date('Y');

    return array_values(array_filter((array) $serie, static function ($punto) use ($ahora) {
        return (int) ($punto[0] ?? 0) < $ahora;
    }));
}

/**
 * Media de una serie [año, valor] entre dos años, ambos incluidos.
 */
function snowy_wp_series_mean($serie, $desde, $hasta)
{
    $valores = [];
    foreach ($serie as $punto) {
        $year = (int) ($punto[0] ?? 0);
        if ($year >= $desde && $year <= $hasta && isset($punto[1]) && $punto[1] !== null) {
            $valores[] = (float) $punto[1];
        }
    }

    return $valores ? array_sum($valores) / count($valores) : null;
}

function snowy_wp_signed($value, $decimals, $unit)
{
    $signo = $value > 0 ? '+' : ($value < 0 ? '−' : '');

    return $signo . number_format(abs($value), $decimals, ',', '.') . ' ' . $unit;
}

/**
 * [snowy_clima] — cuanto ha cambiado el clima del punto desde 1950.
 *
 * No es el tiempo de hoy: es la serie larga. Va aparte de los widgets de dato
 * en vivo porque responde a otra pregunta y se lee en otro momento.
 */
function snowy_wp_shortcode_clima($atts = [])
{
    $atts = snowy_wp_atts([
        'loc'      => '',
        'lat'      => '',
        'lon'      => '',
        'nombre'   => '',
        'records'  => 'si',
        'titulo'   => '',
        'nivel'    => '',
    ], $atts, 'snowy_clima');

    snowy_wp_use_styles();

    $point = snowy_wp_forecast_point($atts);
    if (!$point) {
        return '';
    }

    $clima = snowy_wp_climate($point['lat'], $point['lon']);
    if (!$clima || empty($clima['tendencia'])) {
        return '';
    }

    $nombre = $atts['nombre'] !== '' ? $atts['nombre'] : ($point['name'] !== '' ? $point['name'] : snowy_wp_region_label());
    $titulo = $atts['titulo'] !== ''
        ? $atts['titulo']
        : sprintf(
            /* translators: 1: nombre del lugar, 2: primer año de la serie */
            __('Cómo ha cambiado el clima de %1$s desde %2$s', 'snowy-wp'),
            $nombre,
            $clima['desde']
        );

    $decada  = (float) ($clima['tendencia']['slopePerDecade'] ?? 0);
    $decadas = ($clima['hasta'] - $clima['desde']) / 10;
    $total   = $decada * $decadas;
    $salto   = ($clima['ahora'] !== null && $clima['antes'] !== null)
        ? $clima['ahora'] - $clima['antes']
        : null;

    return snowy_wp_render_widget('clima', [
        'title'        => $titulo,
        'name'         => $nombre,
        'point'        => $point,
        'climate'      => $clima,
        'per_decade'   => $decada,
        'change'       => $total,
        'jump'         => $salto,
        'indices'      => SNOWY_WP_CLIMATE_INDICES,
        'records'      => SNOWY_WP_CLIMATE_RECORDS,
        'show_records' => $atts['records'] !== 'no',
    ], $atts);
}
add_shortcode('snowy_clima', 'snowy_wp_shortcode_clima');
