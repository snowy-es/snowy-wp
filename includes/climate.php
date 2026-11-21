<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * El cubo climatico se reconstruye pocas veces al año: la unica razon para
 * volver a pedirlo antes es que caduque la copia.
 */
const SNOWY_WP_TTL_CLIMATE = 604800;

const SNOWY_WP_TTL_CLIMATE_CURRENT = 43200;

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

const SNOWY_WP_CLIMATE_VARIABLES = [
    'extremos' => 'temperature_mid',
    'media'    => 'temperature_mean',
    'maxima'   => 'temperature_max',
    'minima'   => 'temperature_min',
];

const SNOWY_WP_CLIMATE_VARIABLE_LABELS = [
    'temperature_mid'  => 'temperatura media',
    'temperature_mean' => 'temperatura media',
    'temperature_max'  => 'temperatura máxima',
    'temperature_min'  => 'temperatura mínima',
];

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

const SNOWY_WP_REGION_PATTERN = '/^[A-Z]{2}-[A-Z0-9]{1,3}$/';

function snowy_wp_climate_place($atts)
{
    $region = strtoupper(trim((string) ($atts['region'] ?? '')));
    if ($region !== '') {
        if (!preg_match(SNOWY_WP_REGION_PATTERN, $region)) {
            return null;
        }

        return [
            'kind'  => 'region',
            'query' => ['region' => $region],
            'key'   => 'r:' . $region,
            'name'  => (string) ($atts['nombre'] ?? ''),
        ];
    }

    $loc = trim((string) ($atts['ubicacion'] ?? ''));
    if ($loc === '') {
        $loc = trim((string) ($atts['loc'] ?? ''));
    }

    $point = snowy_wp_forecast_point([
        'loc'    => $loc,
        'lat'    => (string) ($atts['lat'] ?? ''),
        'lon'    => (string) ($atts['lon'] ?? ''),
        'nombre' => (string) ($atts['nombre'] ?? ''),
    ]);
    if (!$point) {
        return null;
    }

    $lat = round((float) $point['lat'], 3);
    $lon = round((float) $point['lon'], 3);
    $name = (string) ($atts['nombre'] ?? '') !== '' ? (string) $atts['nombre'] : (string) $point['name'];

    return [
        'kind'  => 'point',
        'query' => ['lat' => $lat, 'lon' => $lon],
        'key'   => 'p:' . $lat . ',' . $lon,
        'name'  => $name,
        'lat'   => $lat,
        'lon'   => $lon,
    ];
}

function snowy_wp_climate_variable($value)
{
    $value = strtolower(trim((string) $value));
    if (isset(SNOWY_WP_CLIMATE_VARIABLES[$value])) {
        return SNOWY_WP_CLIMATE_VARIABLES[$value];
    }

    return in_array($value, SNOWY_WP_CLIMATE_VARIABLES, true) ? $value : SNOWY_WP_CLIMATE_VARIABLE;
}

function snowy_wp_climate_fetch($path, $place, $params, $ttl, callable $shape)
{
    if (!is_array($place) || empty($place['query'])) {
        return [];
    }

    $query = array_merge($place['query'], array_filter($params, static function ($value) {
        return $value !== null && $value !== '';
    }));
    $key = 'clima_' . md5($path . '|' . http_build_query($query));

    $data = snowy_wp_cached($key, $ttl, static function () use ($path, $query, $shape) {
        $raw = snowy_wp_get($path . '?' . http_build_query($query), SNOWY_WP_CLIMATE_TIMEOUT);

        return is_array($raw) ? $shape($raw) : null;
    });

    return (array) apply_filters('snowy_wp_climate_data', is_array($data) ? $data : [], $path, $place, $params);
}

