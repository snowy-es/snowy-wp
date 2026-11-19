<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="<?php echo esc_attr($classes); ?>">
    <?php if ($show_head) : ?>
    <div class="snowy-wp-head">
        <<?php echo esc_attr($tag); ?>><?php echo esc_html($rows ? __('Polen en el aire', 'snowy-wp') : __('Polen', 'snowy-wp')); ?></<?php echo esc_attr($tag); ?>>
        <span class="snowy-wp-tag"><?php echo esc_html($rows ? __('hoy', 'snowy-wp') : __('sin presencia', 'snowy-wp')); ?></span>
    </div>
    <?php endif; ?>
    <?php if (!$rows) : ?>
    <p class="snowy-wp-empty"><?php esc_html_e('No hay presencia apreciable de ninguno de los pólenes que se vigilan.', 'snowy-wp'); ?></p>
    <?php else : ?>
    <div class="snowy-wp-scroll">
    <table class="snowy-wp-table">
        <thead><tr>
            <th><?php esc_html_e('Tipo', 'snowy-wp'); ?></th>
            <th><?php esc_html_e('Nivel', 'snowy-wp'); ?></th>
            <th><?php esc_html_e('Granos/m³', 'snowy-wp'); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r) : ?>
            <tr>
                <td><?php echo esc_html($r['label']); ?></td>
                <td><span class="snowy-wp-risk <?php echo esc_attr($r['level']['class']); ?>"><?php echo esc_html($r['level']['label']); ?></span></td>
                <td class="snowy-wp-val"><?php echo esc_html(number_format($r['value'], 1, ',', '.')); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <p class="snowy-wp-credit"><?php printf(
        /* translators: %s: enlace a snowy.es */
        esc_html__('Concentraciones de polen servidas por %s. Orientativas: no sustituyen al parte aerobiológico oficial.', 'snowy-wp'),
        '<a href="' . esc_url(SNOWY_WP_SITE) . '" target="_blank" rel="noopener"><strong>Snowy</strong></a>'
    ); ?></p>
    <?php endif; ?>
</div>
