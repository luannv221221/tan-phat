<?php
// Giá trị hiển thị: ưu tiên dữ liệu vừa nhập (khi có lỗi) rồi mới tới DB,
// để người dùng không phải gõ lại toàn bộ chỉ vì sai 1 ô.
$val = function ($key) use ($member, $old) {
    if (!empty($old[$key])) return $old[$key];
    return !empty($member[$key]) ? $member[$key] : '';
};
$err = function ($key) use ($errors) {
    return !empty($errors[$key])
        ? '<small class="text-danger d-block">' . e($errors[$key]) . '</small>' : '';
};
?>
<div class="content">
<div class="sf-page">
<div class="container">

    <div class="breadcrumb-sf"><a href="{{_WEB_URL}}/">Trang chủ</a> / Tài khoản</div>

    @if (!empty($msg))
    <div class="alert alert-success">{{$msg}}</div>
    @endif

    <form method="post" action="{{_WEB_URL}}/thanh-vien" style="max-width:640px">
        <?php echo csrf_field(); ?>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Thông tin thành viên</div>
            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="{{$member['email']}}" disabled/>
                    <small class="text-muted">Email là tên đăng nhập nên không đổi được tại đây.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{$val('name')}}"/>
                    {!! $err('name') !!}
                </div>

                <div class="mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input type="tel" inputmode="numeric" maxlength="11" pattern="0[0-9]{9,10}"
                           name="phone" class="form-control" value="{{$val('phone')}}"/>
                    {!! $err('phone') !!}
                </div>

                <div class="mb-0">
                    <label class="form-label">Địa chỉ</label>
                    <textarea name="address" class="form-control" rows="2">{{$val('address')}}</textarea>
                    {!! $err('address') !!}
                </div>

            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Đổi mật khẩu</div>
            <div class="card-body">
                <p class="text-muted small">Bỏ trống nếu không muốn đổi mật khẩu.</p>

                <div class="mb-3">
                    <label class="form-label">Mật khẩu hiện tại</label>
                    <input type="password" name="current_password" class="form-control" autocomplete="current-password"/>
                    {!! $err('current_password') !!}
                </div>

                <div class="mb-3">
                    <label class="form-label">Mật khẩu mới</label>
                    <input type="password" name="new_password" class="form-control" autocomplete="new-password"/>
                    {!! $err('new_password') !!}
                </div>

                <div class="mb-0">
                    <label class="form-label">Nhập lại mật khẩu mới</label>
                    <input type="password" name="new_password2" class="form-control" autocomplete="new-password"/>
                    {!! $err('new_password2') !!}
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        <a class="btn btn-outline-secondary" href="{{_WEB_URL}}/san-pham">Tiếp tục mua sắm</a>
        <a class="btn btn-outline-secondary" href="{{_WEB_URL}}/thanh-vien/dang-xuat">Đăng xuất</a>
    </form>

</div>
</div>
</div>
