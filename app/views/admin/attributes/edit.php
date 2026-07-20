<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
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

                    <div class="form-group">
                        <label>Tên thông số <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{!empty($old['name'])?$old['name']:$item['name']}}"/>
                        {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Đường dẫn (slug)</label>
                        <input type="text" class="form-control" name="slug" value="{{!empty($old['slug'])?$old['slug']:$item['slug']}}"/>
                        {!! !empty($errors['slug'])?'<small class="text-danger">'.e($errors['slug']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Đơn vị đo</label>
                        <input type="text" class="form-control" name="unit" style="max-width:200px" value="{{!empty($old['unit'])?$old['unit']:(!empty($item['unit'])?$item['unit']:'')}}"/>
                    </div>

                    <div class="form-group">
                        <label>Thứ tự hiển thị</label>
                        <input type="number" class="form-control" name="sort_order" style="max-width:150px" value="{{isset($old['sort_order'])?$old['sort_order']:$item['sort_order']}}"/>
                    </div>

                    <div class="form-group mb-0">
                        <label class="d-block">Trạng thái</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$item['status']==1?'checked':''}}/>
                            <label class="custom-control-label" for="status">Hiển thị</label>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Cập nhật</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
