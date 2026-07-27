<div class="content">
<div class="sf-page">
<div class="container">
    <div class="card border-0 shadow-sm mx-auto" style="max-width:460px"><div class="card-body p-4">
        <h1 class="sf-page-title text-center h3">Đăng ký thành viên</h1>

        <form method="post" action="{{_WEB_URL}}/thanh-vien/dang-ky">
            <?php echo csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label">Họ tên</label>
                <input type="text" name="name" class="form-control" value="{{!empty($old['name'])?$old['name']:''}}" required/>
                {!! !empty($errors['name']) ? '<small class="text-danger">'.e($errors['name']).'</small>' : '' !!}
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{!empty($old['email'])?$old['email']:''}}" required/>
                {!! !empty($errors['email']) ? '<small class="text-danger">'.e($errors['email']).'</small>' : '' !!}
            </div>
            <div class="mb-3">
                <label class="form-label">Số điện thoại</label>
                <input type="text" name="phone" class="form-control" value="{{!empty($old['phone'])?$old['phone']:''}}"/>
            </div>
            <div class="mb-3">
                <label class="form-label">Mật khẩu</label>
                <input type="password" name="password" class="form-control" required/>
                {!! !empty($errors['password']) ? '<small class="text-danger">'.e($errors['password']).'</small>' : '' !!}
            </div>
            <div class="mb-3">
                <label class="form-label">Nhập lại mật khẩu</label>
                <input type="password" name="password2" class="form-control" required/>
                {!! !empty($errors['password2']) ? '<small class="text-danger">'.e($errors['password2']).'</small>' : '' !!}
            </div>
            <button class="btn btn-primary w-100" type="submit">Đăng ký</button>
        </form>
        <p class="text-center text-muted mt-3 mb-0">Đã có tài khoản? <a href="{{_WEB_URL}}/thanh-vien/dang-nhap"><b>Đăng nhập</b></a></p>
    </div></div>
</div>
</div>
</div>
