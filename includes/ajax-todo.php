<?php
defined( 'ABSPATH' ) || exit;

define( 'WPN_TODO_MAX_LENGTH', 500 );
define( 'WPN_TODO_RATE_LIMIT', 20 );   // حداکثر وظیفه جدید در 5 دقیقه

/* ════════════════════════════════
   افزودن وظیفه جدید
════════════════════════════════ */
add_action( 'wp_ajax_wpn_todo_add', 'wpn_ajax_todo_add' );
function wpn_ajax_todo_add() {
    check_ajax_referer( 'wpn_nonce', 'nonce' );

    $user_id = get_current_user_id();
    if ( ! $user_id ) { wp_send_json_error( 'Unauthorized' ); }

    $task = sanitize_textarea_field( isset( $_POST['task'] ) ? $_POST['task'] : '' );
    if ( ! $task ) { wp_send_json_error( 'متن وظیفه خالی است.' ); }

    if ( mb_strlen( $task, 'UTF-8' ) > WPN_TODO_MAX_LENGTH ) {
        wp_send_json_error( sprintf( 'متن نباید از %d کاراکتر بیشتر باشد.', WPN_TODO_MAX_LENGTH ) );
    }

    /* Rate limit */
    $rl_key = 'wpn_todo_rl_' . $user_id;
    $rl     = get_transient( $rl_key );
    if ( false === $rl ) {
        set_transient( $rl_key, 1, 300 );
    } elseif ( (int) $rl >= WPN_TODO_RATE_LIMIT ) {
        wp_send_json_error( 'تعداد افزودن وظایف به حد مجاز رسیده است. کمی صبر کنید.' );
    } else {
        set_transient( $rl_key, $rl + 1, 300 );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'user_todos';

    $inserted = $wpdb->insert(
        $table,
        array( 'user_id' => $user_id, 'task' => $task, 'status' => 0 ),
        array( '%d', '%s', '%d' )
    );

    if ( false === $inserted ) { wp_send_json_error( 'خطا در ذخیره.' ); }

    wp_send_json_success( array(
        'id'         => (int) $wpdb->insert_id,
        'task'       => $task,
        'status'     => 0,
        'created_at' => current_time( 'mysql' ),
    ) );
}

/* ════════════════════════════════
   تغییر وضعیت (در حال انجام / انجام‌شده)
════════════════════════════════ */
add_action( 'wp_ajax_wpn_todo_toggle', 'wpn_ajax_todo_toggle' );
function wpn_ajax_todo_toggle() {
    check_ajax_referer( 'wpn_nonce', 'nonce' );

    $user_id = get_current_user_id();
    if ( ! $user_id ) { wp_send_json_error( 'Unauthorized' ); }

    $id = absint( isset( $_POST['todo_id'] ) ? $_POST['todo_id'] : 0 );
    if ( ! $id ) { wp_send_json_error( 'شناسه نامعتبر.' ); }

    global $wpdb;
    $table = $wpdb->prefix . 'user_todos';

    /* ── فقط وظیفه‌ی خود کاربر ── */
    $current = (int) $wpdb->get_var(
        $wpdb->prepare( "SELECT status FROM {$table} WHERE id = %d AND user_id = %d", $id, $user_id )
    );

    if ( null === $current ) { wp_send_json_error( 'وظیفه یافت نشد.' ); }

    $new_status = $current ? 0 : 1;
    $wpdb->update( $table, array( 'status' => $new_status ), array( 'id' => $id, 'user_id' => $user_id ), array( '%d' ), array( '%d', '%d' ) );

    wp_send_json_success( array( 'id' => $id, 'status' => $new_status ) );
}

/* ════════════════════════════════
   حذف وظیفه
════════════════════════════════ */
add_action( 'wp_ajax_wpn_todo_delete', 'wpn_ajax_todo_delete' );
function wpn_ajax_todo_delete() {
    check_ajax_referer( 'wpn_nonce', 'nonce' );

    $user_id = get_current_user_id();
    if ( ! $user_id ) { wp_send_json_error( 'Unauthorized' ); }

    $id = absint( isset( $_POST['todo_id'] ) ? $_POST['todo_id'] : 0 );
    if ( ! $id ) { wp_send_json_error( 'شناسه نامعتبر.' ); }

    global $wpdb;
    $table = $wpdb->prefix . 'user_todos';

    /* ── فقط وظیفه‌ی خود کاربر قابل حذف است ── */
    $deleted = $wpdb->delete( $table, array( 'id' => $id, 'user_id' => $user_id ), array( '%d', '%d' ) );

    if ( ! $deleted ) { wp_send_json_error( 'وظیفه یافت نشد یا دسترسی ندارید.' ); }

    wp_send_json_success( array( 'id' => $id ) );
}

/* ════════════════════════════════
   دریافت وظایف (داشبورد: ۱۰ تای آخر)
════════════════════════════════ */
add_action( 'wp_ajax_wpn_todo_get', 'wpn_ajax_todo_get' );
function wpn_ajax_todo_get() {
    check_ajax_referer( 'wpn_nonce', 'nonce' );

    $user_id = get_current_user_id();
    if ( ! $user_id ) { wp_send_json_error( 'Unauthorized' ); }

    global $wpdb;
    $table = $wpdb->prefix . 'user_todos';

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, task, status, created_at FROM {$table}
             WHERE user_id = %d
             ORDER BY status ASC, created_at DESC
             LIMIT 10",
            $user_id
        )
    );

    $total = (int) $wpdb->get_var(
        $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE user_id = %d", $user_id )
    );

    wp_send_json_success( array( 'todos' => $rows, 'total' => $total ) );
}
