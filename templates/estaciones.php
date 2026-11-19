<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="<?php echo esc_attr($classes); ?>">
<?php if ($show_head) : ?>
<div class="snowy-wp-head">
    <<?php echo esc_attr($tag); ?>><?php echo esc_html($title); ?></<?php echo esc_attr($tag); ?>>
    <span class="snowy-wp-tag"><?php echo esc_html($frozen ? __('dato histórico', 'snowy-wp') : __('en vivo', 'snowy-wp')); ?></span>
</div>
<?php endif; ?>
<div class="snowy-wp-scroll"><table class="snowy-wp-table snowy-wp-estaciones">
    <thead><tr>
        <th><?php esc_html_e('Estación', 'snowy-wp'); ?></th>
        <th><?php esc_html_e('Ahora', 'snowy-wp'); ?></th>
        <th><?php esc_html_e('Máx.', 'snowy-wp'); ?></th>
        <th><?php esc_html_e('Mín.', 'snowy-wp'); ?></th>
        <th><?php esc_html_e('Humedad', 'snowy-wp'); ?></th>
        <th><?php esc_html_e('Red', 'snowy-wp'); ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($stations as $s) : ?>
        <tr>
            <td><?php echo snowy_wp_station_link($s); ?></td>
            <td class="snowy-wp-val"><?php echo esc_html(snowy_wp_temp($s['current'] ?? null)); ?></td>
            <td><?php echo esc_html(snowy_wp_temp($s['today']['tmax'] ?? null)); ?></td>
            <td><?php echo esc_html(snowy_wp_temp($s['today']['tmin'] ?? null)); ?></td>
            <td><?php echo isset($s['humidity']) && $s['humidity'] !== null ? esc_html($s['humidity']) . ' %' : '—'; ?></td>
            <td><?php echo snowy_wp_network_badge($s['network'] ?? ''); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php echo $frozen ? snowy_wp_snapshot_note($ts) : snowy_wp_credit(); ?>
</div>
