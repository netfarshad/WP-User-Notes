<?php
/**
 * Plugin Name: WP User Notes
 * Plugin URI:  https://www.youtube.com/@netfarshad
 * Description: ارسال یادداشت خصوصی بین کاربران داشبورد وردپرس + لیست وظایف شخصی
 * Version:     1.1.6
 * Author:      فرشاد سعیدزاده
 * Author URI:  https://www.youtube.com/@netfarshad
 * Text Domain: wp-user-notes
 * License:     GPL-2.0+
 */

defined( 'ABSPATH' ) || exit;

define( 'WPN_VERSION', '1.1.6' );
define( 'WPN_DIR',     plugin_dir_path( __FILE__ ) );
define( 'WPN_URL',     plugin_dir_url( __FILE__ ) );

require_once WPN_DIR . 'includes/install.php';
require_once WPN_DIR . 'includes/ajax.php';
require_once WPN_DIR . 'includes/ajax-todo.php';
require_once WPN_DIR . 'includes/dashboard.php';
require_once WPN_DIR . 'includes/archive.php';

register_activation_hook( __FILE__, 'wpn_install' );

/* ── اجرای آپدیت جدول برای نصب‌های قبلی ── */
add_action( 'plugins_loaded', 'wpn_maybe_upgrade' );
function wpn_maybe_upgrade() {
    if ( get_option( 'wpn_db_version' ) !== WPN_VERSION ) {
        wpn_install();
    }
}
