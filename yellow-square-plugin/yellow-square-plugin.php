<?php
/*
Plugin Name: Yellow Square Plugin
Description: Displays a yellow square in the center of a baby blue background using a shortcode.
Version: 1.0.2
Author: Your Name
Text Domain: yellow-square
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ===========================
 * Freemius SDK Integration
 * ===========================
 * - Uses a valid helper name: yellow_square_fs()
 * - Guards against missing SDK to avoid fatals
 * - NOTE: Replace IDs/keys with your own; avoid committing real secrets
 */
if ( ! function_exists( 'yellow_square_fs' ) ) {
    function yellow_square_fs() {
        static $yellow_square_fs = null;

        if ( null === $yellow_square_fs ) {
            $sdk_path = dirname( __FILE__ ) . '/vendor/freemius/start.php';
            if ( file_exists( $sdk_path ) ) {
                require_once $sdk_path;

                if ( function_exists( 'fs_dynamic_init' ) ) {
                    $yellow_square_fs = fs_dynamic_init( array(
                        // TODO: replace with your real Freemius app data
                        'id'                  => '20574',
                        'slug'                => 'yellow-square',
                        'type'                => 'plugin',
                        'public_key'          => 'pk_786a16f156a9233fe6d03954888ab',
                        'is_premium'          => true,
                        'premium_suffix'      => 'Premium',
                        'has_premium_version' => true,
                        'has_addons'          => false,
                        'has_paid_plans'      => true,
                        // If you use a gatekeeper, pull from a constant/env instead of hardcoding:
                        // 'wp_org_gatekeeper'   => defined('YSP_WP_ORG_GATEKEEPER') ? YSP_WP_ORG_GATEKEEPER : '',
                        'menu'                => array(
                            'support' => false,
                        ),
                    ) );
                }
            }
        }

        return $yellow_square_fs;
    }

    // Init Freemius only if SDK loaded, then set basename for upgrade/free->paid logic.
    if ( yellow_square_fs() ) {
        yellow_square_fs()->set_basename( true, __FILE__ );
        do_action( 'yellow_square_fs_loaded' );
    }
}

/**
 * ===========================
 * Utilities / Options
 * ===========================
 */
function ysp_get_dimension( $option_name, $default = 100 ) {
    $val = get_option( $option_name, $default );
    $val = is_numeric( $val ) ? (int) $val : (int) $default;
    return max( 10, min( 1000, $val ) ); // clamp to UI bounds
}

/**
 * ===========================
 * Shortcode: [yellow_square]
 * ===========================
 */
function ysp_render_square() {
    $height = ysp_get_dimension( 'ysp_height', 100 );
    $width  = ysp_get_dimension( 'ysp_width', 100 );

    $html  = '<div id="ysp-container"';
    $html .= ' data-height="' . esc_attr( $height ) . '"';
    $html .= ' data-width="'  . esc_attr( $width )  . '"';
    $html .= '></div>';

    return $html;
}
add_shortcode( 'yellow_square', 'ysp_render_square' );

/**
 * ===========================
 * Assets
 * - Register early
 * - Enqueue only when needed
 * ===========================
 */
function ysp_register_assets() {
    $ver  = '1.0.2';
    $base = plugin_dir_url( __FILE__ );

    wp_register_style(  'ysp-style',  $base . 'style.css',  array(), $ver );
    wp_register_script( 'ysp-script', $base . 'script.js',  array(), $ver, true );
}
add_action( 'wp_enqueue_scripts', 'ysp_register_assets' );

/**
 * Conditionally enqueue on singular pages that contain the shortcode.
 * Fallback: expose a function so anyone calling do_shortcode() can enqueue explicitly.
 */
function ysp_maybe_enqueue_assets() {
    if ( is_singular() ) {
        global $post;
        if ( $post && has_shortcode( $post->post_content, 'yellow_square' ) ) {
            ysp_enqueue_assets_now();
        }
    }
}
add_action( 'wp', 'ysp_maybe_enqueue_assets' );

function ysp_enqueue_assets_now() {
    $height = ysp_get_dimension( 'ysp_height', 100 );
    $width  = ysp_get_dimension( 'ysp_width', 100 );

    wp_enqueue_style( 'ysp-style' );
    wp_enqueue_script( 'ysp-script' );
    wp_localize_script( 'ysp-script', 'yspSettings', array(
        'height' => $height,
        'width'  => $width,
    ) );
}

