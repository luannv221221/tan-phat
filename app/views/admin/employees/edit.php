<?php
$v = function($field, $default = '') use ($old, $item){ if (isset($old[$field])) return $old[$field]; return isset($item[$field]) && $item[$field] !== null ? $item[$field] : $default; };
$isActive = isset($old['status']) ? !empty($old['status']) : ((int) $item['status'] === 1);
?>
<div class="row"><div class="col-lg-10 mx-auto">
    <div class="card card-outline card-warning">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-user-edit mr-2"></i>{{$page_name}}</h3></div>
        <form action="" method="post">
            <?php echo csrf_field(); ?>
            <div class="card-body">
                @if (!empty($msg))
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                @endif
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Mã NV <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" value="{{$v('code')}}"/>
                        {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-5">
                        <label>Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{$v('name')}}"/>
                        {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-4">
                        <label>Giới tính</label>
                        <select name="gender" class="form-control">
                            <option value="">—</option>
                            @foreach ($genders as $k => $label)
                            <option value="{{$k}}" {{$v('gender')==$k?'selected':''}}>{{$label}}</option>
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
                            <option value="{{$d['id']}}" {{$v('department_id')==$d['id']?'selected':''}}>{{$d['name']}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Chức vụ</label>
                        <select name="position_id" class="form-control">
                            <option value="">— Chọn —</option>
                            @foreach ($positions as $p)
                            <option value="{{$p['id']}}" {{$v('position_id')==$p['id']?'selected':''}}>{{$p['name']}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3"><label>Ngày sinh</label><input type="date" name="dob" class="form-control" value="{{$v('dob')}}"/></div>
                    <div class="form-group col-md-3"><label>Điện thoại</label><input type="text" name="phone" class="form-control" value="{{$v('phone')}}"/></div>
                    <div class="form-group col-md-6"><label>Email</label><input type="text" name="email" class="form-control" value="{{$v('email')}}"/></div>
                </div>
                <div class="form-group"><label>Địa chỉ</label><input type="text" name="address" class="form-control" value="{{$v('address')}}"/></div>
                <div class="form-row">
                    <div class="form-group col-md-3"><label>Ngày vào làm</label><input type="date" name="hire_date" class="form-control" value="{{$v('hire_date')}}"/></div>
                    <div class="form-group col-md-3"><label>Lương cơ bản (₫)</label><input type="text" name="salary_base" class="form-control text-right" value="{{$v('salary_base','0')}}"/></div>
                    <div class="form-group col-md-6"><label>Ghi chú</label><input type="text" name="note" class="form-control" value="{{$v('note')}}"/></div>
                </div>
                <div class="form-group mb-0"><div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$isActive?'checked':''}}/>
                    <label class="custom-control-label" for="status">Đang làm việc</label>
                </div></div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button> <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a></div>
        </form>
    </div>
</div></div>
