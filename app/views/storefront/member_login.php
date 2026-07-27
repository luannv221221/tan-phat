<div class="content">
<div class="sf-page">
<div class="container">
    <div class="card border-0 shadow-sm mx-auto" style="max-width:440px"><div class="card-body p-4">
        <h1 class="sf-page-title text-center h3">Đăng nhập thành viên</h1>
        <p class="text-muted text-center small">Đăng nhập để xem tồn kho và gửi yêu cầu báo giá.</p>

        @if (!empty($msg))
        <div class="alert alert-success">{{$msg}}</div>
        @endif
        {!! !empty($errors['login']) ? '<div class="alert alert-danger">'.e($errors['login']).'</div>' : '' !!}

        <form method="post" action="{{_WEB_URL}}/thanh-vien/dang-nhap">
            <?php echo csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{!empty($old['email'])?$old['email']:''}}" required/>
            </div>
            <div class="mb-3">
                <label class="form-label">Mật khẩu</label>
                <input type="password" name="password" class="form-control" required/>
            </div>
            <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
        </form>
        <p class="text-center text-muted mt-3 mb-0">Chưa có tài khoản? <a href="{{_WEB_URL}}/thanh-vien/dang-ky"><b>Đăng ký</b></a></p>
    </div></div>
</div>
</div>
</div>
