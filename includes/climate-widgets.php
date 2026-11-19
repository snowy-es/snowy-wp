<?php

if (!defined('ABSPATH')) {
    exit;
}

const SNOWY_WP_NBSP = "\u{00A0}";

const SNOWY_WP_CLIMATE_PLACE_ATTS = [
    'region'    => '',
    'ubicacion' => '',
    'loc'       => '',
    'lat'       => '',
    'lon'       => '',
    'nombre'    => '',
    'titulo'    => '',
    'nivel'     => '',
];

const SNOWY_WP_MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

const SNOWY_WP_MONTHS_SHORT = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

const SNOWY_WP_CLIMATE_CHANGE_ROWS = [
    'summer_days'     => ['label' => 'Días de calor', 'hint' => 'máxima ≥ 30 °C'],
    'hot_days'        => ['label' => 'Días tórridos', 'hint' => 'máxima ≥ 35 °C'],
    'tropical_nights' => ['label' => 'Noches tropicales', 'hint' => 'mínima ≥ 20 °C'],
    'frost_days'      => ['label' => 'Días de helada', 'hint' => 'mínima ≤ 0 °C'],
];

function snowy_wp_num($value, $decimals = 1)
{
    $rounded = round((float) $value, $decimals);
    if ($rounded == 0) {
        $rounded = 0.0;
    }

    return str_replace('-', '−', number_format($rounded, $decimals, ',', '.'));
}

function snowy_wp_num_signed($value, $decimals = 1, $unit = '')
{
    $rounded = round((float) $value, $decimals);
    $text = ($rounded > 0 ? '+' : '') . snowy_wp_num($rounded, $decimals);

    return $unit === '' ? $text : $text . SNOWY_WP_NBSP . $unit;
}

function snowy_wp_num_unit($value, $decimals, $unit)
{
    return snowy_wp_num($value, $decimals) . SNOWY_WP_NBSP . $unit;
}

function snowy_wp_long_date($date)
{
    $time = strtotime((string) $date . ' 12:00:00');
    if (!$time) {
        return '';
    }

    return (int) gmdate('j', $time) . ' de ' . SNOWY_WP_MONTHS[(int) gmdate('n', $time) - 1];
}

function snowy_wp_climate_uid($prefix)
{
    static $count = 0;
    $count++;

    return 'snowy-wp-' . $prefix . '-' . $count;
}

function snowy_wp_pct($value)
{
    return rtrim(rtrim(number_format(max(0, min(100, (float) $value)), 3, '.', ''), '0'), '.');
}

function snowy_wp_percentile_step($percentile)
{
    if ($percentile === null) {
        return '';
    }

    $p = (float) $percentile;
    if ($p <= 5) {
        return '1';
    }
    if ($p <= 10) {
        return '2';
    }
    if ($p <= 20) {
        return '3';
    }
    if ($p <= 30) {
        return '4';
    }
    if ($p < 40) {
        return '5';
    }
    if ($p <= 60) {
        return 'n';
    }
    if ($p < 70) {
        return '6';
    }
    if ($p < 80) {
        return '7';
    }
    if ($p < 90) {
        return '8';
    }
    if ($p < 95) {
        return '9';
    }

    return '10';
}

function snowy_wp_anomaly_step($anomaly, $spread)
{
    $spread = max(0.0001, (float) $spread);
    $ratio = max(-1, min(1, (float) $anomaly / $spread));
    if ($ratio < 0) {
        return (string) (5 - min(4, (int) floor(-$ratio * 5)));
    }

    return (string) (6 + min(4, (int) floor($ratio * 5)));
}

function snowy_wp_climate_atts($extra, $atts, $shortcode)
{
    return snowy_wp_atts(array_merge(SNOWY_WP_CLIMATE_PLACE_ATTS, $extra), $atts, $shortcode);
}

function snowy_wp_climate_title($atts, $default)
{
    return $atts['titulo'] !== '' ? $atts['titulo'] : $default;
}

function snowy_wp_climate_credit($place, $target = [])
{
    $link = sprintf(
        '<a href="%s" target="_blank" rel="noopener"><strong>Snowy</strong></a>',
        esc_url(SNOWY_WP_SITE . '/clima')
    );

    if (($place['kind'] ?? '') === 'region') {
        $cells = (int) ($target['cellCount'] ?? 0);
        $text = $cells > 0
            ? sprintf(
                /* translators: 1: nombre de la region, 2: numero de celdas, 3: enlace a Snowy */
                __('Media de toda %1$s, no de un punto: %2$s celdas de 0,1° de ERA5-Land (Copernicus/ECMWF) ponderadas por la superficie que ocupan dentro de la región. Datos elaborados por %3$s.', 'snowy-wp'),
                esc_html((string) ($target['name'] ?? '')),
                esc_html((string) $cells),
                $link
            )
            : sprintf(
                /* translators: %s: enlace a Snowy */
                __('Media de la región sobre ERA5-Land (Copernicus/ECMWF), ponderada por superficie. Datos elaborados por %s.', 'snowy-wp'),
                $link
            );
    } else {
        $text = sprintf(
            /* translators: %s: enlace a Snowy */
            __('Serie de ERA5-Land (Copernicus/ECMWF) elaborada por %s. Cada celda cubre unos diez kilómetros de terreno.', 'snowy-wp'),
            $link
        );
    }

    return '<p class="snowy-wp-credit">' . $text . '</p>';
}

function snowy_wp_climate_sr_table($caption, $head, $rows)
{
    $html = '<table class="snowy-wp-sr"><caption>' . esc_html($caption) . '</caption><thead><tr>';
    foreach ($head as $cell) {
        $html .= '<th scope="col">' . esc_html($cell) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach (array_values($row) as $index => $cell) {
            $html .= $index === 0 ? '<th scope="row">' . esc_html($cell) . '</th>' : '<td>' . esc_html($cell) . '</td>';
        }
        $html .= '</tr>';
    }

    return $html . '</tbody></table>';
}

function snowy_wp_nice_ticks($min, $max, $count = 4)
{
    $span = max(0.0001, $max - $min);
    $raw = $span / max(1, $count);
    $magnitude = pow(10, floor(log10($raw)));
    $step = $magnitude;
    foreach ([1, 2, 2.5, 5, 10] as $factor) {
        if ($magnitude * $factor >= $raw) {
            $step = $magnitude * $factor;
            break;
        }
    }

    $ticks = [];
    for ($value = ceil($min / $step) * $step; $value <= $max + 1e-9; $value += $step) {
        $ticks[] = round($value, 6);
    }

    return [$ticks, $step];
}

