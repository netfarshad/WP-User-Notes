<?php
defined( 'ABSPATH' ) || exit;

/* ════════════════════════════════════════
   ثوابت امنیتی
════════════════════════════════════════ */
define( 'WPN_MAX_MESSAGE_LENGTH', 2000 );  // حداکثر طول پیام (کاراکتر)
define( 'WPN_RATE_LIMIT_COUNT',   10   );  // حداکثر پیام در بازه زمانی
define( 'WPN_RATE_LIMIT_WINDOW',  300  );  // بازه زمانی به ثانیه (5 دقیقه)

/* ════════════════════════════════════════
   تابع کمکی: بررسی Rate Limit
════════════════════════════════════════ */
function wpn_check_rate_limit( $user_id ) {
    $key       = 'wpn_rl_' . $user_id;
    $now       = time();
    $window    = WPN_RATE_LIMIT_WINDOW;
    $max_count = WPN_RATE_LIMIT_COUNT;

    $record = get_transient( $key );

    if ( false === $record ) {
        // اولین درخواست در این بازه
        set_transient( $key, array( 'count' => 1, 'start' => $now ), $window );
        return true;
    }

    // اگر بازه زمانی تموم شده، ریست کن
    if ( ( $now - $record['start'] ) >= $window ) {
        set_transient( $key, array( 'count' => 1, 'start' => $now ), $window );
        return true;
    }

    // بررسی تعداد در بازه جاری
    if ( $record['count'] >= $max_count ) {
        return false; // تجاوز از حد مجاز
    }

    // افزایش شمارنده
    $record['count']++;
    set_transient( $key, $record, $window - ( $now - $record['start'] ) );
    return true;
}

/* ════════════════════════════════════════
   ثبت یادداشت جدید
════════════════════════════════════════ */
add_action( 'wp_ajax_wpn_send_note', 'wpn_ajax_send_note' );
function wpn_ajax_send_note() {
    check_ajax_referer( 'wpn_nonce', 'nonce' );

    $current  = get_current_user_id();
    $receiver = absint( isset( $_POST['receiver_id'] ) ? $_POST['receiver_id'] : 0 );
    $message  = sanitize_textarea_field( isset( $_POST['message'] ) ? $_POST['message'] : '' );

    // ── اعتبارسنجی اولیه ──
    if ( ! $current || ! $receiver || ! $message ) {
        wp_send_json_error( 'اطلاعات ناقص است.' );
    }

    if ( $current === $receiver ) {
        wp_send_json_error( 'نمی‌توانید به خودتان یادداشت بفرستید.' );
    }

    // ── بررسی طول پیام ──
    if ( mb_strlen( $message, 'UTF-8' ) > WPN_MAX_MESSAGE_LENGTH ) {
        wp_send_json_error(
            sprintf( 'طول پیام نباید از %d کاراکتر بیشتر باشد.', WPN_MAX_MESSAGE_LENGTH )
        );
    }

    // ── Rate Limiting ──
    if ( ! wpn_check_rate_limit( $current ) ) {
        wp_send_json_error(
            sprintf(
                'تعداد پیام‌های ارسالی شما در %d دقیقه گذشته به حد مجاز (%d پیام) رسیده است. لطفاً کمی صبر کنید.',
                WPN_RATE_LIMIT_WINDOW / 60,
                WPN_RATE_LIMIT_COUNT
            )
        );
    }

    // ── وجود گیرنده ──
    if ( ! get_userdata( $receiver ) ) {
        wp_send_json_error( 'کاربر موردنظر یافت نشد.' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'user_notes';

    $inserted = $wpdb->insert(
        $table,
        array(
            'sender_id'   => $current,
            'receiver_id' => $receiver,
            'message'     => $message,
            'is_read'     => 0,
        ),
        array( '%d', '%d', '%s', '%d' )
    );

    if ( false === $inserted ) {
        wp_send_json_error( 'خطا در ذخیره‌سازی.' );
    }

    wp_send_json_success( 'یادداشت با موفقیت ارسال شد.' );
}

/* ════════════════════════════════════════
   صندوق ورودی: ۲۰ پیام آخر
════════════════════════════════════════ */
add_action( 'wp_ajax_wpn_get_inbox', 'wpn_ajax_get_inbox' );
function wpn_ajax_get_inbox() {
    check_ajax_referer( 'wpn_nonce', 'nonce' );

    $current = get_current_user_id();
    if ( ! $current ) { wp_send_json_error( 'Unauthorized' ); }

    global $wpdb;
    $table = $wpdb->prefix . 'user_notes';

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT n.id, n.message, n.is_read, n.created_at,
                    u.display_name AS sender_name
             FROM {$table} n
             INNER JOIN {$wpdb->users} u ON u.ID = n.sender_id
             WHERE n.receiver_id = %d
             ORDER BY n.created_at DESC
             LIMIT 20",
            $current
        )
    );

    // علامت‌گذاری به‌عنوان خوانده‌شده
    $wpdb->update(
        $table,
        array( 'is_read' => 1 ),
        array( 'receiver_id' => $current, 'is_read' => 0 ),
        array( '%d' ),
        array( '%d', '%d' )
    );

    $total = (int) $wpdb->get_var(
        $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE receiver_id = %d", $current )
    );

    wp_send_json_success( array( 'messages' => $rows, 'total' => $total ) );
}

/* ════════════════════════════════════════
   پیام‌های ارسال‌شده: ۲۰ پیام آخر
════════════════════════════════════════ */
add_action( 'wp_ajax_wpn_get_sent', 'wpn_ajax_get_sent' );
function wpn_ajax_get_sent() {
    check_ajax_referer( 'wpn_nonce', 'nonce' );

    $current = get_current_user_id();
    if ( ! $current ) { wp_send_json_error( 'Unauthorized' ); }

    global $wpdb;
    $table = $wpdb->prefix . 'user_notes';

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT n.id, n.message, n.is_read, n.created_at,
                    u.display_name AS receiver_name
             FROM {$table} n
             INNER JOIN {$wpdb->users} u ON u.ID = n.receiver_id
             WHERE n.sender_id = %d
             ORDER BY n.created_at DESC
             LIMIT 20",
            $current
        )
    );

    $total = (int) $wpdb->get_var(
        $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE sender_id = %d", $current )
    );

    wp_send_json_success( array( 'messages' => $rows, 'total' => $total ) );
}

/* ════════════════════════════════════════
   تعداد پیام‌های خوانده‌نشده
════════════════════════════════════════ */
add_action( 'wp_ajax_wpn_unread_count', 'wpn_ajax_unread_count' );
function wpn_ajax_unread_count() {
    check_ajax_referer( 'wpn_nonce', 'nonce' );

    $current = get_current_user_id();
    if ( ! $current ) { wp_send_json_error( 'Unauthorized' ); }

    global $wpdb;
    $table = $wpdb->prefix . 'user_notes';

    $count = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE receiver_id = %d AND is_read = 0",
            $current
        )
    );

    wp_send_json_success( array( 'count' => $count ) );
}
