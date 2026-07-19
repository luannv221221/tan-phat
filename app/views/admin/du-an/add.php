<div class="row"><div class="col-lg-9 mx-auto">
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>{{$page_name}}</h3></div>
        <form action="" method="post" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <div class="card-body">
                @if (!empty($msg))
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                @endif
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Tên dự án <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{!empty($old['name'])?$old['name']:''}}"/>
                        {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-4">
                        <label>Ngày hoàn thành</label>
                        <input type="date" name="completed_at" class="form-control" value="{{!empty($old['completed_at'])?$old['completed_at']:''}}"/>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Khách hàng</label>
                        <input type="text" name="client" class="form-control" value="{{!empty($old['client'])?$old['client']:''}}"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Địa điểm</label>
                        <input type="text" name="location" class="form-control" value="{{!empty($old['location'])?$old['location']:''}}"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Ảnh (tải lên)</label>
                        <input type="file" name="thumbnail_file" accept="image/*" class="form-control-file"/>
                        {!! !empty($errors['thumbnail_file'])?'<small class="text-danger d-block">'.e($errors['thumbnail_file']).'</small>':false !!}
                        <input type="text" name="thumbnail" class="form-control mt-1" placeholder="hoặc URL ảnh" value="{{!empty($old['thumbnail'])?$old['thumbnail']:''}}"/>
                    </div>
                </div>
                <div class="form-group">
                    <label>Đường dẫn (slug)</label>
                    <input type="text" name="slug" class="form-control" placeholder="Bỏ trống sẽ tự sinh" value="{{!empty($old['slug'])?$old['slug']:''}}"/>
                    {!! !empty($errors['slug'])?'<small class="text-danger">'.e($errors['slug']).'</small>':false !!}
                </div>
                <div class="form-group">
                    <label>Tóm tắt</label>
                    <textarea name="summary" class="form-control" rows="2">{{!empty($old['summary'])?$old['summary']:''}}</textarea>
                </div>
                <div class="form-group">
                    <label>Nội dung (cho phép HTML)</label>
                    <textarea name="content" class="form-control" rows="8">{{!empty($old['content'])?$old['content']:''}}</textarea>
                </div>
                <div class="form-row align-items-center">
                    <div class="form-group col-md-3">
                        <label>Thứ tự</label>
                        <input type="number" name="sort_order" class="form-control" value="{{!empty($old['sort_order'])?$old['sort_order']:'0'}}"/>
                    </div>
                    <div class="form-group col-md-4 mt-4"><div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" name="is_published" id="is_published" value="1"/>
                        <label class="custom-control-label" for="is_published">Đăng ngay</label>
                    </div></div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
            </div>
        </form>
    </div>
</div></div>
