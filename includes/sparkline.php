<?php

if (!defined('ABSPATH')) {
    exit;
}

const SNOWY_WP_TTL_EVOLUTION = 900;
const SNOWY_WP_SPARK_HOURS = 24;
const SNOWY_WP_SPARK_MAX_POINTS = 60;

/**
 * Serie de temperatura de las ultimas horas, normalizada.
 *
 * Cada red devuelve la evolucion con una forma distinta: las oficiales dan
 * timestamp en milisegundos y temperatura suelta, y la de aficionados agrupa por
 * intervalos con epoch en segundos y las medias dentro de metric. Aqui se
 * unifica en pares [instante, grados] para que quien pinta no tenga que saberlo.
 */
function snowy_wp_evolution($network, $id, $hours = SNOWY_WP_SPARK_HOURS)
{
    $hours = max(1, min(48, (int) $hours));

    return snowy_wp_cached('evo_' . md5("$network|$id|$hours"), SNOWY_WP_TTL_EVOLUTION, static function () use ($network, $id, $hours) {
        $data = snowy_wp_get(sprintf(
            '/stations-metrics/%s/%s?evolutionHours=%d',
            rawurlencode(strtolower($network)),
            rawurlencode($id),
            $hours
        ));

        if (!is_array($data) || empty($data['evolution']) || !is_array($data['evolution'])) {
            return null;
        }

        $points = [];
        foreach ($data['evolution'] as $p) {
            if (!is_array($p)) {
                continue;
            }

            if (isset($p['timestamp'], $p['temperature']) && $p['temperature'] !== null) {
                $points[] = [(int) ($p['timestamp'] / 1000), (float) $p['temperature']];
                continue;
            }

            $temp = $p['metric']['tempAvg'] ?? null;
            if (isset($p['epoch']) && $temp !== null) {
                $points[] = [(int) $p['epoch'], (float) $temp];
            }
        }

        if (count($points) < 3) {
            return null;
        }

        usort($points, static fn($a, $b) => $a[0] <=> $b[0]);

        return snowy_wp_thin($points, SNOWY_WP_SPARK_MAX_POINTS);
    });
}

/**
 * Reduce la serie a un numero manejable de puntos conservando extremos.
 *
 * La red de aficionados devuelve una lectura cada cinco minutos: casi trescientos
 * puntos para un grafico de doscientos sesenta pixeles de ancho, que abultaban
 * seis kilobytes de SVG sin dibujar nada que se vea.
 */
function snowy_wp_thin($points, $max)
{
    $total = count($points);
    if ($total <= $max) {
        return $points;
    }

    $paso = $total / $max;
    $out  = [];
    for ($i = 0; $i < $max; $i++) {
        $out[] = $points[(int) floor($i * $paso)];
    }

    // El ultimo punto es el dato actual y no puede perderse por el redondeo.
    $ultimo = $points[$total - 1];
    if (end($out)[0] !== $ultimo[0]) {
        $out[] = $ultimo;
    }

    return $out;
}

/**
 * Grafico de linea en SVG, sin dependencias.
 *
 * Es decorativo respecto al dato que ya esta escrito en cifras al lado, asi que
 * se oculta a los lectores de pantalla en vez de intentar narrarlo; el maximo y
 * el minimo del periodo van en texto debajo.
 */
function snowy_wp_sparkline($points, $width = 260, $height = 48)
{
    if (!$points || count($points) < 3) {
        return '';
    }

    $temps = array_map(static fn($p) => $p[1], $points);
    $min = min($temps);
    $max = max($temps);
    $span = $max - $min;
    if ($span < 0.1) {
        $span = 1.0;
    }

    $first = $points[0][0];
    $last  = $points[count($points) - 1][0];
    $range = max(1, $last - $first);

    $pad = 4;
    $coords = [];
    foreach ($points as $p) {
        $x = $pad + ($p[0] - $first) / $range * ($width - 2 * $pad);
        $y = $height - $pad - ($p[1] - $min) / $span * ($height - 2 * $pad);
        $coords[] = round($x, 1) . ',' . round($y, 1);
    }

    $line = implode(' ', $coords);
    $area = $line . ' ' . round($width - $pad, 1) . ',' . $height . ' ' . $pad . ',' . $height;

    return sprintf(
        '<svg class="snowy-wp-spark" viewBox="0 0 %1$d %2$d" width="%1$d" height="%2$d" '
            . 'preserveAspectRatio="none" role="presentation" aria-hidden="true" focusable="false">'
            . '<polygon points="%3$s" class="snowy-wp-spark-area"/>'
            . '<polyline points="%4$s" class="snowy-wp-spark-line"/>'
            . '</svg>',
        $width,
        $height,
        esc_attr($area),
        esc_attr($line)
    );
}

/**
 * Pie del grafico: cuantas horas cubre y entre que valores se ha movido.
 */
function snowy_wp_sparkline_caption($points, $hours)
{
    $temps = array_map(static fn($p) => $p[1], $points);

    return sprintf(
        /* translators: 1: numero de horas, 2: temperatura minima, 3: temperatura maxima */
        __('Últimas %1$s horas · entre %2$s y %3$s', 'snowy-wp'),
        esc_html($hours),
        esc_html(snowy_wp_temp(min($temps))),
        esc_html(snowy_wp_temp(max($temps)))
    );
}
