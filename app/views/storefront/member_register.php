<div class="form-box">
    <div class="card"><div class="bd">
        <h1 class="page-title tc">Đăng ký thành viên</h1>

        <form method="post" action="{{_WEB_URL}}/thanh-vien/dang-ky">
            <?php echo csrf_field(); ?>
            <div class="fld">
                <label>Họ tên</label>
                <input type="text" name="name" value="{{!empty($old['name'])?$old['name']:''}}" required/>
                {!! !empty($errors['name']) ? '<small style="color:#c0392b">'.e($errors['name']).'</small>' : '' !!}
            </div>
            <div class="fld">
                <label>Email</label>
                <input type="email" name="email" value="{{!empty($old['email'])?$old['email']:''}}" required/>
                {!! !empty($errors['email']) ? '<small style="color:#c0392b">'.e($errors['email']).'</small>' : '' !!}
            </div>
            <div class="fld">
                <label>Số điện thoại</label>
                <input type="text" name="phone" value="{{!empty($old['phone'])?$old['phone']:''}}"/>
            </div>
            <div class="fld">
                <label>Mật khẩu</label>
                <input type="password" name="password" required/>
                {!! !empty($errors['password']) ? '<small style="color:#c0392b">'.e($errors['password']).'</small>' : '' !!}
            </div>
            <div class="fld">
                <label>Nhập lại mật khẩu</label>
                <input type="password" name="password2" required/>
                {!! !empty($errors['password2']) ? '<small style="color:#c0392b">'.e($errors['password2']).'</small>' : '' !!}
            </div>
            <button class="btn btn-brand" type="submit" style="width:100%">Đăng ký</button>
        </form>
        <p class="tc mt muted">Đã có tài khoản? <a href="{{_WEB_URL}}/thanh-vien/dang-nhap"><b>Đăng nhập</b></a></p>
    </div></div>
</div>