function snowy_wp_trend_line($serie, $slope_per_decade)
{
    $slope = (float) $slope_per_decade / 10;
    $residuals = [];
    foreach ($serie as $point) {
        $residuals[] = (float) $point[1] - $slope * (int) $point[0];
    }
    sort($residuals);
    $count = count($residuals);
    if (!$count) {
        return null;
    }
    $middle = intdiv($count, 2);
    $intercept = $count % 2 ? $residuals[$middle] : ($residuals[$middle - 1] + $residuals[$middle]) / 2;

    return [$slope, $intercept];
}

function snowy_wp_density($values, $from, $to, $steps = 120)
{
    $count = count($values);
    if ($count < 3) {
        return [];
    }

    $mean = array_sum($values) / $count;
    $variance = 0;
    foreach ($values as $value) {
        $variance += ($value - $mean) ** 2;
    }
    $sd = sqrt($variance / ($count - 1));
    $bandwidth = max(0.05, 1.06 * $sd * pow($count, -0.2));

    $points = [];
    for ($i = 0; $i <= $steps; $i++) {
        $x = $from + ($to - $from) * $i / $steps;
        $sum = 0;
        foreach ($values as $value) {
            $z = ($x - $value) / $bandwidth;
            $sum += exp(-0.5 * $z * $z);
        }
        $points[] = [$x, $sum / ($count * $bandwidth * sqrt(2 * M_PI))];
    }

    return $points;
}

function snowy_wp_quantile($values, $q)
{
    sort($values);
    $count = count($values);
    if (!$count) {
        return null;
    }
    $position = ($count - 1) * $q;
    $low = (int) floor($position);
    $high = (int) ceil($position);

    return $values[$low] + ($values[$high] - $values[$low]) * ($position - $low);
}

function snowy_wp_shortcode_clima_calendario($atts = [])
{
    $atts = snowy_wp_climate_atts(['anio' => ''], $atts, 'snowy_clima_calendario');
    snowy_wp_use_styles();

    $place = snowy_wp_climate_place($atts);
    $year = $place ? snowy_wp_climate_year_or_previous($place, $atts['anio']) : [];
    if (!$year || empty($year['days'])) {
        return '';
    }

    $grid = [];
    $last = '';
    foreach ($year['days'] as $day) {
        [$date, $percentile, $provisional] = $day;
        $time = strtotime($date . ' 12:00:00');
        if (!$time) {
            continue;
        }
        $grid[(int) gmdate('n', $time)][(int) gmdate('j', $time)] = [$percentile, $provisional];
        if ($percentile !== null) {
            $last = $date;
        }
    }

    $name = snowy_wp_climate_name($place, $year['target']);
    $number = (int) $year['year'];
    $label = sprintf(
        /* translators: 1: año, 2: lugar */
        __('Calendario de %1$s en %2$s: percentil de la temperatura media de cada día frente al mismo día de 1991-2020.', 'snowy-wp'),
        $number,
        $name
    );

    $rects = '';
    $months = [];
    for ($month = 1; $month <= 12; $month++) {
        $days_in_month = (int) gmdate('t', (int) gmmktime(12, 0, 0, $month, 1, $number));
        $warm = $cold = $counted = 0;
        $sum = 0;
        for ($day = 1; $day <= $days_in_month; $day++) {
            $cell = $grid[$month][$day] ?? null;
            $percentile = $cell[0] ?? null;
            $step = snowy_wp_percentile_step($percentile);
            $class = $step === '' ? 'is-empty' : 'snowy-wp-s' . $step . (!empty($cell[1]) ? ' is-provisional' : '');
            $title = $percentile === null
                ? sprintf('%d de %s: sin dato', $day, SNOWY_WP_MONTHS[$month - 1])
                : sprintf('%d de %s: percentil %d%s', $day, SNOWY_WP_MONTHS[$month - 1], $percentile, !empty($cell[1]) ? ' (provisional)' : '');
            $rects .= sprintf(
                '<rect x="%s" y="%s" width=".86" height=".8" class="%s"><title>%s</title></rect>',
                $day - 1 + 0.07,
                $month - 1 + 0.1,
                esc_attr($class),
                esc_html($title)
            );
            if ($percentile !== null) {
                $counted++;
                $sum += $percentile;
                $warm += $percentile >= 80 ? 1 : 0;
                $cold += $percentile <= 20 ? 1 : 0;
            }
        }
        $months[] = [
            SNOWY_WP_MONTHS_SHORT[$month - 1],
            (string) $counted,
            (string) $warm,
            (string) $cold,
            $counted ? (string) round($sum / $counted) : '—',
        ];
    }

    $id = snowy_wp_climate_uid('cal');
    $svg = sprintf(
        '<svg class="snowy-wp-cal-svg" viewBox="0 0 31 12" preserveAspectRatio="none" role="img" aria-labelledby="%1$s-t" focusable="false"><title id="%1$s-t">%2$s</title>%3$s</svg>',
        esc_attr($id),
        esc_html($label),
        $rects
    );

    $provisional_from = $year['provisionalFrom'] !== '' ? $year['provisionalFrom'] : '';

    return snowy_wp_render_widget('clima-calendario', [
        'title'       => snowy_wp_climate_title($atts, sprintf(
            /* translators: 1: año, 2: lugar */
            __('Cada día de %1$s frente a lo normal en %2$s', 'snowy-wp'),
            $number,
            $name
        )),
        'subtitle'    => __('Percentil de la temperatura media de cada día entre los mismos días de 1991-2020', 'snowy-wp'),
        'name'        => $name,
        'year'        => $year,
        'svg'         => $svg,
        'last_date'   => $last,
        'provisional' => $provisional_from,
        'table'       => snowy_wp_climate_sr_table(
            sprintf(__('Días de %d por mes', 'snowy-wp'), $number),
            [__('Mes', 'snowy-wp'), __('Días con dato', 'snowy-wp'), __('Cálidos (p ≥ 80)', 'snowy-wp'), __('Fríos (p ≤ 20)', 'snowy-wp'), __('Percentil medio', 'snowy-wp')],
            $months
        ),
        'credit'      => snowy_wp_climate_credit($place, $year['target']),
    ], $atts);
}
add_shortcode('snowy_clima_calendario', 'snowy_wp_shortcode_clima_calendario');

