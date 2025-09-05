<?php
/*
Plugin Name: Yellow Square Plugin
Description: Displays a yellow square in the center of a baby blue background using a shortcode.
Version: 1.0
Author: Your Name
*/

function ysp_render_square() {
    $height = esc_attr(get_option('ysp_height', 100));
    $width = esc_attr(get_option('ysp_width', 100));
    return '<div id="ysp-container" data-height="' . $height . '" data-width="' . $width . '"></div>';
}
add_shortcode('yellow_square', 'ysp_render_square');



function ysp_enqueue_assets() {
    wp_enqueue_style('ysp-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_script('ysp-script', plugin_dir_url(__FILE__) . 'script.js', array(), false, true);
    wp_localize_script('ysp-script', 'yspSettings', array(
        'height' => get_option('ysp_height', 100),
        'width' => get_option('ysp_width', 100)
    ));
}

// Only enqueue assets if shortcode is present
add_action('the_posts', function($posts) {
    if (empty($posts)) return $posts;
    $found = false;
    foreach ($posts as $post) {
        if (strpos($post->post_content, '[yellow_square]') !== false) {
            $found = true;
            break;
        }
    }
    if ($found) {
        add_action('wp_enqueue_scripts', 'ysp_enqueue_assets');
    }
    return $posts;
});

// Add settings page
add_action('admin_menu', function() {
    add_options_page(
        'Yellow Square Settings',
        'Yellow Square',
        'manage_options',
        'yellow-square-settings',
        'ysp_settings_page'
    );
});

// Register settings
add_action('admin_init', function() {
    register_setting('ysp_settings_group', 'ysp_height');
    register_setting('ysp_settings_group', 'ysp_width');
    // Premium options (not saved yet)
});

function ysp_settings_page() {
    ?>
    <div class="wrap">
        <h1>Yellow Square Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('ysp_settings_group'); ?>
            <?php do_settings_sections('ysp_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Square Height (px)</th>
                    <td><input type="number" name="ysp_height" value="<?php echo esc_attr(get_option('ysp_height', 100)); ?>" min="10" max="1000" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Square Width (px)</th>
                    <td><input type="number" name="ysp_width" value="<?php echo esc_attr(get_option('ysp_width', 100)); ?>" min="10" max="1000" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Square Color <span style="color:gray;">(Premium)</span></th>
                    <td><input type="color" value="#FFFF00" disabled style="background:#eee;" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Border Radius <span style="color:gray;">(Premium)</span></th>
                    <td><input type="number" value="8" disabled style="background:#eee;" /> px</td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p><em>Upgrade to Premium to unlock color and border radius options.</em></p>
    </div>
    <?php
}

