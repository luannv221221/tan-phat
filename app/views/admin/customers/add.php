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
                        Khách vãng lai chỉ cần <b>họ tên</b> và <b>số điện thoại</b>. Mã khách tự cấp
                        (<b>{{$maMoi}}</b>). Thêm xong sẽ sang ngay màn hình khai xe:
                        biển số, số khung (VIN), số máy, hãng / model / năm.
                    </div>

                    <div class="form-group">
                        <label>Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{$val('name')}}" placeholder="VD: Anh Nguyễn Văn Hùng"/>
                        {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Số điện thoại</label>
                            <input type="tel" inputmode="numeric" maxlength="11" name="phone"
                                   class="form-control" value="{{$val('phone')}}" placeholder="0912345678"/>
                            {!! !empty($errors['phone'])?'<small class="text-danger">'.e($errors['phone']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-6">
                            <label>Email <span class="text-muted small">(không bắt buộc)</span></label>
                            <input type="email" name="email" class="form-control" value="{{$val('email')}}"/>
                            {!! !empty($errors['email'])?'<small class="text-danger">'.e($errors['email']).'</small>':false !!}
                        </div>
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
                        <label>Nhóm khách</label>
                        <select name="group_id" class="form-control">
                            <option value="">— Chưa xếp nhóm —</option>
                            @if (!empty($dsNhom))
                            @foreach ($dsNhom as $g)
                            <option value="{{$g['id']}}" {{(string)$val('group_id')===(string)$g['id']?'selected':''}}>{{$g['name']}}</option>
                            @endforeach
                            @endif
                        </select>
                        {!! !empty($errors['group_id'])?'<small class="text-danger">'.e($errors['group_id']).'</small>':false !!}
                    </div>

                    <?php /* Tỉnh / phường: 34 tỉnh, 2 cấp (sau sáp nhập 2025).
                             Danh sách lấy qua admin/dia-gioi. Không bắt buộc. */ ?>
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

                    <div class="form-group mb-0">
                        <label>Địa chỉ <span class="text-muted small">(số nhà, đường)</span></label>
                        <textarea name="address" class="form-control" rows="2">{{$val('address')}}</textarea>
                    </div>
                    <script src="{{asset('public/assets/js/dia-gioi.js')}}"></script>

                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Thêm và khai xe</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