function snowy_wp_shortcode_clima_meses($atts = [])
{
    $atts = snowy_wp_climate_atts(['anio' => ''], $atts, 'snowy_clima_meses');
    snowy_wp_use_styles();

    $place = snowy_wp_climate_place($atts);
    $year = $place ? snowy_wp_climate_year_or_previous($place, $atts['anio']) : [];
    $months = $year['monthly']['months'] ?? [];
    $known = array_filter($months, static fn($m) => $m['anomaly'] !== null);
    if (!$known) {
        return '';
    }

    $anomalies = array_map(static fn($m) => $m['anomaly'], $known);
    $high = max(0, ceil(max($anomalies) * 2) / 2);
    $low = min(0, floor(min($anomalies) * 2) / 2);
    if ($high - $low < 1) {
        if ($low < 0 && $high == 0) {
            $low = $high - 1;
        } else {
            $high = $low + 1;
        }
    }
    $zero = $high / ($high - $low) * 100;
    $spread = [$high, $low, $zero];
    $bars = '';
    $columns = [];
    $rows = [];
    $warmer = 0;
    $peak = null;
    for ($month = 1; $month <= 12; $month++) {
        $item = $months[$month] ?? null;
        $anomaly = $item['anomaly'] ?? null;
        $columns[] = [
            'month'   => SNOWY_WP_MONTHS_SHORT[$month - 1],
            'initial' => mb_substr(SNOWY_WP_MONTHS_SHORT[$month - 1], 0, 1),
            'value'   => $anomaly === null ? '' : snowy_wp_num_signed($anomaly, 1),
            'class'   => $anomaly === null ? 'is-empty' : ($anomaly >= 0 ? 'is-warm' : 'is-cold'),
        ];
        if ($anomaly === null) {
            continue;
        }
        $height = abs($anomaly) / ($high - $low) * 100;
        $bars .= sprintf(
            '<rect x="%s" y="%s" width="6" height="%s" class="%s"><title>%s</title></rect>',
            ($month - 1) * 10 + 2,
            $anomaly >= 0 ? $zero - $height : $zero,
            max(0.4, $height),
            $anomaly >= 0 ? 'is-warm' : 'is-cold',
            esc_html(sprintf('%s: %s °C', SNOWY_WP_MONTHS[$month - 1], snowy_wp_num_signed($anomaly, 1)))
        );
        $warmer += $anomaly > 0 ? 1 : 0;
        if ($peak === null || abs($anomaly) > abs($peak[1])) {
            $peak = [$month, $anomaly];
        }
        $rows[] = [
            ucfirst(SNOWY_WP_MONTHS[$month - 1]),
            snowy_wp_num_unit($item['value'], 1, '°C'),
            snowy_wp_num_unit($item['normal'], 1, '°C'),
            snowy_wp_num_signed($anomaly, 1, '°C'),
        ];
    }

    $name = snowy_wp_climate_name($place, $year['target']);
    $number = (int) $year['year'];
    $id = snowy_wp_climate_uid('meses');
    $label = sprintf(
        /* translators: 1: año, 2: lugar */
        __('Anomalía de la temperatura media de cada mes de %1$s en %2$s frente a 1991-2020.', 'snowy-wp'),
        $number,
        $name
    );
    $svg = sprintf(
        '<svg class="snowy-wp-bars-svg" viewBox="0 0 120 100" preserveAspectRatio="none" role="img" aria-labelledby="%1$s-t" focusable="false"><title id="%1$s-t">%2$s</title><line x1="0" x2="120" y1="%4$s" y2="%4$s" class="snowy-wp-zero" vector-effect="non-scaling-stroke"/>%3$s</svg>',
        esc_attr($id),
        esc_html($label),
        $bars,
        round($zero, 2)
    );

    return snowy_wp_render_widget('clima-meses', [
        'title'    => snowy_wp_climate_title($atts, sprintf(
            /* translators: 1: año, 2: lugar */
            __('%1$s mes a mes en %2$s', 'snowy-wp'),
            $number,
            $name
        )),
        'subtitle' => __('Temperatura media de cada mes frente a la de 1991-2020', 'snowy-wp'),
        'name'     => $name,
        'year'     => $year,
        'svg'      => $svg,
        'spread'   => $spread,
        'columns'  => $columns,
        'summary'  => sprintf(
            /* translators: 1: meses mas calidos, 2: meses con dato, 3: mes con mayor anomalia, 4: anomalia */
            _n('%1$s de %2$s mes más cálido de lo normal. El más alejado de lo normal, %3$s: %4$s.', '%1$s de %2$s meses más cálidos de lo normal. El más alejado de lo normal, %3$s: %4$s.', count($known), 'snowy-wp'),
            $warmer,
            count($known),
            SNOWY_WP_MONTHS[$peak[0] - 1],
            snowy_wp_num_signed($peak[1], 1, '°C')
        ),
        'table'    => snowy_wp_climate_sr_table(
            sprintf(__('Temperatura media de cada mes de %d', 'snowy-wp'), $number),
            [__('Mes', 'snowy-wp'), __('Media', 'snowy-wp'), __('Normal 1991-2020', 'snowy-wp'), __('Diferencia', 'snowy-wp')],
            $rows
        ),
        'credit'   => snowy_wp_climate_credit($place, $year['target']),
    ], $atts);
}
add_shortcode('snowy_clima_meses', 'snowy_wp_shortcode_clima_meses');

function snowy_wp_extreme_verdict($value, $percentiles, $year)
{
    $p = static fn($key) => isset($percentiles[$key]) ? (float) $percentiles[$key] : null;
    if ($p('p95') !== null && $value >= $p('p95')) {
        return [sprintf(__('Sí: %d fue un año extremadamente cálido', 'snowy-wp'), $year), 'is-warm'];
    }
    if ($p('p80') !== null && $value >= $p('p80')) {
        return [sprintf(__('%d fue cálido, pero no extremo', 'snowy-wp'), $year), 'is-warm'];
    }
    if ($p('p5') !== null && $value <= $p('p5')) {
        return [sprintf(__('Sí: %d fue un año extremadamente frío', 'snowy-wp'), $year), 'is-cold'];
    }
    if ($p('p20') !== null && $value <= $p('p20')) {
        return [sprintf(__('%d fue frío, pero no extremo', 'snowy-wp'), $year), 'is-cold'];
    }

    return [sprintf(__('No: %d estuvo dentro de lo normal', 'snowy-wp'), $year), ''];
}

