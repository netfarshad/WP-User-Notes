<?php
defined( 'ABSPATH' ) || exit;

/* ════════════════════════════════════════
   ثبت دو ویجت داشبورد
════════════════════════════════════════ */
add_action( 'wp_dashboard_setup', 'wpn_register_widgets' );
function wpn_register_widgets() {
    wp_add_dashboard_widget( 'wpn_notes_widget', '📩 یادداشت‌های کاربران',  'wpn_render_notes_widget' );
    wp_add_dashboard_widget( 'wpn_todo_widget',  '✅ لیست وظایف من',         'wpn_render_todo_widget'  );
}

/* ════════════════════════════════════════
   CSS / JS — فقط در داشبورد
════════════════════════════════════════ */
add_action( 'admin_head', 'wpn_enqueue_assets' );
function wpn_enqueue_assets() {
    $screen = get_current_screen();
    if ( ! $screen || 'dashboard' !== $screen->id ) { return; }

    $archive_todo_url = admin_url( 'admin.php?page=wpn-todo-archive' );

    $js = array(
        'ajaxurl'    => admin_url( 'admin-ajax.php' ),
        'nonce'      => wp_create_nonce( 'wpn_nonce' ),
        'max_length' => defined('WPN_MAX_MESSAGE_LENGTH') ? WPN_MAX_MESSAGE_LENGTH : 2000,
        'todo_max'   => defined('WPN_TODO_MAX_LENGTH')    ? WPN_TODO_MAX_LENGTH    : 500,
        'todo_archive_url' => $archive_todo_url,
        's' => array(
            'sent'          => 'یادداشت ارسال شد.',
            'error'         => 'خطا. دوباره امتحان کنید.',
            'empty_msg'     => 'پیامی وجود ندارد.',
            'select_user'   => 'لطفاً یک کاربر انتخاب کنید.',
            'empty_text'    => 'لطفاً متن را وارد کنید.',
            'too_long'      => 'متن خیلی طولانی است.',
            'unread'        => 'خوانده‌نشده',
            'read'          => 'خوانده‌شده',
            'loading'       => 'در حال بارگذاری...',
            'more_inbox'    => 'پیام دیگر در آرشیو',
            'more_sent'     => 'پیام دیگر در آرشیو',
            'todo_empty'    => 'وظیفه‌ای ثبت نشده.',
            'todo_more'     => 'وظیفه دیگر در آرشیو',
            'confirm_del'   => 'آیا مطمئن هستید؟',
        ),
    );
    ?>
<style>
/* ─── مشترک ─── */
.wpn-wrap{font-family:Tahoma,Arial,sans-serif;font-size:13px;color:#333;direction:rtl;text-align:right}
.wpn-tabs{display:flex;gap:4px;border-bottom:2px solid #0073aa;margin-bottom:12px}
.wpn-tab{background:none;border:1px solid transparent;padding:7px 14px;cursor:pointer;border-radius:4px 4px 0 0;font-size:13px;color:#555}
.wpn-tab:hover{background:#f0f6fc}
.wpn-tab-active{background:#0073aa!important;color:#fff!important;border-color:#0073aa}
.wpn-panel{display:none}.wpn-panel-active{display:block}
.wpn-list{max-height:300px;overflow-y:auto}
.wpn-item{border:1px solid #ddd;border-radius:4px;padding:9px 11px;margin-bottom:8px;background:#fafafa;line-height:1.7}
.wpn-item.wpn-unread{background:#fff8e1;border-color:#f0ad4e}
.wpn-meta{font-size:11px;color:#999;margin-top:5px;display:flex;justify-content:space-between;align-items:center}
.wpn-badge{display:inline-block;font-size:10px;padding:2px 8px;border-radius:10px;font-weight:bold}
.wpn-badge-unread{background:#f0ad4e;color:#fff}
.wpn-badge-read{background:#d4edda;color:#155724}
.wpn-badge-sent-unread{background:#f8d7da;color:#721c24}
.wpn-badge-sent-read{background:#d4edda;color:#155724}
.wpn-form label{display:block;font-weight:bold;margin:10px 0 4px}
.wpn-form select,.wpn-form textarea,.wpn-form input[type=text]{width:100%;box-sizing:border-box;border:1px solid #ccc;border-radius:3px;padding:6px 8px;font-size:13px;font-family:Tahoma,Arial,sans-serif;direction:rtl}
.wpn-form textarea{resize:vertical}
#wpn-char-counter{font-size:11px;color:#888;text-align:left;margin-top:3px}
#wpn-char-counter.wpn-char-warn{color:#e65c00;font-weight:bold}
#wpn-char-counter.wpn-char-over{color:#c00;font-weight:bold}
#wpn-send-btn{margin-top:8px}
#wpn-send-status{margin-right:10px;font-size:12px}
.wpn-info{color:#888;font-style:italic;padding:4px 0;margin:0}
.wpn-empty{color:#aaa;text-align:center;padding:20px 0;margin:0}
.wpn-archive-bar{margin-top:10px;padding-top:10px;border-top:1px solid #eee;display:flex;align-items:center;justify-content:space-between}
.wpn-archive-bar span{font-size:12px;color:#888}
.wpn-archive-btn{font-size:12px!important;padding:3px 10px!important;height:auto!important;line-height:1.6!important}
.wpn-footer{margin-top:14px;padding-top:10px;border-top:1px solid #eee;text-align:center;font-size:12px;color:#aaa;line-height:2.2}
.wpn-footer a{color:#0073aa;text-decoration:none;margin:0 4px}
.wpn-footer a:hover{text-decoration:underline}

/* ─── To-Do ─── */
#wpn-todo-wrap{font-family:Tahoma,Arial,sans-serif;font-size:13px;color:#333;direction:rtl;text-align:right}
.wpnt-add-row{display:flex;gap:6px;margin-bottom:14px}
.wpnt-add-row input{flex:1;border:1px solid #ccc;border-radius:3px;padding:6px 9px;font-size:13px;font-family:Tahoma,Arial,sans-serif;direction:rtl}
.wpnt-add-btn{white-space:nowrap}
.wpnt-list{max-height:300px;overflow-y:auto}
.wpnt-item{display:flex;align-items:flex-start;gap:8px;border-radius:5px;padding:9px 11px;margin-bottom:7px;border:2px solid #f0ad4e;background:#fffdf0;transition:border-color .25s,background .25s}
.wpnt-item.wpnt-done{border-color:#28a745;background:#f0fff4}
.wpnt-check{flex-shrink:0;width:22px;height:22px;border-radius:50%;border:2px solid #f0ad4e;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;transition:all .2s;margin-top:1px}
.wpnt-item.wpnt-done .wpnt-check{border-color:#28a745;background:#28a745;color:#fff}
.wpnt-text{flex:1;line-height:1.6;word-break:break-word}
.wpnt-item.wpnt-done .wpnt-text{text-decoration:line-through;color:#888}
.wpnt-del{flex-shrink:0;background:none;border:none;cursor:pointer;color:#ccc;font-size:16px;padding:0 2px;line-height:1;margin-top:2px;transition:color .15s}
.wpnt-del:hover{color:#c00}
.wpnt-status-label{font-size:10px;display:block;margin-top:3px;font-weight:bold}
.wpnt-pending-label{color:#b07d00}
.wpnt-done-label{color:#28a745}
.wpnt-footer-note{font-size:11px;color:#aaa;margin-top:3px}
</style>
<script>var WPN=<?php echo wp_json_encode($js); ?>;</script>
    <?php
}

/* ════════════════════════════════════════
   ویجت ۱: پیام‌های کاربر به کاربر
════════════════════════════════════════ */
function wpn_render_notes_widget() {
    $users = get_users( array(
        'exclude' => array( get_current_user_id() ),
        'fields'  => array( 'ID', 'display_name' ),
        'orderby' => 'display_name', 'order' => 'ASC', 'number' => 200,
    ) );
    $archive_inbox = admin_url( 'admin.php?page=wpn-archive&tab=inbox' );
    $archive_sent  = admin_url( 'admin.php?page=wpn-archive&tab=sent' );
    ?>
<div class="wpn-wrap" id="wpn-notes-wrap">
    <div class="wpn-tabs">
        <button class="wpn-tab wpn-tab-active" data-tab="inbox">صندوق ورودی</button>
        <button class="wpn-tab" data-tab="sent">ارسال‌شده‌ها</button>
        <button class="wpn-tab" data-tab="compose">یادداشت جدید</button>
    </div>

    <div id="wpn-tab-inbox" class="wpn-panel wpn-panel-active">
        <div id="wpn-inbox-list" class="wpn-list"><p class="wpn-info">در حال بارگذاری...</p></div>
        <div class="wpn-archive-bar">
            <span id="wpn-inbox-more"></span>
            <a href="<?php echo esc_url($archive_inbox); ?>" class="button wpn-archive-btn">آرشیو ورودی &larr;</a>
        </div>
    </div>

    <div id="wpn-tab-sent" class="wpn-panel">
        <div id="wpn-sent-list" class="wpn-list"><p class="wpn-info">در حال بارگذاری...</p></div>
        <div class="wpn-archive-bar">
            <span id="wpn-sent-more"></span>
            <a href="<?php echo esc_url($archive_sent); ?>" class="button wpn-archive-btn">آرشیو ارسالی &larr;</a>
        </div>
    </div>

    <div id="wpn-tab-compose" class="wpn-panel">
        <div class="wpn-form">
            <label for="wpn-receiver">گیرنده:</label>
            <select id="wpn-receiver">
                <option value="">-- انتخاب کاربر --</option>
                <?php foreach ( $users as $u ) : ?>
                    <option value="<?php echo esc_attr($u->ID); ?>"><?php echo esc_html($u->display_name); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="wpn-message">یادداشت:</label>
            <textarea id="wpn-message" rows="5" placeholder="متن یادداشت..." maxlength="<?php echo esc_attr(defined('WPN_MAX_MESSAGE_LENGTH')?WPN_MAX_MESSAGE_LENGTH:2000); ?>"></textarea>
            <div id="wpn-char-counter">۰ / <?php echo defined('WPN_MAX_MESSAGE_LENGTH')?WPN_MAX_MESSAGE_LENGTH:2000; ?> کاراکتر</div>
            <button id="wpn-send-btn" class="button button-primary">ارسال یادداشت</button>
            <span id="wpn-send-status"></span>
        </div>
    </div>

    <div class="wpn-footer">
        ساخته شده با ❤️ توسط فرشاد سعیدزاده<br>
        <a href="https://www.youtube.com/@netfarshad" target="_blank">▶ یوتیوب</a> &bull;
        <a href="https://t.me/netfarshad" target="_blank">✈ تلگرام</a> &bull;
        <a href="https://www.instagram.com/netfarshad" target="_blank">📷 اینستاگرام</a>
    </div>
</div>

<script>
(function(){
    var MAX=WPN.max_length;
    function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
    function fmt(s){try{var d=new Date(s.replace(' ','T'));return d.toLocaleDateString('fa-IR')+' '+d.toLocaleTimeString('fa-IR',{hour:'2-digit',minute:'2-digit'});}catch(e){return s;}}
    function post(action,extra,cb){
        var p='action='+action+'&nonce='+encodeURIComponent(WPN.nonce)+(extra||'');
        var x=new XMLHttpRequest();x.open('POST',WPN.ajaxurl,true);
        x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
        x.onload=function(){if(x.status===200){try{cb(JSON.parse(x.responseText));}catch(e){}}};x.send(p);
    }
    function loadInbox(){
        var l=document.getElementById('wpn-inbox-list'),m=document.getElementById('wpn-inbox-more');
        l.innerHTML='<p class="wpn-info">'+WPN.s.loading+'</p>';
        post('wpn_get_inbox','',function(r){
            if(!r.success||!r.data||!r.data.messages||!r.data.messages.length){l.innerHTML='<p class="wpn-empty">'+WPN.s.empty_msg+'</p>';m.textContent='';return;}
            var h='';r.data.messages.forEach(function(n){
                var u=(n.is_read==='0'||n.is_read===0);
                h+='<div class="wpn-item'+(u?' wpn-unread':'')+'"><strong>'+esc(n.sender_name)+'</strong><div style="margin-top:4px">'+esc(n.message)+'</div><div class="wpn-meta"><span>'+fmt(n.created_at)+'</span><span class="wpn-badge '+(u?'wpn-badge-unread':'wpn-badge-read')+'">'+(u?WPN.s.unread:WPN.s.read)+'</span></div></div>';
            });
            l.innerHTML=h;var e=r.data.total-r.data.messages.length;
            m.textContent=e>0?(e+' '+WPN.s.more_inbox):'';
        });
    }
    function loadSent(){
        var l=document.getElementById('wpn-sent-list'),m=document.getElementById('wpn-sent-more');
        l.innerHTML='<p class="wpn-info">'+WPN.s.loading+'</p>';
        post('wpn_get_sent','',function(r){
            if(!r.success||!r.data||!r.data.messages||!r.data.messages.length){l.innerHTML='<p class="wpn-empty">'+WPN.s.empty_msg+'</p>';m.textContent='';return;}
            var h='';r.data.messages.forEach(function(n){
                var rd=(n.is_read==='1'||n.is_read===1);
                h+='<div class="wpn-item"><div style="margin-bottom:2px">به: <strong>'+esc(n.receiver_name)+'</strong></div><div>'+esc(n.message)+'</div><div class="wpn-meta"><span>'+fmt(n.created_at)+'</span><span class="wpn-badge '+(rd?'wpn-badge-sent-read':'wpn-badge-sent-unread')+'">'+(rd?'خوانده شد':'هنوز نخوانده')+'</span></div></div>';
            });
            l.innerHTML=h;var e=r.data.total-r.data.messages.length;
            m.textContent=e>0?(e+' '+WPN.s.more_sent):'';
        });
    }
    // تب‌ها — container رو از روی خود المان پیدا می‌کنیم
    var wrap=document.getElementById('wpn-notes-wrap');
    var tabs=wrap.querySelectorAll('.wpn-tab');
    tabs.forEach(function(btn){
        btn.addEventListener('click',function(){
            tabs.forEach(function(b){b.classList.remove('wpn-tab-active');});
            wrap.querySelectorAll('.wpn-panel').forEach(function(p){p.classList.remove('wpn-panel-active');});
            btn.classList.add('wpn-tab-active');
            var t=btn.getAttribute('data-tab');
            document.getElementById('wpn-tab-'+t).classList.add('wpn-panel-active');
            if(t==='inbox')loadInbox();if(t==='sent')loadSent();
        });
    });
    // شمارنده
    var msgBox=document.getElementById('wpn-message'),ctr=document.getElementById('wpn-char-counter');
    msgBox.addEventListener('input',function(){
        var len=msgBox.value.length;ctr.textContent=len+' / '+MAX+' کاراکتر';
        ctr.className=len>=MAX?'wpn-char-over':len>=MAX*.9?'wpn-char-warn':'';
    });
    // ارسال
    document.getElementById('wpn-send-btn').addEventListener('click',function(){
        var rec=document.getElementById('wpn-receiver').value,msg=msgBox.value.trim(),st=document.getElementById('wpn-send-status');
        if(!rec){st.textContent=WPN.s.select_user;return;}
        if(!msg){st.textContent=WPN.s.empty_text;return;}
        if(msg.length>MAX){st.textContent=WPN.s.too_long;return;}
        st.textContent='...';
        post('wpn_send_note','&receiver_id='+encodeURIComponent(rec)+'&message='+encodeURIComponent(msg),function(r){
            if(r.success){st.textContent=WPN.s.sent;msgBox.value='';ctr.textContent='۰ / '+MAX+' کاراکتر';ctr.className='';document.getElementById('wpn-receiver').value='';}
            else{st.textContent=r.data||WPN.s.error;}
            setTimeout(function(){st.textContent='';},4000);
        });
    });
    loadInbox();
})();
</script>
    <?php
}

/* ════════════════════════════════════════
   ویجت ۲: لیست وظایف (To-Do)
════════════════════════════════════════ */
function wpn_render_todo_widget() {
    ?>
<div id="wpn-todo-wrap">

    <div class="wpnt-add-row">
        <input type="text" id="wpnt-new-task" placeholder="وظیفه جدید را بنویسید..." maxlength="<?php echo esc_attr(defined('WPN_TODO_MAX_LENGTH')?WPN_TODO_MAX_LENGTH:500); ?>">
        <button id="wpnt-add-btn" class="button button-primary wpnt-add-btn">+ افزودن</button>
    </div>

    <div id="wpnt-list" class="wpnt-list">
        <p class="wpn-info">در حال بارگذاری...</p>
    </div>

    <div class="wpn-archive-bar">
        <span id="wpnt-more" class="wpnt-footer-note"></span>
        <a href="<?php echo esc_url( admin_url('admin.php?page=wpn-todo-archive') ); ?>" class="button wpn-archive-btn">آرشیو وظایف &larr;</a>
    </div>

    <div class="wpn-footer">
        ساخته شده با ❤️ توسط فرشاد سعیدزاده<br>
        <a href="https://www.youtube.com/@netfarshad" target="_blank">▶ یوتیوب</a> &bull;
        <a href="https://t.me/netfarshad" target="_blank">✈ تلگرام</a> &bull;
        <a href="https://www.instagram.com/netfarshad" target="_blank">📷 اینستاگرام</a>
    </div>
</div>

<script>
(function(){
    function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
    function post(action,extra,cb){
        var p='action='+action+'&nonce='+encodeURIComponent(WPN.nonce)+(extra||'');
        var x=new XMLHttpRequest();x.open('POST',WPN.ajaxurl,true);
        x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
        x.onload=function(){if(x.status===200){try{cb(JSON.parse(x.responseText));}catch(e){}}};x.send(p);
    }

    function buildItem(t){
        var done=(t.status==='1'||t.status===1);
        var div=document.createElement('div');
        div.className='wpnt-item'+(done?' wpnt-done':'');
        div.setAttribute('data-id',t.id);
        div.innerHTML=
            '<button class="wpnt-check" title="'+(done?'برگشت به در حال انجام':'علامت‌گذاری به عنوان انجام‌شده')+'">'+(done?'✓':'')+'</button>'+
            '<div class="wpnt-text">'+esc(t.task)+
                '<span class="wpnt-status-label '+(done?'wpnt-done-label':'wpnt-pending-label')+'">'+(done?'✅ انجام شد':'🕐 در حال انجام')+'</span>'+
            '</div>'+
            '<button class="wpnt-del" title="حذف">✕</button>';

        // تاگل وضعیت
        div.querySelector('.wpnt-check').addEventListener('click',function(){
            post('wpn_todo_toggle','&todo_id='+t.id,function(r){
                if(r.success){ loadTodos(); }
            });
        });
        // حذف
        div.querySelector('.wpnt-del').addEventListener('click',function(){
            if(!confirm(WPN.s.confirm_del))return;
            post('wpn_todo_delete','&todo_id='+t.id,function(r){
                if(r.success){ div.remove(); updateMore(); }
            });
        });
        return div;
    }

    function updateMore(){
        var items=document.querySelectorAll('#wpnt-list .wpnt-item');
        // تعداد نمایشی از data ذخیره شده
    }

    function loadTodos(){
        var list=document.getElementById('wpnt-list');
        var more=document.getElementById('wpnt-more');
        list.innerHTML='<p class="wpn-info">'+WPN.s.loading+'</p>';
        post('wpn_todo_get','',function(r){
            list.innerHTML='';
            if(!r.success||!r.data||!r.data.todos||!r.data.todos.length){
                list.innerHTML='<p class="wpn-empty">'+WPN.s.todo_empty+'</p>';
                more.textContent='';return;
            }
            r.data.todos.forEach(function(t){ list.appendChild(buildItem(t)); });
            var extra=r.data.total-r.data.todos.length;
            more.textContent=extra>0?(extra+' '+WPN.s.todo_more):'';
        });
    }

    // افزودن
    function addTask(){
        var inp=document.getElementById('wpnt-new-task');
        var task=inp.value.trim();
        if(!task)return;
        if(task.length>WPN.todo_max){alert(WPN.s.too_long);return;}
        post('wpn_todo_add','&task='+encodeURIComponent(task),function(r){
            if(r.success){ inp.value=''; loadTodos(); }
            else{ alert(r.data||WPN.s.error); }
        });
    }
    document.getElementById('wpnt-add-btn').addEventListener('click',addTask);
    document.getElementById('wpnt-new-task').addEventListener('keydown',function(e){
        if(e.key==='Enter'){ e.preventDefault(); addTask(); }
    });

    loadTodos();
})();
</script>
    <?php
}