function snowy_wp_climate_target($raw)
{
    $target = is_array($raw['target'] ?? null) ? $raw['target'] : [];

    return [
        'type'      => (string) ($target['type'] ?? ''),
        'code'      => (string) ($target['code'] ?? ''),
        'name'      => (string) ($target['name'] ?? ''),
        'cellCount' => isset($target['cellCount']) ? (int) $target['cellCount'] : null,
        'areaKm2'   => isset($target['areaKm2']) ? (float) $target['areaKm2'] : null,
        'lat'       => isset($target['lat']) ? (float) $target['lat'] : null,
        'lon'       => isset($target['lon']) ? (float) $target['lon'] : null,
    ];
}

function snowy_wp_climate_summary($place, $variable = SNOWY_WP_CLIMATE_VARIABLE)
{
    return snowy_wp_climate_fetch('/climate/summary', $place, ['variable' => $variable], SNOWY_WP_TTL_CLIMATE, static function ($raw) {
        if (empty($raw['trend']) || empty($raw['period'])) {
            return null;
        }

        $decades = [];
        foreach ((array) ($raw['summerDecades']['decades'] ?? []) as $decade) {
            if (!is_array($decade) || !isset($decade['decade'], $decade['warmShare'])) {
                continue;
            }
            $decades[] = [
                'decade'    => (int) $decade['decade'],
                'days'      => (int) ($decade['days'] ?? 0),
                'warmShare' => (float) $decade['warmShare'],
            ];
        }

        return [
            'target'        => snowy_wp_climate_target($raw),
            'variable'      => (string) ($raw['variable'] ?? ''),
            'period'        => (array) $raw['period'],
            'trend'         => (array) $raw['trend'],
            'change'        => (array) ($raw['change'] ?? []),
            'beforeNow'     => (array) ($raw['beforeNow'] ?? []),
            'lastYear'      => (array) ($raw['lastYear'] ?? []),
            'warmShare'     => (array) ($raw['warmShare'] ?? []),
            'summerDecades' => [
                'threshold' => (int) ($raw['warmShare']['threshold'] ?? 80),
                'months'    => (array) ($raw['summerDecades']['months'] ?? [6, 7, 8]),
                'decades'   => $decades,
            ],
        ];
    });
}

function snowy_wp_climate_profile($place, $include = 'annual,trends')
{
    return snowy_wp_climate_fetch('/climate/profile', $place, ['include' => $include], SNOWY_WP_TTL_CLIMATE, static function ($raw) {
        if (empty($raw['annual']) && empty($raw['trends']) && empty($raw['records'])) {
            return null;
        }

        return [
            'target'  => snowy_wp_climate_target($raw),
            'annual'  => (array) ($raw['annual'] ?? []),
            'trends'  => (array) ($raw['trends'] ?? []),
            'records' => (array) ($raw['records'] ?? []),
            'indices' => (array) ($raw['indices'] ?? []),
        ];
    });
}

function snowy_wp_climate_year($place, $year)
{
    $year = (int) $year;
    $ttl  = $year >= (int) wp_date('Y') ? SNOWY_WP_TTL_CLIMATE_CURRENT : SNOWY_WP_TTL_CLIMATE;

    return snowy_wp_climate_fetch('/climate/year', $place, ['year' => $year], $ttl, static function ($raw) {
        if (empty($raw['year']) || empty($raw['daily']['days'])) {
            return null;
        }

        $days = [];
        foreach ((array) $raw['daily']['days'] as $day) {
            if (!is_array($day) || empty($day['date'])) {
                continue;
            }
            $days[] = [
                (string) $day['date'],
                isset($day['percentile']) && $day['percentile'] !== null ? (int) $day['percentile'] : null,
                !empty($day['provisional']),
            ];
        }

        $months = [];
        foreach ((array) ($raw['monthly']['months'] ?? []) as $month) {
            if (!is_array($month) || empty($month['month'])) {
                continue;
            }
            $months[(int) $month['month']] = [
                'value'   => isset($month['value']) ? (float) $month['value'] : null,
                'normal'  => isset($month['normal']) ? (float) $month['normal'] : null,
                'anomaly' => isset($month['anomaly']) && $month['anomaly'] !== null ? (float) $month['anomaly'] : null,
            ];
        }

        return [
            'target'              => snowy_wp_climate_target($raw),
            'year'                => (int) $raw['year'],
            'partial'             => !empty($raw['partial']),
            'lastDate'            => (string) ($raw['lastDate'] ?? ''),
            'consolidatedThrough' => (string) ($raw['consolidatedThrough'] ?? ''),
            'provisionalFrom'     => (string) ($raw['provisionalFrom'] ?? ''),
            'reference'           => (array) ($raw['daily']['reference'] ?? []),
            'warmShare'           => isset($raw['daily']['warmShare']) ? (float) $raw['daily']['warmShare'] : null,
            'coldShare'           => isset($raw['daily']['coldShare']) ? (float) $raw['daily']['coldShare'] : null,
            'days'                => $days,
            'monthly'             => [
                'variable'  => (string) ($raw['monthly']['variable'] ?? ''),
                'reference' => (array) ($raw['monthly']['reference'] ?? []),
                'months'    => $months,
            ],
            'rank'                => (array) ($raw['rank'] ?? []),
        ];
    });
}