function snowy_wp_shortcode_clima_extremo($atts = [])
{
    $atts = snowy_wp_climate_atts(['anio' => '', 'variable' => 'media'], $atts, 'snowy_clima_extremo');
    snowy_wp_use_styles();

    $place = snowy_wp_climate_place($atts);
    if (!$place) {
        return '';
    }
    $variable = snowy_wp_climate_variable($atts['variable']);
    $summary = snowy_wp_climate_summary($place, $variable);
    $profile = snowy_wp_climate_profile($place, 'annual');
    $serie = snowy_wp_climate_annual($profile, $variable);
    $last = $summary['lastYear'] ?? [];
    if (!$serie || empty($last['reference'])) {
        return '';
    }

    $values = [];
    foreach ($serie as $point) {
        $values[(int) $point[0]] = (float) $point[1];
    }
    $year = (int) $atts['anio'] > 0 ? (int) $atts['anio'] : (int) ($last['year'] ?? 0);
    if (!isset($values[$year])) {
        return '';
    }

    $reference = $last['reference'];
    $from = (int) ($reference['from'] ?? 1991);
    $to = (int) ($reference['to'] ?? 2020);
    $sample = array_values(array_filter($values, static fn($y) => $y >= $from && $y <= $to, ARRAY_FILTER_USE_KEY));
    if (count($sample) < 10) {
        return '';
    }

    $value = $values[$year];
    $mean = array_sum($sample) / count($sample);
    $percentiles = (array) ($reference['percentiles'] ?? []);
    foreach (['p5' => .05, 'p20' => .2, 'p50' => .5, 'p80' => .8, 'p95' => .95] as $key => $q) {
        if (!isset($percentiles[$key])) {
            $percentiles[$key] = snowy_wp_quantile($sample, $q);
        }
    }
    $sorted = $values;
    arsort($sorted);
    $rank = array_search($year, array_keys($sorted), true) + 1;

    $low = min(min($sample), $value);
    $high = max(max($sample), $value);
    $pad = ($high - $low) * 0.18 + 0.2;
    $low -= $pad;
    $high += $pad;
    $density = snowy_wp_density($sample, $low, $high);
    $peak = max(array_map(static fn($p) => $p[1], $density));
    $x = static fn($v) => ($v - $low) / ($high - $low) * 100;
    $path = 'M0 100';
    foreach ($density as $point) {
        $path .= sprintf(' L%.2f %.2f', $x($point[0]), 100 - $point[1] / $peak * 92);
    }
    $path .= ' L100 100 Z';

    $id = snowy_wp_climate_uid('extremo');
    $stops = '';
    for ($step = 1; $step <= 10; $step++) {
        $center = $mean + ($step - 5.5) / 4.5 * max(abs($high - $mean), abs($mean - $low));
        $stops .= sprintf('<stop offset="%s%%" class="snowy-wp-stop-%d"/>', snowy_wp_pct($x($center)), $step);
    }
    $name = snowy_wp_climate_name($place, $summary['target'] ?? []);
    $label = sprintf(
        /* translators: 1: año, 2: valor, 3: lugar, 4: inicio del periodo de referencia, 5: fin */
        __('Distribución de la temperatura media anual de %3$s en %4$s-%5$s, con %1$s marcado en %2$s.', 'snowy-wp'),
        $year,
        snowy_wp_num_unit($value, 1, '°C'),
        $name,
        $from,
        $to
    );
    $svg = sprintf(
        '<svg class="snowy-wp-dist-svg" viewBox="0 0 100 100" preserveAspectRatio="none" role="img" aria-labelledby="%1$s-t" focusable="false"><title id="%1$s-t">%2$s</title><defs><linearGradient id="%1$s-g" x1="0" x2="1" y1="0" y2="0">%3$s</linearGradient></defs><path d="%4$s" fill="url(#%1$s-g)" class="snowy-wp-dist-area"/><path d="%4$s" class="snowy-wp-dist-edge" vector-effect="non-scaling-stroke"/></svg>',
        esc_attr($id),
        esc_html($label),
        $stops,
        esc_attr($path)
    );

    $marks = [];
    foreach (['p5' => 'P5', 'p50' => __('Mediana', 'snowy-wp'), 'p95' => 'P95'] as $key => $text) {
        $marks[] = [
            'left'  => snowy_wp_pct($x((float) $percentiles[$key])),
            'label' => $text,
            'value' => ' · ' . snowy_wp_num_unit($percentiles[$key], 1, '°C'),
            'key'   => $key,
        ];
    }

    [$verdict, $tone] = snowy_wp_extreme_verdict($value, $percentiles, $year);
    $first = (int) min(array_keys($values));

    return snowy_wp_render_widget('clima-extremo', [
        'title'    => snowy_wp_climate_title($atts, sprintf(
            /* translators: 1: año, 2: lugar */
            __('¿Fue %1$s un año extremo en %2$s?', 'snowy-wp'),
            $year,
            $name
        )),
        'subtitle' => sprintf(
            /* translators: 1: variable, 2: inicio del periodo, 3: fin */
            __('La %1$s del año frente a la de cada año de %2$s-%3$s', 'snowy-wp'),
            SNOWY_WP_CLIMATE_VARIABLE_LABELS[$variable] ?? __('temperatura media', 'snowy-wp'),
            $from,
            $to
        ),
        'name'     => $name,
        'year'     => $year,
        'value'    => $value,
        'verdict'  => $verdict,
        'tone'     => $tone,
        'detail'   => sprintf(
            /* translators: 1: año, 2: valor, 3: diferencia, 4: inicio, 5: fin, 6: media, 7: puesto, 8: total de años, 9: primer año */
            __('La media de %1$s fue %2$s, %3$s respecto a la de %4$s-%5$s (%6$s). Es el %7$s.º año más cálido de los %8$s desde %9$s.', 'snowy-wp'),
            $year,
            snowy_wp_num_unit($value, 1, '°C'),
            snowy_wp_num_signed($value - $mean, 1, '°C'),
            $from,
            $to,
            snowy_wp_num_unit($mean, 1, '°C'),
            $rank,
            count($values),
            $first
        ),
        'svg'      => $svg,
        'marks'    => $marks,
        'marker'   => ['left' => snowy_wp_pct($x($value)), 'label' => $year . ' · ' . snowy_wp_num_unit($value, 1, '°C')],
        'axis'     => [__('Más frío', 'snowy-wp'), __('Más cálido', 'snowy-wp')],
        'credit'   => snowy_wp_climate_credit($place, $summary['target'] ?? []),
    ], $atts);
}
add_shortcode('snowy_clima_extremo', 'snowy_wp_shortcode_clima_extremo');

