<div class="row justify-content-center">
    <div class="col-md-10 col-lg-7">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>{{$page_name}}</h3></div>
            <form action="" method="post">
                <?php echo csrf_field(); ?>
    <?php /* Một khách nhiều xe: xe khai ở màn Sửa (ngay sau khi lưu), vì xe
             phải gắn vào một khách đã có mã. */ ?>
    <div class="alert alert-info py-2"><i class="fas fa-car mr-1"></i>
        Lưu khách xong sẽ mở ngay khối <b>Xe của khách</b> để khai biển số, số khung (VIN), số máy, hãng / model / năm.
    </div>
                <div class="card-body">
                    @if (!empty($msg))
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                    @endif
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Mã <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" placeholder="VD: KH001" value="{{!empty($old['code'])?$old['code']:''}}"/>
                            {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-8">
                            <label>Tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{!empty($old['name'])?$old['name']:''}}"/>
                            {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Loại</label>
                            <select name="type" class="form-control">
                                @foreach ($types as $k => $label)
                                <option value="{{$k}}" {{(!empty($old['type']) && $old['type']==$k)?'selected':''}}>{{$label}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Mã số thuế</label>
                            <input type="text" class="form-control" name="tax_code" value="{{!empty($old['tax_code'])?$old['tax_code']:''}}"/>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Điện thoại</label>
                            <input type="tel" class="form-control" name="phone" value="{{!empty($old['phone'])?$old['phone']:''}}"/>
                        </div>
                    </div>
                    <?php /* Tỉnh / phường theo cơ cấu sau sáp nhập 2025: 34 tỉnh,
                             2 cấp, KHÔNG còn quận/huyện. Danh sách lấy qua
                             admin/dia-gioi (server gọi API ngoài, có nhớ tạm).
                             Không bắt buộc: NCC nước ngoài hay dữ liệu cũ vẫn lưu được. */ ?>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tỉnh / Thành phố</label>
                            <select name="province_code" class="form-control"
                                    data-dia-gioi="tinh"
                                    data-url="{{_WEB_URL.'/admin/dia-gioi'}}"
                                    data-chon="{{!empty($old['province_code'])?$old['province_code']:''}}">
                                <option value="">— Chọn tỉnh / thành phố —</option>
                            </select>
                            {!! !empty($errors['province_code'])?'<small class="text-danger">'.e($errors['province_code']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-4">
                            <label>Phường / Xã</label>
                            <select name="ward_code" class="form-control"
                                    data-dia-gioi="xa" data-chon="{{!empty($old['ward_code'])?$old['ward_code']:''}}" disabled>
                                <option value="">— Chọn tỉnh trước —</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Địa chỉ <span class="text-muted small">(số nhà, đường)</span></label>
                            <input type="text" class="form-control" name="address" value="{{!empty($old['address'])?$old['address']:''}}"/>
                        </div>
                    </div>
                    <script src="{{asset('public/assets/js/dia-gioi.js')}}"></script>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Thứ tự</label>
                            <input type="number" class="form-control" name="sort_order" value="{{!empty($old['sort_order'])?$old['sort_order']:'0'}}"/>
                        </div>
                        <div class="form-group col-md-9 align-self-end">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" checked/>
                                <label class="custom-control-label" for="status">Đang dùng</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Thêm mới</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
