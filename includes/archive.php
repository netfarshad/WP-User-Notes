<?php
defined( 'ABSPATH' ) || exit;

/* ════════════════════════════════════════
   صفحه آرشیو پیام‌های کاربر به کاربر
════════════════════════════════════════ */
add_action( 'admin_menu', 'wpn_register_archive_pages' );
function wpn_register_archive_pages() {
    add_submenu_page( null, 'آرشیو یادداشت‌ها',  'آرشیو یادداشت‌ها',  'read', 'wpn-archive',      'wpn_render_notes_archive' );
    add_submenu_page( null, 'آرشیو وظایف من',     'آرشیو وظایف',       'read', 'wpn-todo-archive',  'wpn_render_todo_archive'  );
}

/* ─── CSS مشترک آرشیوها ─── */
function wpn_archive_css() { ?>
<style>
body{font-family:Tahoma,Arial,sans-serif;direction:rtl;text-align:right;background:#f1f1f1;color:#333;margin:0;padding:0}
.wpna-wrap{max-width:860px;margin:30px auto;padding:0 15px}
.wpna-header{background:#fff;border:1px solid #ccd;border-radius:6px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.wpna-header h1{margin:0;font-size:18px;color:#1d2327}
.wpna-tabs{display:flex;gap:6px}
.wpna-tab{text-decoration:none;padding:7px 16px;border-radius:4px;font-size:13px;border:1px solid #ccd;background:#f9f9f9;color:#555}
.wpna-tab:hover{background:#f0f6fc;color:#0073aa}
.wpna-tab-active{background:#0073aa;color:#fff!important;border-color:#0073aa}
.wpna-back{font-size:12px;color:#0073aa;text-decoration:none;border:1px solid #0073aa;padding:5px 12px;border-radius:4px}
.wpna-back:hover{background:#0073aa;color:#fff}
.wpna-card{background:#fff;border:1px solid #ddd;border-radius:6px;margin-bottom:12px;padding:14px 18px;line-height:1.8}
.wpna-card.wpna-unread{border-right:4px solid #f0ad4e;background:#fffdf5}
.wpna-card-meta{font-size:12px;color:#999;margin-top:6px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px}
.wpna-badge{display:inline-block;font-size:11px;padding:2px 9px;border-radius:10px;font-weight:bold}
.wpna-badge-unread{background:#f0ad4e;color:#fff}
.wpna-badge-read{background:#d4edda;color:#155724}
.wpna-badge-sent-unread{background:#f8d7da;color:#721c24}
.wpna-badge-sent-read{background:#d4edda;color:#155724}
.wpna-msg{margin-top:6px;white-space:pre-wrap;word-break:break-word}
.wpna-empty{background:#fff;border:1px solid #ddd;border-radius:6px;padding:40px;text-align:center;color:#aaa;font-size:14px}
.wpna-pagination{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:20px;flex-wrap:wrap}
.wpna-pagination a,.wpna-pagination span{padding:6px 12px;border:1px solid #ccd;border-radius:4px;text-decoration:none;font-size:13px;color:#0073aa;background:#fff}
.wpna-pagination .wpna-current{background:#0073aa;color:#fff;border-color:#0073aa}
.wpna-pagination .wpna-disabled{color:#bbb;pointer-events:none;border-color:#eee;background:#fafafa}
.wpna-info-bar{font-size:12px;color:#888;margin-bottom:12px}
.wpna-footer{margin-top:24px;padding-top:14px;border-top:1px solid #e0e0e0;text-align:center;font-size:12px;color:#bbb;line-height:2.2}
.wpna-footer a{color:#0073aa;text-decoration:none;margin:0 5px}
/* Todo آرشیو */
.wpnt-card{background:#fff;border:2px solid #f0ad4e;border-radius:6px;margin-bottom:10px;padding:13px 16px;line-height:1.8;display:flex;align-items:flex-start;gap:10px}
.wpnt-card.wpnt-done{border-color:#28a745;background:#f6fff8}
.wpnt-icon{font-size:18px;flex-shrink:0;margin-top:2px}
.wpnt-task-text{flex:1;word-break:break-word}
.wpnt-task-text.wpnt-done-text{text-decoration:line-through;color:#888}
.wpnt-status{font-size:11px;font-weight:bold;margin-top:4px;display:block}
.wpnt-status-pending{color:#b07d00}
.wpnt-status-done{color:#28a745}
</style>
<?php }

/* ─── تابع کمکی صفحه‌بندی ─── */
function wpn_paginate( $base_url, $paged, $total_pages ) {
    if ( $total_pages <= 1 ) return;
    echo '<div class="wpna-pagination">';
    echo $paged > 1
        ? '<a href="' . esc_url($base_url . '&paged=' . ($paged-1)) . '">&laquo; قبلی</a>'
        : '<span class="wpna-disabled">&laquo; قبلی</span>';
    $start = max(1,$paged-2); $end = min($total_pages,$paged+2);
    if($start>1) echo '<span>...</span>';
    for($i=$start;$i<=$end;$i++)
        echo $i===$paged
            ? '<span class="wpna-current">'.$i.'</span>'
            : '<a href="'.esc_url($base_url.'&paged='.$i).'">'.$i.'</a>';
    if($end<$total_pages) echo '<span>...</span>';
    echo $paged < $total_pages
        ? '<a href="' . esc_url($base_url . '&paged=' . ($paged+1)) . '">بعدی &raquo;</a>'
        : '<span class="wpna-disabled">بعدی &raquo;</span>';
    echo '</div>';
}

/* ─── فوتر مشترک ─── */
function wpn_archive_footer() { ?>
<div class="wpna-footer">
    ساخته شده با ❤️ توسط فرشاد سعیدزاده &nbsp;&bull;&nbsp;
    <a href="https://www.youtube.com/@netfarshad" target="_blank">▶ یوتیوب</a> &bull;
    <a href="https://t.me/netfarshad" target="_blank">✈ تلگرام</a> &bull;
    <a href="https://www.instagram.com/netfarshad" target="_blank">📷 اینستاگرام</a>
</div>
<?php }

/* ════════════════════════════════════════
   رندر: آرشیو پیام‌های کاربر به کاربر
════════════════════════════════════════ */
function wpn_render_notes_archive() {
    if ( ! is_user_logged_in() || ! current_user_can('read') ) {
        wp_die('دسترسی غیرمجاز.','خطا',array('response'=>403));
    }
    $current   = get_current_user_id();
    $tab       = ( isset($_GET['tab']) && $_GET['tab']==='sent' ) ? 'sent' : 'inbox';
    $per_page  = 15;
    $paged     = isset($_GET['paged']) ? max(1,absint($_GET['paged'])) : 1;
    $offset    = ($paged-1)*$per_page;
    global $wpdb; $table = $wpdb->prefix . 'user_notes';

    if($tab==='inbox'){
        $total = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE receiver_id=%d",$current));
        $rows  = $wpdb->get_results($wpdb->prepare(
            "SELECT n.id,n.message,n.is_read,n.created_at,u.display_name AS other_name
             FROM {$table} n INNER JOIN {$wpdb->users} u ON u.ID=n.sender_id
             WHERE n.receiver_id=%d ORDER BY n.created_at DESC LIMIT %d OFFSET %d",
            $current,$per_page,$offset));
        if(!empty($rows)){
            $ids=array_map(function($r){return(int)$r->id;},$rows);
            $ph=implode(',',array_fill(0,count($ids),'%d'));
            $wpdb->query($wpdb->prepare("UPDATE {$table} SET is_read=1 WHERE id IN({$ph}) AND receiver_id=%d",array_merge($ids,array($current))));
        }
    } else {
        $total = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE sender_id=%d",$current));
        $rows  = $wpdb->get_results($wpdb->prepare(
            "SELECT n.id,n.message,n.is_read,n.created_at,u.display_name AS other_name
             FROM {$table} n INNER JOIN {$wpdb->users} u ON u.ID=n.receiver_id
             WHERE n.sender_id=%d ORDER BY n.created_at DESC LIMIT %d OFFSET %d",
            $current,$per_page,$offset));
    }
    $total_pages = max(1,ceil($total/$per_page));
    $base_url    = admin_url('admin.php?page=wpn-archive&tab='.$tab);
    ?><!DOCTYPE html><html <?php language_attributes();?>><head>
    <meta charset="<?php bloginfo('charset');?>"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>آرشیو یادداشت‌ها</title><?php do_action('admin_head'); wpn_archive_css(); ?>
    </head><body class="wp-admin"><div class="wpna-wrap">
    <div class="wpna-header">
        <h1>آرشیو یادداشت‌های من</h1>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <div class="wpna-tabs">
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpn-archive&tab=inbox'));?>" class="wpna-tab <?php echo $tab==='inbox'?'wpna-tab-active':'';?>">ورودی</a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpn-archive&tab=sent'));?>"  class="wpna-tab <?php echo $tab==='sent'?'wpna-tab-active':'';?>">ارسالی</a>
            </div>
            <a href="<?php echo esc_url(admin_url('index.php'));?>" class="wpna-back">&rarr; داشبورد</a>
        </div>
    </div>
    <div class="wpna-info-bar">مجموع: <strong><?php echo number_format_i18n($total);?></strong> پیام &mdash; صفحه <strong><?php echo $paged;?></strong> از <strong><?php echo $total_pages;?></strong></div>
    <?php if(empty($rows)): ?><div class="wpna-empty">پیامی وجود ندارد.</div>
    <?php else: foreach($rows as $n):
        $unread=($n->is_read==='0'||(int)$n->is_read===0);
        $date=wp_date('Y/m/d H:i',strtotime($n->created_at));?>
        <div class="wpna-card <?php echo($tab==='inbox'&&$unread)?'wpna-unread':'';?>">
            <?php if($tab==='inbox'):?><span style="font-weight:bold">از: <?php echo esc_html($n->other_name);?></span>
            <?php else:?>              <span style="font-weight:bold">به: <?php echo esc_html($n->other_name);?></span>
            <?php endif;?>
            <div class="wpna-msg"><?php echo esc_html($n->message);?></div>
            <div class="wpna-card-meta">
                <span><?php echo esc_html($date);?></span>
                <?php if($tab==='inbox'):?>
                    <span class="wpna-badge <?php echo $unread?'wpna-badge-unread':'wpna-badge-read';?>"><?php echo $unread?'خوانده‌نشده':'خوانده‌شده';?></span>
                <?php else:?>
                    <span class="wpna-badge <?php echo $unread?'wpna-badge-sent-unread':'wpna-badge-sent-read';?>"><?php echo $unread?'هنوز نخوانده':'خوانده شد';?></span>
                <?php endif;?>
            </div>
        </div>
    <?php endforeach; wpn_paginate($base_url,$paged,$total_pages); endif;?>
    <?php wpn_archive_footer();?></div></body></html>
    <?php exit;
}

/* ════════════════════════════════════════
   رندر: آرشیو وظایف شخصی (To-Do)
════════════════════════════════════════ */
function wpn_render_todo_archive() {
    if ( ! is_user_logged_in() || ! current_user_can('read') ) {
        wp_die('دسترسی غیرمجاز.','خطا',array('response'=>403));
    }
    $current  = get_current_user_id();
    $filter   = (isset($_GET['filter'])&&$_GET['filter']==='done') ? 'done' : (isset($_GET['filter'])&&$_GET['filter']==='pending'?'pending':'all');
    $per_page = 15;
    $paged    = isset($_GET['paged']) ? max(1,absint($_GET['paged'])) : 1;
    $offset   = ($paged-1)*$per_page;
    global $wpdb; $table = $wpdb->prefix . 'user_todos';

    $where = $wpdb->prepare("WHERE user_id=%d",$current);
    if($filter==='done')    $where.=" AND status=1";
    if($filter==='pending') $where.=" AND status=0";

    $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");
    $rows  = $wpdb->get_results($wpdb->prepare(
        "SELECT id,task,status,created_at FROM {$table} {$where}
         ORDER BY status ASC, created_at DESC LIMIT %d OFFSET %d",
        $per_page,$offset));
    $total_pages = max(1,ceil($total/$per_page));
    $base_url    = admin_url('admin.php?page=wpn-todo-archive&filter='.$filter);

    // حذف از این صفحه
    if(!empty($_POST['wpnt_delete_id']) && check_admin_referer('wpnt_delete','wpnt_nonce')){
        $del_id = absint($_POST['wpnt_delete_id']);
        $wpdb->delete($table,array('id'=>$del_id,'user_id'=>$current),array('%d','%d'));
        wp_safe_redirect($base_url.'&paged='.$paged.'&deleted=1'); exit;
    }
    ?><!DOCTYPE html><html <?php language_attributes();?>><head>
    <meta charset="<?php bloginfo('charset');?>"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>آرشیو وظایف من</title><?php do_action('admin_head'); wpn_archive_css(); ?>
    </head><body class="wp-admin"><div class="wpna-wrap">

    <div class="wpna-header">
        <h1>آرشیو وظایف من</h1>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <div class="wpna-tabs">
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpn-todo-archive&filter=all'));?>"     class="wpna-tab <?php echo $filter==='all'    ?'wpna-tab-active':'';?>">همه</a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpn-todo-archive&filter=pending'));?>" class="wpna-tab <?php echo $filter==='pending'?'wpna-tab-active':'';?>">در حال انجام</a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpn-todo-archive&filter=done'));?>"    class="wpna-tab <?php echo $filter==='done'   ?'wpna-tab-active':'';?>">انجام‌شده</a>
            </div>
            <a href="<?php echo esc_url(admin_url('index.php'));?>" class="wpna-back">&rarr; داشبورد</a>
        </div>
    </div>

    <?php if(isset($_GET['deleted'])): ?>
    <div style="background:#d4edda;border:1px solid #28a745;color:#155724;padding:10px 14px;border-radius:5px;margin-bottom:14px;">وظیفه با موفقیت حذف شد.</div>
    <?php endif;?>

    <div class="wpna-info-bar">مجموع: <strong><?php echo number_format_i18n($total);?></strong> وظیفه &mdash; صفحه <strong><?php echo $paged;?></strong> از <strong><?php echo $total_pages;?></strong></div>

    <?php if(empty($rows)): ?><div class="wpna-empty">وظیفه‌ای یافت نشد.</div>
    <?php else: foreach($rows as $t):
        $done=($t->status==='1'||(int)$t->status===1);
        $date=wp_date('Y/m/d H:i',strtotime($t->created_at));?>
        <div class="wpnt-card <?php echo $done?'wpnt-done':'';?>">
            <div class="wpnt-icon"><?php echo $done?'✅':'🕐';?></div>
            <div style="flex:1">
                <div class="wpnt-task-text <?php echo $done?'wpnt-done-text':'';?>"><?php echo esc_html($t->task);?></div>
                <span class="wpnt-status <?php echo $done?'wpnt-status-done':'wpnt-status-pending';?>"><?php echo $done?'انجام شد':'در حال انجام';?></span>
                <div style="font-size:11px;color:#bbb;margin-top:2px"><?php echo esc_html($date);?></div>
            </div>
            <form method="post" style="flex-shrink:0;margin:0" onsubmit="return confirm('آیا مطمئن هستید؟')">
                <?php wp_nonce_field('wpnt_delete','wpnt_nonce');?>
                <input type="hidden" name="wpnt_delete_id" value="<?php echo (int)$t->id;?>">
                <button type="submit" style="background:none;border:none;cursor:pointer;color:#ccc;font-size:18px;padding:0;line-height:1" title="حذف" onmouseover="this.style.color='#c00'" onmouseout="this.style.color='#ccc'">✕</button>
            </form>
        </div>
    <?php endforeach; wpn_paginate($base_url,$paged,$total_pages); endif;?>
    <?php wpn_archive_footer();?></div></body></html>
    <?php exit;
}