function snowy_wp_shortcode_clima_serie($atts = [])
{
    $atts = snowy_wp_climate_atts(['variable' => 'media'], $atts, 'snowy_clima_serie');
    snowy_wp_use_styles();

    $place = snowy_wp_climate_place($atts);
    if (!$place) {
        return '';
    }
    $variable = snowy_wp_climate_variable($atts['variable']);
    $summary = snowy_wp_climate_summary($place, $variable);
    $profile = snowy_wp_climate_profile($place, 'annual');
    $serie = snowy_wp_climate_annual($profile, $variable);
    $trend = $summary['trend'] ?? [];
    if (count($serie) < 10 || !isset($trend['slopePerDecade'])) {
        return '';
    }

    $years = array_map(static fn($p) => (int) $p[0], $serie);
    $temps = array_map(static fn($p) => (float) $p[1], $serie);
    $first = min($years);
    $last = max($years);
    [$ticks, $step] = snowy_wp_nice_ticks(min($temps), max($temps), 4);
    $bottom = min(min($temps), $ticks[0]) - $step * 0.25;
    $top = max(max($temps), end($ticks)) + $step * 0.25;
    $x = static fn($year) => ($year - $first) / max(1, $last - $first) * 100;
    $y = static fn($value) => (1 - ($value - $bottom) / ($top - $bottom)) * 100;

    $line = [];
    $dots = [];
    $mean = array_sum($temps) / count($temps);
    $spread = max(array_map(static fn($t) => abs($t - $mean), $temps));
    foreach ($serie as $point) {
        $line[] = sprintf('%.2f,%.2f', $x((int) $point[0]), $y((float) $point[1]));
        $dots[] = [
            'left'  => snowy_wp_pct($x((int) $point[0])),
            'top'   => snowy_wp_pct($y((float) $point[1])),
            'step'  => snowy_wp_anomaly_step((float) $point[1] - $mean, $spread),
            'title' => $point[0] . ': ' . snowy_wp_num_unit($point[1], 1, '°C'),
        ];
    }

    $fit = snowy_wp_trend_line($serie, $trend['slopePerDecade']);
    $trend_line = '';
    if ($fit) {
        $trend_line = sprintf(
            '<line x1="0" x2="100" y1="%.2f" y2="%.2f" class="snowy-wp-trend-line" vector-effect="non-scaling-stroke"/>',
            $y($fit[0] * $first + $fit[1]),
            $y($fit[0] * $last + $fit[1])
        );
    }

    $grid = '';
    $y_labels = [];
    foreach ($ticks as $tick) {
        $grid .= sprintf('<line x1="0" x2="100" y1="%1$.2f" y2="%1$.2f" class="snowy-wp-grid" vector-effect="non-scaling-stroke"/>', $y($tick));
        $y_labels[] = ['top' => snowy_wp_pct($y($tick)), 'label' => snowy_wp_num($tick, $step < 1 ? 1 : 0) . '°'];
    }
    $x_labels = [];
    for ($decade = (int) (ceil($first / 10) * 10); $decade <= $last; $decade += 10) {
        $x_labels[] = ['left' => snowy_wp_pct($x($decade)), 'label' => (string) $decade];
    }

    $name = snowy_wp_climate_name($place, $summary['target'] ?? []);
    $id = snowy_wp_climate_uid('serie');
    $svg = sprintf(
        '<svg class="snowy-wp-line-svg" viewBox="0 0 100 100" preserveAspectRatio="none" role="img" aria-labelledby="%1$s-t" focusable="false"><title id="%1$s-t">%2$s</title>%3$s<polyline points="%4$s" class="snowy-wp-series-line" vector-effect="non-scaling-stroke"/>%5$s</svg>',
        esc_attr($id),
        esc_html(sprintf(
            /* translators: 1: lugar, 2: primer año, 3: ultimo año, 4: tendencia */
            __('Temperatura media de cada año en %1$s de %2$s a %3$s, con una tendencia de %4$s por década.', 'snowy-wp'),
            $name,
            $first,
            $last,
            snowy_wp_num_signed($trend['slopePerDecade'], 2, '°C')
        )),
        $grid,
        implode(' ', $line),
        $trend_line
    );

    $warmest = array_keys($temps, max($temps))[0];
    $coldest = array_keys($temps, min($temps))[0];

    return snowy_wp_render_widget('clima-serie', [
        'title'    => snowy_wp_climate_title($atts, sprintf(
            /* translators: 1: lugar, 2: primer año */
            __('La temperatura de %1$s año a año desde %2$s', 'snowy-wp'),
            $name,
            $first
        )),
        'subtitle' => __('Media de cada año y su recta de tendencia', 'snowy-wp'),
        'name'     => $name,
        'figures'  => [
            [__('Cada década', 'snowy-wp'), snowy_wp_num_signed($trend['slopePerDecade'], 2, '°C'), !empty($trend['significant'])
                ? __('Tendencia estadísticamente significativa', 'snowy-wp')
                : __('Tendencia aún no significativa', 'snowy-wp')],
            [__('Año más cálido', 'snowy-wp'), (string) $years[$warmest], snowy_wp_num_unit($temps[$warmest], 1, '°C')],
            [__('Año más frío', 'snowy-wp'), (string) $years[$coldest], snowy_wp_num_unit($temps[$coldest], 1, '°C')],
        ],
        'svg'      => $svg,
        'dots'     => $dots,
        'y_labels' => $y_labels,
        'x_labels' => $x_labels,
        'legend'   => [__('Media del año', 'snowy-wp'), __('Tendencia', 'snowy-wp')],
        'table'    => snowy_wp_climate_sr_table(
            __('Temperatura media de cada año', 'snowy-wp'),
            [__('Año', 'snowy-wp'), __('Media', 'snowy-wp')],
            array_map(static fn($p) => [(string) $p[0], snowy_wp_num_unit($p[1], 1, '°C')], $serie)
        ),
        'credit'   => snowy_wp_climate_credit($place, $summary['target'] ?? []),
    ], $atts);
}
add_shortcode('snowy_clima_serie', 'snowy_wp_shortcode_clima_serie');

