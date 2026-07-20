<div class="row justify-content-center"><div class="col-md-10 col-lg-8">
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-plane-departure mr-2"></i>{{$page_name}}</h3></div>
        <form action="" method="post">
            <?php echo csrf_field(); ?>
            <div class="card-body">
                @if (!empty($msg))
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                @endif
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Nhân viên <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-control">
                            <option value="">— Chọn nhân viên —</option>
                            @foreach ($employees as $e)
                            <option value="{{$e['id']}}" {{(!empty($old['employee_id']) && $old['employee_id']==$e['id'])?'selected':''}}>{{$e['code'].' - '.$e['name']}}</option>
                            @endforeach
                        </select>
                        {!! !empty($errors['employee_id'])?'<small class="text-danger">'.e($errors['employee_id']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-6">
                        <label>Loại nghỉ</label>
                        <select name="leave_type" class="form-control">
                            @foreach ($types as $k => $label)
                            <option value="{{$k}}" {{(!empty($old['leave_type']) && $old['leave_type']==$k)?'selected':''}}>{{$label}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Từ ngày <span class="text-danger">*</span></label>
                        <input type="date" name="from_date" class="form-control" value="{{!empty($old['from_date'])?$old['from_date']:$today}}"/>
                        {!! !empty($errors['from_date'])?'<small class="text-danger">'.e($errors['from_date']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-4">
                        <label>Đến ngày <span class="text-danger">*</span></label>
                        <input type="date" name="to_date" class="form-control" value="{{!empty($old['to_date'])?$old['to_date']:$today}}"/>
                        {!! !empty($errors['to_date'])?'<small class="text-danger">'.e($errors['to_date']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-4">
                        <label>Số ngày <span class="text-muted small">(bỏ trống sẽ tự tính)</span></label>
                        <input type="text" name="days" class="form-control" value="{{!empty($old['days'])?$old['days']:''}}"/>
                    </div>
                </div>
                <div class="form-group mb-0"><label>Lý do</label><textarea name="reason" class="form-control" rows="2">{{!empty($old['reason'])?$old['reason']:''}}</textarea></div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lập đơn</button> <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a></div>
        </form>
    </div>
</div></div>
