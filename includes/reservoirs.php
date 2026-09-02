<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * El boletin hidrologico del MITECO se publica una vez por semana, asi que
 * refrescar mas a menudo solo gasta cuota para traer el mismo numero.
 */
const SNOWY_WP_TTL_RESERVOIRS = 21600;

const SNOWY_WP_FILL_LEVELS = [
    'critical' => ['label' => 'Crítico',  'class' => 'is-very-poor'],
    'low'      => ['label' => 'Bajo',     'class' => 'is-poor'],
    'moderate' => ['label' => 'Moderado', 'class' => 'is-moderate'],
    'good'     => ['label' => 'Bueno',    'class' => 'is-fair'],
    'full'     => ['label' => 'Lleno',    'class' => 'is-good'],
];

function snowy_wp_reservoirs($region, $cuenca = '')
{
    $query = [];
    if ($region !== '') {
        $query['comunidadAutonoma'] = $region;
    }
    if ($cuenca !== '') {
        $query['riverBasin'] = $cuenca;
    }

    return snowy_wp_cached('reservoirs_' . md5($region . '|' . $cuenca), SNOWY_WP_TTL_RESERVOIRS, static function () use ($query) {
        $path = '/reservoirs' . ($query ? '?' . http_build_query($query) : '');

        return snowy_wp_get($path);
    });
}

/**
 * Lluvia prevista sobre la cuenca, que es lo que decide si el embalse sube.
 */
function snowy_wp_basin_forecast($basin)
{
    if ($basin === '') {
        return null;
    }

    $all = snowy_wp_cached('basin_forecasts', SNOWY_WP_TTL_RESERVOIRS, static function () {
        return snowy_wp_get('/reservoirs/basin-forecasts');
    });

    return $all['forecasts'][$basin] ?? null;
}

function snowy_wp_volume($value)
{
    return $value === null ? '—' : number_format((float) $value, 1, ',', '.') . ' hm³';
}

function snowy_wp_fill_level($reservoir)
{
    $level = $reservoir['fillLevel'] ?? '';

    return SNOWY_WP_FILL_LEVELS[$level] ?? ['label' => __('Sin clasificar', 'snowy-wp'), 'class' => ''];
}

/**
 * Barra de llenado. Acompana al numero en vez de sustituirlo: el porcentaje
 * exacto es el dato, la barra es lo que se lee de un vistazo.
 */
function snowy_wp_fill_bar($percentage, $class)
{
    return sprintf(
        '<span class="snowy-wp-bar"><span class="snowy-wp-bar-fill %s" style="width:%s%%"></span></span>',
        esc_attr($class),
        esc_attr(number_format(max(0, min(100, (float) $percentage)), 1, '.', ''))
    );
}

function snowy_wp_reservoir_change($reservoir)
{
    $change = $reservoir['weeklyChange'] ?? null;
    if ($change === null) {
        return '—';
    }

    $change = (float) $change;
    $signo  = $change > 0 ? '+' : ($change < 0 ? '−' : '');
    $clase  = $change > 0 ? 'is-up' : ($change < 0 ? 'is-down' : '');

    return sprintf(
        '<span class="snowy-wp-trend %s">%s%s</span>',
        esc_attr($clase),
        esc_html($signo),
        esc_html(number_format(abs($change), 1, ',', '.') . ' hm³')
    );
}

/**
 * [snowy_embalses] — estado de los embalses de la region.
 *
 * El total de la region va primero porque es la pregunta real —cuanta agua hay
 * guardada— y el desglose por embalse debajo para quien busca el suyo.
 */
