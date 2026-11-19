<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="<?php echo esc_attr($classes); ?>">
    <?php if ($show_head) : ?>
    <div class="snowy-wp-head">
        <<?php echo esc_attr($tag); ?>><?php echo esc_html($title); ?></<?php echo esc_attr($tag); ?>>
        <span class="snowy-wp-tag"><?php esc_html_e('en vivo', 'snowy-wp'); ?></span>
    </div>
    <?php endif; ?>
    <div class="snowy-wp-scroll">
    <table class="snowy-wp-table snowy-wp-comparador">
        <thead><tr>
            <th></th>
            <?php foreach ($stations as $s) : ?>
                <th><?php echo snowy_wp_station_link($s); ?></th>
            <?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $m) : ?>
            <tr>
                <th scope="row"><?php echo esc_html($m['columna']); ?></th>
                <?php foreach ($stations as $s) : ?>
                    <td class="snowy-wp-val"><?php
                        echo esc_html(snowy_wp_format_metric(snowy_wp_field($s, $m['campo']), $m));
                    ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php echo snowy_wp_credit(); ?>
</div>
