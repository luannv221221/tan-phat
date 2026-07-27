<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>{{$page_name}}</h3>
            </div>

            <form action="" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    @if (!empty($msg))
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                    @endif

                    <div class="form-group">
                        <label>Tiêu đề</label>
                        <input type="text" class="form-control" name="title" placeholder="VD: Khuyến mãi tháng 7" value="{{!empty($old['title'])?$old['title']:''}}"/>
                        <small class="form-text text-muted">Không bắt buộc — dùng làm mô tả/alt ảnh.</small>
                    </div>

                    <div class="form-group">
                        <label>Ảnh banner <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="image" id="image" accept="image/*"/>
                            <label class="custom-file-label" for="image">Chọn ảnh...</label>
                        </div>
                        <small class="form-text text-muted">Khuyến nghị ảnh ngang (VD 1600×500). JPG, PNG, GIF, WEBP · tối đa 3MB.</small>
                        {!! !empty($errors['image'])?'<small class="text-danger d-block">'.e($errors['image']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Liên kết khi bấm</label>
                        <input type="text" class="form-control" name="link" placeholder="VD: /san-pham hoặc https://..." value="{{!empty($old['link'])?$old['link']:''}}"/>
                        <small class="form-text text-muted">Không bắt buộc.</small>
                    </div>

                    <div class="form-group">
                        <label>Thứ tự hiển thị</label>
                        <input type="number" class="form-control" name="sort_order" style="max-width:150px" value="{{!empty($old['sort_order'])?$old['sort_order']:'0'}}"/>
                    </div>

                    <div class="form-group mb-0">
                        <label class="d-block">Trạng thái</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" checked/>
                            <label class="custom-control-label" for="status">Hiển thị</label>
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

<script>
document.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'image') {
        var name = e.target.files.length ? e.target.files[0].name : 'Chọn ảnh...';
        var lbl = e.target.parentNode.querySelector('.custom-file-label');
        if (lbl) lbl.textContent = name;
    }
});
</script>
