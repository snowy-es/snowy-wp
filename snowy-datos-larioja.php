<?php
/**
 * Plugin Name: Snowy — datos de La Rioja
 * Description: Shortcodes que pintan datos en vivo de la red de estaciones de Snowy, nuestra plataforma meteorológica, para las páginas de datos de La Rioja.
 * Version: 1.0.0
 * Author: Jorge Carrera Diez
 * Author URI: https://snowy.es
 */

if (!defined('ABSPATH')) {
    exit;
}

const SNOWY_LR_ENDPOINT = 'https://api.snowy.es/stations/markers';
const SNOWY_LR_STATE    = 'La Rioja';
const SNOWY_LR_TTL      = 600;
const SNOWY_LR_CACHE    = 'snowy_lr_stations';

/**
 * Clave de API de Snowy.
 *
 * Se lee de una constante definida en wp-config.php, NUNCA escrita aquí: este
 * fichero vive en el repositorio y acabaría en git.
 *
 * La llamada sale de PHP, del servidor, así que la clave no llega al navegador
 * del visitante. Si algún día el plugin pidiera los datos con JavaScript desde
 * el cliente, habría que quitar la clave: sería pública.
 */
function snowy_lr_auth_headers()
{
    if (!defined('SNOWY_API_KEY') || !SNOWY_API_KEY) {
        return [];
    }

    return ['x-api-key' => SNOWY_API_KEY];
}

/**
 * Estaciones de la red Snowy en La Rioja, cacheadas para no golpear la API en
 * cada visita. Devuelve [] si la API no responde: las plantillas deben
 * degradar sin romper la página.
 */
function snowy_lr_stations()
{
    $cached = get_transient(SNOWY_LR_CACHE);
    if (is_array($cached)) {
        return $cached;
    }

    $response = wp_remote_get(SNOWY_LR_ENDPOINT, [
        'timeout' => 8,
        'headers' => snowy_lr_auth_headers(),
    ]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return [];
    }

    $all = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($all)) {
        return [];
    }

    $stations = array_values(array_filter($all, static function ($s) {
        return isset($s['state']) && $s['state'] === SNOWY_LR_STATE;
    }));

    set_transient(SNOWY_LR_CACHE, $stations, SNOWY_LR_TTL);

    return $stations;
}

/**
 * Congela los datos de un widget dentro del post.
 *
 * Un widget en una pagina de datos debe ir siempre en vivo; dentro de un post
 * de actualidad, no: si el aviso caduca o la temperatura cambia, el articulo se
 * queda hablando de algo que ya no se ve. Con modo="snapshot" se guardan los
 * datos la primera vez que se renderiza y a partir de ahi se pintan esos.
 *
 * El snapshot vive en un post_meta del propio post, asi que viaja con el y no
 * depende de que la API siga devolviendo lo mismo.
 */
function snowy_lr_snapshot($clave, callable $cargar)
{
    $post_id = get_the_ID();
    if (!$post_id) {
        return $cargar();
    }

    $meta = '_snowy_snap_' . md5($clave);
    $guardado = get_post_meta($post_id, $meta, true);

    if (is_array($guardado) && isset($guardado['data'])) {
        return $guardado;
    }

    $snap = ['data' => $cargar(), 'ts' => time()];
    update_post_meta($post_id, $meta, $snap);

    return $snap;
}

function snowy_lr_snapshot_note($ts)
{
    return sprintf(
        '<p class="snowy-lr-credit snowy-lr-frozen">Datos congelados del <strong>%s</strong>, tal y como estaban cuando se publicó este artículo. No se actualizan.</p>',
        esc_html(wp_date('j \d\e F \d\e Y \a \l\a\s H:i', $ts))
    );
}

/**
 * Filtra las estaciones por una lista de identificadores separados por comas.
 * Permite mostrar solo las estaciones propias en vez de toda la red.
 */
function snowy_lr_filter_ids($stations, $ids)
{
    $ids = array_filter(array_map('trim', explode(',', (string) $ids)));
    if (!$ids) {
        return $stations;
    }
    $ids = array_map('strtolower', $ids);

    return array_values(array_filter($stations, static function ($s) use ($ids) {
        return in_array(strtolower($s['stationId'] ?? ''), $ids, true);
    }));
}

/**
 * WUNDERGROUND nunca se nombra como tal de cara al lector.
 */
function snowy_lr_network_label($network)
{
    return $network === 'AEMET' ? 'AEMET' : 'estación de aficionado';
}

function snowy_lr_network_badge($network)
{
    $isAemet = $network === 'AEMET';
    return sprintf('<span class="snowy-lr-net%s">%s</span>',
        $isAemet ? ' is-aemet' : '',
        esc_html(snowy_lr_network_label($network)));
}

/**
 * Enlace a la ficha de la estacion en Snowy. Es el que convierte el widget en
 * una puerta de entrada: el lector llega al dato aqui y sigue en snowy.es.
 */
function snowy_lr_station_link($station)
{
    $name = $station['stationName'] ?? '';
    $id   = $station['stationId'] ?? '';

    if (!$id) {
        return esc_html($name);
    }

    return sprintf(
        '<a href="https://snowy.es/stations/%s" target="_blank" rel="noopener" class="snowy-lr-link">%s</a>',
        rawurlencode($id),
        esc_html($name)
    );
}

function snowy_lr_temp($value)
{
    return $value === null ? '—' : number_format((float) $value, 1, ',', '.') . ' °C';
}

function snowy_lr_credit()
{
    $count = count(snowy_lr_stations());
    $when  = wp_date('H:i');

    return '<p class="snowy-lr-credit">Datos de las <strong>' . esc_html($count) . ' estaciones de La Rioja</strong> '
        . 'de la red de <a href="https://snowy.es" target="_blank" rel="noopener"><strong>Snowy</strong></a>, '
        . 'nuestra plataforma meteorológica, con AEMET y estaciones de aficionados. '
        . 'Actualizado a las ' . esc_html($when) . '.</p>';
}

/**
 * [snowy_extremos] — máximas y mínimas del día en La Rioja.
 * Usa today.tmax / today.tmin, que son extremos ya cerrados y homogéneos
 * entre redes, a diferencia de precipitation.
 */
