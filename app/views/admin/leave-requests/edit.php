<?php
$v = function($field, $default = '') use ($old, $item){ if (isset($old[$field])) return $old[$field]; return isset($item[$field]) && $item[$field] !== null ? $item[$field] : $default; };
$badge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="row justify-content-center"><div class="col-md-10 col-lg-8">
    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-plane-departure mr-2"></i>{{$page_name}}</h3>
            <div class="card-tools"><span class="badge badge-{{$badge[$item['status']] ?? 'secondary'}} p-2">{{$statuses[$item['status']] ?? $item['status']}}</span></div>
        </div>
        <form action="" method="post">
            <?php echo csrf_field(); ?>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Nhân viên <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-control">
                            <option value="">— Chọn nhân viên —</option>
                            @foreach ($employees as $e)
                            <option value="{{$e['id']}}" {{$v('employee_id')==$e['id']?'selected':''}}>{{$e['code'].' - '.$e['name']}}</option>
                            @endforeach
                        </select>
                        {!! !empty($errors['employee_id'])?'<small class="text-danger">'.e($errors['employee_id']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-6">
                        <label>Loại nghỉ</label>
                        <select name="leave_type" class="form-control">
                            @foreach ($types as $k => $label)
                            <option value="{{$k}}" {{$v('leave_type')==$k?'selected':''}}>{{$label}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Từ ngày <span class="text-danger">*</span></label>
                        <input type="date" name="from_date" class="form-control" value="{{$v('from_date')}}"/>
                        {!! !empty($errors['from_date'])?'<small class="text-danger">'.e($errors['from_date']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-4">
                        <label>Đến ngày <span class="text-danger">*</span></label>
                        <input type="date" name="to_date" class="form-control" value="{{$v('to_date')}}"/>
                        {!! !empty($errors['to_date'])?'<small class="text-danger">'.e($errors['to_date']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-4"><label>Số ngày</label><input type="text" name="days" class="form-control" value="{{$v('days')}}"/></div>
                </div>
                <div class="form-group mb-0"><label>Lý do</label><textarea name="reason" class="form-control" rows="2">{{$v('reason')}}</textarea></div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
                @if ($item['status']=='pending' && route('admin/'.$routeBase.'/edit/'.$item['id']))
                <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=approved'}}" class="btn btn-success"><i class="fas fa-check mr-1"></i> Duyệt</a>
                <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=rejected'}}" class="btn btn-outline-secondary"><i class="fas fa-times mr-1"></i> Từ chối</a>
                @endif
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
            </div>
        </form>
    </div>
</div></div>
