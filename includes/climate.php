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
    $atts = shortcode_atts([
        'loc'      => '',
        'lat'      => '',
        'lon'      => '',
        'nombre'   => '',
        'records'  => 'si',
        'titulo'   => '',
        'nivel'    => '',
    ], (array) $atts, 'snowy_clima');

    snowy_wp_use_styles();

    $point = snowy_wp_forecast_point($atts);
    if (!$point) {
        return '';
    }

    $clima = snowy_wp_climate($point['lat'], $point['lon']);
    if (!$clima || empty($clima['tendencia'])) {
        return '';
    }

    $tag    = snowy_wp_heading_tag($atts['nivel']);
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

    ob_start(); ?>
    <div class="snowy-wp-wrap">
        <div class="snowy-wp-head">
            <<?php echo esc_attr($tag); ?>><?php echo esc_html($titulo); ?></<?php echo esc_attr($tag); ?>>
            <span class="snowy-wp-tag"><?php printf(
                /* translators: 1: primer año de la serie, 2: ultimo año */
                esc_html__('%1$s-%2$s', 'snowy-wp'),
                esc_html($clima['desde']),
                esc_html($clima['hasta'])
            ); ?></span>
        </div>
        <div class="snowy-wp-total">
            <div class="snowy-wp-total-figure">
                <strong><?php echo esc_html(snowy_wp_signed($total, 1, '°C')); ?></strong>
                <span><?php esc_html_e('desde el inicio de la serie', 'snowy-wp'); ?></span>
            </div>
            <div class="snowy-wp-total-body">
                <p class="snowy-wp-total-meta">
                    <?php printf(
                        /* translators: %s: variacion de temperatura por decada */
                        esc_html__('La temperatura media sube %s.', 'snowy-wp'),
                        '<strong>' . esc_html(snowy_wp_signed($decada, 2, '°C')) . '</strong> ' . esc_html__('por década', 'snowy-wp')
                    ); ?>
                    <?php echo esc_html(empty($clima['tendencia']['significant'])
                        ? __('La tendencia todavía no es estadísticamente firme.', 'snowy-wp')
                        : __('La tendencia es estadísticamente firme.', 'snowy-wp')); ?>
                </p>
                <?php if ($salto !== null) : ?>
                    <p class="snowy-wp-total-meta"><?php printf(
                        /* translators: 1: media de la ultima decada, 2: media de las tres primeras decadas */
                        esc_html__('La última década promedia %1$s, frente a %2$s de las tres primeras.', 'snowy-wp'),
                        '<strong>' . esc_html(number_format($clima['ahora'], 1, ',', '.') . ' °C') . '</strong>',
                        esc_html(number_format($clima['antes'], 1, ',', '.') . ' °C')
                    ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($clima['indices']) : ?>
            <div class="snowy-wp-scroll"><table class="snowy-wp-table">
                <thead><tr>
                    <th><?php esc_html_e('Días al año', 'snowy-wp'); ?></th>
                    <th><?php printf(
                        /* translators: 1: primer año, 2: año treinta de la serie */
                        esc_html__('%1$s-%2$s', 'snowy-wp'),
                        esc_html($clima['desde']),
                        esc_html($clima['desde'] + 29)
                    ); ?></th>
                    <th><?php printf(
                        /* translators: 1: hace diez años, 2: ultimo año */
                        esc_html__('%1$s-%2$s', 'snowy-wp'),
                        esc_html($clima['hasta'] - 9),
                        esc_html($clima['hasta'])
                    ); ?></th>
                    <th><?php esc_html_e('Cambio', 'snowy-wp'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach (SNOWY_WP_CLIMATE_INDICES as $key => $label) : ?>
                    <?php
                    $fila = $clima['indices'][$key] ?? null;
                    if (!$fila || $fila['antes'] === null || $fila['ahora'] === null) {
                        continue;
                    }
                    $cambio = $fila['ahora'] - $fila['antes'];
                    ?>
                    <tr>
                        <td><?php echo esc_html($label); ?></td>
                        <td><?php echo esc_html(number_format($fila['antes'], 1, ',', '.')); ?></td>
                        <td class="snowy-wp-val"><?php echo esc_html(number_format($fila['ahora'], 1, ',', '.')); ?></td>
                        <td><span class="snowy-wp-trend <?php echo esc_attr($cambio > 0 ? 'is-up' : ($cambio < 0 ? 'is-down' : '')); ?>">
                            <?php echo esc_html(snowy_wp_signed($cambio, 1, __('días', 'snowy-wp'))); ?>
                        </span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
        <?php if ($atts['records'] !== 'no' && $clima['records']) : ?>
            <dl class="snowy-wp-card-grid snowy-wp-records">
                <?php foreach (SNOWY_WP_CLIMATE_RECORDS as $key => $meta) : ?>
                    <?php
                    $record = $clima['records'][$key] ?? null;
                    if (!$record || !isset($record[0], $record[1])) {
                        continue;
                    }
                    ?>
                    <div>
                        <dt><?php echo esc_html($meta['label']); ?></dt>
                        <dd><?php echo esc_html(number_format((float) $record[1], 1, ',', '.') . ' ' . $meta['unit']); ?>
                            <small><?php echo esc_html(wp_date('j M Y', strtotime($record[0]))); ?></small></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
        <p class="snowy-wp-credit"><?php printf(
            /* translators: %s: enlace a snowy.es */
            esc_html__('Serie de ERA5-Land procesada por %s. Cada celda cubre unos diez kilómetros de terreno.', 'snowy-wp'),
            '<a href="' . esc_url(SNOWY_WP_SITE . '/clima') . '" target="_blank" rel="noopener"><strong>Snowy</strong></a>'
        ); ?></p>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('snowy_clima', 'snowy_wp_shortcode_clima');
