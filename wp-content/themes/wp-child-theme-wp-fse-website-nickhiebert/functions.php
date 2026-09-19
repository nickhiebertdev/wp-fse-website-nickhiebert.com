<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if (!function_exists('chld_thm_cfg_locale_css')):
    function chld_thm_cfg_locale_css($uri)
    {
        if (empty($uri) && is_rtl() && file_exists(get_template_directory() . '/rtl.css'))
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter('locale_stylesheet_uri', 'chld_thm_cfg_locale_css');

if (!function_exists('chld_thm_cfg_parent_css')):
    function chld_thm_cfg_parent_css()
    {
        wp_enqueue_style('chld_thm_cfg_parent', trailingslashit(get_template_directory_uri()) . 'style.css', array());
    }
endif;
add_action('wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10);

if (!function_exists('child_theme_configurator_css')):
    function child_theme_configurator_css()
    {
        wp_enqueue_style('chld_thm_cfg_child', trailingslashit(get_stylesheet_directory_uri()) . 'style.css', array('chld_thm_cfg_parent'));
    }
endif;
add_action('wp_enqueue_scripts', 'child_theme_configurator_css', 10);

// Allow SVG uploads
add_filter('upload_mimes', function ($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
});


// ACF's UI Create Field Groups - Top-Level Options Page
if (function_exists('acf_add_options_page')) {
    acf_add_options_page(array(
        'page_title'    => 'ACF Options Settings',
        'menu_title'    => 'ACF Options Settings',
        'menu_slug'     => 'acf-options-settings',
        'capability'    => 'edit_posts',
        'redirect'      => false,
    ));
}

// ACF Repeater - Tech Stack
function nickhiebert_tech_stack_shortcode()
{
    if (!have_rows('tech_stack_items', 'option')) return '';
    ob_start();
?>
    <div class="tech-stack">
        <ul class="tech-stack-grid">
            <?php while (have_rows('tech_stack_items', 'option')): the_row();
                $icon = get_sub_field('icon');
                $label = get_sub_field('label');
                $width = absint(get_sub_field('width'));
                $height = absint(get_sub_field('height'));
                $url = get_sub_field('url');
                $class = get_sub_field('class');
                if (!$icon) continue;
            ?>
                <li class="tech-stack-item">
                    <?php if ($url): ?><a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener"><?php endif; ?>
                        <img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($label); ?>" <?php if ($width) : ?>width="<?php echo $width; ?>" <?php endif; ?> <?php if ($height) : ?>height="<?php echo $height; ?>" <?php endif; ?><?php if ($class) : ?>class="<?php echo esc_attr($class); ?>" <?php endif; ?>loading="lazy">
                        <?php if ($url): ?></a><?php endif; ?>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
<?php
    return ob_get_clean();
}
add_shortcode('tech_stack', 'nickhiebert_tech_stack_shortcode');

// END ENQUEUE PARENT ACTION
