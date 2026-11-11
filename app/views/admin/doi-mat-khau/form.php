@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="row justify-content-center"><div class="col-md-8 col-lg-6">
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-key mr-2"></i>{{$page_name}}</h3></div>

        <form action="" method="post" autocomplete="off">
            <?php echo csrf_field(); ?>
            <div class="card-body">

                <p class="text-muted mb-3">
                    Tài khoản <strong>{{!empty($nguoiDung['name'])?$nguoiDung['name']:'—'}}</strong>
                    @if (!empty($nguoiDung['email']))
                    — {{$nguoiDung['email']}}
                    @endif
                </p>

                <div class="form-group">
                    <label>Mật khẩu hiện tại <span class="text-danger">*</span></label>
                    <input type="password" name="current_password" class="form-control" autocomplete="current-password"/>
                    {!! !empty($errors['current_password'])?'<small class="text-danger">'.e($errors['current_password']).'</small>':false !!}
                </div>

                <div class="form-group">
                    <label>Mật khẩu mới <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" class="form-control" autocomplete="new-password"/>
                    <small class="form-text text-muted">Tối thiểu {{$toiThieu}} ký tự.</small>
                    {!! !empty($errors['new_password'])?'<small class="text-danger">'.e($errors['new_password']).'</small>':false !!}
                </div>

                <div class="form-group mb-0">
                    <label>Nhập lại mật khẩu mới <span class="text-danger">*</span></label>
                    <input type="password" name="new_password2" class="form-control" autocomplete="new-password"/>
                    {!! !empty($errors['new_password2'])?'<small class="text-danger">'.e($errors['new_password2']).'</small>':false !!}
                </div>

                <?php /* NÓI TRƯỚC HẬU QUẢ. Đổi mật khẩu xong mà điện thoại tự
                         đăng xuất thì người dùng tưởng hỏng và gọi hỗ trợ. Báo
                         trước thì đó là tính năng, không phải sự cố. */ ?>
                <div class="alert alert-warning mt-3 mb-0 py-2 small">
                    <i class="fas fa-info-circle mr-1"></i>
                    Đổi xong, mọi thiết bị khác đang đăng nhập tài khoản này sẽ bị đăng xuất.
                    Máy anh/chị đang dùng thì vẫn ở nguyên.
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Đổi mật khẩu</button>
                <a href="{{_WEB_URL.'/admin'}}" class="btn btn-default">Quay lại</a>
            </div>
        </form>
    </div>
</div></div>
