<?php
$val = function ($key) use ($item, $old) {
    if (isset($old[$key]) && $old[$key] !== '') return $old[$key];
    return isset($item[$key]) ? $item[$key] : '';
};
$statusOn = isset($old['status']) ? (int) $old['status'] === 1 : (int) $item['status'] === 1;
?>
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    {{$msgError}}
</div>
@endif

<div class="row justify-content-center">
    <div class="col-lg-10">

        <?php /* XE CỦA KHÁCH — một khách nhiều xe, một xe nhiều phiếu. Đặt TRÊN
                 form hồ sơ: gần như lần nào mở khách ở gara cũng là để tiếp nhận
                 hoặc khai xe. Xe là bảng `vehicles` đầy đủ, dùng chung với màn
                 Xe của khách và Đối tượng. */ ?>
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-car mr-2"></i>Xe của khách
                    <span class="badge badge-info ml-1">{{count((array) $dsXe)}}</span>
                </h3>
                <div class="card-tools">
                    @if (route('admin/vehicles/add'))
                    <a href="{{_WEB_URL.'/admin/vehicles/add?ve=customer&partner_id='.$item['id']}}" class="btn btn-sm btn-info"><i class="fas fa-plus mr-1"></i> Thêm xe cho khách này</a>
                    @endif
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr>
                        <th style="width:130px">Biển số</th>
                        <th>Xe</th>
                        <th style="width:190px">Số khung (VIN) / số máy</th>
                        <th style="width:100px" class="text-right">Số km</th>
                        <th style="width:80px" class="text-center">Số phiếu</th>
                        <th style="width:120px" class="text-center">Thao tác</th>
                    </tr></thead>
                    <tbody>
                    @if (!empty($dsXe))
                        @foreach ($dsXe as $x)
                        <tr>
                            <td><span class="font-weight-bold text-uppercase">{{$x['bien_so']}}</span></td>
                            <td>{{VehiclesModel::tenXe($x) !== '' ? VehiclesModel::tenXe($x) : '—'}}{{!empty($x['mau_xe']) ? ' · '.$x['mau_xe'] : ''}}</td>
                            <td class="small text-muted">{{!empty($x['so_khung']) ? $x['so_khung'] : '—'}}{{!empty($x['so_may']) ? ' / '.$x['so_may'] : ''}}</td>
                            <td class="text-right">{!! $x['so_km'] !== null ? number_format($x['so_km'], 0, ',', '.') : '<span class="text-muted">—</span>' !!}</td>
                            <td class="text-center">{{isset($soPhieu[(int)$x['id']]) ? (int)$soPhieu[(int)$x['id']] : 0}}</td>
                            <td class="text-center text-nowrap">
                                @if (route('admin/receptions/add'))
                                <a href="{{_WEB_URL.'/admin/receptions/add?vehicle_id='.$x['id']}}" class="btn btn-sm btn-outline-info" title="Lập phiếu tiếp nhận xe này"><i class="fas fa-clipboard-check"></i></a>
                                @endif
                                @if (route('admin/vehicles/edit/'.$x['id']))
                                <a href="{{_WEB_URL.'/admin/vehicles/edit/'.$x['id']}}" class="btn btn-sm btn-outline-warning" title="Sửa / lịch sử xe"><i class="fas fa-edit"></i></a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr><td colspan="6" class="text-center text-muted py-3">
                            Khách này chưa khai xe nào. Bấm <b>Thêm xe cho khách này</b> — biển số, số khung (VIN), số máy, hãng / model / năm lấy từ Danh mục xe.
                        </td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-2"></i>Hồ sơ khách — <span class="text-muted">{{$item['code']}}</span></h3>
                <div class="card-tools">
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-icon" title="Quay lại"><i class="fas fa-arrow-left"></i></a>
                </div>
            </div>

            <form action="" method="post">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    @if (!empty($msg))
                    <div class="alert alert-info py-2">{{$msg}}</div>
                    @endif

                    <div class="form-group">
                        <label>Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{$val('name')}}"/>
                        {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Số điện thoại</label>
                            <input type="tel" inputmode="numeric" maxlength="11" name="phone" class="form-control" value="{{$val('phone')}}"/>
                            {!! !empty($errors['phone'])?'<small class="text-danger">'.e($errors['phone']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-6">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="{{$val('email')}}"/>
                            {!! !empty($errors['email'])?'<small class="text-danger">'.e($errors['email']).'</small>':false !!}
                        </div>
                    </div>

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

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$statusOn?'checked':''}}/>
                            <label class="custom-control-label" for="status">Đang giao dịch</label>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu hồ sơ</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
