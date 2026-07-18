<div class="row"><div class="col-lg-9 mx-auto">
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>{{$page_name}}</h3></div>
        <form action="" method="post">
            <?php echo csrf_field(); ?>
            <div class="card-body">
                @if (!empty($msg))
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                @endif
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Tiêu đề <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{!empty($old['title'])?$old['title']:''}}"/>
                        {!! !empty($errors['title'])?'<small class="text-danger">'.e($errors['title']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-4">
                        <label>Danh mục</label>
                        <select name="category_id" class="form-control">
                            <option value="">— Chọn —</option>
                            @foreach ($categories as $c)
                            <option value="{{$c['id']}}" {{(!empty($old['category_id']) && $old['category_id']==$c['id'])?'selected':''}}>{{$c['name']}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Đường dẫn (slug)</label>
                        <input type="text" name="slug" class="form-control" placeholder="Bỏ trống sẽ tự sinh" value="{{!empty($old['slug'])?$old['slug']:''}}"/>
                        {!! !empty($errors['slug'])?'<small class="text-danger">'.e($errors['slug']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-4">
                        <label>Ảnh (URL)</label>
                        <input type="text" name="thumbnail" class="form-control" value="{{!empty($old['thumbnail'])?$old['thumbnail']:''}}"/>
                    </div>
                </div>
                <div class="form-group">
                    <label>Tóm tắt</label>
                    <textarea name="summary" class="form-control" rows="2">{{!empty($old['summary'])?$old['summary']:''}}</textarea>
                </div>
                <div class="form-group">
                    <label>Nội dung (cho phép HTML)</label>
                    <textarea name="content" class="form-control" rows="10">{{!empty($old['content'])?$old['content']:''}}</textarea>
                </div>
                <div class="form-group mb-0"><div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" name="is_published" id="is_published" value="1"/>
                    <label class="custom-control-label" for="is_published">Đăng ngay</label>
                </div></div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
            </div>
        </form>
    </div>
</div></div>