function snowy_lr_shortcode_extremos($atts)
{
    $atts = shortcode_atts(['limite' => 8], $atts, 'snowy_extremos');
    $stations = snowy_lr_stations();
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
    <div class="snowy-lr-extremos snowy-lr-wrap">
        <div class="snowy-lr-wrap">
            <div class="snowy-lr-head"><h3>Las más cálidas de hoy</h3><span class="snowy-lr-tag">máximas</span></div>
            <table class="snowy-lr-table">
                <thead><tr><th>Estación</th><th>Máxima</th><th>Red</th></tr></thead>
                <tbody>
                <?php foreach ($hot as $s) : ?>
                    <tr>
                        <td><?php echo snowy_lr_station_link($s); ?></td>
                        <td class="snowy-lr-val"><?php echo esc_html(snowy_lr_temp($s['today']['tmax'])); ?></td>
                        <td><?php echo snowy_lr_network_badge($s['network'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="snowy-lr-wrap">
            <div class="snowy-lr-head"><h3>Las más frías de hoy</h3><span class="snowy-lr-tag">mínimas</span></div>
            <table class="snowy-lr-table">
                <thead><tr><th>Estación</th><th>Mínima</th><th>Red</th></tr></thead>
                <tbody>
                <?php foreach ($cold as $s) : ?>
                    <tr>
                        <td><?php echo snowy_lr_station_link($s); ?></td>
                        <td class="snowy-lr-val"><?php echo esc_html(snowy_lr_temp($s['today']['tmin'])); ?></td>
                        <td><?php echo snowy_lr_network_badge($s['network'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php echo snowy_lr_credit(); ?>
    <?php
    return ob_get_clean();
}
add_shortcode('snowy_extremos', 'snowy_lr_shortcode_extremos');

/**
 * [snowy_estaciones] — todas las estaciones riojanas con su dato actual.
 */
function snowy_lr_shortcode_estaciones($atts = [])
{
    $atts = shortcode_atts(['ids' => '', 'modo' => 'vivo', 'titulo' => ''], (array) $atts, 'snowy_estaciones');
    $congelado = $atts['modo'] === 'snapshot';

    if ($congelado) {
        $snap = snowy_lr_snapshot('estaciones|' . $atts['ids'], static function () use ($atts) {
            return snowy_lr_filter_ids(snowy_lr_stations(), $atts['ids']);
        });
        $stations = $snap['data'];
        $ts = $snap['ts'];
    } else {
        $stations = snowy_lr_filter_ids(snowy_lr_stations(), $atts['ids']);
        $ts = null;
    }

    if (!$stations) {
        return '';
    }

    usort($stations, static fn($a, $b) => strcmp($a['stationName'], $b['stationName']));

    ob_start(); ?>
    <div class="snowy-lr-wrap">
    <div class="snowy-lr-head">
        <h3><?php echo esc_html($atts['titulo'] !== '' ? $atts['titulo'] : 'Estaciones de La Rioja'); ?></h3>
        <span class="snowy-lr-tag"><?php echo $congelado ? 'dato histórico' : 'en vivo'; ?></span>
    </div>
    <table class="snowy-lr-table snowy-lr-estaciones">
        <thead>
        <tr><th>Estación</th><th>Ahora</th><th>Máx.</th><th>Mín.</th><th>Humedad</th><th>Red</th></tr>
        </thead>
        <tbody>
        <?php foreach ($stations as $s) : ?>
            <tr>
                <td><?php echo snowy_lr_station_link($s); ?></td>
                <td class="snowy-lr-val"><?php echo esc_html(snowy_lr_temp($s['current'] ?? null)); ?></td>
                <td><?php echo esc_html(snowy_lr_temp($s['today']['tmax'] ?? null)); ?></td>
                <td><?php echo esc_html(snowy_lr_temp($s['today']['tmin'] ?? null)); ?></td>
                <td><?php echo isset($s['humidity']) && $s['humidity'] !== null ? esc_html($s['humidity']) . ' %' : '—'; ?></td>
                <td><?php echo snowy_lr_network_badge($s['network'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php echo $congelado ? snowy_lr_snapshot_note($ts) : snowy_lr_credit(); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('snowy_estaciones', 'snowy_lr_shortcode_estaciones');

/**
 * [snowy_viento] — rachas máximas del día.
 */
function snowy_lr_shortcode_viento($atts)
{
    $atts = shortcode_atts(['limite' => 8], $atts, 'snowy_viento');
    $stations = array_filter(snowy_lr_stations(), static fn($s) => isset($s['today']['gustMax']) && $s['today']['gustMax'] !== null);
    if (!$stations) {
        return '';
    }

    usort($stations, static fn($a, $b) => $b['today']['gustMax'] <=> $a['today']['gustMax']);
    $stations = array_slice($stations, 0, max(1, (int) $atts['limite']));

    ob_start(); ?>
    <div class="snowy-lr-wrap">
    <div class="snowy-lr-head"><h3>Rachas más fuertes de hoy</h3><span class="snowy-lr-tag">viento</span></div>
    <table class="snowy-lr-table">
        <thead><tr><th>Estación</th><th>Racha máxima</th><th>Red</th></tr></thead>
        <tbody>
        <?php foreach ($stations as $s) : ?>
            <tr>
                <td><?php echo snowy_lr_station_link($s); ?></td>
                <td class="snowy-lr-val"><?php echo esc_html(number_format((float) $s['today']['gustMax'], 1, ',', '.')); ?> km/h</td>
                <td><?php echo snowy_lr_network_badge($s['network'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php echo snowy_lr_credit(); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('snowy_viento', 'snowy_lr_shortcode_viento');

function snowy_lr_styles()
{
    $css = <<<CSS
.snowy-lr-wrap{--sn-ink:#0f172a;--sn-mute:#64748b;--sn-line:#e2e8f0;--sn-accent:#0369a1;--sn-soft:#f8fafc;
  border:1px solid var(--sn-line);border-radius:14px;background:#fff;overflow:hidden;margin:1.75rem 0;
  box-shadow:0 1px 2px rgba(15,23,42,.04),0 8px 24px -12px rgba(15,23,42,.12)}
.snowy-lr-head{display:flex;align-items:baseline;justify-content:space-between;gap:1rem;flex-wrap:wrap;
  padding:.9rem 1.15rem;background:var(--sn-soft);border-bottom:1px solid var(--sn-line)}
.snowy-lr-head h3{margin:0;font-size:1rem;font-weight:700;color:var(--sn-ink);letter-spacing:-.01em}
.snowy-lr-head .snowy-lr-tag{font-size:.7rem;text-transform:uppercase;letter-spacing:.09em;
  color:var(--sn-accent);font-weight:700}
.snowy-lr-extremos{display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:0}
.snowy-lr-extremos>.snowy-lr-wrap{margin:0;border-radius:0;border:0;box-shadow:none;
  border-right:1px solid var(--sn-line)}
.snowy-lr-extremos>.snowy-lr-wrap:last-child{border-right:0}
.snowy-lr-table{width:100%;border-collapse:collapse;margin:0;font-size:.93rem}
.snowy-lr-table th{font-size:.68rem;text-transform:uppercase;letter-spacing:.07em;color:var(--sn-mute);
  font-weight:600;text-align:left;padding:.6rem 1.15rem;border-bottom:1px solid var(--sn-line);white-space:nowrap}
.snowy-lr-table td{padding:.62rem 1.15rem;border-bottom:1px solid #f1f5f9;color:var(--sn-ink);vertical-align:middle}
.snowy-lr-table tbody tr:last-child td{border-bottom:0}
.snowy-lr-table tbody tr:hover{background:#f8fafc}
.snowy-lr-val{font-variant-numeric:tabular-nums;font-weight:700;white-space:nowrap}
.snowy-lr-net{display:inline-block;font-size:.68rem;padding:.16rem .5rem;border-radius:999px;
  background:#f1f5f9;color:var(--sn-mute);white-space:nowrap}
.snowy-lr-net.is-aemet{background:#e0f2fe;color:#075985}
.snowy-lr-link{color:var(--sn-ink);text-decoration:none;font-weight:600;
  border-bottom:1px solid rgba(3,105,161,.28);transition:border-color .15s,color .15s}
.snowy-lr-link:hover{color:var(--sn-accent);border-bottom-color:var(--sn-accent)}
.snowy-lr-credit{font-size:.78rem;line-height:1.55;color:var(--sn-mute);margin:0;
  padding:.75rem 1.15rem;background:var(--sn-soft);border-top:1px solid var(--sn-line)}
.snowy-lr-credit a{color:var(--sn-accent)}
.snowy-lr-card{border:1px solid var(--sn-line);border-left:4px solid var(--sn-accent);border-radius:12px;
  padding:1.05rem 1.25rem;margin:1.75rem 0;background:linear-gradient(180deg,#f8fafc,#fff)}
.snowy-lr-card-eyebrow{font-size:.68rem;text-transform:uppercase;letter-spacing:.09em;color:var(--sn-accent);
  margin:0 0 .25rem;font-weight:700}
.snowy-lr-card-title{margin:0 0 .85rem;font-size:1.15rem;font-weight:700}
.snowy-lr-card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(104px,1fr));gap:.85rem}
.snowy-lr-card-grid span{display:block;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;
  color:var(--sn-mute);margin-bottom:.12rem}
.snowy-lr-card-grid strong{font-size:1.2rem;font-variant-numeric:tabular-nums}
.snowy-lr-card-foot{margin:.9rem 0 0;font-size:.76rem;color:var(--sn-mute)}
@media(max-width:640px){
  .snowy-lr-table th:nth-child(3),.snowy-lr-table td:nth-child(3){display:none}
  .snowy-lr-table{font-size:.88rem}
  .snowy-lr-table th,.snowy-lr-table td{padding:.55rem .8rem}
  .snowy-lr-head{padding:.8rem}
  .snowy-lr-credit{padding:.7rem .8rem}
}
CSS;
    wp_register_style('snowy-lr', false);
    wp_enqueue_style('snowy-lr');
    wp_add_inline_style('snowy-lr', $css);
}
add_action('wp_enqueue_scripts', 'snowy_lr_styles');

/**
 * [snowy_estacion id="9115X"] — ficha de una estación concreta, para incrustar
 * dentro de un post junto al dato del que se está hablando.
 */
function snowy_lr_shortcode_estacion($atts)
{
    $atts = shortcode_atts(['id' => '', 'nombre' => ''], $atts, 'snowy_estacion');
    $stations = snowy_lr_stations();
    if (!$stations) {
        return '';
    }

    $needle = strtolower(trim($atts['id'] !== '' ? $atts['id'] : $atts['nombre']));
    if ($needle === '') {
        return '';
    }

    $found = null;
    foreach ($stations as $s) {
        if (strtolower($s['stationId'] ?? '') === $needle
            || strtolower($s['stationName'] ?? '') === $needle) {
            $found = $s;
            break;
        }
    }
    if (!$found) {
        return '';
    }

    $gust = isset($found['today']['gustMax']) && $found['today']['gustMax'] !== null
        ? number_format((float) $found['today']['gustMax'], 1, ',', '.') . ' km/h'
        : '—';

    ob_start(); ?>
    <aside class="snowy-lr-card">
        <p class="snowy-lr-card-eyebrow">Dato en vivo · red de Snowy</p>
        <h4 class="snowy-lr-card-title"><?php echo snowy_lr_station_link($found); ?></h4>
        <div class="snowy-lr-card-grid">
            <div><span>Ahora</span><strong><?php echo esc_html(snowy_lr_temp($found['current'] ?? null)); ?></strong></div>
            <div><span>Máxima hoy</span><strong><?php echo esc_html(snowy_lr_temp($found['today']['tmax'] ?? null)); ?></strong></div>
            <div><span>Mínima hoy</span><strong><?php echo esc_html(snowy_lr_temp($found['today']['tmin'] ?? null)); ?></strong></div>
            <div><span>Racha máx.</span><strong><?php echo esc_html($gust); ?></strong></div>
        </div>
        <p class="snowy-lr-card-foot">
            <?php echo snowy_lr_network_badge($found['network'] ?? ''); ?>
            · medido por <a href="https://snowy.es" target="_blank" rel="noopener"><strong>Snowy</strong></a>,
            nuestra plataforma meteorológica
        </p>
    </aside>
    <?php
    return ob_get_clean();
}
add_shortcode('snowy_estacion', 'snowy_lr_shortcode_estacion');

const SNOWY_LR_HAZARDS_ENDPOINT = 'https://api.snowy.es/hazards';
const SNOWY_LR_HAZARDS_CACHE    = 'snowy_lr_hazards';
const SNOWY_LR_HAZARDS_TTL      = 900;

/**
 * Avisos de AEMET filtrados por comunidad. La fuente devuelve tres días
 * (today, tomorrow, dayAfterTomorrow) para toda España.
 */
function snowy_lr_hazards()
{
    $cached = get_transient(SNOWY_LR_HAZARDS_CACHE);
    if (is_array($cached)) {
        return $cached;
    }

    $response = wp_remote_get(SNOWY_LR_HAZARDS_ENDPOINT, [
        'timeout' => 8,
        'headers' => snowy_lr_auth_headers(),
    ]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return [];
    }

    $all = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($all)) {
        return [];
    }

    $days = [
        'today'            => 'Hoy',
        'tomorrow'         => 'Mañana',
        'dayAfterTomorrow' => 'Pasado mañana',
    ];

    $out = [];
    foreach ($days as $key => $label) {
        $items = array_values(array_filter($all[$key] ?? [], static function ($a) {
            return isset($a['ccaa']) && $a['ccaa'] === 'La Rioja';
        }));
        if ($items) {
            $out[] = ['label' => $label, 'items' => $items];
        }
    }

    set_transient(SNOWY_LR_HAZARDS_CACHE, $out, SNOWY_LR_HAZARDS_TTL);

    return $out;
}

function snowy_lr_risk_badge($level)
{
    $level = strtolower((string) $level);
    $known = ['amarillo', 'naranja', 'rojo'];
    $class = in_array($level, $known, true) ? 'is-' . $level : '';

    return sprintf('<span class="snowy-lr-risk %s">%s</span>', esc_attr($class), esc_html(ucfirst($level)));
}

function snowy_lr_hazard_window($aviso)
{
    $start = isset($aviso['startTime']) ? strtotime($aviso['startTime']) : null;
    $end   = isset($aviso['endTime']) ? strtotime($aviso['endTime']) : null;
    if (!$start || !$end) {
        return '';
    }

    return sprintf('de %s a %s', wp_date('H:i', $start), wp_date('H:i', $end));
}

/**
 * [snowy_avisos] — avisos de AEMET vigentes en La Rioja.
 *
 * Si no hay ninguno lo dice explícitamente: en un portal meteorológico el "sin
 * avisos" es información, no un hueco vacío.
 */
function snowy_lr_shortcode_avisos($atts = [])
{
    $atts = shortcode_atts(['modo' => 'vivo'], (array) $atts, 'snowy_avisos');
    $congelado = $atts['modo'] === 'snapshot';

    if ($congelado) {
        $snap = snowy_lr_snapshot('avisos', 'snowy_lr_hazards');
        $dias = $snap['data'];
        $ts = $snap['ts'];
    } else {
        $dias = snowy_lr_hazards();
        $ts = null;
    }

    ob_start(); ?>
    <div class="snowy-lr-wrap">
        <div class="snowy-lr-head">
            <h3>Avisos de AEMET en La Rioja</h3>
            <span class="snowy-lr-tag"><?php echo $congelado ? 'aviso histórico' : ($dias ? 'vigentes' : 'sin avisos'); ?></span>
        </div>
        <?php if (!$dias) : ?>
            <p class="snowy-lr-empty">
                <strong>No hay avisos activos en La Rioja</strong> para los próximos tres días.
            </p>
        <?php else : ?>
            <?php foreach ($dias as $dia) : ?>
                <p class="snowy-lr-day"><?php echo esc_html($dia['label']); ?></p>
                <table class="snowy-lr-table">
                    <thead><tr><th>Nivel</th><th>Fenómeno</th><th>Zona</th><th>Horario</th></tr></thead>
                    <tbody>
                    <?php foreach ($dia['items'] as $a) : ?>
                        <tr>
                            <td><?php echo snowy_lr_risk_badge($a['riskLevel'] ?? ''); ?></td>
                            <td class="snowy-lr-val"><?php echo esc_html($a['type'] ?? '—'); ?></td>
                            <td><?php echo esc_html($a['zone'] ?? '—'); ?></td>
                            <td><?php echo esc_html(snowy_lr_hazard_window($a)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($congelado) : ?>
            <?php echo snowy_lr_snapshot_note($ts); ?>
        <?php else : ?>
        <p class="snowy-lr-credit">
            Avisos oficiales de <strong>AEMET</strong>, recogidos por
            <a href="https://snowy.es" target="_blank" rel="noopener"><strong>Snowy</strong></a>,
            nuestra plataforma meteorológica. Puedes verlos sobre el
            <a href="https://snowy.es/map?lat=42.35&amp;lng=-2.45&amp;zoom=9" target="_blank" rel="noopener">mapa interactivo</a>.
            La fuente que manda siempre es
            <a href="https://www.aemet.es/es/eltiempo/prediccion/avisos" target="_blank" rel="noopener nofollow">AEMET</a>.
        </p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('snowy_avisos', 'snowy_lr_shortcode_avisos');

function snowy_lr_hazard_styles()
{
    $css = '.snowy-lr-risk{display:inline-block;font-size:.7rem;font-weight:700;padding:.2rem .6rem;'
        . 'border-radius:999px;background:#f1f5f9;color:#475569;white-space:nowrap}'
        . '.snowy-lr-risk.is-amarillo{background:#fef9c3;color:#854d0e}'
        . '.snowy-lr-risk.is-naranja{background:#ffedd5;color:#9a3412}'
        . '.snowy-lr-risk.is-rojo{background:#fee2e2;color:#991b1b}'
        . '.snowy-lr-day{margin:0;padding:.6rem 1.15rem .1rem;font-size:.72rem;text-transform:uppercase;'
        . 'letter-spacing:.07em;color:#64748b;font-weight:700}'
        . '.snowy-lr-empty{margin:0;padding:1.15rem;color:#0f172a;font-size:.95rem}'
        . '.snowy-lr-frozen{background:#fffbeb;border-top-color:#fde68a;color:#854d0e}';
    wp_add_inline_style('snowy-lr', $css);
}
add_action('wp_enqueue_scripts', 'snowy_lr_hazard_styles', 11);

/**
 * Página de ayuda en el admin: los shortcodes disponibles, con ejemplo y
 * copiado a un clic. Se insertan escribiéndolos en cualquier editor, o con el
 * bloque "Shortcode" de Gutenberg.
 */
function snowy_lr_admin_menu()
{
    add_menu_page(
        'Widgets de Snowy',
        'Widgets Snowy',
        'edit_posts',
        'snowy-widgets',
        'snowy_lr_admin_page',
        'dashicons-cloud',
        58
    );
}
add_action('admin_menu', 'snowy_lr_admin_menu');

function snowy_lr_admin_page()
{
    $shortcodes = [
        [
            'code'  => '[snowy_avisos]',
            'name'  => 'Avisos de AEMET en La Rioja',
            'desc'  => 'Avisos vigentes para hoy, mañana y pasado, con nivel y horario. Si no hay ninguno, lo dice.',
        ],
        [
            'code'  => '[snowy_extremos limite="8"]',
            'name'  => 'Extremos del día',
            'desc'  => 'Las estaciones más cálidas y más frías de hoy. El atributo limite controla cuántas se muestran.',
        ],
        [
            'code'  => '[snowy_estaciones]',
            'name'  => 'Todas las estaciones',
            'desc'  => 'Tabla completa de las estaciones riojanas con temperatura actual, máxima, mínima y humedad.',
        ],
        [
            'code'  => '[snowy_viento limite="8"]',
            'name'  => 'Rachas de viento',
            'desc'  => 'Ranking de rachas máximas registradas hoy.',
        ],
        [
            'code'  => '[snowy_estacion id="9115X"]',
            'name'  => 'Ficha de una estación',
            'desc'  => 'Tarjeta con el dato de una estación concreta, para incrustar dentro de un artículo. Acepta id o nombre.',
        ],
    ];

    $stations = snowy_lr_stations();
    ?>
    <div class="wrap">
        <h1>Widgets de Snowy</h1>
        <p>
            Datos en vivo de <a href="https://snowy.es" target="_blank" rel="noopener"><strong>Snowy</strong></a>,
            nuestra plataforma meteorológica.
            <?php if ($stations) : ?>
                Conectado correctamente: <strong><?php echo count($stations); ?> estaciones</strong> de La Rioja.
            <?php else : ?>
                <strong style="color:#b32d2e">Sin conexión con la API.</strong>
                Revisa que <code>SNOWY_API_KEY</code> esté definida en <code>wp-config.php</code>.
            <?php endif; ?>
        </p>

        <h2>Cómo insertarlos</h2>
        <p>
            Copia el código y pégalo donde quieras que aparezca. En el editor de bloques,
            usa el bloque <strong>Shortcode</strong>; en el editor clásico, pégalo directamente en el texto.
        </p>

        <table class="widefat striped" style="max-width:900px">
            <thead><tr><th style="width:270px">Shortcode</th><th>Qué muestra</th></tr></thead>
            <tbody>
            <?php foreach ($shortcodes as $sc) : ?>
                <tr>
                    <td>
                        <code style="display:block;padding:.4rem;background:#f6f7f7;user-select:all"><?php echo esc_html($sc['code']); ?></code>
                    </td>
                    <td>
                        <strong><?php echo esc_html($sc['name']); ?></strong><br>
                        <span style="color:#646970"><?php echo esc_html($sc['desc']); ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($stations) : ?>
            <h2>Identificadores de estación</h2>
            <p>Para usar con <code>[snowy_estacion id="..."]</code>:</p>
            <table class="widefat striped" style="max-width:640px">
                <thead><tr><th>Estación</th><th style="width:130px">id</th></tr></thead>
                <tbody>
                <?php foreach ($stations as $s) : ?>
                    <tr>
                        <td><?php echo esc_html($s['stationName']); ?></td>
                        <td><code style="user-select:all"><?php echo esc_html($s['stationId']); ?></code></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Bloques de Gutenberg.
 *
 * Cada bloque reutiliza el render_callback del shortcode equivalente: la lógica
 * vive en un solo sitio. La previsualización dentro del editor la resuelve
 * ServerSideRender, que llama a ese mismo PHP por la REST API.
 */
function snowy_lr_register_blocks()
{
    if (!function_exists('register_block_type')) {
        return;
    }

    wp_register_script(
        'snowy-lr-blocks',
        plugins_url('assets/blocks.js', __FILE__),
        ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n'],
        '1.1.0',
        true
    );

    $blocks = [
        'snowy/avisos'     => ['cb' => 'snowy_lr_shortcode_avisos',     'attrs' => []],
        'snowy/estaciones' => ['cb' => 'snowy_lr_shortcode_estaciones', 'attrs' => []],
        'snowy/extremos'   => ['cb' => 'snowy_lr_shortcode_extremos',   'attrs' => ['limite' => ['type' => 'number', 'default' => 8]]],
        'snowy/viento'     => ['cb' => 'snowy_lr_shortcode_viento',     'attrs' => ['limite' => ['type' => 'number', 'default' => 8]]],
        'snowy/estacion'   => ['cb' => 'snowy_lr_shortcode_estacion',   'attrs' => ['id' => ['type' => 'string', 'default' => '']]],
    ];

    foreach ($blocks as $name => $conf) {
        register_block_type($name, [
            'api_version'     => 2,
            'editor_script'   => 'snowy-lr-blocks',
            'attributes'      => $conf['attrs'],
            'render_callback' => static function ($attributes) use ($conf) {
                return call_user_func($conf['cb'], $attributes);
            },
        ]);
    }
}
add_action('init', 'snowy_lr_register_blocks');

/**
 * Los estilos se encolan en wp_enqueue_scripts, que no corre dentro del editor.
 * Sin esto la previsualización saldría sin formato.
 */
function snowy_lr_editor_styles()
{
    snowy_lr_styles();
    snowy_lr_hazard_styles();
}
add_action('enqueue_block_assets', 'snowy_lr_editor_styles');

/**
 * Banda flotante de avisos, inyectada en el pie de la home.
 *
 * Solo aparece si hay avisos vigentes: sin avisos no molesta. Es descartable y
 * recuerda el descarte durante la sesión, para no castigar a quien ya la ha
 * visto.
 */
function snowy_lr_floating_alert()
{
    if (!is_front_page() && !is_home()) {
        return;
    }

    $dias = snowy_lr_hazards();
    if (!$dias) {
        return;
    }

    $hoy = null;
    foreach ($dias as $d) {
        if ($d['label'] === 'Hoy') {
            $hoy = $d;
            break;
        }
    }
    $bloque = $hoy ?: $dias[0];
    $items  = $bloque['items'];
    $peor   = 'amarillo';
    foreach ($items as $a) {
        $nivel = strtolower($a['riskLevel'] ?? '');
        if ($nivel === 'rojo') { $peor = 'rojo'; break; }
        if ($nivel === 'naranja') { $peor = 'naranja'; }
    }

    $tipos = array_values(array_unique(array_map(static function ($a) {
        return $a['type'] ?? '';
    }, $items)));

    ?>
    <div class="snowy-alert is-<?php echo esc_attr($peor); ?>" id="snowy-alert" role="status">
        <div class="snowy-alert__body">
            <span class="snowy-alert__level"><?php echo esc_html(ucfirst($peor)); ?></span>
            <span class="snowy-alert__text">
                <strong><?php echo esc_html(implode(' y ', array_slice($tipos, 0, 2))); ?></strong>
                <span class="snowy-alert__meta"><?php echo esc_html(ucfirst(strtolower($bloque['label']))); ?><?php
                    echo count($items) > 1 ? ' · ' . count($items) . ' zonas' : ''; ?></span>
            </span>
            <a class="snowy-alert__link" href="https://snowy.es/map?lat=42.35&lng=-2.45&zoom=9" target="_blank" rel="noopener">Ver en el mapa</a>
        </div>
        <button class="snowy-alert__close" aria-label="Cerrar aviso" onclick="this.parentNode.remove();try{sessionStorage.setItem('snowyAlertOff','1')}catch(e){}">&times;</button>
    </div>
    <script>
    (function(){
      var e = document.getElementById('snowy-alert');
      if (!e) return;
      try { if (sessionStorage.getItem('snowyAlertOff')) e.remove(); } catch (err) {}
    })();
    </script>
    <style>
    .snowy-alert{position:fixed;left:50%;transform:translateX(-50%);bottom:18px;z-index:9999;
      display:flex;align-items:center;gap:.6rem;max-width:min(680px,calc(100% - 24px));
      padding:.6rem .7rem .6rem 1rem;border-radius:999px;background:#fff;
      box-shadow:0 8px 30px -8px rgba(15,23,42,.3),0 0 0 1px rgba(15,23,42,.06);
      font-size:.9rem;line-height:1.35;animation:snowyAlertIn .35s ease-out}
    @keyframes snowyAlertIn{from{opacity:0;transform:translate(-50%,14px)}to{opacity:1;transform:translate(-50%,0)}}
    @media(max-width:680px){.snowy-alert{animation-name:snowyAlertDown}}
    .snowy-alert__body{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;min-width:0}
    .snowy-alert__level{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;
      padding:.22rem .6rem;border-radius:999px;white-space:nowrap}
    .snowy-alert.is-amarillo .snowy-alert__level{background:#fef9c3;color:#854d0e}
    .snowy-alert.is-naranja .snowy-alert__level{background:#ffedd5;color:#9a3412}
    .snowy-alert.is-rojo .snowy-alert__level{background:#fee2e2;color:#991b1b}
    .snowy-alert__text{color:#0f172a;min-width:0}
    .snowy-alert__count{color:#64748b;font-size:.82rem}
    .snowy-alert__link{color:#0369a1;font-weight:600;text-decoration:none;white-space:nowrap}
    .snowy-alert__link:hover{text-decoration:underline}
    .snowy-alert__close{border:0;background:transparent;font-size:1.35rem;line-height:1;color:#94a3b8;
      cursor:pointer;padding:0 .35rem}
    .snowy-alert__close:hover{color:#0f172a}
    /* En movil deja de ser flotante: se coloca al principio del body y empuja
       al contenido. Con position:fixed siempre acababa pisando algo, y el
       banner de cookies tiene prioridad legal sobre cualquier aviso nuestro. */
    .snowy-alert__meta{color:#64748b}
    @media(max-width:680px){
      .snowy-alert{position:static;transform:none;width:100%;max-width:100%;left:0;right:0;bottom:auto;
        margin:0;border-radius:0;padding:.6rem .8rem;gap:.55rem;align-items:center;animation:none;
        box-shadow:none;border-bottom:1px solid rgba(15,23,42,.1)}
      .snowy-alert__body{flex:1;flex-direction:row;align-items:center;gap:.55rem;flex-wrap:nowrap;min-width:0}
      .snowy-alert__level{font-size:.6rem;padding:.18rem .45rem;flex:0 0 auto}
      .snowy-alert__text{font-size:.82rem;line-height:1.25;min-width:0;display:flex;
        flex-direction:column;gap:.05rem}
      .snowy-alert__text strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
      .snowy-alert__meta{font-size:.74rem}
      .snowy-alert__link{flex:0 0 auto;font-size:.78rem;margin-left:auto}
      .snowy-alert__close{font-size:1.35rem;padding:0 .1rem;flex:0 0 auto}
    }
    @keyframes snowyAlertDown{from{opacity:0;transform:translateY(-100%)}to{opacity:1;transform:translateY(0)}}
    </style>
    <?php
}
add_action('wp_body_open', 'snowy_lr_floating_alert');

/**
 * Franja de datos en vivo + tarjeta del eclipse, bajo el hero de la portada.
 *
 * La home promete "datos en directo" en su propio subtítulo y lo primero que
 * aparecía eran artículos. Esta franja cumple esa promesa y da motivo para
 * volver a diario, que es lo que un listado de posts no tiene.
 */
function snowy_lr_home_strip()
{
    $stations = array_filter(snowy_lr_stations(), static function ($s) {
        return isset($s['today']['tmax'], $s['today']['tmin']);
    });
    if (!$stations) {
        return;
    }

    $porMax = $stations;
    $porMin = $stations;
    usort($porMax, static fn($a, $b) => $b['today']['tmax'] <=> $a['today']['tmax']);
    usort($porMin, static fn($a, $b) => $a['today']['tmin'] <=> $b['today']['tmin']);
    $calida = reset($porMax);
    $fria   = reset($porMin);

    $rachas = array_filter($stations, static fn($s) => !empty($s['today']['gustMax']));
    usort($rachas, static fn($a, $b) => $b['today']['gustMax'] <=> $a['today']['gustMax']);
    $racha = $rachas ? reset($rachas) : null;

    // Cuenta atras del eclipse del 12 de agosto de 2026, 20:28 CEST en La Rioja
    $eclipse = mktime(20, 28, 0, 8, 12, 2026);
    $faltan  = (int) ceil(($eclipse - current_time('timestamp')) / DAY_IN_SECONDS);
    ?>
    <div class="snowy-home">
        <section class="snowy-home__live" aria-label="Datos en vivo de La Rioja">
            <span class="snowy-home__eyebrow">Ahora en La Rioja</span>
            <div class="snowy-home__items">
                <a class="snowy-home__item" href="https://snowy.es/stations/<?php echo esc_attr($calida['stationId']); ?>" target="_blank" rel="noopener">
                    <span class="snowy-home__ico is-hot" aria-hidden="true">▲</span>
                    <strong><?php echo esc_html(snowy_lr_temp($calida['today']['tmax'])); ?></strong>
                    <span class="snowy-home__place"><?php echo esc_html($calida['stationName']); ?></span>
                </a>
                <a class="snowy-home__item" href="https://snowy.es/stations/<?php echo esc_attr($fria['stationId']); ?>" target="_blank" rel="noopener">
                    <span class="snowy-home__ico is-cold" aria-hidden="true">▼</span>
                    <strong><?php echo esc_html(snowy_lr_temp($fria['today']['tmin'])); ?></strong>
                    <span class="snowy-home__place"><?php echo esc_html($fria['stationName']); ?></span>
                </a>
                <?php if ($racha) : ?>
                <a class="snowy-home__item" href="https://snowy.es/stations/<?php echo esc_attr($racha['stationId']); ?>" target="_blank" rel="noopener">
                    <span class="snowy-home__ico is-wind" aria-hidden="true">≈</span>
                    <strong><?php echo esc_html(number_format((float) $racha['today']['gustMax'], 0, ',', '.')); ?> km/h</strong>
                    <span class="snowy-home__place"><?php echo esc_html($racha['stationName']); ?></span>
                </a>
                <?php endif; ?>
            </div>
            <a class="snowy-home__all" href="/tiempo-real/estaciones-meteorologicas-la-rioja/">Ver las <?php echo count(snowy_lr_stations()); ?> estaciones →</a>
        </section>

        <?php if ($faltan > 0) : ?>
        <a class="snowy-home__eclipse" href="https://snowy.es/eclipse-2026/comunidad/la-rioja" target="_blank" rel="noopener">
            <span class="snowy-home__eclipse-icon" aria-hidden="true"></span>
            <span class="snowy-home__eclipse-body">
                <span class="snowy-home__eclipse-kicker">Eclipse solar total · 12 de agosto</span>
                <strong class="snowy-home__eclipse-title">
                    <?php echo $faltan === 1 ? 'Mañana' : 'Faltan ' . $faltan . ' días'; ?>
                </strong>
                <span class="snowy-home__eclipse-text">La Rioja entera dentro de la franja de totalidad. Hasta 97 segundos de noche en Ezcaray.</span>
            </span>
            <span class="snowy-home__eclipse-cta">Ver por municipios →</span>
        </a>
        <?php endif; ?>
    </div>
    <?php
}
add_action('snowy_lr_after_hero', 'snowy_lr_home_strip');

function snowy_lr_home_styles()
{
    if (!is_front_page() && !is_home()) {
        return;
    }
    $css = <<<CSS
.snowy-home{display:grid;grid-template-columns:1.6fr 1fr;gap:1rem;margin:1.25rem 0 .5rem}
.snowy-home__live,.snowy-home__eclipse{border:1px solid #e2e8f0;border-radius:14px;background:#fff;
  box-shadow:0 1px 2px rgba(15,23,42,.04)}
.snowy-home__live{padding:.9rem 1.15rem;display:flex;flex-wrap:wrap;align-items:center;gap:.55rem 1.4rem}
.snowy-home__eyebrow{font-size:.66rem;text-transform:uppercase;letter-spacing:.1em;font-weight:800;
  color:#0369a1;width:100%}
.snowy-home__items{display:flex;flex-wrap:wrap;gap:.35rem 1.6rem;flex:1;min-width:0}
.snowy-home__item{display:inline-flex;align-items:baseline;gap:.4rem;text-decoration:none;color:#0f172a;
  white-space:nowrap}
.snowy-home__item strong{font-size:1.05rem;font-variant-numeric:tabular-nums}
.snowy-home__place{font-size:.82rem;color:#64748b;overflow:hidden;text-overflow:ellipsis;max-width:15ch}
.snowy-home__item:hover .snowy-home__place{color:#0369a1}
.snowy-home__ico{font-size:.8rem;font-weight:700}
.snowy-home__ico.is-hot{color:#c2410c}.snowy-home__ico.is-cold{color:#1e40af}.snowy-home__ico.is-wind{color:#0f766e}
.snowy-home__all{font-size:.8rem;color:#0369a1;text-decoration:none;font-weight:600;white-space:nowrap}
.snowy-home__all:hover{text-decoration:underline}
.snowy-home__eclipse{display:flex;align-items:center;gap:.85rem;padding:.9rem 1.1rem;text-decoration:none;
  color:#f8fafc;background:linear-gradient(135deg,#0b1126,#1e293b);border-color:#1e293b;
  transition:transform .15s,box-shadow .15s}
.snowy-home__eclipse:hover{transform:translateY(-1px);box-shadow:0 10px 24px -12px rgba(15,23,42,.6)}
.snowy-home__eclipse-icon{width:32px;height:32px;border-radius:50%;flex:0 0 auto;background:#0b1126;
  box-shadow:0 0 0 2px #6d5bd0,0 0 16px 3px rgba(109,91,208,.5)}
.snowy-home__eclipse-body{display:flex;flex-direction:column;gap:.1rem;min-width:0}
.snowy-home__eclipse-kicker{font-size:.64rem;text-transform:uppercase;letter-spacing:.09em;color:#34cbe5;font-weight:800}
.snowy-home__eclipse-title{font-size:1.02rem;color:#fabf24}
.snowy-home__eclipse-text{font-size:.76rem;color:#cbd5e1;line-height:1.35}
.snowy-home__eclipse-cta{margin-left:auto;font-size:.76rem;color:#34cbe5;font-weight:600;white-space:nowrap}
@media(max-width:900px){.snowy-home{grid-template-columns:1fr}
  .snowy-home__eclipse-cta{display:none}}
@media(max-width:560px){.snowy-home__place{max-width:11ch}.snowy-home__items{gap:.35rem 1rem}}
CSS;
    wp_register_style('snowy-lr-home', false);
    wp_enqueue_style('snowy-lr-home');
    wp_add_inline_style('snowy-lr-home', $css);
}
add_action('wp_enqueue_scripts', 'snowy_lr_home_styles');

const SNOWY_LR_FEATURED_ID = 'ILOGRO41';
const SNOWY_LR_LIVE_CACHE = 'snowy_lr_live_';

/**
 * Dato EN VIVO de una estacion.
 *
 * /stations/markers es un snapshot por lotes y puede ir con horas de retraso;
 * /stations-metrics/<red>/<id> es la lectura real, la misma que usa la ficha de
 * snowy.es. Para la estacion destacada queremos la segunda.
 */
function snowy_lr_live_station($network, $id)
{
    $clave = SNOWY_LR_LIVE_CACHE . md5($network . $id);
    $cached = get_transient($clave);
    if (is_array($cached)) {
        return $cached;
    }

    // La ruta del endpoint va en minusculas; el snapshot devuelve la red en mayusculas.
    $url = sprintf('https://api.snowy.es/stations-metrics/%s/%s', rawurlencode(strtolower($network)), rawurlencode($id));
    $res = wp_remote_get($url, ['timeout' => 8, 'headers' => snowy_lr_auth_headers()]);
    if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
        return [];
    }

    $data = json_decode(wp_remote_retrieve_body($res), true);
    if (!is_array($data)) {
        return [];
    }

    set_transient($clave, $data, 300);

    return $data;
}

/**
 * Estacion protagonista en la portada: Logroño - La Cava, la nuestra.
 * Si la API no responde no se pinta nada y la portada sigue igual.
 */
function snowy_lr_featured_station()
{
    $stations = snowy_lr_stations();
    if (!$stations) {
        return;
    }

    $s = null;
    foreach ($stations as $st) {
        if (($st['stationId'] ?? '') === SNOWY_LR_FEATURED_ID) {
            $s = $st;
            break;
        }
    }
    if (!$s) {
        return;
    }

    $url  = 'https://snowy.es/stations/' . rawurlencode(SNOWY_LR_FEATURED_ID);
    $live = snowy_lr_live_station($s['network'] ?? 'wunderground', SNOWY_LR_FEATURED_ID);
    $m    = $live['metric'] ?? [];

    // El dato en vivo manda; el snapshot solo cubre lo que aquel no traiga.
    $actual = $m['temp'] ?? ($s['current'] ?? null);
    $tm     = $live['today']['metric'] ?? [];
    $tmax   = $tm['tempHigh'] ?? ($s['today']['tmax'] ?? null);
    $tmin   = $tm['tempLow'] ?? ($s['today']['tmin'] ?? null);
    $gustV  = $tm['windgustHigh'] ?? ($m['windGust'] ?? ($s['today']['gustMax'] ?? null));
    $humV   = $live['humidity'] ?? ($s['humidity'] ?? null);
    $presV  = $m['pressure'] ?? ($s['pressure'] ?? null);

    $gust  = $gustV !== null ? number_format((float) $gustV, 0, ',', '.') . ' km/h' : '—';
    $hum   = $humV !== null ? $humV . ' %' : '—';
    $pres  = $presV !== null ? number_format((float) $presV, 0, ',', '.') . ' hPa' : null;
    // obsTimeLocal ya viene en hora local de la estacion: se formatea tal cual
    // en vez de reconvertir el UTC, que introducia una hora de desfase.
    $obsLocal = !empty($live['obsTimeLocal']) ? substr($live['obsTimeLocal'], 11, 5) : null;
    $obs   = !empty($live['obsTimeUtc']) ? strtotime($live['obsTimeUtc'])
        : (!empty($s['lastUpdate']) ? (int) ($s['lastUpdate'] / 1000) : null);
    ?>
    <section class="snowy-feat" aria-label="Datos en directo de Logroño">
        <div class="snowy-feat__head">
            <span class="snowy-feat__badges">
                <img class="snowy-feat__logo" src="https://lariojameteo.es/wp-content/uploads/2017/09/cropped-cropped-cropped-Sologota_Favicon-WEB.png" alt="La Rioja Meteo" width="26" height="26" loading="lazy" />
                <span class="snowy-feat__station-ico" aria-hidden="true">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round">
                        <path d="M12 3v18M12 3l-5 3M12 3l5 3M12 9l-5 3M12 9l5 3"/><path d="M7 21h10"/>
                    </svg>
                </span>
            </span>
            <span class="snowy-feat__kicker">Nuestra estación · en directo</span>
            <h2 class="snowy-feat__title"><?php echo esc_html($s['stationName']); ?></h2>
        </div>
        <div class="snowy-feat__main">
            <?php
            // Se muestra la hora real de la medida en vez de "ahora mismo": el
            // dato puede llegar con retraso desde la estacion y decir "ahora"
            // cuando es de hace dos horas seria mentir al lector.
            $medido = $obs;
            $minutos = $medido ? (int) round((current_time('timestamp', true) - $medido) / 60) : null;
            ?>
            <div class="snowy-feat__now">
                <span class="snowy-feat__temp"><?php echo esc_html(snowy_lr_temp($actual)); ?></span>
                <span class="snowy-feat__label">
                    <?php $hora = $obsLocal ?: ($medido ? wp_date('H:i', $medido) : null); ?>
                    <?php if ($hora && $minutos !== null && $minutos > 45) : ?>
                        medido a las <?php echo esc_html($hora); ?>
                    <?php elseif ($hora) : ?>
                        última medida · <?php echo esc_html($hora); ?>
                    <?php else : ?>
                        última medida
                    <?php endif; ?>
                </span>
            </div>
            <dl class="snowy-feat__grid">
                <div><dt>Máxima hoy</dt><dd><?php echo esc_html(snowy_lr_temp($tmax)); ?></dd></div>
                <div><dt>Mínima hoy</dt><dd><?php echo esc_html(snowy_lr_temp($tmin)); ?></dd></div>
                <div><dt>Humedad</dt><dd><?php echo esc_html($hum); ?></dd></div>
                <div><dt>Racha máx.</dt><dd><?php echo esc_html($gust); ?></dd></div>
                <?php if ($pres) : ?>
                <div><dt>Presión</dt><dd><?php echo esc_html($pres); ?></dd></div>
                <?php endif; ?>
            </dl>
        </div>
        <a class="snowy-feat__cta" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">
            Ver histórico y gráficas en Snowy →
        </a>
    </section>
    <?php
}
add_action('snowy_lr_before_more_posts', 'snowy_lr_featured_station');

function snowy_lr_featured_styles()
{
    if (!is_front_page() && !is_home()) {
        return;
    }
    $css = <<<CSS
.snowy-feat{border:1px solid #e2e8f0;border-radius:16px;background:#fff;padding:1.25rem 1.4rem;
  margin:1.75rem 0;box-shadow:0 1px 2px rgba(15,23,42,.04),0 10px 28px -18px rgba(15,23,42,.25)}
.snowy-feat__kicker{font-size:.66rem;text-transform:uppercase;letter-spacing:.1em;font-weight:800;color:#0369a1}
.snowy-feat__title{margin:.15rem 0 1rem;font-size:1.25rem;font-weight:800;color:#0f172a}
.snowy-feat__main{display:flex;align-items:center;gap:1.75rem;flex-wrap:wrap}
.snowy-feat__now{display:flex;flex-direction:column;line-height:1}
.snowy-feat__temp{font-size:3rem;font-weight:800;color:#0369a1;font-variant-numeric:tabular-nums}
.snowy-feat__label{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-top:.35rem}
.snowy-feat__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(92px,1fr));gap:.9rem 1.4rem;
  margin:0;flex:1;min-width:0}
.snowy-feat__grid div{min-width:0}
.snowy-feat__grid dt{font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin:0}
.snowy-feat__grid dd{margin:.1rem 0 0;font-size:1.05rem;font-weight:700;color:#0f172a;font-variant-numeric:tabular-nums}
.snowy-feat__cta{display:inline-block;margin-top:1.1rem;font-size:.85rem;font-weight:600;color:#0369a1;
  text-decoration:none}
.snowy-feat__cta:hover{text-decoration:underline}
.snowy-feat__badges{display:inline-flex;align-items:center;gap:.45rem;margin-bottom:.4rem}
.snowy-feat__logo{width:26px;height:26px;object-fit:contain;background:transparent;padding:0}
.snowy-feat__station-ico{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;
  border-radius:6px;background:#e0f2fe;color:#0369a1}
@media(max-width:560px){
  .snowy-feat{padding:1.05rem}
  .snowy-feat__temp{font-size:2.4rem}
  .snowy-feat__main{gap:.9rem;align-items:flex-start}
  /* dos columnas: la ficha ocupaba demasiado alto en vertical */
  .snowy-feat__grid{grid-template-columns:repeat(2,1fr);gap:.65rem 1rem;width:100%}
  .snowy-feat__grid dd{font-size:.98rem}
  .snowy-feat__now{flex-direction:row;align-items:baseline;gap:.5rem;width:100%}
  .snowy-feat__label{margin-top:0}
  .snowy-feat__cta{margin-top:.9rem}
}
CSS;
    wp_register_style('snowy-lr-feat', false);
    wp_enqueue_style('snowy-lr-feat');
    wp_add_inline_style('snowy-lr-feat', $css);
}
add_action('wp_enqueue_scripts', 'snowy_lr_featured_styles');