function snowy_wp_climate_stripes($serie)
{
    if (count($serie) < 2) {
        return null;
    }

    $temps = array_map(static fn($p) => (float) $p[1], $serie);
    $mean = array_sum($temps) / count($temps);
    $spread = max(array_map(static fn($t) => abs($t - $mean), $temps));
    $count = count($serie);
    $rects = '';
    foreach (array_values($serie) as $index => $point) {
        $rects .= sprintf(
            '<rect x="%d" y="0" width="1.04" height="1" class="snowy-wp-s%s"><title>%s</title></rect>',
            $index,
            snowy_wp_anomaly_step((float) $point[1] - $mean, $spread),
            esc_html($point[0] . ': ' . snowy_wp_num_unit($point[1], 1, '°C'))
        );
    }

    $years = array_map(static fn($p) => (int) $p[0], $serie);
    $warmest = array_keys($temps, max($temps))[0];

    return [
        'rects'   => $rects,
        'count'   => $count,
        'first'   => min($years),
        'last'    => max($years),
        'mean'    => $mean,
        'warmest' => ['year' => $years[$warmest], 'value' => $temps[$warmest], 'left' => snowy_wp_pct(($warmest + 0.5) / $count * 100)],
        'years'   => $years,
    ];
}

function snowy_wp_climate_change_note($change, $variable, $first)
{
    $label = SNOWY_WP_CLIMATE_VARIABLE_LABELS[$variable] ?? __('temperatura media', 'snowy-wp');
    $span = $change['span'] ?? null;
    if (($change['method'] ?? '') === 'climatologyShift' && is_array($span)) {
        return sprintf(
            /* translators: 1: variable, 2-3: periodo reciente, 4-5: primer periodo */
            __('Lo que ha subido la %1$s: la de %2$s-%3$s frente a la de %4$s-%5$s', 'snowy-wp'),
            $label,
            (int) $span['toStart'],
            (int) $span['toEnd'],
            (int) $span['fromStart'],
            (int) $span['fromEnd']
        );
    }

    return sprintf(
        /* translators: 1: variable, 2: primer año */
        __('Lo que ha subido la %1$s desde %2$s, según la tendencia de la serie', 'snowy-wp'),
        $label,
        $first
    );
}

function snowy_wp_climate_stripes_svg($stripes, $label)
{
    $id = snowy_wp_climate_uid('franjas');

    return sprintf(
        '<svg class="snowy-wp-stripes-svg" viewBox="0 0 %1$d 1" preserveAspectRatio="none" role="img" aria-labelledby="%2$s-t" focusable="false"><title id="%2$s-t">%3$s</title>%4$s</svg>',
        (int) $stripes['count'],
        esc_attr($id),
        esc_html($label),
        $stripes['rects']
    );
}

function snowy_wp_shortcode_clima_franjas($atts = [])
{
    $atts = snowy_wp_climate_atts(['variable' => 'media', 'cifra' => 'si'], $atts, 'snowy_clima_franjas');
    snowy_wp_use_styles();

    $place = snowy_wp_climate_place($atts);
    if (!$place) {
        return '';
    }
    $variable = snowy_wp_climate_variable($atts['variable']);
    $profile = snowy_wp_climate_profile($place, 'annual');
    $stripes = snowy_wp_climate_stripes(snowy_wp_climate_annual($profile, $variable));
    if (!$stripes) {
        return '';
    }

    $summary = $atts['cifra'] !== 'no' ? snowy_wp_climate_summary($place, $variable) : [];
    $change = isset($summary['change']['value']) ? (float) $summary['change']['value'] : null;
    $name = snowy_wp_climate_name($place, $profile['target'] ?? []);
    $label = sprintf(
        /* translators: 1: lugar, 2: primer año, 3: ultimo año */
        __('Franjas climáticas de %1$s: una por año de %2$s a %3$s, de azul (más frío que la media) a rojo (más cálido).', 'snowy-wp'),
        $name,
        $stripes['first'],
        $stripes['last']
    );

    $ticks = [];
    for ($decade = (int) (ceil($stripes['first'] / 10) * 10); $decade <= $stripes['last']; $decade += 10) {
        $index = array_search($decade, $stripes['years'], true);
        if ($index !== false) {
            $ticks[] = ['left' => snowy_wp_pct(($index + 0.5) / $stripes['count'] * 100), 'label' => (string) $decade];
        }
    }

    return snowy_wp_render_widget('clima-franjas', [
        'title'    => snowy_wp_climate_title($atts, sprintf(
            /* translators: 1: lugar, 2: primer año */
            __('Cada año desde %2$s en %1$s', 'snowy-wp'),
            $name,
            $stripes['first']
        )),
        'subtitle' => sprintf(
            /* translators: 1: primer año, 2: ultimo año */
            __('Cada franja es un año, frente a la media de %1$s-%2$s', 'snowy-wp'),
            $stripes['first'],
            $stripes['last']
        ),
        'name'     => $name,
        'stripes'  => $stripes,
        'svg'      => snowy_wp_climate_stripes_svg($stripes, $label),
        'ticks'    => $ticks,
        'change'   => $change,
        'figure'   => $change !== null ? snowy_wp_num_signed($change, 1, '°C') : '',
        'figure_note' => $change !== null ? snowy_wp_climate_change_note($summary['change'], $variable, $stripes['first']) : '',
        'warmest'  => sprintf(
            /* translators: 1: año, 2: valor */
            __('%1$s, el más cálido · %2$s', 'snowy-wp'),
            $stripes['warmest']['year'],
            snowy_wp_num_unit($stripes['warmest']['value'], 1, '°C')
        ),
        'legend'   => [__('Más frío', 'snowy-wp'), __('Más cálido', 'snowy-wp')],
        'credit'   => snowy_wp_climate_credit($place, $profile['target'] ?? []),
    ], $atts);
}
add_shortcode('snowy_clima_franjas', 'snowy_wp_shortcode_clima_franjas');

