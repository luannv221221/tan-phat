<?php
$v = function($field, $default = '') use ($old, $item){
    if (isset($old[$field])) return $old[$field];
    return isset($item[$field]) ? $item[$field] : $default;
};
$isDefault = isset($old['is_default']) ? !empty($old['is_default']) : ((int) $item['is_default'] === 1);
$isActive  = isset($old['status'])     ? !empty($old['status'])     : ((int) $item['status'] === 1);
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>{{$page_name}}</h3>
            </div>

            <form action="" method="post">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    @if (!empty($msg))
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                    @endif

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Mã kho <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" value="{{$v('code')}}"/>
                            {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-8">
                            <label>Tên kho <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{$v('name')}}"/>
                            {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <input type="text" class="form-control" name="address" value="{{$v('address')}}"/>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Điện thoại</label>
                            <input type="text" class="form-control" name="phone" value="{{$v('phone')}}"/>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Thứ tự</label>
                            <input type="number" class="form-control" name="sort_order" value="{{$v('sort_order','0')}}"/>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="is_default" id="is_default" value="1" {{$isDefault?'checked':''}}/>
                            <label class="custom-control-label" for="is_default">Đặt làm kho mặc định</label>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$isActive?'checked':''}}/>
                            <label class="custom-control-label" for="status">Hoạt động</label>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu thay đổi</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
