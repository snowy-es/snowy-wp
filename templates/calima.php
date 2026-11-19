<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="<?php echo esc_attr($classes); ?>">
    <?php if ($show_head) : ?>
    <div class="snowy-wp-head">
        <<?php echo esc_attr($tag); ?>><?php esc_html_e('Polvo sahariano', 'snowy-wp'); ?></<?php echo esc_attr($tag); ?>>
        <span class="snowy-wp-tag"><?php
            echo esc_html($peak < 10 ? __('sin calima prevista', 'snowy-wp') : __('previsión', 'snowy-wp'));
        ?></span>
    </div>
    <?php endif; ?>
    <?php if ($peak < 10) : ?>
        <p class="snowy-wp-empty"><?php esc_html_e('No se espera polvo en suspensión en los próximos días.', 'snowy-wp'); ?></p>
    <?php else : ?>
        <div class="snowy-wp-scroll">
        <table class="snowy-wp-table">
            <thead><tr>
                <th><?php esc_html_e('Día', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('Intensidad', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('Máximo', 'snowy-wp'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($days as $fecha => $valor) : ?>
                <?php $level = snowy_wp_dust_level($valor); ?>
                <tr>
                    <td><?php echo esc_html(wp_date('l j', strtotime($fecha))); ?></td>
                    <td><span class="snowy-wp-risk <?php echo esc_attr($level['class']); ?>"><?php echo esc_html($level['label']); ?></span></td>
                    <td class="snowy-wp-val"><?php echo esc_html(number_format($valor, 0, ',', '.')); ?> µg/m³</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
    <p class="snowy-wp-credit"><?php printf(
        /* translators: %s: enlace a snowy.es */
        esc_html__('Previsión de polvo en suspensión servida por %s.', 'snowy-wp'),
        '<a href="' . esc_url(SNOWY_WP_SITE) . '" target="_blank" rel="noopener"><strong>Snowy</strong></a>'
    ); ?></p>
</div>
