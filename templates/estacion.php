<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<aside class="<?php echo esc_attr($classes); ?>">
    <?php if ($show_head) : ?>
    <p class="snowy-wp-card-eyebrow"><?php esc_html_e('Dato en vivo · red de Snowy', 'snowy-wp'); ?></p>
    <?php endif; ?>
    <h4 class="snowy-wp-card-title"><?php echo snowy_wp_station_link($station); ?></h4>
    <div class="snowy-wp-card-grid">
        <div><span><?php esc_html_e('Ahora', 'snowy-wp'); ?></span><strong><?php echo esc_html(snowy_wp_temp($station['current'] ?? null)); ?></strong></div>
        <div><span><?php esc_html_e('Máxima hoy', 'snowy-wp'); ?></span><strong><?php echo esc_html(snowy_wp_temp($station['today']['tmax'] ?? null)); ?></strong></div>
        <div><span><?php esc_html_e('Mínima hoy', 'snowy-wp'); ?></span><strong><?php echo esc_html(snowy_wp_temp($station['today']['tmin'] ?? null)); ?></strong></div>
        <div><span><?php esc_html_e('Racha máx.', 'snowy-wp'); ?></span><strong><?php echo esc_html(snowy_wp_speed($station['today']['gustMax'] ?? null)); ?></strong></div>
    </div>
    <?php if ($points) : ?>
        <div class="snowy-wp-card-spark">
            <?php echo snowy_wp_sparkline($points); ?>
            <p class="snowy-wp-spark-caption"><?php echo snowy_wp_sparkline_caption($points, SNOWY_WP_SPARK_HOURS); ?></p>
        </div>
    <?php endif; ?>
    <p class="snowy-wp-card-foot">
        <?php echo snowy_wp_network_badge($station['network'] ?? ''); ?>
        <?php printf(
            /* translators: %s: enlace a snowy.es */
            esc_html__('· medido por %s', 'snowy-wp'),
            '<a href="' . esc_url(SNOWY_WP_SITE) . '" target="_blank" rel="noopener"><strong>Snowy</strong></a>'
        ); ?>
    </p>
</aside>
