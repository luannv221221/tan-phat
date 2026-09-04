<?php
$val = function ($key) use ($old) {
    return isset($old[$key]) ? $old[$key] : '';
};
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>{{$page_name}}</h3>
                <div class="card-tools">
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-icon" title="Quay lại"><i class="fas fa-arrow-left"></i></a>
                </div>
            </div>

            <form action="" method="post">
                <?php echo csrf_field(); ?>
                <div class="card-body">

                    <div class="alert alert-info py-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Khách vãng lai chỉ cần <b>họ tên</b> và <b>số điện thoại</b>.
                        Email và mật khẩu để trống — khách không cần tài khoản đăng nhập website.
                        Thêm xong sẽ sang ngay màn hình khai biển số xe.
                    </div>

                    <div class="form-group">
                        <label>Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{$val('name')}}" placeholder="VD: Anh Nguyễn Văn Hùng"/>
                        {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="tel" inputmode="numeric" maxlength="11" name="phone"
                               class="form-control" value="{{$val('phone')}}" placeholder="0912345678"/>
                        {!! !empty($errors['phone'])?'<small class="text-danger">'.e($errors['phone']).'</small>':false !!}
                    </div>

                    <?php /* Ô xác nhận chỉ hiện khi đã bị chặn vì trùng số — bày sẵn
                             thì người dùng tích bừa cho xong, và cảnh báo mất tác dụng. */ ?>
                    @if (!empty($errors['phone']) && strpos($errors['phone'], 'đã thuộc về khách') !== false)
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="xac_nhan_trung" id="xac_nhan_trung" value="1"/>
                            <label class="custom-control-label" for="xac_nhan_trung">
                                Tôi biết số này đã có người dùng, vẫn tạo hồ sơ mới
                            </label>
                        </div>
                    </div>
                    @endif

                    <div class="form-group">
                        <label>Email <span class="text-muted small">(không bắt buộc)</span></label>
                        <input type="email" name="email" class="form-control" value="{{$val('email')}}"/>
                        <small class="form-text text-muted">
                            Chỉ cần khi khách muốn tự đăng nhập website để xem đơn hàng.
                        </small>
                        {!! !empty($errors['email'])?'<small class="text-danger">'.e($errors['email']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <textarea name="address" class="form-control" rows="2">{{$val('address')}}</textarea>
                    </div>

                    <div class="form-group mb-0">
                        <label>Mật khẩu <span class="text-muted small">(không bắt buộc)</span></label>
                        <input type="password" name="new_password" class="form-control" autocomplete="new-password"
                               placeholder="Bỏ trống nếu khách không đăng nhập website"/>
                        {!! !empty($errors['new_password'])?'<small class="text-danger">'.e($errors['new_password']).'</small>':false !!}
                    </div>

                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Thêm và khai xe</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
