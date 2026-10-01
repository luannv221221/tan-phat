<?php $v = function($k, $mac = '') use ($old){ return isset($old[$k]) ? $old[$k] : $mac; }; ?>
<form action="" method="post">
    <?php echo csrf_field(); ?>
    @if (!empty($msg))
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
    @endif
    <?php /* Khai xe từ màn Đối tượng / Khách hàng: lưu xong quay về đúng khách đó */ ?>
    @if ($ve === 'partner' || $ve === 'customer')
    <input type="hidden" name="ve" value="{{$ve}}"/>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-car mr-2"></i>{{$page_name}}</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Chủ xe <span class="text-muted small">(đối tượng khách)</span></label>
                    <select name="partner_id" class="form-control js-search" data-placeholder="Gõ tên hoặc mã để tìm...">
                        <option value="">— Chưa gán chủ / khách vãng lai —</option>
                        @if (!empty($partners))
                        @foreach ($partners as $p)
                        <option value="{{$p['id']}}" {{(int)$v('partner_id')===(int)$p['id']?'selected':''}}>{{$p['code'].' - '.$p['name']}}</option>
                        @endforeach
                        @endif
                    </select>
                    {!! !empty($errors['partner_id'])?'<small class="text-danger">'.e($errors['partner_id']).'</small>':false !!}
                    <small class="form-text text-muted">Một khách có nhiều xe — chọn cùng một khách cho các xe của họ.</small>
                </div>
                <div class="form-group col-md-3">
                    <label>Biển số xe <span class="text-danger">*</span></label>
                    <input type="text" name="bien_so" class="form-control text-uppercase"
                           placeholder="VD: 30A-123.45" value="{{$v('bien_so')}}"/>
                    {!! !empty($errors['bien_so'])?'<small class="text-danger">'.e($errors['bien_so']).'</small>':false !!}
                    <small class="form-text text-muted">Không trùng với xe khác; gõ kiểu nào cũng nhận.</small>
                </div>
                <div class="form-group col-md-3">
                    <label>Số km hiện tại</label>
                    <input type="text" name="so_km" class="form-control text-right" placeholder="VD: 45.000" value="{{$v('so_km')}}"/>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Số khung (VIN)</label>
                    <input type="text" name="so_khung" class="form-control text-uppercase"
                           placeholder="17 ký tự sau kính lái / khung xe" value="{{$v('so_khung')}}"/>
                    {!! !empty($errors['so_khung'])?'<small class="text-danger">'.e($errors['so_khung']).'</small>':false !!}
                    <small class="form-text text-muted">Để trống được; đã ghi thì không được trùng xe khác.</small>
                </div>
                <div class="form-group col-md-6">
                    <label>Số máy</label>
                    <input type="text" name="so_may" class="form-control text-uppercase" value="{{$v('so_may')}}"/>
                </div>
            </div>

            <?php /* Hãng → model → năm → màu lấy từ Danh mục xe. CHỈ ĐƯỢC CHỌN:
                     ô gõ tay cũ đã bỏ vì mỗi người ghi một kiểu thì lọc và báo
                     cáo không gom được. Danh mục thiếu thì bấm + thêm tại chỗ. */ ?>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Hãng xe <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <select name="brand_id" class="form-control" data-xe="hang" data-url="{{_WEB_URL.'/admin/vehicles'}}">
                            <option value="">— Chọn hãng —</option>
                            @if (!empty($hangDs))
                            @foreach ($hangDs as $h)
                            <option value="{{$h['id']}}" {{(int)$v('brand_id')===(int)$h['id']?'selected':''}}>{{$h['name']}}</option>
                            @endforeach
                            @endif
                        </select>
                        <div class="input-group-append">
                            {!! nut_them_nhanh('hang', 'brand_id', ['nhan' => 'hãng xe', 'vd' => 'VD: Mitsubishi', 'day' => 1]) !!}
                        </div>
                    </div>
                    {!! !empty($errors['brand_id'])?'<small class="text-danger">'.e($errors['brand_id']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4">
                    <label>Model <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <select name="model_id" class="form-control" data-xe="model" data-chon="{{$v('model_id')}}" disabled>
                            <option value="">— Chọn hãng trước —</option>
                        </select>
                        <div class="input-group-append">
                            {!! nut_them_nhanh('model', 'model_id', ['nhan' => 'model', 'vd' => 'VD: Xpander', 'cha' => 'brand_id', 'nhan_cha' => 'hãng xe', 'cha_bat_buoc' => 1, 'day' => 1]) !!}
                        </div>
                    </div>
                    {!! !empty($errors['model_id'])?'<small class="text-danger">'.e($errors['model_id']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4">
                    <label>Năm sản xuất</label>
                    <div class="input-group">
                        <select name="car_year_id" class="form-control" data-xe="nam" data-chon="{{$v('car_year_id')}}" disabled>
                            <option value="">— Chọn model trước —</option>
                        </select>
                        <div class="input-group-append">
                            {!! nut_them_nhanh('nam', 'car_year_id', ['nhan' => 'năm SX', 'vd' => 'VD: 2022', 'cha' => 'model_id', 'nhan_cha' => 'model', 'cha_bat_buoc' => 1]) !!}
                        </div>
                    </div>
                    {!! !empty($errors['car_year_id'])?'<small class="text-danger">'.e($errors['car_year_id']).'</small>':false !!}
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Màu xe</label>
                    <div class="input-group">
                        <select name="color_id" class="form-control" data-xe="mau">
                            <option value="">— Không chọn —</option>
                            @if (!empty($mauDs))
                            @foreach ($mauDs as $m)
                            <option value="{{$m['id']}}" {{(int)$v('color_id')===(int)$m['id']?'selected':''}}>{{$m['name']}}</option>
                            @endforeach
                            @endif
                        </select>
                        <div class="input-group-append">
                            {!! nut_them_nhanh('mau', 'color_id', ['nhan' => 'màu xe', 'vd' => 'VD: Vàng cát']) !!}
                        </div>
                    </div>
                    {!! !empty($errors['color_id'])?'<small class="text-danger">'.e($errors['color_id']).'</small>':false !!}
                </div>
                <div class="form-group col-md-8 align-self-end">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" checked/>
                        <label class="custom-control-label" for="status">Đang dùng</label>
                    </div>
                </div>
            </div>

            <div class="form-group mb-0">
                <label>Ghi chú</label>
                <input type="text" name="ghi_chu" class="form-control" value="{{$v('ghi_chu')}}"/>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Thêm xe</button>
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
        </div>
    </div>
</form>
<script src="{{asset('public/assets/js/xe-danh-muc.js')}}"></script>
<script src="{{asset('public/assets/js/them-nhanh.js')}}"></script>
