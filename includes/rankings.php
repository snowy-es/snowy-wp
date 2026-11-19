<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Variables que se pueden rankear, con donde vive cada una dentro de la
 * estacion y como se escribe.
 *
 * `orden` es el que tiene sentido leer primero: en temperatura maxima interesa
 * la mas alta y en minima la mas baja.
 */
const SNOWY_WP_METRICS = [
    'temperatura' => [
        'label'    => 'Temperatura actual',
        'campo'    => 'current',
        'unidad'   => '°C',
        'decimales' => 1,
        'orden'    => 'desc',
        'columna'  => 'Ahora',
    ],
    'maxima' => [
        'label'    => 'Temperatura máxima',
        'campo'    => 'today.tmax',
        'unidad'   => '°C',
        'decimales' => 1,
        'orden'    => 'desc',
        'columna'  => 'Máxima',
    ],
    'minima' => [
        'label'    => 'Temperatura mínima',
        'campo'    => 'today.tmin',
        'unidad'   => '°C',
        'decimales' => 1,
        'orden'    => 'asc',
        'columna'  => 'Mínima',
    ],
    'lluvia' => [
        'label'    => 'Precipitación acumulada',
        'campo'    => 'precipitation',
        'unidad'   => 'mm',
        'decimales' => 1,
        'orden'    => 'desc',
        'columna'  => 'Acumulado',
    ],
    'racha' => [
        'label'    => 'Racha máxima',
        'campo'    => 'today.gustMax',
        'unidad'   => 'km/h',
        'decimales' => 0,
        'orden'    => 'desc',
        'columna'  => 'Racha',
    ],
    'humedad' => [
        'label'    => 'Humedad',
        'campo'    => 'humidity',
        'unidad'   => '%',
        'decimales' => 0,
        'orden'    => 'desc',
        'columna'  => 'Humedad',
    ],
    'presion' => [
        'label'    => 'Presión',
        'campo'    => 'pressure',
        'unidad'   => 'hPa',
        'decimales' => 0,
        'orden'    => 'asc',
        'columna'  => 'Presión',
    ],
];

/**
 * Lee un campo que puede venir anidado, como today.tmax.
 */
function snowy_wp_field($station, $ruta)
{
    $valor = $station;
    foreach (explode('.', $ruta) as $parte) {
        if (!is_array($valor) || !array_key_exists($parte, $valor)) {
            return null;
        }
        $valor = $valor[$parte];
    }

    return is_numeric($valor) ? (float) $valor : null;
}

function snowy_wp_format_metric($valor, $metric)
{
    if ($valor === null) {
        return '—';
    }

    return number_format($valor, $metric['decimales'], ',', '.') . ' ' . $metric['unidad'];
}

/**
 * Estaciones ordenadas por una variable, descartando las que no la miden.
 */
function snowy_wp_ranked($metric, $orden, $limite, $solo_positivos = false)
{
    $stations = array_filter(snowy_wp_stations(), static function ($s) use ($metric, $solo_positivos) {
        $v = snowy_wp_field($s, $metric['campo']);

        return $v !== null && (!$solo_positivos || $v > 0);
    });

    if (!$stations) {
        return [];
    }

    usort($stations, static function ($a, $b) use ($metric, $orden) {
        $va = snowy_wp_field($a, $metric['campo']);
        $vb = snowy_wp_field($b, $metric['campo']);

        return $orden === 'asc' ? $va <=> $vb : $vb <=> $va;
    });

    return array_slice($stations, 0, max(1, (int) $limite));
}

function snowy_wp_render_ranking($stations, $metric, $titulo, $etiqueta, $tag, $atts = [])
{
    $atts = array_merge(SNOWY_WP_DISPLAY_DEFAULTS, ['nivel' => $tag], (array) $atts);

    return snowy_wp_render_widget('ranking', [
        'title'    => $titulo,
        'label'    => $etiqueta,
        'metric'   => $metric,
        'stations' => $stations,
    ], $atts);
}

/**
 * [snowy_ranking variable="lluvia"] — la misma tabla para cualquier variable.
 */
function snowy_wp_shortcode_ranking($atts = [])
{
    $atts = snowy_wp_atts([
        'variable' => 'temperatura',
        'limite'   => 8,
        'orden'    => '',
        'titulo'   => '',
        'nivel'    => '',
    ], $atts, 'snowy_ranking');
    snowy_wp_use_styles();


    $metric = SNOWY_WP_METRICS[$atts['variable']] ?? null;
    if (!$metric) {
        return '';
    }

    $orden = in_array($atts['orden'], ['asc', 'desc'], true) ? $atts['orden'] : $metric['orden'];
    $stations = snowy_wp_ranked($metric, $orden, $atts['limite']);
    if (!$stations) {
        return '';
    }

    $titulo = $atts['titulo'] !== ''
        ? $atts['titulo']
        : sprintf('%s en %s', $metric['label'], snowy_wp_region_label());

    return snowy_wp_render_ranking(
        $stations,
        $metric,
        $titulo,
        $orden === 'asc' ? __('de menor a mayor', 'snowy-wp') : __('de mayor a menor', 'snowy-wp'),
        $atts['nivel'],
        $atts
    );
}
add_shortcode('snowy_ranking', 'snowy_wp_shortcode_ranking');

/**
 * [snowy_lluvia] — acumulados del dia.
 *
 * Solo se listan las estaciones que han recogido algo: una tabla de ceros no
 * informa, y decir "no ha llovido" con una frase se entiende mejor.
 */
function snowy_wp_shortcode_lluvia($atts = [])
{
    $atts = snowy_wp_atts(['limite' => 10, 'nivel' => ''], $atts, 'snowy_lluvia');
    snowy_wp_use_styles();

    $stations = snowy_wp_ranked(SNOWY_WP_METRICS['lluvia'], 'desc', $atts['limite'], true);
    $region = snowy_wp_region_label();

    return snowy_wp_render_widget('lluvia', [
        'title'    => $stations
            ? sprintf(
                /* translators: %s: nombre de la region configurada */
                __('Lluvia acumulada hoy en %s', 'snowy-wp'),
                $region
            )
            : sprintf(
                /* translators: %s: nombre de la region configurada */
                __('Lluvia en %s', 'snowy-wp'),
                $region
            ),
        'metric'   => SNOWY_WP_METRICS['lluvia'],
        'stations' => $stations,
    ], $atts);
}
add_shortcode('snowy_lluvia', 'snowy_wp_shortcode_lluvia');

/**
 * [snowy_comparador ids="A,B"] — dos o mas estaciones en columnas.
 */
function snowy_wp_shortcode_comparador($atts = [])
{
    $atts = snowy_wp_atts(['ids' => '', 'titulo' => '', 'nivel' => ''], $atts, 'snowy_comparador');
    $stations = snowy_wp_filter_ids(snowy_wp_stations(), $atts['ids']);
    snowy_wp_use_styles();

    if (count($stations) < 2) {
        return '';
    }

    $rows = [];
    foreach (['temperatura', 'maxima', 'minima', 'humedad', 'racha', 'lluvia'] as $clave) {
        $rows[$clave] = SNOWY_WP_METRICS[$clave];
    }

    return snowy_wp_render_widget('comparador', [
        'title'    => $atts['titulo'] !== '' ? $atts['titulo'] : __('Comparativa de estaciones', 'snowy-wp'),
        'stations' => $stations,
        'rows'     => $rows,
    ], $atts);
}
add_shortcode('snowy_comparador', 'snowy_wp_shortcode_comparador');
