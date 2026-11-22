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
 * El conjunto de embalses de la cuenca, la comunidad o España, sumado por el
 * engine con su nivel de llenado, su nivel de sequia y su proyeccion.
 */
function snowy_wp_reservoir_group($region, $cuenca = '')
{
    $by   = $cuenca !== '' ? 'basin' : ($region !== '' ? 'community' : 'country');
    $name = $cuenca !== '' ? $cuenca : ($region !== '' ? $region : 'España');

    $data = snowy_wp_cached('reservoir_groups_' . $by, SNOWY_WP_TTL_RESERVOIRS, static function () use ($by) {
        return snowy_wp_get('/reservoirs/groups?by=' . rawurlencode($by));
    });

    $wanted = remove_accents(mb_strtolower($name));
    foreach ($data['groups'] ?? [] as $group) {
        if (remove_accents(mb_strtolower($group['name'] ?? '')) === $wanted) {
            return $group;
        }
    }

    return null;
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
    $atts = snowy_wp_atts([
        'region'  => '',
        'cuenca'  => '',
        'limite'  => 0,
        'lluvia'  => 'si',
        'titulo'  => '',
        'nivel'   => '',
    ], $atts, 'snowy_embalses');

    snowy_wp_use_styles();

    $region = trim((string) ($atts['region'] !== '' ? $atts['region'] : snowy_wp_option('region')));
    $cuenca = trim((string) $atts['cuenca']);

    $data = snowy_wp_reservoirs($region, $cuenca);
    $embalses = $data['reservoirs'] ?? [];
    if (!$embalses) {
        return '';
    }

    usort($embalses, static fn($a, $b) => ($b['totalCapacity'] ?? 0) <=> ($a['totalCapacity'] ?? 0));

    $grupo = snowy_wp_reservoir_group($region, $cuenca);
    if (!$grupo) {
        return '';
    }
    $capacidad  = (float) ($grupo['totalCapacity'] ?? 0);
    $volumen    = (float) ($grupo['currentVolume'] ?? 0);
    $semana     = (float) ($grupo['weeklyChange'] ?? 0);
    $porcentaje = (float) ($grupo['fillPercentage'] ?? 0);
    $conjunto   = snowy_wp_fill_level($grupo);

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

    return snowy_wp_render_widget('embalses', [
        'title'          => $titulo,
        'region'         => $region,
        'percentage'     => $porcentaje,
        'overall'        => $conjunto,
        'volume'         => $volumen,
        'capacity'       => $capacidad,
        'count'          => count($embalses),
        'week'           => $semana,
        'basin'          => $cuencas[0] ?? '',
        'basin_forecast' => $prevision,
        'reservoirs'     => $lista,
        'date'           => $fecha,
        'link'           => snowy_wp_reservoirs_link($region),
    ], $atts);
}
add_shortcode('snowy_embalses', 'snowy_wp_shortcode_embalses');

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
