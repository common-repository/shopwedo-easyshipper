<?php
/*
Plugin Name: ShopWeDo e-fulfilment
Plugin URI:  https://wordpress.org/plugins/shopwedo-easyshipper
Description: Integrates the ShopWeDo Multi-Carrier Pick-up Location Finder into your WooCommerce Orders & Checkout. Compatible with ShopWeDo e-fulfilment.
Version:     1.2.0
Author:      ShopWeDo.com
Author URI:  https://www.shopwedo.com/
License:     GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: shopwedo-easyshipper
Domain Path: /languages

WooCommerce ShopWeDo is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 */
function activate_shopwedo() {
	require_once plugin_dir_path( __FILE__ ) . 'classes/shopwedo-activator.php';
	Shopwedo_Activator::activate();
}
register_activation_hook(__FILE__, 'activate_shopwedo');

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_shopwedo() {
	require_once plugin_dir_path( __FILE__ ) . 'classes/shopwedo-deactivator.php';
	Shopwedo_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_shopwedo' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'classes/shopwedo-pu.php';