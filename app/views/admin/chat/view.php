@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif

<?php
/* TỰ NHẬN TIN, KHÔNG PHẢI F5 — khách báo 26/11/2026 "cái chat cứ phải f5 mới
   nhận tin" (kèm "Nhưng ko thấy typing"). Trước đó trang này tĩnh: tin khách
   chỉ hiện khi tải lại, trả lời thì gửi form rồi tải lại cả trang.
   Nay: hỏi tin mới 3 giây một lần (admin/chat/poll/<id>), trả lời bằng AJAX,
   hiện "Khách đang nhập…". Tắt JS thì form vẫn gửi như cũ. */
$idCuoi = 0;
foreach ((array) $messages as $__m) $idCuoi = max($idCuoi, (int) $__m['id']);
?>
<style>
.cb-dong{display:flex;margin-bottom:8px}
.cb-dong.nv{justify-content:flex-end}
.cb-bong{max-width:75%;padding:8px 12px;border-radius:12px;background:#fff;border:1px solid #e6e6e6;white-space:pre-wrap;word-break:break-word}
.cb-dong.nv .cb-bong{background:#c0392b;color:#fff;border-color:#c0392b}
.cb-gio{font-size:11px;opacity:.7;margin-top:3px;white-space:normal}
#chat-dang-go{font-style:italic}
</style>

<div class="row justify-content-center"><div class="col-lg-8">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-comment-dots mr-2"></i>{{!empty($conv['guest_name']) ? $conv['guest_name'] : 'Khách'}}{{!empty($conv['guest_phone']) ? ' · '.$conv['guest_phone'] : ''}}</h3>
            <div class="card-tools">
                @if (route('admin/'.$routeBase.'/edit/'.$conv['id']))
                    @if ($conv['status']=='open')
                    <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$conv['id'].'?status=closed'}}" class="btn btn-sm btn-outline-secondary">Đóng</a>
                    @else
                    <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$conv['id'].'?status=open'}}" class="btn btn-sm btn-outline-success">Mở lại</a>
                    @endif
                @endif
            </div>
        </div>
        <div class="card-body" id="chat-khung" style="max-height:460px;overflow-y:auto;background:#f5f6f8"
             data-poll="{{_WEB_URL.'/admin/'.$routeBase.'/poll/'.$conv['id']}}"
             data-go="{{_WEB_URL.'/admin/'.$routeBase.'/dang-go/'.$conv['id']}}"
             data-cuoi="<?php echo (int) $idCuoi; ?>">
            @if (!empty($messages))
                @foreach ($messages as $m)
                <div class="cb-dong{{$m['sender'] === 'staff' ? ' nv' : ''}}" data-id="<?php echo (int) $m['id']; ?>">
                    <div class="cb-bong">{{$m['body']}}<div class="cb-gio">{{$m['create_at']}}</div></div>
                </div>
                @endforeach
            @else
                <p class="text-muted text-center" id="chat-trong">Chưa có tin nhắn.</p>
            @endif
        </div>
        <div id="chat-dang-go" class="small text-muted px-3 pt-2" style="display:none">Khách đang nhập…</div>
        <div class="card-footer">
            <form action="{{_WEB_URL.'/admin/'.$routeBase.'/reply/'.$conv['id']}}" method="post" class="form-inline" id="chat-form">
                <?php echo csrf_field(); ?>
                <input type="text" name="body" id="chat-input" class="form-control flex-grow-1 mr-2" placeholder="Nhập trả lời..." autocomplete="off" maxlength="2000" required/>
                <button type="submit" class="btn btn-primary" id="chat-gui"><i class="fas fa-paper-plane mr-1"></i> Gửi</button>
            </form>
            <div id="chat-loi" class="small text-danger mt-1" style="display:none"></div>
        </div>
    </div>
    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">← Về inbox</a>
</div></div>

