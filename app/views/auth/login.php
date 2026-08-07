<div class="auth-wrap">
    <div class="auth-card">

        <div class="auth-logo"><?php echo icon('shield'); ?></div>
        <h1>Đăng nhập</h1>
        <p class="auth-sub">Nhập tài khoản để vào hệ thống quản trị</p>

        @if (!empty($msg))
        <div class="alert alert-danger">{{$msg}}</div>
        @endif

        <form action="" method="post">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label for="login-email">Email</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><?php echo icon('at-sign'); ?></span>
                    </div>
                    <input id="login-email" name="email" type="text" class="form-control" placeholder="Email..."/>
                </div>
                {!! !empty($errors['email']) ? '<small class="text-danger">'.e($errors['email']).'</small>' : false !!}
            </div>

            <div class="form-group">
                <label for="login-password">Mật khẩu</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><?php echo icon('lock'); ?></span>
                    </div>
                    <input id="login-password" name="password" type="password" class="form-control" placeholder="Mật khẩu..."/>
                </div>
                {!! !empty($errors['password']) ? '<small class="text-danger">'.e($errors['password']).'</small>' : false !!}
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="login-remember" name="remember" value="1"/>
                    <label class="custom-control-label" for="login-remember">Ghi nhớ đăng nhập trên máy này</label>
                </div>
                <small class="text-muted d-block mt-1">
                    Không tích thì phiên tự thoát sau <?php echo (int) (defined('_SESSION_IDLE_MINUTES') ? _SESSION_IDLE_MINUTES : 15); ?> phút không thao tác.
                    Đừng tích khi dùng máy công cộng.
                </small>
            </div>

            <button type="submit" class="btn btn-primary">Đăng nhập</button>
        </form>

    </div>
</div>
