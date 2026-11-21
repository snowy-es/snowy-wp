<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="<?php echo esc_attr($classes); ?>">
    <?php if ($show_head) : ?>
    <div class="snowy-wp-head">
        <<?php echo esc_attr($tag); ?>><?php echo esc_html($title); ?></<?php echo esc_attr($tag); ?>>
        <span class="snowy-wp-tag"><?php echo esc_html($label); ?></span>
    </div>
    <?php endif; ?>
    <div class="snowy-wp-scroll">
    <table class="snowy-wp-table snowy-wp-ranking">
        <thead><tr>
            <th><?php esc_html_e('Estación', 'snowy-wp'); ?></th>
            <th><?php echo esc_html($metric['columna']); ?></th>
            <th><?php esc_html_e('Red', 'snowy-wp'); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($stations as $s) : ?>
            <tr>
                <td><?php echo snowy_wp_station_link($s); ?></td>
                <td class="snowy-wp-val"><?php
                    echo esc_html(snowy_wp_format_metric(snowy_wp_field($s, 'valor'), $metric));
                ?></td>
                <td class="snowy-wp-ranking__net"><?php echo snowy_wp_network_badge($s['network'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php echo snowy_wp_credit($note); ?>
</div>