function snowy_wp_shortcode_clima_veranos($atts = [])
{
    $atts = snowy_wp_climate_atts([], $atts, 'snowy_clima_veranos');
    snowy_wp_use_styles();

    $place = snowy_wp_climate_place($atts);
    $summary = $place ? snowy_wp_climate_summary($place, SNOWY_WP_CLIMATE_VARIABLE) : [];
    $decades = $summary['summerDecades']['decades'] ?? [];
    if (count($decades) < 2) {
        return '';
    }

    $threshold = (int) ($summary['summerDecades']['threshold'] ?? 80);
    $normal = 100 - $threshold;
    $scale = max(50, ceil(max(array_map(static fn($d) => $d['warmShare'], $decades)) / 10) * 10 + 5);
    $rows = [];
    foreach ($decades as $decade) {
        $years = max(1, (int) round($decade['days'] / 92));
        $rows[] = [
            'label' => $decade['decade'] . '-' . ($years > 1 ? substr((string) ($decade['decade'] + $years - 1), -2) : ''),
            'share' => $decade['warmShare'],
            'text'  => snowy_wp_num($decade['warmShare'], 0) . SNOWY_WP_NBSP . '%',
            'width' => snowy_wp_pct($decade['warmShare'] / $scale * 100),
            'class' => $decade['warmShare'] >= $normal ? 'is-warm' : 'is-cold',
        ];
        if ($years === 1) {
            $rows[count($rows) - 1]['label'] = (string) $decade['decade'];
        }
    }

    $name = snowy_wp_climate_name($place, $summary['target'] ?? []);
    $first = reset($rows);
    $last = end($rows);

    return snowy_wp_render_widget('clima-veranos', [
        'title'    => snowy_wp_climate_title($atts, sprintf(
            /* translators: %s: lugar */
            __('El verano en %s, por décadas', 'snowy-wp'),
            $name
        )),
        'subtitle' => sprintf(
            /* translators: %d: percentil */
            __('Días de junio a agosto por encima del percentil %d de su fecha', 'snowy-wp'),
            $threshold
        ),
        'name'     => $name,
        'rows'     => $rows,
        'normal'   => ['left' => snowy_wp_pct($normal / $scale * 100), 'label' => sprintf(
            /* translators: %s: porcentaje normal */
            __('Lo normal en 1991-2020: %s', 'snowy-wp'),
            $normal . SNOWY_WP_NBSP . '%'
        )],
        'summary'  => sprintf(
            /* translators: 1: decada inicial, 2: porcentaje inicial, 3: decada final, 4: porcentaje final */
            __('Los días de verano cálidos para su fecha han pasado del %2$s en %1$s al %4$s en %3$s.', 'snowy-wp'),
            $first['label'],
            $first['text'],
            $last['label'],
            $last['text']
        ),
        'table'    => snowy_wp_climate_sr_table(
            __('Parte de los días de verano cálidos para su fecha, por décadas', 'snowy-wp'),
            [__('Década', 'snowy-wp'), __('Días cálidos', 'snowy-wp')],
            array_map(static fn($row) => [$row['label'], $row['text']], $rows)
        ),
        'credit'   => snowy_wp_climate_credit($place, $summary['target'] ?? []),
    ], $atts);
}
add_shortcode('snowy_clima_veranos', 'snowy_wp_shortcode_clima_veranos');

function snowy_wp_shortcode_clima_cambios($atts = [])
{
    $atts = snowy_wp_climate_atts(['variable' => 'media'], $atts, 'snowy_clima_cambios');
    snowy_wp_use_styles();

    $place = snowy_wp_climate_place($atts);
    if (!$place) {
        return '';
    }
    $variable = snowy_wp_climate_variable($atts['variable']);
    $summary = snowy_wp_climate_summary($place, $variable);
    $before_now = $summary['beforeNow'] ?? [];
    if (!isset($before_now['before']['value'], $before_now['now']['value'])) {
        return '';
    }

    $before = $before_now['before'];
    $now = $before_now['now'];
    $cards = [[
        'label'  => ucfirst(SNOWY_WP_CLIMATE_VARIABLE_LABELS[$variable] ?? __('temperatura media', 'snowy-wp')),
        'hint'   => __('del año', 'snowy-wp'),
        'figure' => snowy_wp_num_signed(round($now['value'], 1) - round($before['value'], 1), 1, '°C'),
        'detail' => sprintf(
            /* translators: 1: antes, 2: ahora */
            __('De %1$s a %2$s', 'snowy-wp'),
            snowy_wp_num_unit($before['value'], 1, '°C'),
            snowy_wp_num_unit($now['value'], 1, '°C')
        ),
        'class'  => $now['value'] >= $before['value'] ? 'is-warm' : 'is-cold',
    ]];

    foreach (SNOWY_WP_CLIMATE_CHANGE_ROWS as $key => $meta) {
        $row = $before_now['indices'][$key] ?? null;
        if (!is_array($row) || !isset($row['before'], $row['now'])) {
            continue;
        }
        $decimals = max(abs((float) $row['before']), abs((float) $row['now'])) < 5 ? 1 : 0;
        $delta = round((float) $row['now'], $decimals) - round((float) $row['before'], $decimals);
        $cards[] = [
            'label'  => __($meta['label'], 'snowy-wp'),
            'hint'   => __($meta['hint'], 'snowy-wp'),
            'figure' => snowy_wp_num_signed($delta, $decimals, _n('día', 'días', abs(round($delta, $decimals)) == 1 ? 1 : 2, 'snowy-wp')),
            'detail' => sprintf(
                /* translators: 1: antes, 2: ahora */
                __('De %1$s a %2$s días al año', 'snowy-wp'),
                snowy_wp_num($row['before'], $decimals),
                snowy_wp_num($row['now'], $decimals)
            ),
            'class'  => ($key === 'frost_days' ? -$delta : $delta) >= 0 ? 'is-warm' : 'is-cold',
        ];
    }

    $name = snowy_wp_climate_name($place, $summary['target'] ?? []);

    return snowy_wp_render_widget('clima-cambios', [
        'title'    => snowy_wp_climate_title($atts, sprintf(
            /* translators: %s: lugar */
            __('Lo que ha cambiado en %s', 'snowy-wp'),
            $name
        )),
        'subtitle' => sprintf(
            /* translators: 1-2: periodo reciente, 3-4: primer periodo */
            __('Media de %1$s-%2$s frente a la de %3$s-%4$s', 'snowy-wp'),
            (int) $now['from'],
            (int) $now['to'],
            (int) $before['from'],
            (int) $before['to']
        ),
        'name'     => $name,
        'cards'    => $cards,
        'credit'   => snowy_wp_climate_credit($place, $summary['target'] ?? []),
    ], $atts);
}
add_shortcode('snowy_clima_cambios', 'snowy_wp_shortcode_clima_cambios');

