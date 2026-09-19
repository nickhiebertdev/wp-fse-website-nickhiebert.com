<?php

/**
 * Frontend markup for the Modal CTA Notification block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 */

$context = array('isOpen' => true);
?>

<div
    <?php echo get_block_wrapper_attributes(); ?>
    data-wp-interactive="nickhiebert/modal-cta-notification"
    <?php echo wp_interactivity_data_wp_context($context); ?>
    data-wp-bind--hidden="!context.isOpen">

    <div class="modal-cta-notification__actions">
        <a href="mailto:nick.hiebert@gmail.com" class="modal-cta-notification__cta email wp-element-button">
            <?php esc_html_e('Email Nick', 'wp-fse-website-nickhiebert-blocks'); ?>
        </a>
        <a href="https://www.linkedin.com/in/nickhiebert/" class="modal-cta-notification__cta linkedin wp-element-button" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e('LinkedIn', 'wp-fse-website-nickhiebert-blocks'); ?>
        </a>
    </div>

    <a
        href="#"
        role="button"
        class="modal-cta-notification__close"
        aria-label="<?php esc_attr_e('Dismiss notification', 'wp-fse-website-nickhiebert-blocks'); ?>"
        data-wp-on--click="actions.close"
        data-wp-on--keydown="actions.handleKeydown">
        &#10005;
    </a>
</div>