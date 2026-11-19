<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="<?php echo esc_attr($classes); ?>">
    <?php foreach ($columns as $column) : ?>
    <div class="snowy-wp-wrap">
        <?php if ($show_head) : ?>
        <div class="snowy-wp-head">
            <<?php echo esc_attr($tag); ?>><?php echo esc_html($column['title']); ?></<?php echo esc_attr($tag); ?>>
            <span class="snowy-wp-tag"><?php echo esc_html($column['label']); ?></span>
        </div>
        <?php endif; ?>
        <div class="snowy-wp-scroll"><table class="snowy-wp-table">
            <thead><tr>
                <th><?php esc_html_e('Estación', 'snowy-wp'); ?></th>
                <th><?php echo esc_html($column['column']); ?></th>
                <th><?php esc_html_e('Red', 'snowy-wp'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($column['stations'] as $s) : ?>
                <tr>
                    <td><?php echo snowy_wp_station_link($s); ?></td>
                    <td class="snowy-wp-val"><?php echo esc_html(snowy_wp_temp($s['today'][$column['field']])); ?></td>
                    <td><?php echo snowy_wp_network_badge($s['network'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
    <?php endforeach; ?>
</div>
<?php echo snowy_wp_credit(); ?>
