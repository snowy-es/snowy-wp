<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Catalogo de piezas disponibles. Es la fuente de la pagina de ayuda del admin
 * y evita que la lista se desincronice de lo que el plugin registra de verdad.
 */
const SNOWY_WP_WIDGETS = [
    'avisos' => [
        'code'  => '[snowy_avisos]',
        'name'  => 'Avisos de AEMET',
        'desc'  => 'Avisos vigentes para hoy, mañana y pasado, con nivel y horario. Si no hay ninguno, lo dice.',
        'attrs' => [
            'modo' => 'vivo (por defecto) o snapshot para congelar los avisos dentro de un artículo.',
        ],
    ],
    'extremos' => [
        'code'  => '[snowy_extremos limite="8"]',
        'name'  => 'Extremos del día',
        'desc'  => 'Las estaciones más cálidas y más frías de hoy.',
        'attrs' => [
            'limite' => 'Cuántas estaciones se muestran en cada columna. Por defecto 8.',
        ],
    ],
    'estaciones' => [
        'code'  => '[snowy_estaciones]',
        'name'  => 'Todas las estaciones',
        'desc'  => 'Tabla completa con temperatura actual, máxima, mínima y humedad.',
        'attrs' => [
            'ids'    => 'Identificadores separados por comas para mostrar solo unas cuantas. Vacío las muestra todas.',
            'titulo' => 'Encabezado propio. Vacío usa el de la región configurada.',
            'modo'   => 'vivo (por defecto) o snapshot para congelar la tabla dentro de un artículo.',
        ],
    ],
    'viento' => [
        'code'  => '[snowy_viento limite="8"]',
        'name'  => 'Rachas de viento',
        'desc'  => 'Ranking de rachas máximas registradas hoy.',
        'attrs' => [
            'limite' => 'Cuántas estaciones se muestran. Por defecto 8.',
        ],
    ],
    'aire' => [
        'code'  => '[snowy_aire]',
        'name'  => 'Calidad del aire',
        'desc'  => 'Índice europeo con su nivel, más PM2,5, PM10, ozono y NO₂. Se ubica solo en el centro de tus estaciones.',
        'attrs' => [
            'lat' => 'Latitud del punto a consultar. Vacío usa el centro de la región configurada.',
            'lon' => 'Longitud del punto a consultar.',
        ],
    ],
    'polen' => [
        'code'  => '[snowy_polen]',
        'name'  => 'Polen en el aire',
        'desc'  => 'Gramíneas, olivo, abedul, aliso, artemisa y ambrosía, con su nivel de riesgo. Solo lista los que tienen presencia.',
        'attrs' => [
            'todos' => 'si para listar también los que están a cero. Por defecto no.',
            'lat'   => 'Latitud del punto. Vacío usa el centro de la región configurada.',
            'lon'   => 'Longitud del punto.',
        ],
    ],
    'estacion' => [
        'code'  => '[snowy_estacion id="9115X"]',
        'name'  => 'Ficha de una estación',
        'desc'  => 'Tarjeta con el dato de una estación concreta, para incrustar dentro de un artículo.',
        'attrs' => [
            'id'     => 'Identificador de la estación. Los tienes en la tabla de abajo.',
            'nombre' => 'Alternativa a id: el nombre exacto de la estación.',
        ],
    ],
];

function snowy_wp_admin_menu()
{
    add_menu_page(
        __('Widgets de Snowy', 'snowy-wp'),
        __('Widgets Snowy', 'snowy-wp'),
        'edit_posts',
        'snowy-wp-widgets',
        'snowy_wp_admin_page',
        'dashicons-cloud',
        58
    );
}
add_action('admin_menu', 'snowy_wp_admin_menu');

function snowy_wp_admin_page()
{
    $stations = snowy_wp_stations();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Widgets de Snowy', 'snowy-wp'); ?></h1>
        <p>
            <?php if ($stations) : ?>
                <?php printf(
                    /* translators: 1: numero de estaciones, 2: region configurada */
                    esc_html__('Conectado: %1$s estaciones disponibles en %2$s.', 'snowy-wp'),
                    '<strong>' . count($stations) . '</strong>',
                    '<strong>' . esc_html(snowy_wp_region_label()) . '</strong>'
                ); ?>
            <?php else : ?>
                <strong style="color:#b32d2e"><?php esc_html_e('Sin conexión con la API.', 'snowy-wp'); ?></strong>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=snowy-wp-settings')); ?>"><?php esc_html_e('Revisa los ajustes del plugin.', 'snowy-wp'); ?></a>
            <?php endif; ?>
        </p>

        <h2><?php esc_html_e('Cómo insertarlos', 'snowy-wp'); ?></h2>
        <p><?php esc_html_e('En el editor de bloques, escribe /snowy y elige el bloque. En el editor clásico, pega el shortcode directamente en el texto.', 'snowy-wp'); ?></p>
        <p style="max-width:900px">
            <strong><?php esc_html_e('Vivo o congelado:', 'snowy-wp'); ?></strong>
            <?php esc_html_e('en una página de datos los widgets van en vivo. Dentro de un artículo de actualidad conviene congelarlos con modo="snapshot", o con el interruptor del bloque: así el texto y el dato siguen contando lo mismo dentro de un mes. El dato se congela al publicar, no mientras editas, y nunca se congela una respuesta vacía.', 'snowy-wp'); ?>
        </p>

        <table class="widefat striped" style="max-width:900px">
            <thead><tr>
                <th style="width:270px"><?php esc_html_e('Shortcode', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('Qué muestra', 'snowy-wp'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach (SNOWY_WP_WIDGETS as $w) : ?>
                <tr>
                    <td><code style="display:block;padding:.4rem;background:#f6f7f7;user-select:all"><?php echo esc_html($w['code']); ?></code></td>
                    <td>
                        <strong><?php echo esc_html($w['name']); ?></strong><br>
                        <span style="color:#646970"><?php echo esc_html($w['desc']); ?></span>
                        <?php if (!empty($w['attrs'])) : ?>
                            <ul style="margin:.5rem 0 0;color:#646970">
                                <?php foreach ($w['attrs'] as $attr => $help) : ?>
                                    <li><code><?php echo esc_html($attr); ?></code> — <?php echo esc_html($help); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($stations) : ?>
            <h2><?php esc_html_e('Identificadores de estación', 'snowy-wp'); ?></h2>
            <p><?php esc_html_e('Para usar con [snowy_estacion id="..."] o [snowy_estaciones ids="..."]:', 'snowy-wp'); ?></p>
            <table class="widefat striped" style="max-width:640px">
                <thead><tr>
                    <th><?php esc_html_e('Estación', 'snowy-wp'); ?></th>
                    <th style="width:130px"><?php esc_html_e('id', 'snowy-wp'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($stations as $s) : ?>
                    <tr>
                        <td><?php echo esc_html($s['stationName'] ?? ''); ?></td>
                        <td><code style="user-select:all"><?php echo esc_html($s['stationId'] ?? ''); ?></code></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
