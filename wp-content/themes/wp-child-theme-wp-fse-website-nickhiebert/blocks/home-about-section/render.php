<?php

/**
 * Home About Section block template.
 *
 * @var array $block      The block settings and attributes.
 * @var bool  $is_preview True when rendering inside the block editor.
 * @var int   $post_id    The ID of the post the block is on.
 */

$image = get_field('home_about_image');
$copy  = get_field('home_about_copy');

// Show a hint in the editor when the block is empty, instead of blank space
if (! $image && ! $copy) {
    if ($is_preview) {
        echo '<p>Add an image and copy in the block sidebar.</p>';
    }
    return;
}
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
    <?php if ($image) : ?>
        <div class="home-about__media">
            <?php echo wp_get_attachment_image($image, 'large'); ?>
        </div>
    <?php endif; ?>

    <?php if ($copy) : ?>
        <div class="home-about__copy">
            <?php echo wp_kses_post(wpautop($copy)); ?>
        </div>
    <?php endif; ?>
</div>