<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="<?php echo esc_attr($classes); ?>">
    <?php if ($show_head) : ?>
    <div class="snowy-wp-head">
        <<?php echo esc_attr($tag); ?>><?php printf(
            /* translators: %s: nombre de la region configurada */
            esc_html__('Avisos de AEMET en %s', 'snowy-wp'),
            esc_html($region)
        ); ?></<?php echo esc_attr($tag); ?>>
        <span class="snowy-wp-tag"><?php
            echo esc_html($frozen
                ? __('aviso histórico', 'snowy-wp')
                : ($days ? __('vigentes', 'snowy-wp') : __('sin avisos', 'snowy-wp')));
        ?></span>
    </div>
    <?php endif; ?>
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