function snowy_wp_station_title($name)
{
    $name = trim(preg_replace('/\s+/', ' ', str_replace('_', ' ', (string) $name)));
    if ($name === '' || mb_strtoupper($name) !== $name) {
        return (string) apply_filters('snowy_wp_station_name', $name);
    }

    $name = preg_replace_callback('/(^|[\s\-\/(])(\p{L})/u', static function ($m) {
        return $m[1] . mb_strtoupper($m[2]);
    }, mb_strtolower($name));

    return (string) apply_filters('snowy_wp_station_name', $name);
}

function snowy_wp_shortcode_clima_lluvia($atts = [])
{
    $atts = snowy_wp_climate_atts(['anio' => ''], $atts, 'snowy_clima_lluvia');
    snowy_wp_use_styles();

    $place = snowy_wp_climate_place($atts);
    $rain = $place ? snowy_wp_climate_rain($place, $atts['anio']) : [];
    if (!$rain || $rain['percentOfNormal'] === null || !$rain['stations']) {
        return '';
    }

    $stations = array_values(array_filter($rain['stations'], static fn($s) => $s['percentOfNormal'] !== null));
    if (!$stations) {
        return '';
    }

    $total = array_sum(array_map(static fn($s) => (float) $s['totalMm'], $stations)) / count($stations);
    $normal = array_sum(array_map(static fn($s) => (float) $s['normalToDateMm'], $stations)) / count($stations);
    $dates = array_filter(array_map(static fn($s) => $s['lastDate'], $stations));
    $until = $dates ? min($dates) : '';
    $scale = max($total, $normal) * 1.08;
    $name = snowy_wp_climate_name($place, ['name' => $rain['region']['name'] ?? '']);
    $year = (int) $rain['year'];
    $count = count($stations);
    $percent = (float) $rain['percentOfNormal'];

    $rows = array_map(static function ($s) {
        return [
            'name'    => snowy_wp_station_title($s['name']),
            'total'   => snowy_wp_num_unit($s['totalMm'], 0, 'mm'),
            'normal'  => snowy_wp_num_unit($s['normalToDateMm'], 0, 'mm'),
            'percent' => snowy_wp_num($s['percentOfNormal'], 0) . SNOWY_WP_NBSP . '%',
            'until'   => snowy_wp_long_date($s['lastDate']),
            'filled'  => (int) $s['filledDays'],
        ];
    }, $stations);

    $names = implode(', ', array_map(static fn($r) => $r['name'], $rows));
    $filled = array_sum(array_map(static fn($r) => $r['filled'], $rows));

    return snowy_wp_render_widget('clima-lluvia', [
        'title'    => snowy_wp_climate_title($atts, sprintf(
            /* translators: 1: año, 2: lugar */
            __('La lluvia de %1$s en %2$s', 'snowy-wp'),
            $year,
            $name
        )),
        'subtitle' => sprintf(
            /* translators: %s: fecha */
            __('Acumulado desde el 1 de enero hasta el %s, frente a lo normal en 1991-2020', 'snowy-wp'),
            snowy_wp_long_date($until)
        ),
        'name'     => $name,
        'figure'   => snowy_wp_num($percent, 0) . SNOWY_WP_NBSP . '%',
        'figure_note' => $percent >= 100 ? __('de lo normal a esta fecha: más lluvia de la habitual', 'snowy-wp') : __('de lo normal a esta fecha: menos lluvia de la habitual', 'snowy-wp'),
        'tone'     => $percent >= 100 ? 'is-wet' : 'is-dry',
        'bars'     => [
            ['label' => sprintf(__('%d hasta ahora', 'snowy-wp'), $year), 'text' => snowy_wp_num_unit($total, 0, 'mm'), 'width' => snowy_wp_pct($total / $scale * 100), 'class' => 'is-year'],
            ['label' => __('Lo normal a esta fecha', 'snowy-wp'), 'text' => snowy_wp_num_unit($normal, 0, 'mm'), 'width' => snowy_wp_pct($normal / $scale * 100), 'class' => 'is-normal'],
        ],
        'stations' => $rows,
        'basis'    => sprintf(
            /* translators: 1: numero de estaciones, 2: lugar, 3: nombres */
            _n(
                'Calculado con %1$s pluviómetro de %2$s con serie larga y normal de 1991-2020: %3$s.',
                'Calculado con %1$s pluviómetros de %2$s con serie larga y normal de 1991-2020, como media de sus porcentajes: %3$s.',
                $count,
                'snowy-wp'
            ),
            $count,
            $name,
            $names
        ) . ($filled > 0 ? ' ' . sprintf(
            /* translators: %d: dias */
            _n('%d día sin dato se completa con lo normal de su fecha.', '%d días sin dato se completan con lo normal de su fecha.', $filled, 'snowy-wp'),
            $filled
        ) : ''),
        'credit'   => '<p class="snowy-wp-credit">' . sprintf(
            /* translators: %s: enlace a Snowy */
            __('Fuente: GHCN-Daily (NOAA NCEI) y AEMET. Datos elaborados por %s.', 'snowy-wp'),
            '<a href="' . esc_url(SNOWY_WP_SITE . '/clima') . '" target="_blank" rel="noopener"><strong>Snowy</strong></a>'
        ) . '</p>',
    ], $atts);
}
add_shortcode('snowy_clima_lluvia', 'snowy_wp_shortcode_clima_lluvia');
