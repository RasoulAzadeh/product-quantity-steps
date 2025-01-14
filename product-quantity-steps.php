<?php
/*
Plugin Name: Product Quantity Steps
Plugin URI: https://github.com/DatisFreeman/woocommerce-product-quantity-steps
Description: Manage WooCommerce product and/or variants' quantity steps with client-side validation and admin configuration.
Version: 0.4.1
Author: Rasoul Azadeh
Author URI: https://www.linkedin.com/in/rasoul-azadeh
Text Domain: product-quantity-steps
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Include admin settings
require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-settings.php';

// Include variation settings
require_once plugin_dir_path(__FILE__) . 'includes/admin/variation-settings.php';

// Include frontend scripts
require_once plugin_dir_path(__FILE__) . 'includes/public/public-scripts.php';
