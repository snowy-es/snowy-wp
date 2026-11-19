<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="<?php echo esc_attr($classes); ?>">
    <?php if ($show_head) : ?>
    <div class="snowy-wp-head">
        <<?php echo esc_attr($tag); ?>><?php echo esc_html($title); ?></<?php echo esc_attr($tag); ?>>
        <span class="snowy-wp-tag"><?php echo esc_html($subtitle); ?></span>
    </div>
    <?php endif; ?>
    <div class="snowy-wp-climate-body">
        <div class="snowy-wp-big <?php echo esc_attr($tone); ?>">
            <strong><?php echo esc_html($figure); ?></strong>
            <span><?php echo esc_html($figure_note); ?></span>
        </div>
        <ul class="snowy-wp-rainbars">
            <?php foreach ($bars as $bar) : ?>
            <li>
                <span class="snowy-wp-rainbars-label"><?php echo esc_html($bar['label']); ?></span>
                <span class="snowy-wp-rainbars-track" aria-hidden="true"><span class="snowy-wp-rainbars-bar <?php echo esc_attr($bar['class']); ?>" style="width:<?php echo esc_attr($bar['width']); ?>%"></span></span>
                <span class="snowy-wp-rainbars-value"><?php echo esc_html($bar['text']); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if (count($stations) > 1) : ?>
        <div class="snowy-wp-scroll"><table class="snowy-wp-table">
            <thead><tr>
                <th><?php esc_html_e('Pluviómetro', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('Acumulado', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('Normal', 'snowy-wp'); ?></th>
                <th><?php esc_html_e('De lo normal', 'snowy-wp'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($stations as $station) : ?>
                <tr>
                    <td><?php echo esc_html($station['name']); ?></td>
                    <td class="snowy-wp-val"><?php echo esc_html($station['total']); ?></td>
                    <td><?php echo esc_html($station['normal']); ?></td>
                    <td><?php echo esc_html($station['percent']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
        <p class="snowy-wp-note"><?php echo esc_html($basis); ?></p>
    </div>
    <?php echo $credit; ?>
</div>
