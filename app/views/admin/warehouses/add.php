<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>{{$page_name}}</h3>
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
                            <input type="text" class="form-control" name="code" placeholder="VD: KHO01" value="{{!empty($old['code'])?$old['code']:''}}"/>
                            {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-8">
                            <label>Tên kho <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="VD: Kho tổng" value="{{!empty($old['name'])?$old['name']:''}}"/>
                            {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <input type="text" class="form-control" name="address" value="{{!empty($old['address'])?$old['address']:''}}"/>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Điện thoại</label>
                            <input type="text" class="form-control" name="phone" value="{{!empty($old['phone'])?$old['phone']:''}}"/>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Thứ tự</label>
                            <input type="number" class="form-control" name="sort_order" value="{{!empty($old['sort_order'])?$old['sort_order']:'0'}}"/>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="is_default" id="is_default" value="1"/>
                            <label class="custom-control-label" for="is_default">Đặt làm kho mặc định</label>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" checked/>
                            <label class="custom-control-label" for="status">Hoạt động</label>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Thêm mới</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
