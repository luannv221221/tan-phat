<div class="content">
<div class="sf-page">
<div class="container">

    <div class="breadcrumb-sf"><a href="{{_WEB_URL}}/">Trang chủ</a> / Tài khoản</div>

    @if (!empty($msg))
    <div class="alert alert-success">{{$msg}}</div>
    @endif

    <div class="card border-0 shadow-sm" style="max-width:640px">
        <div class="card-header bg-white fw-semibold">Thông tin thành viên</div>
        <div class="card-body">
            <table class="table table-bordered mb-3">
                <tbody>
                    <tr><td style="width:160px;background:#f8f9fa">Họ tên</td><td>{{$member['name']}}</td></tr>
                    <tr><td style="background:#f8f9fa">Email</td><td>{{$member['email']}}</td></tr>
                    <tr><td style="background:#f8f9fa">Điện thoại</td><td>{{!empty($member['phone'])?$member['phone']:'—'}}</td></tr>
                </tbody>
            </table>
            <a class="btn btn-primary" href="{{_WEB_URL}}/san-pham">Tiếp tục mua sắm</a>
            <a class="btn btn-outline-secondary" href="{{_WEB_URL}}/thanh-vien/dang-xuat">Đăng xuất</a>
        </div>
    </div>

</div>
</div>
</div>
