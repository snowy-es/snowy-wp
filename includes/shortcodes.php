<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * [snowy_extremos] — maximas y minimas del dia.
 *
 * Usa today.tmax / today.tmin, que son extremos ya cerrados y homogeneos entre
 * redes, a diferencia de la precipitacion.
 */
function snowy_wp_shortcode_extremos($atts = [])
{
    $atts = snowy_wp_atts(['limite' => 8, 'nivel' => ''], $atts, 'snowy_extremos');
    snowy_wp_use_styles();

    $stations = snowy_wp_stations();
    if (!$stations) {
        return '';
    }

    $withMax = array_filter($stations, static fn($s) => isset($s['today']['tmax']) && $s['today']['tmax'] !== null);
    $withMin = array_filter($stations, static fn($s) => isset($s['today']['tmin']) && $s['today']['tmin'] !== null);
    if (!$withMax || !$withMin) {
        return '';
    }

    usort($withMax, static fn($a, $b) => $b['today']['tmax'] <=> $a['today']['tmax']);
    usort($withMin, static fn($a, $b) => $a['today']['tmin'] <=> $b['today']['tmin']);

    $limit = max(1, (int) $atts['limite']);
    $hot   = array_slice($withMax, 0, $limit);
    $cold  = array_slice($withMin, 0, $limit);

    return snowy_wp_render_widget('extremos', [
        'columns' => [
            [
                'title'    => __('Las más cálidas de hoy', 'snowy-wp'),
                'label'    => __('máximas', 'snowy-wp'),
                'column'   => __('Máxima', 'snowy-wp'),
                'field'    => 'tmax',
                'stations' => $hot,
            ],
            [
                'title'    => __('Las más frías de hoy', 'snowy-wp'),
                'label'    => __('mínimas', 'snowy-wp'),
                'column'   => __('Mínima', 'snowy-wp'),
                'field'    => 'tmin',
                'stations' => $cold,
            ],
        ],
    ], $atts, ['snowy-wp-extremos', 'snowy-wp-wrap']);
}
add_shortcode('snowy_extremos', 'snowy_wp_shortcode_extremos');

/**
 * [snowy_estaciones] — todas las estaciones con su dato actual.
 */
function snowy_wp_shortcode_estaciones($atts = [])
{
    $atts = snowy_wp_atts(['ids' => '', 'modo' => 'vivo', 'titulo' => '', 'nivel' => ''], $atts, 'snowy_estaciones');
    $frozen = $atts['modo'] === 'snapshot';
    snowy_wp_use_styles();


    if ($frozen) {
        $snap = snowy_wp_snapshot('estaciones|' . $atts['ids'], static function () use ($atts) {
            return snowy_wp_filter_ids(snowy_wp_stations(), $atts['ids']);
        });
        $stations = $snap['data'];
        $ts = $snap['ts'];
        $frozen = $ts !== null;
    } else {
        $stations = snowy_wp_filter_ids(snowy_wp_stations(), $atts['ids']);
        $ts = null;
    }

    if (!$stations) {
        return '';
    }

    usort($stations, static fn($a, $b) => strcmp($a['stationName'] ?? '', $b['stationName'] ?? ''));

    $title = $atts['titulo'] !== ''
        ? $atts['titulo']
        : sprintf(
            /* translators: %s: nombre de la region configurada */
            __('Estaciones de %s', 'snowy-wp'),
            snowy_wp_region_label()
        );

    return snowy_wp_render_widget('estaciones', [
        'title'    => $title,
        'stations' => $stations,
        'frozen'   => $frozen,
        'ts'       => $ts,
    ], $atts);
}
add_shortcode('snowy_estaciones', 'snowy_wp_shortcode_estaciones');

/**
 * [snowy_viento] — rachas maximas del dia.
 */
function snowy_wp_shortcode_viento($atts = [])
{
    $atts = snowy_wp_atts(['limite' => 8, 'nivel' => ''], $atts, 'snowy_viento');
    snowy_wp_use_styles();

    $stations = array_filter(snowy_wp_stations(), static fn($s) => isset($s['today']['gustMax']) && $s['today']['gustMax'] !== null);
    if (!$stations) {
        return '';
    }

    usort($stations, static fn($a, $b) => $b['today']['gustMax'] <=> $a['today']['gustMax']);
    $stations = array_slice($stations, 0, max(1, (int) $atts['limite']));

    return snowy_wp_render_widget('viento', [
        'title'    => __('Rachas más fuertes de hoy', 'snowy-wp'),
        'stations' => $stations,
    ], $atts);
}
add_shortcode('snowy_viento', 'snowy_wp_shortcode_viento');

/**
 * [snowy_estacion id="..."] — ficha de una estacion concreta, para incrustar
 * dentro de un post junto al dato del que se esta hablando.
 */
function snowy_wp_shortcode_estacion($atts = [])
{
    $atts = snowy_wp_atts(['id' => '', 'nombre' => '', 'grafico' => 'si', 'nivel' => ''], $atts, 'snowy_estacion');
    $stations = snowy_wp_stations();
    snowy_wp_use_styles();

    if (!$stations) {
        return '';
    }

    $needle = strtolower(trim($atts['id'] !== '' ? $atts['id'] : $atts['nombre']));
    if ($needle === '') {
        return '';
    }

    $found = null;
    foreach ($stations as $s) {
        if (strtolower($s['stationId'] ?? '') === $needle || strtolower($s['stationName'] ?? '') === $needle) {
            $found = $s;
            break;
        }
    }
    if (!$found) {
        return '';
    }

    return snowy_wp_render_widget('estacion', [
        'station' => $found,
        'points'  => $atts['grafico'] !== 'no'
            ? snowy_wp_evolution($found['network'] ?? '', $found['stationId'] ?? '')
            : null,
    ], $atts, ['snowy-wp-card']);
}
add_shortcode('snowy_estacion', 'snowy_wp_shortcode_estacion');

/**
 * [snowy_avisos] — avisos de AEMET vigentes.
 *
 * Si no hay ninguno lo dice explicitamente: en un portal meteorologico el "sin
 * avisos" es informacion, no un hueco vacio.
 */
function snowy_wp_shortcode_avisos($atts = [])
{
    $atts = snowy_wp_atts(['modo' => 'vivo', 'nivel' => ''], $atts, 'snowy_avisos');
    $frozen = $atts['modo'] === 'snapshot';
    snowy_wp_use_styles();


    if ($frozen) {
        $snap = snowy_wp_snapshot('avisos', 'snowy_wp_hazards');
        $days = $snap['data'];
        $ts = $snap['ts'];
        $frozen = $ts !== null;
    } else {
        $days = snowy_wp_hazards();
        $ts = null;
    }

    return snowy_wp_render_widget('avisos', [
        'region' => snowy_wp_region_label(),
        'days'   => $days,
        'frozen' => $frozen,
        'ts'     => $ts,
    ], $atts);
}
add_shortcode('snowy_avisos', 'snowy_wp_shortcode_avisos');
