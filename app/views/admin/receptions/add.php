<?php
$v = function($k, $mac = '') use ($old){ return isset($old[$k]) ? $old[$k] : $mac; };
$tien = function($x){ return number_format((float) $x, 0, ',', '.'); };
?>
<form action="" method="post">
    <?php echo csrf_field(); ?>
    @if (!empty($msg))
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
    @endif

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>{{$page_name}}</h3>
            <div class="card-tools text-muted small">Số phiếu sẽ là <code>{{$soPhieu}}</code></div>
        </div>
        <div class="card-body">
            <?php /* XE là gốc của phiếu: mọi chứng từ của lần vào xưởng này sẽ
                     treo vào phiếu, và phiếu treo vào xe. Không chọn được xe thì
                     khai xe ở màn Xe của khách trước — không gõ biển số tự do
                     nữa, vì đó chính là chỗ sai cũ. */ ?>
            <div class="form-row">
                <div class="form-group col-md-8">
                    <label>Xe vào xưởng <span class="text-danger">*</span></label>
                    <select name="vehicle_id" class="form-control js-search" data-placeholder="Gõ biển số để tìm...">
                        <option value="">— Chọn xe theo biển số —</option>
                        @if (!empty($xeDs))
                        @foreach ($xeDs as $x)
                        <?php $nhan = $x['bien_so'] . ' — ' . VehiclesModel::tenXe($x) . (!empty($x['chu_ten']) ? ' — ' . $x['chu_ten'] : ' — (chưa gán chủ)'); ?>
                        <option value="{{$x['id']}}" {{(int)$v('vehicle_id')===(int)$x['id']?'selected':''}}>{{$nhan}}</option>
                        @endforeach
                        @endif
                    </select>
                    {!! !empty($errors['vehicle_id'])?'<small class="text-danger">'.e($errors['vehicle_id']).'</small>':false !!}
                    <small class="form-text text-muted">
                        Xe chưa có trong hệ thống?
                        <a href="{{_WEB_URL.'/admin/vehicles/add'}}" target="_blank">Khai xe mới</a> rồi quay lại chọn.
                    </small>
                </div>
                <div class="form-group col-md-4">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control">
                        @foreach ($statuses as $k => $ten)
                        <option value="{{$k}}" {{$v('status', 'tiep_nhan')===$k?'selected':''}}>{{$ten}}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if (!empty($xe))
            <div class="alert alert-secondary py-2">
                <i class="fas fa-car mr-1"></i>
                <b class="text-uppercase">{{$xe['bien_so']}}</b>
                {{VehiclesModel::tenXe($xe) !== '' ? '— '.VehiclesModel::tenXe($xe) : ''}}
                {{!empty($xe['chu_ten']) ? '· Chủ: '.$xe['chu_ten'] : '· Chưa gán chủ'}}
                {!! $xe['so_km'] !== null ? '· Km đã ghi nhận: <b>'.$tien($xe['so_km']).'</b>' : '' !!}
            </div>
            @endif

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Ngày vào <span class="text-danger">*</span></label>
                    <input type="date" name="ngay_vao" class="form-control" value="{{$v('ngay_vao', $today)}}"/>
                    {!! !empty($errors['ngay_vao'])?'<small class="text-danger">'.e($errors['ngay_vao']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Km vào</label>
                    <input type="text" name="km_vao" class="form-control text-right" value="{{$v('km_vao')}}"/>
                    {!! !empty($errors['km_vao'])?'<small class="text-danger">'.e($errors['km_vao']).'</small>':false !!}
                    <small class="form-text text-muted">Số trên đồng hồ lúc xe vào.</small>
                </div>
                <div class="form-group col-md-3">
                    <label>Cố vấn dịch vụ</label>
                    <select name="co_van_id" class="form-control">
                        <option value="">— Chọn nhân viên —</option>
                        @if (!empty($coVanDs))
                        @foreach ($coVanDs as $u)
                        <option value="{{$u['id']}}" {{(int)$v('co_van_id')===(int)$u['id']?'selected':''}}>{{$u['name']}}</option>
                        @endforeach
                        @endif
                    </select>
                    {!! !empty($errors['co_van_id'])?'<small class="text-danger">'.e($errors['co_van_id']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Hoặc gõ tên cố vấn</label>
                    <input type="text" name="co_van" class="form-control" value="{{$v('co_van')}}"/>
                    <small class="form-text text-muted">Chỉ dùng khi người đó chưa có tài khoản.</small>
                </div>
            </div>

            <div class="form-group">
                <label>Nội dung khách yêu cầu</label>
                <textarea name="yeu_cau_khach" class="form-control" rows="2"
                          placeholder="VD: Bảo dưỡng 50.000 km, kiểm tra tiếng kêu gầm trước">{{$v('yeu_cau_khach')}}</textarea>
            </div>
            <div class="form-group">
                <label>Tình trạng xe khi vào</label>
                <textarea name="tinh_trang_xe" class="form-control" rows="2"
                          placeholder="VD: Xước cản sau, thiếu nắp che gầm, còn 1/4 bình nhiên liệu">{{$v('tinh_trang_xe')}}</textarea>
                <small class="form-text text-muted">Ghi rõ lúc nhận xe để sau không tranh cãi.</small>
            </div>
            <div class="form-group mb-0">
                <label>Ghi chú</label>
                <input type="text" name="note" class="form-control" value="{{$v('note')}}"/>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1"></i> Lập phiếu tiếp nhận</button>
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
        </div>
    </div>
</form>