function snowy_wp_shortcode_embalses($atts = [])
{
    $atts = shortcode_atts([
        'region'  => '',
        'cuenca'  => '',
        'limite'  => 0,
        'lluvia'  => 'si',
        'titulo'  => '',
        'nivel'   => '',
    ], (array) $atts, 'snowy_embalses');

    snowy_wp_use_styles();

    $region = trim((string) ($atts['region'] !== '' ? $atts['region'] : snowy_wp_option('region')));
    $cuenca = trim((string) $atts['cuenca']);
    $tag    = snowy_wp_heading_tag($atts['nivel']);

    $data = snowy_wp_reservoirs($region, $cuenca);
    $embalses = $data['reservoirs'] ?? [];
    if (!$embalses) {
        return '';
    }

    usort($embalses, static fn($a, $b) => ($b['totalCapacity'] ?? 0) <=> ($a['totalCapacity'] ?? 0));

    $capacidad = 0.0;
    $volumen   = 0.0;
    $semana    = 0.0;
    foreach ($embalses as $e) {
        $capacidad += (float) ($e['totalCapacity'] ?? 0);
        $volumen   += (float) ($e['currentVolume'] ?? 0);
        $semana    += (float) ($e['weeklyChange'] ?? 0);
    }
    $porcentaje = $capacidad > 0 ? ($volumen / $capacidad) * 100 : 0;
    $conjunto   = snowy_wp_fill_level(['fillLevel' => snowy_wp_fill_level_for($porcentaje)]);

    $limite = (int) $atts['limite'];
    $lista  = $limite > 0 ? array_slice($embalses, 0, $limite) : $embalses;

    $cuencas = array_values(array_unique(array_filter(array_map(static function ($e) {
        return $e['riverBasin'] ?? '';
    }, $embalses))));
    $prevision = $atts['lluvia'] !== 'no' && count($cuencas) === 1
        ? snowy_wp_basin_forecast($cuencas[0])
        : null;

    $fecha = $data['lastUpdate'] ?? ($embalses[0]['date'] ?? '');
    $titulo = $atts['titulo'] !== ''
        ? $atts['titulo']
        : sprintf(
            /* translators: %s: nombre de la region configurada */
            __('Embalses de %s', 'snowy-wp'),
            $region !== '' ? $region : __('España', 'snowy-wp')
        );

    ob_start(); ?>
    <div class="snowy-wp-wrap">
        <div class="snowy-wp-head">
            <<?php echo esc_attr($tag); ?>><?php echo esc_html($titulo); ?></<?php echo esc_attr($tag); ?>>
            <span class="snowy-wp-tag"><?php esc_html_e('agua embalsada', 'snowy-wp'); ?></span>
        </div>
        <div class="snowy-wp-total">
            <div class="snowy-wp-total-figure">
                <strong><?php echo esc_html(number_format($porcentaje, 1, ',', '.')); ?> %</strong>
                <span><?php echo esc_html($conjunto['label']); ?></span>
            </div>
            <div class="snowy-wp-total-body">
                <?php echo snowy_wp_fill_bar($porcentaje, $conjunto['class']); ?>
                <p class="snowy-wp-total-meta">
                    <?php printf(
                        /* translators: 1: volumen embalsado, 2: capacidad total, 3: numero de embalses */
                        esc_html__('%1$s de %2$s en %3$s embalses.', 'snowy-wp'),
                        '<strong>' . esc_html(snowy_wp_volume($volumen)) . '</strong>',
                        esc_html(snowy_wp_volume($capacidad)),
                        esc_html(count($embalses))
                    ); ?>
                    <?php if ($semana != 0.0) : ?>
                        <?php printf(
                            /* translators: %s: variacion de volumen en la ultima semana */
                            esc_html__('Esta semana %s.', 'snowy-wp'),
                            $semana > 0
                                ? '<span class="snowy-wp-trend is-up">' . esc_html(sprintf(__('ha ganado %s', 'snowy-wp'), snowy_wp_volume(abs($semana)))) . '</span>'
                                : '<span class="snowy-wp-trend is-down">' . esc_html(sprintf(__('ha perdido %s', 'snowy-wp'), snowy_wp_volume(abs($semana)))) . '</span>'
                        ); ?>
                    <?php endif; ?>
                </p>
                <?php if ($prevision && isset($prevision['totalPrecipitation7d'])) : ?>
                    <p class="snowy-wp-total-meta"><?php printf(
                        /* translators: 1: nombre de la cuenca, 2: precipitacion prevista */
                        esc_html__('Lluvia prevista en la cuenca del %1$s: %2$s en siete días.', 'snowy-wp'),
                        esc_html($cuencas[0]),
                        '<strong>' . esc_html(number_format((float) $prevision['totalPrecipitation7d'], 1, ',', '.') . ' mm') . '</strong>'
                    ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="snowy-wp-scroll"><table class="snowy-wp-table">
            <thead><tr>
                <th><?php esc_html_e('Embalse', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('Llenado', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('Embalsado', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('Capacidad', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('Esta semana', 'snowy-wp'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($lista as $e) : ?>
                <?php $nivel = snowy_wp_fill_level($e); ?>
                <tr>
                    <td><?php echo esc_html($e['name'] ?? '—'); ?></td>
                    <td class="snowy-wp-val">
                        <?php echo esc_html(number_format((float) ($e['fillPercentage'] ?? 0), 1, ',', '.')); ?> %
                        <?php echo snowy_wp_fill_bar($e['fillPercentage'] ?? 0, $nivel['class']); ?>
                    </td>
                    <td><?php echo esc_html(snowy_wp_volume($e['currentVolume'] ?? null)); ?></td>
                    <td><?php echo esc_html(snowy_wp_volume($e['totalCapacity'] ?? null)); ?></td>
                    <td><?php echo snowy_wp_reservoir_change($e); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <p class="snowy-wp-credit"><?php
            printf(
                /* translators: %s: enlace a la pagina de embalses en snowy.es */
                esc_html__('Datos del boletín hidrológico del MITECO, servidos por %s.', 'snowy-wp'),
                '<a href="' . esc_url(snowy_wp_reservoirs_link($region)) . '" target="_blank" rel="noopener"><strong>Snowy</strong></a>'
            );
            if ($fecha) {
                echo ' ';
                printf(
                    /* translators: %s: fecha del ultimo boletin */
                    esc_html__('Última lectura del %s.', 'snowy-wp'),
                    esc_html(wp_date('j \d\e F \d\e Y', strtotime($fecha)))
                );
            }
        ?></p>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('snowy_embalses', 'snowy_wp_shortcode_embalses');

/**
 * Los mismos cortes que aplica la API a cada embalse, para poder clasificar
 * tambien el conjunto de la region.
 */
function snowy_wp_fill_level_for($percentage)
{
    if ($percentage < 25) {
        return 'critical';
    }
    if ($percentage < 40) {
        return 'low';
    }
    if ($percentage < 60) {
        return 'moderate';
    }
    if ($percentage < 80) {
        return 'good';
    }

    return 'full';
}

/**
 * Enlace a la pagina de la comunidad en snowy.es. Se enlaza el conjunto y no
 * cada embalse porque alli no hay ficha por embalse: todas las filas irian al
 * mismo sitio.
 */
function snowy_wp_reservoirs_link($region)
{
    if ($region === '') {
        return SNOWY_WP_SITE . '/embalses';
    }

    return SNOWY_WP_SITE . '/embalses/' . sanitize_title(remove_accents($region));
}
