<?php
$v = function($field, $default = '') use ($old, $item){
    if (isset($old[$field])) return $old[$field];
    return isset($item[$field]) && $item[$field] !== null ? $item[$field] : $default;
};
$badge = ['received' => 'secondary', 'processing' => 'warning', 'done' => 'success', 'cancelled' => 'danger'];
$__laBaoTri = ($loai === 'bao_tri');
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline {{$__laBaoTri ? 'card-info' : 'card-primary'}}">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas {{$__laBaoTri ? 'fa-oil-can' : 'fa-tools'}} mr-2"></i>
            <span class="badge badge-{{$__laBaoTri ? 'info' : 'primary'}} mr-1">{{$loais[$loai]}}</span>
            Phiếu <code>{{$item['request_no']}}</code>
        </h3>
        <div class="card-tools"><span class="badge badge-{{$badge[$item['status']] ?? 'secondary'}} p-2">{{$statuses[$item['status']] ?? $item['status']}}</span></div>
    </div>
    <div class="card-body py-2">
        <span class="mr-2 small text-muted">Chuyển trạng thái:</span>
        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=received'}}" class="btn btn-sm btn-outline-secondary">Tiếp nhận</a>
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=processing'}}" class="btn btn-sm btn-outline-warning">Đang xử lý</a>
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=done'}}" class="btn btn-sm btn-outline-success">Hoàn tất</a>
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=cancelled'}}" class="btn btn-sm btn-outline-danger">Huỷ</a>
        @endif
        <?php /* Bảo trì xong thì hẹn luôn lần sau khi khách còn đứng ở quầy —
                 điền sẵn khách và xe, người trực chỉ chọn ngày hẹn. */ ?>
        @if ($__laBaoTri && $item['status'] === 'done' && route('admin/'.$routeBase.'/add'))
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add?loai=bao_tri&tu='.$item['id']}}" class="btn btn-sm btn-info ml-2"><i class="fas fa-redo mr-1"></i> Hẹn lần bảo trì kế tiếp</a>
        @endif
        @if (!empty($item['completed_date']))
        <span class="float-right text-muted small">Hoàn tất: {{$item['completed_date']}}</span>
        @endif
    </div>
</div>

<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-signature mr-2"></i>Biên bản giao nhận thiết bị</h3>
        <div class="card-tools">
            @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/handover-add/'.$item['id'].'?type=receive'}}" class="btn btn-sm btn-outline-info"><i class="fas fa-arrow-down mr-1"></i> BB nhận thiết bị</a>
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/handover-add/'.$item['id'].'?type=return'}}" class="btn btn-sm btn-outline-success"><i class="fas fa-arrow-up mr-1"></i> BB trả thiết bị</a>
            @endif
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th style="width:130px">Số BB</th>
                    <th>Loại</th>
                    <th style="width:110px">Ngày</th>
                    <th>Bên giao → Bên nhận</th>
                    <th style="width:120px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($handovers))
                @foreach ($handovers as $h)
                <tr>
                    <td><code>{{$h['handover_no']}}</code></td>
                    <td>{!! $h['type']==='return' ? '<span class="badge badge-success">Trả thiết bị</span>' : '<span class="badge badge-info">Nhận thiết bị</span>' !!}</td>
                    <td>{{$h['handover_date']}}</td>
                    <td class="text-muted small">{{!empty($h['deliverer'])?$h['deliverer']:'—'}} → {{!empty($h['receiver'])?$h['receiver']:'—'}}</td>
                    <td class="text-center">
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/handover-print/'.$h['id']}}" target="_blank" class="btn btn-sm btn-outline-primary" title="In"><i class="fas fa-print"></i></a>
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/handover-delete/'.$h['id']}}" onclick="return confirm('Xoá biên bản này?')" class="btn btn-sm btn-outline-danger" title="Xoá"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="5" class="text-center text-muted py-3">Chưa có biên bản. Bấm <b>BB nhận thiết bị</b> khi tiếp nhận, <b>BB trả thiết bị</b> khi hoàn tất.</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

