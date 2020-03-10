<?php 
/**
 * Plugin Name
 *
 * @package           Woo Print Orders & Shipping Labels
 * @author            Raylin Aquino
 * @copyright         2019 raylinaquino.com
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Woo Print Orders & Shipping Labels
 * Plugin URI:        https://raylinaquino.com
 * Description:       Add capabilities for printer multiple orders and Shipping Labels
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      5.2
 * Author:            Raylin Aquino
 * Author URI:        https://raylinaquino.com
 * Text Domain:       woo_print_inv_labels
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

define('WOO_INV_LABELS_CURRENT_DIR', plugin_dir_path( __FILE__ ));
define('WOO_INV_LABELS_CURRENT_URL', plugin_dir_url( __FILE__ ));

 

require( WOO_INV_LABELS_CURRENT_DIR.'/woo.class.php');

load_plugin_textdomain( 'woo_print_inv_labels', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

new WooPrintInvLabels(); 
 
?>