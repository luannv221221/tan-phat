<div class="crumb"><a href="{{_WEB_URL}}/">Trang chủ</a> / Tài khoản</div>

@if (!empty($msg))
<div class="alert alert-ok">{{$msg}}</div>
@endif

<div class="card" style="max-width:640px"><div class="hd">Thông tin thành viên</div><div class="bd">
    <table class="spec" style="width:100%;border-collapse:collapse">
        <tr><td style="width:160px;background:#fafafa;padding:10px;border:1px solid #e6e6e6">Họ tên</td><td style="padding:10px;border:1px solid #e6e6e6">{{$member['name']}}</td></tr>
        <tr><td style="background:#fafafa;padding:10px;border:1px solid #e6e6e6">Email</td><td style="padding:10px;border:1px solid #e6e6e6">{{$member['email']}}</td></tr>
        <tr><td style="background:#fafafa;padding:10px;border:1px solid #e6e6e6">Điện thoại</td><td style="padding:10px;border:1px solid #e6e6e6">{{!empty($member['phone'])?$member['phone']:'—'}}</td></tr>
    </table>
    <div class="mt">
        <a class="btn btn-brand" href="{{_WEB_URL}}/san-pham">Tiếp tục mua sắm</a>
        <a class="btn" href="{{_WEB_URL}}/thanh-vien/dang-xuat">Đăng xuất</a>
    </div>
</div></div>
