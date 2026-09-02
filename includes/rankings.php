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

function snowy_wp_render_ranking($stations, $metric, $titulo, $etiqueta, $tag)
{
    ob_start(); ?>
    <div class="snowy-wp-wrap">
        <div class="snowy-wp-head">
            <<?php echo esc_attr($tag); ?>><?php echo esc_html($titulo); ?></<?php echo esc_attr($tag); ?>>
            <span class="snowy-wp-tag"><?php echo esc_html($etiqueta); ?></span>
        </div>
        <div class="snowy-wp-scroll">
        <table class="snowy-wp-table">
            <thead><tr>
                <th><?php esc_html_e('Estación', 'snowy-wp'); ?></th>
                <th><?php echo esc_html($metric['columna']); ?></th>
                <th><?php esc_html_e('Red', 'snowy-wp'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($stations as $s) : ?>
                <tr>
                    <td><?php echo snowy_wp_station_link($s); ?></td>
                    <td class="snowy-wp-val"><?php
                        echo esc_html(snowy_wp_format_metric(snowy_wp_field($s, $metric['campo']), $metric));
                    ?></td>
                    <td><?php echo snowy_wp_network_badge($s['network'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php echo snowy_wp_credit(); ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * [snowy_ranking variable="lluvia"] — la misma tabla para cualquier variable.
 */
function snowy_wp_shortcode_ranking($atts = [])
{
    $atts = shortcode_atts([
        'variable' => 'temperatura',
        'limite'   => 8,
        'orden'    => '',
        'titulo'   => '',
        'nivel'    => '',
    ], (array) $atts, 'snowy_ranking');
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
        snowy_wp_heading_tag($atts['nivel'])
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
    $atts = shortcode_atts(['limite' => 10, 'nivel' => ''], (array) $atts, 'snowy_lluvia');
    $metric = SNOWY_WP_METRICS['lluvia'];
    $tag = snowy_wp_heading_tag($atts['nivel']);
    snowy_wp_use_styles();

    $stations = snowy_wp_ranked($metric, 'desc', $atts['limite'], true);
    $region = snowy_wp_region_label();

    if (!$stations) {
        ob_start(); ?>
        <div class="snowy-wp-wrap">
            <div class="snowy-wp-head">
                <<?php echo esc_attr($tag); ?>><?php printf(
                    /* translators: %s: nombre de la region configurada */
                    esc_html__('Lluvia en %s', 'snowy-wp'),
                    esc_html($region)
                ); ?></<?php echo esc_attr($tag); ?>>
                <span class="snowy-wp-tag"><?php esc_html_e('sin lluvia', 'snowy-wp'); ?></span>
            </div>
            <p class="snowy-wp-empty"><?php esc_html_e('Ninguna estación de la red ha recogido precipitación hoy.', 'snowy-wp'); ?></p>
            <?php echo snowy_wp_credit(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    $html = snowy_wp_render_ranking(
        $stations,
        $metric,
        sprintf(
            /* translators: %s: nombre de la region configurada */
            __('Lluvia acumulada hoy en %s', 'snowy-wp'),
            $region
        ),
        __('acumulado del día', 'snowy-wp'),
        $tag
    );

    // La precipitacion no es homogenea entre redes y conviene decirlo: no es lo
    // mismo un pluviometro calibrado que uno de aficionado sin mantenimiento.
    return str_replace(
        '<p class="snowy-wp-credit">',
        '<p class="snowy-wp-credit">' . esc_html__('Los acumulados dependen del pluviómetro de cada estación y no son directamente comparables entre redes. ', 'snowy-wp'),
        $html
    );
}
add_shortcode('snowy_lluvia', 'snowy_wp_shortcode_lluvia');

/**
 * [snowy_comparador ids="A,B"] — dos o mas estaciones en columnas.
 */
function snowy_wp_shortcode_comparador($atts = [])
{
    $atts = shortcode_atts(['ids' => '', 'titulo' => '', 'nivel' => ''], (array) $atts, 'snowy_comparador');
    $stations = snowy_wp_filter_ids(snowy_wp_stations(), $atts['ids']);
    snowy_wp_use_styles();

    if (count($stations) < 2) {
        return '';
    }

    $filas = ['temperatura', 'maxima', 'minima', 'humedad', 'racha', 'lluvia'];
    $tag = snowy_wp_heading_tag($atts['nivel']);

    ob_start(); ?>
    <div class="snowy-wp-wrap">
        <div class="snowy-wp-head">
            <<?php echo esc_attr($tag); ?>><?php
                echo esc_html($atts['titulo'] !== '' ? $atts['titulo'] : __('Comparativa de estaciones', 'snowy-wp'));
            ?></<?php echo esc_attr($tag); ?>>
            <span class="snowy-wp-tag"><?php esc_html_e('en vivo', 'snowy-wp'); ?></span>
        </div>
        <div class="snowy-wp-scroll">
        <table class="snowy-wp-table snowy-wp-comparador">
            <thead><tr>
                <th></th>
                <?php foreach ($stations as $s) : ?>
                    <th><?php echo snowy_wp_station_link($s); ?></th>
                <?php endforeach; ?>
            </tr></thead>
            <tbody>
            <?php foreach ($filas as $clave) : ?>
                <?php $m = SNOWY_WP_METRICS[$clave]; ?>
                <tr>
                    <th scope="row"><?php echo esc_html($m['columna']); ?></th>
                    <?php foreach ($stations as $s) : ?>
                        <td class="snowy-wp-val"><?php
                            echo esc_html(snowy_wp_format_metric(snowy_wp_field($s, $m['campo']), $m));
                        ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php echo snowy_wp_credit(); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('snowy_comparador', 'snowy_wp_shortcode_comparador');
