<div class="row justify-content-center"><div class="col-md-9 col-lg-7">
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>{{$page_name}}</h3></div>
        <form action="" method="post" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <div class="card-body">
                @if (!empty($msg))
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                @endif
                <div class="form-group">
                    <label>Tên album <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{!empty($old['name'])?$old['name']:''}}"/>
                    {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" class="form-control" placeholder="Bỏ trống sẽ tự sinh" value="{{!empty($old['slug'])?$old['slug']:''}}"/>
                    {!! !empty($errors['slug'])?'<small class="text-danger">'.e($errors['slug']).'</small>':false !!}
                </div>
                <div class="form-group"><label>Mô tả</label><textarea name="description" class="form-control" rows="2">{{!empty($old['description'])?$old['description']:''}}</textarea></div>
                <div class="form-group">
                    <label>Ảnh bìa</label>
                    <input type="file" name="cover_file" accept="image/*" class="form-control-file"/>
                    {!! !empty($errors['cover_file'])?'<small class="text-danger d-block">'.e($errors['cover_file']).'</small>':false !!}
                </div>
                <div class="form-row align-items-center">
                    <div class="form-group col-md-3"><label>Thứ tự</label><input type="number" name="sort_order" class="form-control" value="{{!empty($old['sort_order'])?$old['sort_order']:'0'}}"/></div>
                    <div class="form-group col-md-4 mt-4"><div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" name="is_published" id="is_published" value="1"/>
                        <label class="custom-control-label" for="is_published">Đăng</label>
                    </div></div>
                </div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Tạo & thêm ảnh</button> <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a></div>
        </form>
    </div>
</div></div>
