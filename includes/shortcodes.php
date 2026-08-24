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
    $atts = shortcode_atts(['limite' => 8], (array) $atts, 'snowy_extremos');
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

    ob_start(); ?>
    <div class="snowy-wp-extremos snowy-wp-wrap">
        <div class="snowy-wp-wrap">
            <div class="snowy-wp-head">
                <h3><?php esc_html_e('Las más cálidas de hoy', 'snowy-wp'); ?></h3>
                <span class="snowy-wp-tag"><?php esc_html_e('máximas', 'snowy-wp'); ?></span>
            </div>
            <div class="snowy-wp-scroll"><table class="snowy-wp-table">
                <thead><tr>
                    <th><?php esc_html_e('Estación', 'snowy-wp'); ?></th>
                    <th><?php esc_html_e('Máxima', 'snowy-wp'); ?></th>
                    <th><?php esc_html_e('Red', 'snowy-wp'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($hot as $s) : ?>
                    <tr>
                        <td><?php echo snowy_wp_station_link($s); ?></td>
                        <td class="snowy-wp-val"><?php echo esc_html(snowy_wp_temp($s['today']['tmax'])); ?></td>
                        <td><?php echo snowy_wp_network_badge($s['network'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
        <div class="snowy-wp-wrap">
            <div class="snowy-wp-head">
                <h3><?php esc_html_e('Las más frías de hoy', 'snowy-wp'); ?></h3>
                <span class="snowy-wp-tag"><?php esc_html_e('mínimas', 'snowy-wp'); ?></span>
            </div>
            <div class="snowy-wp-scroll"><table class="snowy-wp-table">
                <thead><tr>
                    <th><?php esc_html_e('Estación', 'snowy-wp'); ?></th>
                    <th><?php esc_html_e('Mínima', 'snowy-wp'); ?></th>
                    <th><?php esc_html_e('Red', 'snowy-wp'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($cold as $s) : ?>
                    <tr>
                        <td><?php echo snowy_wp_station_link($s); ?></td>
                        <td class="snowy-wp-val"><?php echo esc_html(snowy_wp_temp($s['today']['tmin'])); ?></td>
                        <td><?php echo snowy_wp_network_badge($s['network'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    </div>
    <?php echo snowy_wp_credit(); ?>
    <?php
    return ob_get_clean();
}
add_shortcode('snowy_extremos', 'snowy_wp_shortcode_extremos');

/**
 * [snowy_estaciones] — todas las estaciones con su dato actual.
 */
function snowy_wp_shortcode_estaciones($atts = [])
{
    $atts = shortcode_atts(['ids' => '', 'modo' => 'vivo', 'titulo' => ''], (array) $atts, 'snowy_estaciones');
    $frozen = $atts['modo'] === 'snapshot';

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

    ob_start(); ?>
    <div class="snowy-wp-wrap">
    <div class="snowy-wp-head">
        <h3><?php echo esc_html($title); ?></h3>
        <span class="snowy-wp-tag"><?php echo esc_html($frozen ? __('dato histórico', 'snowy-wp') : __('en vivo', 'snowy-wp')); ?></span>
    </div>
    <div class="snowy-wp-scroll"><table class="snowy-wp-table snowy-wp-estaciones">
        <thead><tr>
            <th><?php esc_html_e('Estación', 'snowy-wp'); ?></th>
            <th><?php esc_html_e('Ahora', 'snowy-wp'); ?></th>
            <th><?php esc_html_e('Máx.', 'snowy-wp'); ?></th>
            <th><?php esc_html_e('Mín.', 'snowy-wp'); ?></th>
            <th><?php esc_html_e('Humedad', 'snowy-wp'); ?></th>
            <th><?php esc_html_e('Red', 'snowy-wp'); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($stations as $s) : ?>
            <tr>
                <td><?php echo snowy_wp_station_link($s); ?></td>
                <td class="snowy-wp-val"><?php echo esc_html(snowy_wp_temp($s['current'] ?? null)); ?></td>
                <td><?php echo esc_html(snowy_wp_temp($s['today']['tmax'] ?? null)); ?></td>
                <td><?php echo esc_html(snowy_wp_temp($s['today']['tmin'] ?? null)); ?></td>
                <td><?php echo isset($s['humidity']) && $s['humidity'] !== null ? esc_html($s['humidity']) . ' %' : '—'; ?></td>
                <td><?php echo snowy_wp_network_badge($s['network'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php echo $frozen ? snowy_wp_snapshot_note($ts) : snowy_wp_credit(); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('snowy_estaciones', 'snowy_wp_shortcode_estaciones');

/**
 * [snowy_viento] — rachas maximas del dia.
 */
function snowy_wp_shortcode_viento($atts = [])
{
    $atts = shortcode_atts(['limite' => 8], (array) $atts, 'snowy_viento');
    $stations = array_filter(snowy_wp_stations(), static fn($s) => isset($s['today']['gustMax']) && $s['today']['gustMax'] !== null);
    if (!$stations) {
        return '';
    }

    usort($stations, static fn($a, $b) => $b['today']['gustMax'] <=> $a['today']['gustMax']);
    $stations = array_slice($stations, 0, max(1, (int) $atts['limite']));

    ob_start(); ?>
    <div class="snowy-wp-wrap">
    <div class="snowy-wp-head">
        <h3><?php esc_html_e('Rachas más fuertes de hoy', 'snowy-wp'); ?></h3>
        <span class="snowy-wp-tag"><?php esc_html_e('viento', 'snowy-wp'); ?></span>
    </div>
    <div class="snowy-wp-scroll"><table class="snowy-wp-table">
        <thead><tr>
            <th><?php esc_html_e('Estación', 'snowy-wp'); ?></th>
            <th><?php esc_html_e('Racha máxima', 'snowy-wp'); ?></th>
            <th><?php esc_html_e('Red', 'snowy-wp'); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($stations as $s) : ?>
            <tr>
                <td><?php echo snowy_wp_station_link($s); ?></td>
                <td class="snowy-wp-val"><?php echo esc_html(snowy_wp_speed($s['today']['gustMax'])); ?></td>
                <td><?php echo snowy_wp_network_badge($s['network'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php echo snowy_wp_credit(); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('snowy_viento', 'snowy_wp_shortcode_viento');

/**
 * [snowy_estacion id="..."] — ficha de una estacion concreta, para incrustar
 * dentro de un post junto al dato del que se esta hablando.
 */
function snowy_wp_shortcode_estacion($atts = [])
{
    $atts = shortcode_atts(['id' => '', 'nombre' => ''], (array) $atts, 'snowy_estacion');
    $stations = snowy_wp_stations();
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

    ob_start(); ?>
    <aside class="snowy-wp-card">
        <p class="snowy-wp-card-eyebrow"><?php esc_html_e('Dato en vivo · red de Snowy', 'snowy-wp'); ?></p>
        <h4 class="snowy-wp-card-title"><?php echo snowy_wp_station_link($found); ?></h4>
        <div class="snowy-wp-card-grid">
            <div><span><?php esc_html_e('Ahora', 'snowy-wp'); ?></span><strong><?php echo esc_html(snowy_wp_temp($found['current'] ?? null)); ?></strong></div>
            <div><span><?php esc_html_e('Máxima hoy', 'snowy-wp'); ?></span><strong><?php echo esc_html(snowy_wp_temp($found['today']['tmax'] ?? null)); ?></strong></div>
            <div><span><?php esc_html_e('Mínima hoy', 'snowy-wp'); ?></span><strong><?php echo esc_html(snowy_wp_temp($found['today']['tmin'] ?? null)); ?></strong></div>
            <div><span><?php esc_html_e('Racha máx.', 'snowy-wp'); ?></span><strong><?php echo esc_html(snowy_wp_speed($found['today']['gustMax'] ?? null)); ?></strong></div>
        </div>
        <p class="snowy-wp-card-foot">
            <?php echo snowy_wp_network_badge($found['network'] ?? ''); ?>
            <?php printf(
                /* translators: %s: enlace a snowy.es */
                esc_html__('· medido por %s', 'snowy-wp'),
                '<a href="' . esc_url(SNOWY_WP_SITE) . '" target="_blank" rel="noopener"><strong>Snowy</strong></a>'
            ); ?>
        </p>
    </aside>
    <?php
    return ob_get_clean();
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
    $atts = shortcode_atts(['modo' => 'vivo'], (array) $atts, 'snowy_avisos');
    $frozen = $atts['modo'] === 'snapshot';

    if ($frozen) {
        $snap = snowy_wp_snapshot('avisos', 'snowy_wp_hazards');
        $days = $snap['data'];
        $ts = $snap['ts'];
        $frozen = $ts !== null;
    } else {
        $days = snowy_wp_hazards();
        $ts = null;
    }

    $region = snowy_wp_region_label();

    ob_start(); ?>
    <div class="snowy-wp-wrap">
        <div class="snowy-wp-head">
            <h3><?php printf(
                /* translators: %s: nombre de la region configurada */
                esc_html__('Avisos de AEMET en %s', 'snowy-wp'),
                esc_html($region)
            ); ?></h3>
            <span class="snowy-wp-tag"><?php
                echo esc_html($frozen
                    ? __('aviso histórico', 'snowy-wp')
                    : ($days ? __('vigentes', 'snowy-wp') : __('sin avisos', 'snowy-wp')));
            ?></span>
        </div>
        <?php if (!$days) : ?>
            <p class="snowy-wp-empty"><?php printf(
                /* translators: %s: nombre de la region configurada */
                esc_html__('No hay avisos activos en %s para los próximos tres días.', 'snowy-wp'),
                '<strong>' . esc_html($region) . '</strong>'
            ); ?></p>
        <?php else : ?>
            <?php foreach ($days as $day) : ?>
                <p class="snowy-wp-day"><?php echo esc_html($day['label']); ?></p>
                <div class="snowy-wp-scroll"><table class="snowy-wp-table">
                    <thead><tr>
                        <th><?php esc_html_e('Nivel', 'snowy-wp'); ?></th>
                        <th><?php esc_html_e('Fenómeno', 'snowy-wp'); ?></th>
                        <th><?php esc_html_e('Zona', 'snowy-wp'); ?></th>
                        <th><?php esc_html_e('Horario', 'snowy-wp'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($day['items'] as $a) : ?>
                        <tr>
                            <td><?php echo snowy_wp_risk_badge($a['riskLevel'] ?? ''); ?></td>
                            <td class="snowy-wp-val"><?php echo esc_html($a['type'] ?? '—'); ?></td>
                            <td><?php echo esc_html($a['zone'] ?? '—'); ?></td>
                            <td><?php echo esc_html(snowy_wp_hazard_window($a)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($frozen) : ?>
            <?php echo snowy_wp_snapshot_note($ts); ?>
        <?php else : ?>
            <p class="snowy-wp-credit"><?php printf(
                /* translators: 1: enlace a Snowy, 2: enlace a la web de avisos de AEMET */
                esc_html__('Avisos oficiales de AEMET, recogidos por %1$s. La fuente que manda siempre es %2$s.', 'snowy-wp'),
                '<a href="' . esc_url(SNOWY_WP_SITE) . '" target="_blank" rel="noopener"><strong>Snowy</strong></a>',
                '<a href="https://www.aemet.es/es/eltiempo/prediccion/avisos" target="_blank" rel="noopener nofollow">AEMET</a>'
            ); ?></p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('snowy_avisos', 'snowy_wp_shortcode_avisos');
