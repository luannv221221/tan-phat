<?php
$v = function($field, $default = '') use ($old, $item){
    if (isset($old[$field])) return $old[$field];
    return isset($item[$field]) && $item[$field] !== null ? $item[$field] : $default;
};
$badge = ['received' => 'secondary', 'processing' => 'warning', 'done' => 'success', 'cancelled' => 'danger'];
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tools mr-2"></i>Phiếu <code>{{$item['request_no']}}</code></h3>
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
        @if (!empty($item['completed_date']))
        <span class="float-right text-muted small">Hoàn tất: {{$item['completed_date']}}</span>
        @endif
    </div>
</div>

<form action="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" method="post">
    <?php echo csrf_field(); ?>
    <div class="card"><div class="card-body">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Đối tượng (khách)</label>
                <select name="partner_id" class="form-control">
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
                <input type="text" name="phone" class="form-control" value="{{$v('phone')}}"/>
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
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-3">
                <label>Ngày tiếp nhận <span class="text-danger">*</span></label>
                <input type="date" name="received_date" class="form-control" value="{{$v('received_date')}}"/>
                {!! !empty($errors['received_date'])?'<small class="text-danger">'.e($errors['received_date']).'</small>':false !!}
            </div>
            <div class="form-group col-md-3">
                <label>Ngày hẹn trả</label>
                <input type="date" name="appointment_date" class="form-control" value="{{$v('appointment_date')}}"/>
            </div>
            <div class="form-group col-md-3">
                <label>Kỹ thuật viên</label>
                <input type="text" name="technician" class="form-control" value="{{$v('technician')}}"/>
            </div>
            <div class="form-group col-md-3">
                <label>Phí sửa (₫)</label>
                <input type="text" name="fee" class="form-control text-right" value="{{$v('fee','0')}}"/>
            </div>
        </div>
        <div class="form-group">
            <label>Mô tả lỗi / tình trạng</label>
            <textarea name="issue" class="form-control" rows="2">{{$v('issue')}}</textarea>
        </div>
        <div class="form-group">
            <label>Chẩn đoán / xử lý</label>
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
