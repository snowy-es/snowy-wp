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
        <ul class="snowy-wp-changes">
            <?php foreach ($cards as $card) : ?>
            <li class="<?php echo esc_attr($card['class']); ?>">
                <span class="snowy-wp-changes-label"><?php echo esc_html($card['label']); ?> <small><?php echo esc_html($card['hint']); ?></small></span>
                <strong><?php echo esc_html($card['figure']); ?></strong>
                <span class="snowy-wp-changes-detail"><?php echo esc_html($card['detail']); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php echo $credit; ?>
</div>
