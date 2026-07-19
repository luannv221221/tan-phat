<div class="row"><div class="col-lg-10 mx-auto">
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>{{$page_name}}</h3></div>
        <form action="" method="post">
            <?php echo csrf_field(); ?>
            <div class="card-body">
                @if (!empty($msg))
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                @endif
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Mã NV <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" value="{{!empty($old['code'])?$old['code']:''}}"/>
                        {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-5">
                        <label>Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{!empty($old['name'])?$old['name']:''}}"/>
                        {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-4">
                        <label>Giới tính</label>
                        <select name="gender" class="form-control">
                            <option value="">—</option>
                            @foreach ($genders as $k => $label)
                            <option value="{{$k}}" {{(!empty($old['gender']) && $old['gender']==$k)?'selected':''}}>{{$label}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Phòng ban</label>
                        <select name="department_id" class="form-control">
                            <option value="">— Chọn —</option>
                            @foreach ($departments as $d)
                            <option value="{{$d['id']}}" {{(!empty($old['department_id']) && $old['department_id']==$d['id'])?'selected':''}}>{{$d['name']}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Chức vụ</label>
                        <select name="position_id" class="form-control">
                            <option value="">— Chọn —</option>
                            @foreach ($positions as $p)
                            <option value="{{$p['id']}}" {{(!empty($old['position_id']) && $old['position_id']==$p['id'])?'selected':''}}>{{$p['name']}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3"><label>Ngày sinh</label><input type="date" name="dob" class="form-control" value="{{!empty($old['dob'])?$old['dob']:''}}"/></div>
                    <div class="form-group col-md-3"><label>Điện thoại</label><input type="text" name="phone" class="form-control" value="{{!empty($old['phone'])?$old['phone']:''}}"/></div>
                    <div class="form-group col-md-6"><label>Email</label><input type="text" name="email" class="form-control" value="{{!empty($old['email'])?$old['email']:''}}"/></div>
                </div>
                <div class="form-group"><label>Địa chỉ</label><input type="text" name="address" class="form-control" value="{{!empty($old['address'])?$old['address']:''}}"/></div>
                <div class="form-row">
                    <div class="form-group col-md-3"><label>Ngày vào làm</label><input type="date" name="hire_date" class="form-control" value="{{!empty($old['hire_date'])?$old['hire_date']:''}}"/></div>
                    <div class="form-group col-md-3"><label>Lương cơ bản (₫)</label><input type="text" name="salary_base" class="form-control text-right" value="{{!empty($old['salary_base'])?$old['salary_base']:'0'}}"/></div>
                    <div class="form-group col-md-6"><label>Ghi chú</label><input type="text" name="note" class="form-control" value="{{!empty($old['note'])?$old['note']:''}}"/></div>
                </div>
                <div class="form-group mb-0"><div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" checked/>
                    <label class="custom-control-label" for="status">Đang làm việc</label>
                </div></div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Thêm</button> <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a></div>
        </form>
    </div>
</div></div>
