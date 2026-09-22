<?php
$val = function ($key) use ($item, $old) {
    if (isset($old[$key]) && $old[$key] !== '') return $old[$key];
    return isset($item[$key]) ? $item[$key] : '';
};
$statusOn = isset($old['status']) ? (int) $old['status'] === 1 : (int) $item['status'] === 1;
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{$page_name}}</h3>
                <div class="card-tools">
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-icon" title="Quay lại"><i class="fas fa-arrow-left"></i></a>
                </div>
            </div>

            <form action="" method="post">
                <?php echo csrf_field(); ?>
                <div class="card-body">

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" value="{{$item['email']}}" disabled/>
                        <small class="form-text text-muted">Email là tên đăng nhập của khách, không sửa ở đây.</small>
                    </div>

                    <div class="form-group">
                        <label>Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{$val('name')}}"/>
                        {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="tel" inputmode="numeric" maxlength="11" pattern="0[0-9]{9,10}"
                               name="phone" class="form-control" value="{{$val('phone')}}"/>
                        {!! !empty($errors['phone'])?'<small class="text-danger">'.e($errors['phone']).'</small>':false !!}
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Tỉnh / Thành phố</label>
                            <select name="province_code" class="form-control"
                                    data-dia-gioi="tinh"
                                    data-url="{{_WEB_URL.'/admin/dia-gioi'}}"
                                    data-chon="{{$val('province_code')}}">
                                <option value="">— Chọn tỉnh / thành phố —</option>
                            </select>
                            {!! !empty($errors['province_code'])?'<small class="text-danger">'.e($errors['province_code']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-6">
                            <label>Phường / Xã</label>
                            <select name="ward_code" class="form-control"
                                    data-dia-gioi="xa" data-chon="{{$val('ward_code')}}" disabled>
                                <option value="">— Chọn tỉnh trước —</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Địa chỉ <span class="text-muted small">(số nhà, đường)</span></label>
                        <textarea name="address" class="form-control" rows="2">{{$val('address')}}</textarea>
                    </div>
                    <script src="{{asset('public/assets/js/dia-gioi.js')}}"></script>

                    <div class="form-group">
                        <label>Đặt lại mật khẩu</label>
                        <input type="password" name="new_password" class="form-control" autocomplete="new-password"
                               placeholder="Bỏ trống nếu không đổi mật khẩu"/>
                        <small class="form-text text-muted">
                            Mật khẩu lưu dạng bcrypt nên không đọc lại được — chỉ có thể đặt mật khẩu mới rồi báo cho khách.
                        </small>
                        {!! !empty($errors['new_password'])?'<small class="text-danger d-block">'.e($errors['new_password']).'</small>':false !!}
                    </div>

                    <div class="form-group mb-0">
                        <label class="d-block">Trạng thái</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$statusOn?'checked':''}}/>
                            <label class="custom-control-label" for="status">Cho phép đăng nhập</label>
                        </div>
                    </div>

                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
