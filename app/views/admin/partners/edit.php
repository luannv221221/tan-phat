<div class="row justify-content-center">
    <div class="col-md-10 col-lg-7">
        <div class="card card-outline card-warning">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-edit mr-2"></i>{{$page_name}}</h3></div>
            <?php /* XE CỦA KHÁCH — một khách nhiều xe. Khai ở đây thì mọi phiếu tiếp nhận,
         báo giá, hoá đơn, phiếu bảo hành của xe đó đều nối về một bản ghi, thay
         vì gõ lại biển số trên từng chứng từ như trước. */ ?>
<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-car mr-2"></i>Xe của khách
            <span class="badge badge-info ml-1">{{count((array) $xeDs)}}</span>
        </h3>
        <div class="card-tools">
            @if (route('admin/vehicles/add'))
            <a href="{{_WEB_URL.'/admin/vehicles/add?ve=partner&partner_id='.$item['id']}}" class="btn btn-sm btn-info"><i class="fas fa-plus mr-1"></i> Thêm xe cho khách này</a>
            @endif
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-sm table-hover mb-0">
            <thead><tr>
                <th style="width:140px">Biển số</th>
                <th>Xe</th>
                <th style="width:170px">Số khung / số máy</th>
                <th style="width:110px" class="text-right">Số km</th>
                <th style="width:150px" class="text-center">Thao tác</th>
            </tr></thead>
            <tbody>
            @if (!empty($xeDs))
                @foreach ($xeDs as $x)
                <tr>
                    <td><span class="font-weight-bold text-uppercase">{{$x['bien_so']}}</span></td>
                    <td>{{VehiclesModel::tenXe($x) !== '' ? VehiclesModel::tenXe($x) : '—'}}</td>
                    <td class="small text-muted">{{!empty($x['so_khung']) ? $x['so_khung'] : '—'}}{{!empty($x['so_may']) ? ' / '.$x['so_may'] : ''}}</td>
                    <td class="text-right">{!! $x['so_km'] !== null ? number_format($x['so_km'], 0, ',', '.') : '<span class="text-muted">—</span>' !!}</td>
                    <td class="text-center text-nowrap">
                        @if (route('admin/receptions/add'))
                        <a href="{{_WEB_URL.'/admin/receptions/add?vehicle_id='.$x['id']}}" class="btn btn-sm btn-outline-info" title="Tiếp nhận xe này"><i class="fas fa-clipboard-check"></i></a>
                        @endif
                        @if (route('admin/vehicles/edit/'.$x['id']))
                        <a href="{{_WEB_URL.'/admin/vehicles/edit/'.$x['id']}}" class="btn btn-sm btn-outline-warning" title="Sửa / lịch sử xe"><i class="fas fa-edit"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="5" class="text-center text-muted py-3">
                    Khách này chưa khai xe nào. Bấm <b>Thêm xe cho khách này</b> — biển số, số khung (VIN), số máy, hãng / model / năm lấy từ Danh mục xe.
                </td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
<form action="" method="post">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    @if (!empty($msg))
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                    @endif
                    <?php $selType = isset($old['type']) ? $old['type'] : $item['type']; ?>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Mã <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" value="{{!empty($old['code'])?$old['code']:$item['code']}}"/>
                            {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-8">
                            <label>Tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{!empty($old['name'])?$old['name']:$item['name']}}"/>
                            {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Loại</label>
                            <select name="type" class="form-control">
                                @foreach ($types as $k => $label)
                                <option value="{{$k}}" {{$selType==$k?'selected':''}}>{{$label}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Mã số thuế</label>
                            <input type="text" class="form-control" name="tax_code" value="{{!empty($old['tax_code'])?$old['tax_code']:(!empty($item['tax_code'])?$item['tax_code']:'')}}"/>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Điện thoại</label>
                            <input type="tel" class="form-control" name="phone" value="{{!empty($old['phone'])?$old['phone']:(!empty($item['phone'])?$item['phone']:'')}}"/>
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
                                    data-chon="{{!empty($old['province_code'])?$old['province_code']:(!empty($item['province_code'])?$item['province_code']:'')}}">
                                <option value="">— Chọn tỉnh / thành phố —</option>
                            </select>
                            {!! !empty($errors['province_code'])?'<small class="text-danger">'.e($errors['province_code']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-4">
                            <label>Phường / Xã</label>
                            <select name="ward_code" class="form-control"
                                    data-dia-gioi="xa" data-chon="{{!empty($old['ward_code'])?$old['ward_code']:(!empty($item['ward_code'])?$item['ward_code']:'')}}" disabled>
                                <option value="">— Chọn tỉnh trước —</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Địa chỉ <span class="text-muted small">(số nhà, đường)</span></label>
                            <input type="text" class="form-control" name="address" value="{{!empty($old['address'])?$old['address']:(!empty($item['address'])?$item['address']:'')}}"/>
                        </div>
                    </div>
                    <script src="{{asset('public/assets/js/dia-gioi.js')}}"></script>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Thứ tự</label>
                            <input type="number" class="form-control" name="sort_order" value="{{isset($old['sort_order'])?$old['sort_order']:$item['sort_order']}}"/>
                        </div>
                        <div class="form-group col-md-9 align-self-end">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$item['status']==1?'checked':''}}/>
                                <label class="custom-control-label" for="status">Đang dùng</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Cập nhật</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
