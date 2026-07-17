<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-6">
            <h1>Đăng nhập hệ thống</h1>
            @if (!empty($msg))
            <div class="alert alert-danger text-center">
                {{$msg}}
            </div>
            @endif
            <form action="" method="post">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="">Email</label>
                    <input name="email" type="text" class="form-control" placeholder="Email...">
                    {!! !empty($errors['email'])?'<span style="color:red;">'.$errors['email'].'</span>':false; !!}
                </div>

                <div class="form-group">
                    <label for="">Mật khẩu</label>
                    <input name="password" type="password" class="form-control" placeholder="Mật khẩu...">
                    {!! !empty($errors['password'])?'<span style="color:red;">'.$errors['password'].'</span>':false; !!}
                </div>
                <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
            </form>
        </div>
    </div>
</div>