function snowy_wp_climate_year_or_previous($place, $requested)
{
    $year = (int) $requested > 0 ? (int) $requested : (int) wp_date('Y');
    $data = snowy_wp_climate_year($place, $year);
    if (!$data && (int) $requested <= 0) {
        $data = snowy_wp_climate_year($place, $year - 1);
    }

    return $data;
}

function snowy_wp_climate_rain($place, $year = 0)
{
    if (!is_array($place) || ($place['kind'] ?? '') !== 'region') {
        return [];
    }

    $year = (int) $year > 0 ? (int) $year : (int) wp_date('Y');

    return snowy_wp_climate_fetch('/climate/monitor', $place, ['year' => $year], SNOWY_WP_TTL_CLIMATE_CURRENT, static function ($raw) {
        if (!isset($raw['percentOfNormal'], $raw['stations'])) {
            return null;
        }

        $stations = [];
        foreach ((array) $raw['stations'] as $station) {
            if (!is_array($station)) {
                continue;
            }
            $stations[] = [
                'name'            => (string) ($station['name'] ?? ''),
                'elevationM'      => isset($station['elevationM']) ? (int) $station['elevationM'] : null,
                'lastDate'        => (string) ($station['lastDate'] ?? ''),
                'totalMm'         => isset($station['totalMm']) ? (float) $station['totalMm'] : null,
                'normalToDateMm'  => isset($station['normalToDateMm']) ? (float) $station['normalToDateMm'] : null,
                'percentOfNormal' => isset($station['percentOfNormal']) ? (float) $station['percentOfNormal'] : null,
                'filledDays'      => (int) ($station['filledDays'] ?? 0),
            ];
        }

        return [
            'region'           => (array) ($raw['region'] ?? []),
            'year'             => (int) ($raw['year'] ?? 0),
            'reference'        => (array) ($raw['reference'] ?? []),
            'method'           => (string) ($raw['method'] ?? ''),
            'percentOfNormal'  => $raw['percentOfNormal'] !== null ? (float) $raw['percentOfNormal'] : null,
            'stationCount'     => (int) ($raw['stationCount'] ?? count($stations)),
            'stationsWithData' => (int) ($raw['stationsWithData'] ?? count($stations)),
            'stations'         => $stations,
        ];
    });
}

function snowy_wp_climate_annual($profile, $variable)
{
    $serie = $profile['annual'][$variable] ?? ($profile['annual']['temperature_mean'] ?? []);

    return snowy_wp_drop_current_year($serie);
}

function snowy_wp_climate_name($place, $target = [])
{
    if (!empty($place['name'])) {
        return (string) $place['name'];
    }
    if (!empty($target['name'])) {
        return (string) $target['name'];
    }

    return snowy_wp_region_label();
}

function snowy_wp_climate($lat, $lon)
{
    return snowy_wp_climate_overview([
        'kind'  => 'point',
        'query' => ['lat' => round((float) $lat, 3), 'lon' => round((float) $lon, 3)],
        'key'   => 'p:' . $lat . ',' . $lon,
        'name'  => '',
    ]);
}