<script>
(function(){
    var khung = document.getElementById('chat-khung');
    var form  = document.getElementById('chat-form');
    if (!khung || !form || !window.fetch) return;   // trình duyệt quá cũ: giữ form thường

    var urlHoi = khung.getAttribute('data-poll');
    var urlGo  = khung.getAttribute('data-go');
    var cuoi   = parseInt(khung.getAttribute('data-cuoi'), 10) || 0;
    var input  = document.getElementById('chat-input');
    var nut    = document.getElementById('chat-gui');
    var loiEl  = document.getElementById('chat-loi');
    var goEl   = document.getElementById('chat-dang-go');
    var token  = (form.querySelector('input[name="_token"]') || {}).value || '';
    var AJAX   = {'X-Requested-With': 'XMLHttpRequest'};

    /* Tin đã có trên màn hình — tin vừa gửi (AJAX) và lần hỏi kế tiếp có thể
       cùng trả về một tin, không được vẽ hai lần. */
    var daCo = {};
    [].forEach.call(khung.querySelectorAll('[data-id]'), function(el){ daCo[el.getAttribute('data-id')] = 1; });

    function baoLoi(s){ loiEl.textContent = s || ''; loiEl.style.display = s ? 'block' : 'none'; }
    function ganDay(){ return khung.scrollHeight - khung.scrollTop - khung.clientHeight < 80; }
    function cuonXuong(){ khung.scrollTop = khung.scrollHeight; }
    cuonXuong();

    function them(m){
        if (!m || !m.id || daCo[m.id]) return;
        daCo[m.id] = 1;
        if (m.id > cuoi) cuoi = m.id;
        var trong = document.getElementById('chat-trong');
        if (trong) trong.parentNode.removeChild(trong);
        var day = ganDay();
        var dong = document.createElement('div');
        dong.className = 'cb-dong' + (m.sender === 'staff' ? ' nv' : '');
        dong.setAttribute('data-id', m.id);
        var bong = document.createElement('div'); bong.className = 'cb-bong';
        bong.appendChild(document.createTextNode(m.body));
        var gio = document.createElement('div'); gio.className = 'cb-gio'; gio.textContent = m.at || '';
        bong.appendChild(gio); dong.appendChild(bong); khung.appendChild(dong);
        /* Đang đọc tin cũ ở trên thì không giật xuống; đang ở cuối thì theo tin mới. */
        if (day || m.sender === 'staff') cuonXuong();
    }

    var dangHoi = false, dung = false;
    function hoi(){
        if (dangHoi || dung) return;
        dangHoi = true;
        fetch(urlHoi + '?after=' + cuoi, {credentials: 'same-origin', headers: AJAX})
            .then(function(r){
                /* Hết phiên thì máy chủ chuyển sang trang đăng nhập (HTML) — dừng
                   hỏi và nói rõ, đừng để nhân viên tưởng khách im lặng. */
                var ct = r.headers.get('content-type') || '';
                if (!r.ok || ct.indexOf('application/json') < 0) throw 'phien';
                return r.json();
            })
            .then(function(d){
                (d.messages || []).forEach(them);
                goEl.style.display = d.typing ? 'block' : 'none';
            })
            .catch(function(e){
                if (e === 'phien'){
                    dung = true;
                    goEl.style.display = 'none';
                    baoLoi('Phiên đăng nhập đã hết — tải lại trang (F5) để đăng nhập và nhận tin tiếp.');
                }
                /* Mất mạng chốc lát: lần sau hỏi lại, không báo gì. */
            })
            .then(function(){ dangHoi = false; });
    }
    setInterval(hoi, 3000);
    document.addEventListener('visibilitychange', function(){ if (!document.hidden) hoi(); });

    /* Báo "đang gõ" tối đa 3 giây một lần — khách thấy "Nhân viên đang trả lời…". */
    var lanGo = 0;
    input.addEventListener('input', function(){
        if (!input.value.trim()) return;
        var bay = Date.now();
        if (bay - lanGo < 3000) return;
        lanGo = bay;
        var fd = new FormData(); fd.append('_token', token);
        fetch(urlGo, {method: 'POST', body: fd, credentials: 'same-origin', headers: AJAX}).catch(function(){});
    });

    form.addEventListener('submit', function(e){
        e.preventDefault();
        if (!input.value.trim()) return;
        var fd = new FormData(form);
        nut.disabled = true;
        fetch(form.action, {method: 'POST', body: fd, credentials: 'same-origin', headers: AJAX})
            .then(function(r){
                var ct = r.headers.get('content-type') || '';
                if (ct.indexOf('application/json') < 0) throw 'phien';
                return r.json();
            })
            .then(function(d){
                if (d && d.ok){ input.value = ''; lanGo = 0; baoLoi(''); them(d); }
                else baoLoi((d && d.message) ? d.message : 'Không gửi được, thử lại.');
            })
            .catch(function(e){
                baoLoi(e === 'phien' ? 'Phiên đăng nhập đã hết — tải lại trang (F5) rồi gửi lại.'
                                     : 'Mất kết nối, chưa gửi được — thử lại.');
            })
            .then(function(){ nut.disabled = false; input.focus(); });
    });
})();
</script>
