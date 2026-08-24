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
            'nivel' => 'Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.',
        ],
        'block'   => 'Snowy · Avisos de AEMET',
        'ejemplo' => '[snowy_avisos modo="snapshot"]',
    ],
    'extremos' => [
        'code'  => '[snowy_extremos limite="8"]',
        'name'  => 'Extremos del día',
        'desc'  => 'Las estaciones más cálidas y más frías de hoy.',
        'attrs' => [
            'limite' => 'Cuántas estaciones se muestran en cada columna. Por defecto 8.',
            'nivel' => 'Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.',
        ],
        'block'   => 'Snowy · Extremos del día',
        'ejemplo' => '[snowy_extremos limite="5" nivel="h2"]',
    ],
    'estaciones' => [
        'code'  => '[snowy_estaciones]',
        'name'  => 'Todas las estaciones',
        'desc'  => 'Tabla completa con temperatura actual, máxima, mínima y humedad.',
        'attrs' => [
            'ids'    => 'Identificadores separados por comas para mostrar solo unas cuantas. Vacío las muestra todas.',
            'titulo' => 'Encabezado propio. Vacío usa el de la región configurada.',
            'modo'   => 'vivo (por defecto) o snapshot para congelar la tabla dentro de un artículo.',
            'nivel' => 'Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.',
        ],
        'block'   => 'Snowy · Estaciones',
        'ejemplo' => '[snowy_estaciones ids="9115X,ILOGRO41" titulo="Nuestras estaciones"]',
    ],
    'viento' => [
        'code'  => '[snowy_viento limite="8"]',
        'name'  => 'Rachas de viento',
        'desc'  => 'Ranking de rachas máximas registradas hoy.',
        'attrs' => [
            'limite' => 'Cuántas estaciones se muestran. Por defecto 8.',
            'nivel' => 'Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.',
        ],
        'block'   => 'Snowy · Rachas de viento',
        'ejemplo' => '[snowy_viento limite="10"]',
    ],
    'aire' => [
        'code'  => '[snowy_aire]',
        'name'  => 'Calidad del aire',
        'desc'  => 'Índice europeo con su nivel, más PM2,5, PM10, ozono y NO₂. Se ubica solo en el centro de tus estaciones.',
        'attrs' => [
            'lat' => 'Latitud del punto a consultar. Vacío usa el centro de la región configurada.',
            'lon' => 'Longitud del punto a consultar.',
            'nivel' => 'Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.',
        ],
        'block'   => 'Snowy · Calidad del aire',
        'ejemplo' => '[snowy_aire]',
    ],
    'polen' => [
        'code'  => '[snowy_polen]',
        'name'  => 'Polen en el aire',
        'desc'  => 'Gramíneas, olivo, abedul, aliso, artemisa y ambrosía, con su nivel de riesgo. Solo lista los que tienen presencia.',
        'attrs' => [
            'todos' => 'si para listar también los que están a cero. Por defecto no.',
            'lat'   => 'Latitud del punto. Vacío usa el centro de la región configurada.',
            'lon'   => 'Longitud del punto.',
            'nivel' => 'Nivel del encabezado: h2, h3, h4 o p para quitarlo. Vacío usa el de los ajustes.',
        ],
        'block'   => 'Snowy · Polen',
        'ejemplo' => '[snowy_polen todos="si"]',
    ],
    'estacion' => [
        'code'  => '[snowy_estacion id="9115X"]',
        'name'  => 'Ficha de una estación',
        'desc'  => 'Tarjeta con el dato de una estación concreta, para incrustar dentro de un artículo.',
        'attrs' => [
            'id'     => 'Identificador de la estación. Los tienes en la tabla de abajo.',
            'nombre' => 'Alternativa a id: el nombre exacto de la estación.',
            'grafico' => 'no para ocultar la evolución de las últimas 24 horas. Por defecto se muestra.',
        ],
        'block'   => 'Snowy · Ficha de estación',
        'ejemplo' => '[snowy_estacion id="9115X"]',
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
    $ajustes  = admin_url('options-general.php?page=snowy-wp-settings');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Widgets de Snowy', 'snowy-wp'); ?></h1>

        <?php if ($stations) : ?>
            <div class="notice notice-success inline" style="margin:1rem 0">
                <p><?php printf(
                    /* translators: 1: numero de estaciones, 2: region configurada */
                    esc_html__('Conectado: %1$s estaciones disponibles en %2$s.', 'snowy-wp'),
                    '<strong>' . count($stations) . '</strong>',
                    '<strong>' . esc_html(snowy_wp_region_label()) . '</strong>'
                ); ?></p>
            </div>
        <?php else : ?>
            <div class="notice notice-error inline" style="margin:1rem 0">
                <p>
                    <strong><?php esc_html_e('Sin conexión con la API.', 'snowy-wp'); ?></strong>
                    <a href="<?php echo esc_url($ajustes); ?>"><?php esc_html_e('Revisa los ajustes del plugin.', 'snowy-wp'); ?></a>
                </p>
            </div>
        <?php endif; ?>

        <h2><?php esc_html_e('Cómo insertar un widget', 'snowy-wp'); ?></h2>
        <p style="max-width:900px">
            <?php esc_html_e('Hay dos formas y pintan exactamente lo mismo, porque por dentro comparten el código:', 'snowy-wp'); ?>
        </p>
        <ul style="max-width:900px;list-style:disc;margin-left:1.5rem">
            <li><?php printf(
                /* translators: %s: el texto que se escribe en el editor */
                esc_html__('En el editor de bloques, escribe %s y elige el que quieras. Se previsualiza dentro del propio editor y sus opciones están en el panel lateral.', 'snowy-wp'),
                '<code>/snowy</code>'
            ); ?></li>
            <li><?php esc_html_e('En el editor clásico, o dentro de cualquier campo de texto, pega el shortcode tal cual.', 'snowy-wp'); ?></li>
        </ul>

        <h2><?php esc_html_e('Piezas disponibles', 'snowy-wp'); ?></h2>
        <table class="widefat striped" style="max-width:980px">
            <thead><tr>
                <th style="width:290px"><?php esc_html_e('Shortcode', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('Qué muestra y qué admite', 'snowy-wp'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach (SNOWY_WP_WIDGETS as $w) : ?>
                <tr>
                    <td>
                        <code style="display:block;padding:.4rem;background:#f6f7f7;user-select:all"><?php echo esc_html($w['code']); ?></code>
                        <?php if (!empty($w['block'])) : ?>
                            <p style="margin:.4rem 0 0;color:#646970;font-size:.9em">
                                <?php esc_html_e('Bloque:', 'snowy-wp'); ?> <strong><?php echo esc_html($w['block']); ?></strong>
                            </p>
                        <?php endif; ?>
                    </td>
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
                        <?php if (!empty($w['ejemplo'])) : ?>
                            <p style="margin:.5rem 0 0">
                                <?php esc_html_e('Ejemplo:', 'snowy-wp'); ?>
                                <code style="user-select:all"><?php echo esc_html($w['ejemplo']); ?></code>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h2><?php esc_html_e('En vivo o congelado', 'snowy-wp'); ?></h2>
        <p style="max-width:900px">
            <?php esc_html_e('En una página de datos los widgets van en vivo y se actualizan solos. Dentro de un artículo de actualidad conviene congelarlos, con el interruptor del bloque o con modo="snapshot": así el texto y el dato siguen contando lo mismo dentro de un mes.', 'snowy-wp'); ?>
        </p>
        <ul style="max-width:900px;list-style:disc;margin-left:1.5rem;color:#646970">
            <li><?php esc_html_e('El dato se congela al publicar, no mientras editas.', 'snowy-wp'); ?></li>
            <li><?php esc_html_e('Nunca se congela una respuesta vacía: una caída pasajera dejaría el widget muerto para siempre.', 'snowy-wp'); ?></li>
            <li><?php esc_html_e('El widget congelado avisa de la fecha, para que el lector sepa a cuándo corresponde.', 'snowy-wp'); ?></li>
        </ul>

        <h2><?php esc_html_e('Qué esperar', 'snowy-wp'); ?></h2>
        <table class="widefat striped" style="max-width:980px">
            <tbody>
            <tr>
                <td style="width:290px"><strong><?php esc_html_e('Si la API no responde', 'snowy-wp'); ?></strong></td>
                <td><?php esc_html_e('Se sirve la última lectura buena, guardada durante 24 horas, indicando su antigüedad. Si tampoco la hay, el widget no pinta nada y la página se sirve igual: un fallo nuestro no puede tumbar tu web.', 'snowy-wp'); ?></td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Cada cuánto se actualiza', 'snowy-wp'); ?></strong></td>
                <td><?php esc_html_e('Las estaciones cada 10 minutos, los avisos cada 15 y el aire cada 30. Al guardar los ajustes la caché se vacía.', 'snowy-wp'); ?></td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Dónde va la clave', 'snowy-wp'); ?></strong></td>
                <td><?php esc_html_e('Las peticiones salen de tu servidor, nunca del navegador del visitante, así que la clave no queda expuesta. Se puede guardar en los ajustes o definir SNOWY_API_KEY en wp-config.php, que tiene prioridad.', 'snowy-wp'); ?></td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Atribución', 'snowy-wp'); ?></strong></td>
                <td><?php esc_html_e('Cada widget indica de dónde viene el dato y los avisos atribuyen a AEMET como fuente. Es la condición de uso y no se puede desactivar.', 'snowy-wp'); ?></td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Actualizaciones', 'snowy-wp'); ?></strong></td>
                <td><?php esc_html_e('El plugin comprueba por su cuenta si hay una versión nueva publicada y la ofrece en Plugins, como cualquier otro. No se instala nada sin que lo pidas.', 'snowy-wp'); ?></td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Si la clave deja de valer', 'snowy-wp'); ?></strong></td>
                <td><?php esc_html_e('Se revisa a diario y se avisa por correo al administrador la primera vez que falla. El diagnóstico también aparece en Herramientas > Salud del sitio.', 'snowy-wp'); ?></td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Encabezados', 'snowy-wp'); ?></strong></td>
                <td><?php printf(
                    /* translators: %s: enlace a los ajustes */
                    esc_html__('El título de cada widget usa el nivel elegido en %s, o el del atributo nivel. Conviene que encaje bajo los encabezados de tus artículos.', 'snowy-wp'),
                    '<a href="' . esc_url($ajustes) . '">' . esc_html__('los ajustes', 'snowy-wp') . '</a>'
                ); ?></td>
            </tr>
            </tbody>
        </table>

        <?php if ($stations) : ?>
            <h2><?php esc_html_e('Identificadores de estación', 'snowy-wp'); ?></h2>
            <p><?php esc_html_e('Para usar con los atributos id e ids. Haz clic sobre uno para seleccionarlo.', 'snowy-wp'); ?></p>
            <table class="widefat striped" style="max-width:720px">
                <thead><tr>
                    <th><?php esc_html_e('Estación', 'snowy-wp'); ?></th>
                    <th style="width:150px"><?php esc_html_e('id', 'snowy-wp'); ?></th>
                    <th style="width:170px"><?php esc_html_e('Red', 'snowy-wp'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($stations as $s) : ?>
                    <tr>
                        <td><?php echo esc_html($s['stationName'] ?? ''); ?></td>
                        <td><code style="user-select:all"><?php echo esc_html($s['stationId'] ?? ''); ?></code></td>
                        <td style="color:#646970"><?php echo esc_html(snowy_wp_network_label($s['network'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2><?php esc_html_e('Composiciones listas', 'snowy-wp'); ?></h2>
        <p style="max-width:900px">
            <?php esc_html_e('En el editor de bloques, el botón de insertar tiene una pestaña de Patrones con una categoría Snowy. Ahí hay tres montajes que ya combinan varias piezas:', 'snowy-wp'); ?>
        </p>
        <ul style="max-width:900px;list-style:disc;margin-left:1.5rem;color:#646970">
            <?php foreach (SNOWY_WP_PATTERNS as $pat) : ?>
                <li><strong><?php echo esc_html($pat['title']); ?></strong> — <?php echo esc_html($pat['description']); ?></li>
            <?php endforeach; ?>
        </ul>

        <h2><?php esc_html_e('Ayuda', 'snowy-wp'); ?></h2>
        <p>
            <?php printf(
                /* translators: %s: direccion de correo de contacto */
                esc_html__('¿Algo no encaja, o necesitas un dato que aquí no está? Escribe a %s.', 'snowy-wp'),
                '<a href="mailto:' . esc_attr(SNOWY_WP_CONTACT) . '"><strong>' . esc_html(SNOWY_WP_CONTACT) . '</strong></a>'
            ); ?>
        </p>
    </div>
    <?php
}