<form action="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" method="post">
    <?php echo csrf_field(); ?>
    <div class="card"><div class="card-body">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Đối tượng (khách)</label>
                <select name="partner_id" class="form-control js-search" data-placeholder="Gõ tên hoặc mã để tìm...">
                    <option value="">— Chọn / khách lẻ —</option>
                    @foreach ($partners as $pn)
                    <option value="{{$pn['id']}}" {{$v('partner_id')==$pn['id']?'selected':''}}>{{$pn['code'].' - '.$pn['name']}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-4">
                <label>Tên khách (nếu lẻ)</label>
                <input type="text" name="customer_name" class="form-control" value="{{$v('customer_name')}}"/>
                {!! !empty($errors['customer_name'])?'<small class="text-danger">'.e($errors['customer_name']).'</small>':false !!}
            </div>
            <div class="form-group col-md-4">
                <label>Điện thoại</label>
                <input type="tel" name="phone" class="form-control" value="{{$v('phone')}}"/>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Sản phẩm (trong danh mục)</label>
                <select name="part_id" class="form-control">
                    <option value="">— Không chọn —</option>
                    @foreach ($parts as $p)
                    <option value="{{$p['id']}}" {{$v('part_id')==$p['id']?'selected':''}}>{{$p['code'].' - '.$p['name']}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-4">
                <label>Tên thiết bị (nhập tay)</label>
                <input type="text" name="product_name" class="form-control" value="{{$v('product_name')}}"/>
                {!! !empty($errors['product_name'])?'<small class="text-danger">'.e($errors['product_name']).'</small>':false !!}
            </div>
            <div class="form-group col-md-4">
                <label>Số serial</label>
                <input type="text" name="serial_no" class="form-control" value="{{$v('serial_no')}}"/>
                <small class="form-text text-muted">Serial của phụ tùng, khác biển số xe bên dưới.</small>
            </div>
        </div>

        <?php /* XE MANG PHỤ TÙNG ĐÓ — xem chú thích ở add.php */ ?>
        <div class="form-row" data-xe-khach="partner_id" data-url="<?php echo _WEB_URL; ?>/admin/vehicles">
            <div class="form-group col-md-4">
                <label>Biển số xe</label>
                <select class="form-control js-xe-list d-none"></select>
                <input type="text" name="bien_so" class="form-control text-uppercase js-xe-go"
                       placeholder="VD: 30A-123.45" value="{{$v('bien_so')}}"/>
                <small class="form-text text-muted js-xe-nhac">Bảo dưỡng xe thì chỉ cần biển số, không phải chọn sản phẩm.</small>
            </div>
            <div class="form-group col-md-3">
                <label>Số km</label>
                <input type="text" name="so_km" class="form-control text-right"
                       placeholder="VD: 100.000" value="{{$v('so_km')}}"/>
                <small class="form-text text-muted js-xe-km">Gõ số km đọc trên đồng hồ lúc xe vào.</small>
            </div>
        </div>
        <script src="{{asset('public/assets/js/xe-cua-khach.js')}}"></script>
        <div class="form-row">
            <div class="form-group col-md-3">
                <label>Ngày tiếp nhận <span class="text-danger">*</span></label>
                <input type="date" name="received_date" class="form-control" value="{{$v('received_date')}}"/>
                {!! !empty($errors['received_date'])?'<small class="text-danger">'.e($errors['received_date']).'</small>':false !!}
            </div>
            <div class="form-group col-md-3">
                <label>Ngày hẹn</label>
                <input type="date" name="appointment_date" class="form-control" value="{{$v('appointment_date')}}"/>
            </div>
            <div class="form-group col-md-3">
                <label>Kỹ thuật viên</label>
                <input type="text" name="technician" class="form-control" value="{{$v('technician')}}"/>
            </div>
            <div class="form-group col-md-3">
                <label>Phí (₫)</label>
                <input type="number" min="0" step="1" name="fee" class="form-control text-right" value="{{$v('fee','0')}}"/>
            </div>
        </div>
        <div class="form-group">
            <label>Tình trạng / việc cần làm</label>
            <textarea name="issue" class="form-control" rows="2">{{$v('issue')}}</textarea>
        </div>
        <div class="form-group">
            <label>Chẩn đoán / đã làm</label>
            <textarea name="diagnosis" class="form-control" rows="2">{{$v('diagnosis')}}</textarea>
        </div>
        <div class="form-group mb-0">
            <label>Ghi chú</label>
            <input type="text" name="note" class="form-control" value="{{$v('note')}}"/>
        </div>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá phiếu này?')" class="btn btn-outline-danger"><i class="fas fa-trash mr-1"></i> Xoá</a>
        @endif
        <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Về danh sách</a>
    </div>
    </div>
</form>
