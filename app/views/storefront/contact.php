<?php
$flashType = ''; $flashMsg = '';
if (!empty($flash) && strpos($flash, '|') !== false){
    list($flashType, $flashMsg) = explode('|', $flash, 2);
}
$ov = function($k) use ($old){ return isset($old[$k]) ? $old[$k] : ''; };
?>
<style>
.contact-wrap{display:grid;grid-template-columns:1fr 1.3fr;gap:22px}
@media(max-width:760px){.contact-wrap{grid-template-columns:1fr}}
.contact-form label{display:block;font-weight:600;margin:10px 0 4px;font-size:14px}
.contact-form input,.contact-form textarea{width:100%;padding:9px 12px;border:1px solid var(--line);border-radius:6px;font-size:14px;font-family:inherit}
.contact-form textarea{min-height:120px;resize:vertical}
.req{color:var(--brand)}
</style>

<h1 style="margin:6px 0 16px">Liên hệ Tân Phát</h1>

@if (!empty($flashMsg))
<div class="alert {{$flashType==='ok'?'alert-ok':'alert-err'}}">{{$flashMsg}}</div>
@endif

<div class="contact-wrap">
    <div class="card"><div class="bd">
        <h3 style="margin-top:0">Thông tin liên hệ</h3>
        <p class="muted">Hotline: <b>{{!empty($settings['hotline'])?$settings['hotline']:'1900 0000'}}</b></p>
        <p class="muted">Email: <b>{{!empty($settings['email'])?$settings['email']:'info@tanphat.vn'}}</b></p>
        @if (!empty($settings['address']))
        <p class="muted">Địa chỉ: {{$settings['address']}}</p>
        @endif
        <hr style="border:none;border-top:1px solid var(--line);margin:16px 0"/>
        <h3>Nhận bản tin</h3>
        <p class="muted" style="font-size:14px">Đăng ký để nhận thông tin sản phẩm & khuyến mãi mới.</p>
        <form method="post" action="{{_WEB_URL.'/dang-ky-ban-tin'}}" class="contact-form">
            <?php echo csrf_field(); ?>
            <div style="display:flex;gap:8px">
                <input type="email" name="email" placeholder="Email của bạn" required/>
                <button type="submit" class="btn btn-brand" style="white-space:nowrap">Đăng ký</button>
            </div>
        </form>
    </div></div>

    <div class="card"><div class="bd">
        <h3 style="margin-top:0">Gửi yêu cầu / câu hỏi</h3>
        <form method="post" action="{{_WEB_URL.'/lien-he'}}" class="contact-form">
            <?php echo csrf_field(); ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div><label>Họ tên <span class="req">*</span></label><input type="text" name="name" value="{{$ov('name')}}" required/></div>
                <div><label>Điện thoại</label><input type="text" name="phone" value="{{$ov('phone')}}"/></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div><label>Email</label><input type="email" name="email" value="{{$ov('email')}}"/></div>
                <div><label>Tiêu đề</label><input type="text" name="subject" value="{{$ov('subject')}}"/></div>
            </div>
            <label>Nội dung <span class="req">*</span></label>
            <textarea name="message" required>{{$ov('message')}}</textarea>
            <p class="muted" style="font-size:13px;margin:6px 0">Vui lòng để lại ít nhất số điện thoại hoặc email để chúng tôi phản hồi.</p>
            <button type="submit" class="btn btn-brand">Gửi liên hệ</button>
        </form>
    </div></div>
</div>
