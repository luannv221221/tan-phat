<div class="form-box">
    <div class="card"><div class="bd">
        <h1 class="page-title tc">Đăng nhập thành viên</h1>
        <p class="muted tc" style="margin-top:0">Đăng nhập để xem tồn kho và gửi yêu cầu báo giá.</p>

        @if (!empty($msg))
        <div class="alert alert-ok">{{$msg}}</div>
        @endif
        {!! !empty($errors['login']) ? '<div class="alert alert-err">'.e($errors['login']).'</div>' : '' !!}

        <form method="post" action="{{_WEB_URL}}/thanh-vien/dang-nhap">
            <?php echo csrf_field(); ?>
            <div class="fld">
                <label>Email</label>
                <input type="email" name="email" value="{{!empty($old['email'])?$old['email']:''}}" required/>
            </div>
            <div class="fld">
                <label>Mật khẩu</label>
                <input type="password" name="password" required/>
            </div>
            <button class="btn btn-brand" type="submit" style="width:100%">Đăng nhập</button>
        </form>
        <p class="tc mt muted">Chưa có tài khoản? <a href="{{_WEB_URL}}/thanh-vien/dang-ky"><b>Đăng ký</b></a></p>
    </div></div>
</div>