function snowy_wp_climate_overview($place)
{
    $summary = snowy_wp_climate_summary($place, SNOWY_WP_CLIMATE_VARIABLE);
    if ($summary) {
        $profile = snowy_wp_climate_profile($place, 'records');

        return snowy_wp_climate_overview_from_summary($summary, $profile['records'] ?? []);
    }

    if (($place['kind'] ?? '') !== 'point') {
        return [];
    }

    return snowy_wp_cached('climate_' . md5($place['query']['lat'] . '|' . $place['query']['lon']), SNOWY_WP_TTL_CLIMATE, static function () use ($place) {
        $cell = snowy_wp_get(
            sprintf('/climate/cell?lat=%s&lon=%s', rawurlencode((string) $place['query']['lat']), rawurlencode((string) $place['query']['lon'])),
            SNOWY_WP_CLIMATE_TIMEOUT
        );

        return $cell ? snowy_wp_climate_summary_from_cell($cell) : null;
    });
}

function snowy_wp_climate_overview_from_summary($summary, $records)
{
    $before = $summary['beforeNow']['before'] ?? [];
    $now    = $summary['beforeNow']['now'] ?? [];
    $period = $summary['period'];
    $desde  = (int) ($before['from'] ?? ($period['yearStart'] ?? 0));
    $hasta  = (int) ($period['lastCompleteYear'] ?? ($now['to'] ?? 0));
    if (!$desde || !$hasta) {
        return [];
    }

    $indices = [];
    foreach (SNOWY_WP_CLIMATE_INDICES as $key => $label) {
        $fila = $summary['beforeNow']['indices'][$key] ?? null;
        if (!is_array($fila) || !isset($fila['before'], $fila['now'])) {
            continue;
        }
        $indices[$key] = [
            'antes'  => (float) $fila['before'],
            'ahora'  => (float) $fila['now'],
            'decada' => $fila['slopePerDecade'] ?? null,
            'firme'  => !empty($fila['significant']),
        ];
    }

    return [
        'variable'  => $summary['variable'],
        'desde'     => $desde,
        'hasta'     => $hasta,
        'tendencia' => [
            'slopePerDecade' => (float) ($summary['trend']['slopePerDecade'] ?? 0),
            'significant'    => !empty($summary['trend']['significant']),
            'pValue'         => $summary['trend']['pValue'] ?? null,
        ],
        'antes'     => isset($before['value']) ? (float) $before['value'] : null,
        'ahora'     => isset($now['value']) ? (float) $now['value'] : null,
        'indices'   => $indices,
        'records'   => (array) $records,
        'target'    => $summary['target'],
    ];
}

function snowy_wp_climate_summary_from_cell($cell)
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
        return is_array($punto) && (int) ($punto[0] ?? 0) < $ahora && isset($punto[1]) && $punto[1] !== null;
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
        'region'    => '',
        'ubicacion' => '',
        'loc'       => '',
        'lat'       => '',
        'lon'       => '',
        'nombre'    => '',
        'records'   => 'si',
        'titulo'    => '',
        'nivel'     => '',
    ], $atts, 'snowy_clima');

    snowy_wp_use_styles();

    $place = snowy_wp_climate_place($atts);
    if (!$place) {
        return '';
    }

    $clima = snowy_wp_climate_overview($place);
    if (!$clima || empty($clima['tendencia'])) {
        return '';
    }

    $nombre = snowy_wp_climate_name($place, $clima['target'] ?? []);
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
        'point'        => $place,
        'climate'      => $clima,
        'per_decade'   => $decada,
        'change'       => $total,
        'jump'         => $salto,
        'indices'      => SNOWY_WP_CLIMATE_INDICES,
        'records'      => SNOWY_WP_CLIMATE_RECORDS,
        'show_records' => $atts['records'] !== 'no',
        'credit'       => snowy_wp_climate_credit($place, $clima['target'] ?? []),
    ], $atts);
}
add_shortcode('snowy_clima', 'snowy_wp_shortcode_clima');
