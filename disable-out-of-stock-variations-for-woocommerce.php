<?php
/**
 * Plugin Name: Disable Out of Stock Variations for WooCommerce
 * Description: Automatically disables out-of-stock variations and allows custom Out of Stock text for dropdowns.
 * Version: 1.0.2
 * Author: Amirreza Saeedabadi
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Text Domain: disable-out-of-stock-variations-for-woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load translations
load_plugin_textdomain(
    'disable-out-of-stock-variations-for-woocommerce',
    false,
    dirname( plugin_basename( __FILE__ ) ) . '/languages/'
);

/*----------------------------------------------------------
| Activation Requirement Check
----------------------------------------------------------*/
function doosvfw_check_requirements() {
    if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( esc_html__( 'This plugin requires PHP version 7.4 or higher.', 'disable-out-of-stock-variations-for-woocommerce' ) );
    }
    global $wp_version;
    if ( version_compare( $wp_version, '5.9', '<' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( esc_html__( 'This plugin requires WordPress 5.9 or higher.', 'disable-out-of-stock-variations-for-woocommerce' ) );
    }
    if ( ! class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( esc_html__( 'This plugin requires WooCommerce to be installed and active.', 'disable-out-of-stock-variations-for-woocommerce' ) );
    }
}
register_activation_hook( __FILE__, 'doosvfw_check_requirements' );

/*----------------------------------------------------------
| Disable Out Of Stock Variations
----------------------------------------------------------*/
function doosvfw_disable_variations( $active, $variation ) {
    if ( ! $variation->is_in_stock() ) return false;
    return $active;
}
add_filter( 'woocommerce_variation_is_active', 'doosvfw_disable_variations', 10, 2 );

/*----------------------------------------------------------
| Settings
----------------------------------------------------------*/
function doosvfw_register_settings() {
    register_setting(
        'doosvfw_settings_group',
        'doosvfw_outofstock_text',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
}
add_action( 'admin_init', 'doosvfw_register_settings' );

/*----------------------------------------------------------
| Admin Menu
----------------------------------------------------------*/
function doosvfw_add_submenu() {
    add_submenu_page(
        'woocommerce',
        esc_html__( 'Disable Out of Stock Variations', 'disable-out-of-stock-variations-for-woocommerce' ),
        esc_html__( 'Variation Stock', 'disable-out-of-stock-variations-for-woocommerce' ),
        'manage_options',
        'doosvfw-settings',
        'doosvfw_settings_page'
    );
}
add_action( 'admin_menu', 'doosvfw_add_submenu' );

/*----------------------------------------------------------
| Settings Page
----------------------------------------------------------*/
function doosvfw_settings_page() {
    $value = get_option( 'doosvfw_outofstock_text', '' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Disable Out of Stock Variations', 'disable-out-of-stock-variations-for-woocommerce' ); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'doosvfw_settings_group' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Out of Stock Text', 'disable-out-of-stock-variations-for-woocommerce' ); ?></th>
                    <td>
                        <input
                            type="text"
                            name="doosvfw_outofstock_text"
                            value="<?php echo esc_attr( $value ); ?>"
                            placeholder="<?php echo esc_attr__( 'Out of stock', 'disable-out-of-stock-variations-for-woocommerce' ); ?>"
                            class="regular-text"
                        />
                        <p class="description"><?php esc_html_e( 'Leave empty to use default text.', 'disable-out-of-stock-variations-for-woocommerce' ); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/*----------------------------------------------------------
| Change Variation Option Text in Dropdown
----------------------------------------------------------*/
function doosvfw_variation_option_label( $term_name ) {
    global $product;

    if ( ! $product || ! $product->is_type( 'variable' ) ) return $term_name;

    // Custom text entered by user
    $custom_text = trim( get_option( 'doosvfw_outofstock_text', '' ) );

    // If empty, use gettext for translation
    $display_text = ! empty( $custom_text ) ? $custom_text : __( 'Out of stock', 'disable-out-of-stock-variations-for-woocommerce' );

    foreach ( $product->get_children() as $variation_id ) {
        $variation = wc_get_product( $variation_id );
        if ( ! $variation ) continue;

        $attributes = $variation->get_attributes();

        foreach ( $attributes as $value ) {
            if ( $value === $term_name && ! $variation->is_in_stock() ) {
                return $term_name . ' (' . $display_text . ')';
            }
        }
    }

    return $term_name;
}
add_filter( 'woocommerce_variation_option_name', 'doosvfw_variation_option_label', 10, 1 );
