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
    $atts = shortcode_atts(['lat' => '', 'lon' => ''], (array) $atts, 'snowy_aire');
    $point = snowy_wp_air_point($atts);
    if (!$point) {
        return '';
    }

    $data = snowy_wp_air_quality($point['lat'], $point['lon']);
    $now  = $data['current'] ?? null;
    if (!$now || !isset($now['aqi'])) {
        return '';
    }

    $level = SNOWY_WP_AIR_LEVELS[$now['level'] ?? ''] ?? null;

    ob_start(); ?>
    <div class="snowy-wp-wrap">
        <div class="snowy-wp-head">
            <h3><?php printf(
                /* translators: %s: nombre de la region configurada */
                esc_html__('Calidad del aire en %s', 'snowy-wp'),
                esc_html(snowy_wp_region_label())
            ); ?></h3>
            <span class="snowy-wp-tag"><?php esc_html_e('en vivo', 'snowy-wp'); ?></span>
        </div>
        <div class="snowy-wp-air">
            <div class="snowy-wp-air-index <?php echo esc_attr($level['class'] ?? ''); ?>">
                <strong><?php echo esc_html((int) $now['aqi']); ?></strong>
                <span><?php echo esc_html($level ? $level['label'] : __('Sin clasificar', 'snowy-wp')); ?></span>
            </div>
            <dl class="snowy-wp-card-grid">
                <?php foreach (SNOWY_WP_POLLUTANTS as $key => $p) : ?>
                    <?php if (isset($now[$key]) && $now[$key] !== null) : ?>
                        <div>
                            <span><?php echo esc_html($p['label']); ?></span>
                            <strong><?php echo esc_html(number_format((float) $now[$key], 1, ',', '.')); ?>
                                <small><?php echo esc_html($p['unit']); ?></small></strong>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </dl>
        </div>
        <p class="snowy-wp-credit"><?php printf(
            /* translators: %s: enlace a snowy.es */
            esc_html__('Índice europeo de calidad del aire, servido por %s.', 'snowy-wp'),
            '<a href="' . esc_url(SNOWY_WP_SITE) . '" target="_blank" rel="noopener"><strong>Snowy</strong></a>'
        ); ?></p>
    </div>
    <?php
    return ob_get_clean();
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
    $atts = shortcode_atts(['lat' => '', 'lon' => '', 'todos' => 'no'], (array) $atts, 'snowy_polen');
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

    if (!$rows) {
        ob_start(); ?>
        <div class="snowy-wp-wrap">
            <div class="snowy-wp-head">
                <h3><?php esc_html_e('Polen', 'snowy-wp'); ?></h3>
                <span class="snowy-wp-tag"><?php esc_html_e('sin presencia', 'snowy-wp'); ?></span>
            </div>
            <p class="snowy-wp-empty"><?php esc_html_e('No hay presencia apreciable de ninguno de los pólenes que se vigilan.', 'snowy-wp'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    usort($rows, static fn($a, $b) => $b['value'] <=> $a['value']);

    ob_start(); ?>
    <div class="snowy-wp-wrap">
        <div class="snowy-wp-head">
            <h3><?php esc_html_e('Polen en el aire', 'snowy-wp'); ?></h3>
            <span class="snowy-wp-tag"><?php esc_html_e('hoy', 'snowy-wp'); ?></span>
        </div>
        <div class="snowy-wp-scroll">
        <table class="snowy-wp-table">
            <thead><tr>
                <th><?php esc_html_e('Tipo', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('Nivel', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('Granos/m³', 'snowy-wp'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $r) : ?>
                <tr>
                    <td><?php echo esc_html($r['label']); ?></td>
                    <td><span class="snowy-wp-risk <?php echo esc_attr($r['level']['class']); ?>"><?php echo esc_html($r['level']['label']); ?></span></td>
                    <td class="snowy-wp-val"><?php echo esc_html(number_format($r['value'], 1, ',', '.')); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <p class="snowy-wp-credit"><?php printf(
            /* translators: %s: enlace a snowy.es */
            esc_html__('Concentraciones de polen servidas por %s. Orientativas: no sustituyen al parte aerobiológico oficial.', 'snowy-wp'),
            '<a href="' . esc_url(SNOWY_WP_SITE) . '" target="_blank" rel="noopener"><strong>Snowy</strong></a>'
        ); ?></p>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('snowy_polen', 'snowy_wp_shortcode_polen');
