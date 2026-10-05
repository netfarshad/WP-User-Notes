<?php
defined( 'ABSPATH' ) || exit;

function wpn_install() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    /* ── جدول پیام‌های کاربر به کاربر ── */
    $t1  = $wpdb->prefix . 'user_notes';
    dbDelta( "CREATE TABLE IF NOT EXISTS {$t1} (
        id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        sender_id   BIGINT(20) UNSIGNED NOT NULL,
        receiver_id BIGINT(20) UNSIGNED NOT NULL,
        message     TEXT NOT NULL,
        is_read     TINYINT(1) NOT NULL DEFAULT 0,
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY sender_id   (sender_id),
        KEY receiver_id (receiver_id)
    ) {$charset};" );

    /* ── جدول وظایف شخصی (To-Do) ── */
    $t2 = $wpdb->prefix . 'user_todos';
    dbDelta( "CREATE TABLE IF NOT EXISTS {$t2} (
        id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id    BIGINT(20) UNSIGNED NOT NULL,
        task       TEXT NOT NULL,
        status     TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id)
    ) {$charset};" );

    update_option( 'wpn_db_version', WPN_VERSION );
}