/**
 * ===========================
 * Settings (Admin)
 * ===========================
 */
function ysp_register_settings() {
    register_setting( 'ysp_settings_group', 'ysp_height', array(
        'type'              => 'integer',
        'sanitize_callback' => function( $val ) { return max( 10, min( 1000, (int) $val ) ); },
        'default'           => 100,
    ) );

    register_setting( 'ysp_settings_group', 'ysp_width', array(
        'type'              => 'integer',
        'sanitize_callback' => function( $val ) { return max( 10, min( 1000, (int) $val ) ); },
        'default'           => 100,
    ) );
}
add_action( 'admin_init', 'ysp_register_settings' );

function ysp_add_options_page() {
    add_options_page(
        __( 'Yellow Square Settings', 'yellow-square' ),
        __( 'Yellow Square', 'yellow-square' ),
        'manage_options',
        'yellow-square-settings',
        'ysp_settings_page'
    );
}
add_action( 'admin_menu', 'ysp_add_options_page' );

/**
 * Settings page with Freemius "Upgrade/Purchase" button
 */
function ysp_settings_page() {
    // Freemius URLs (guarded so it won't fatal if SDK isn't loaded)
    $upgrade_url = '';
    $account_url = '';
    $is_premium  = false;

    if ( function_exists( 'yellow_square_fs' ) && yellow_square_fs() ) {
        $upgrade_url = yellow_square_fs()->get_upgrade_url();
        $account_url = yellow_square_fs()->get_account_url();
        // Treat paying/activated users as premium:
        $is_premium  = yellow_square_fs()->can_use_premium_code();
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Yellow Square Settings', 'yellow-square' ); ?></h1>

        <?php if ( $is_premium ) : ?>
            <div class="notice notice-success" style="margin: 15px 0;">
                <p><strong><?php esc_html_e( 'Premium active', 'yellow-square' ); ?></strong> — <?php esc_html_e( 'Thanks for supporting the plugin!', 'yellow-square' ); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php
                settings_fields( 'ysp_settings_group' );
                do_settings_sections( 'ysp_settings_group' );
            ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Square Height (px)', 'yellow-square' ); ?></th>
                    <td>
                        <input type="number"
                               name="ysp_height"
                               value="<?php echo esc_attr( get_option( 'ysp_height', 100 ) ); ?>"
                               min="10" max="1000" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Square Width (px)', 'yellow-square' ); ?></th>
                    <td>
                        <input type="number"
                               name="ysp_width"
                               value="<?php echo esc_attr( get_option( 'ysp_width', 100 ) ); ?>"
                               min="10" max="1000" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Square Color', 'yellow-square' ); ?>
                        <span style="color:gray;"><?php esc_html_e( '(Premium)', 'yellow-square' ); ?></span>
                    </th>
                    <td><input type="color" value="#FFFF00" disabled style="background:#eee;" /></td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Border Radius', 'yellow-square' ); ?>
                        <span style="color:gray;"><?php esc_html_e( '(Premium)', 'yellow-square' ); ?></span>
                    </th>
                    <td>
                        <input type="number" value="8" disabled style="background:#eee;" /> px
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>

        <p><em><?php esc_html_e( 'Upgrade to Premium to unlock color and border radius options.', 'yellow-square' ); ?></em></p>

        <div style="margin-top:12px;">
            <?php if ( $is_premium && $account_url ) : ?>
                <a href="<?php echo esc_url( $account_url ); ?>" class="button">
                    <?php esc_html_e( 'Manage License', 'yellow-square' ); ?>
                </a>
            <?php elseif ( $upgrade_url ) : ?>
                <a href="<?php echo esc_url( $upgrade_url ); ?>" class="button button-primary" target="_blank" rel="noopener">
                    <?php esc_html_e( 'Upgrade to Premium', 'yellow-square' ); ?>
                </a>
            <?php else : ?>
                <!-- Fallback if Freemius isn't initialized -->
                <a href="#" class="button button-primary" onclick="alert('Upgrade link unavailable: Freemius SDK not initialized.'); return false;">
                    <?php esc_html_e( 'Upgrade to Premium', 'yellow-square' ); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
