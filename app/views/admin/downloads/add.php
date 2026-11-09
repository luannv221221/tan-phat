<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-arrow-down mr-2"></i>{{$page_name}}</h3>
            </div>

            <form action="" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="card-body">

                    <div class="form-group">
                        <label>Tên tệp <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" id="dl-title"
                               placeholder="VD: Bảng giá phụ tùng 2026"
                               value="{{!empty($old['title'])?$old['title']:''}}"/>
                        {!! !empty($errors['title'])?'<small class="text-danger d-block">'.e($errors['title']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Đường dẫn</label>
                        <input type="text" class="form-control" name="slug" id="dl-slug"
                               placeholder="tự điền theo tên tệp"
                               value="{{!empty($old['slug'])?$old['slug']:''}}"/>
                        <small class="form-text text-muted">
                            Phần sau <code>/tai-ve/</code>. Để trống thì tự lấy theo tên tệp.
                            Trùng với tệp khác thì hệ thống tự thêm số vào cuối.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Tệp <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="file" id="file"/>
                            <label class="custom-file-label" for="file">Chọn tệp...</label>
                        </div>
                        <small class="form-text text-muted">
                            PDF, Word, Excel, PowerPoint, ZIP, RAR, 7z, TXT, CSV, JPG, PNG · tối đa 25MB.
                        </small>
                        {!! !empty($errors['file'])?'<small class="text-danger d-block">'.e($errors['file']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Mô tả ngắn</label>
                        <textarea class="form-control" name="description" rows="2"
                                  placeholder="Hiện dưới tên tệp ở trang Tải về">{{!empty($old['description'])?$old['description']:''}}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Thứ tự hiển thị</label>
                        <input type="number" class="form-control" name="sort_order" style="max-width:150px"
                               value="{{!empty($old['sort_order'])?$old['sort_order']:'0'}}"/>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="require_login" id="require_login" value="1"/>
                            <label class="custom-control-label" for="require_login">Chỉ thành viên mới tải được</label>
                        </div>
                        <small class="form-text text-muted">
                            Khách chưa đăng nhập sẽ được đưa sang trang đăng nhập.
                            <b>Link trực tiếp vẫn tải được</b> — đó là link anh chủ động gửi riêng.
                        </small>
                    </div>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="is_published" id="is_published" value="1" checked/>
                            <label class="custom-control-label" for="is_published">Hiện trên website</label>
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

<script src="{{asset('public/assets/js/seo-slug.js')}}"></script>
<script>
/* Tên tệp -> đường dẫn, dùng chung hàm với màn Tin và màn Hàng hoá để bảng bỏ
   dấu chỉ có một bản. Chỉ tự điền khi người dùng chưa gõ gì vào ô đường dẫn. */
document.addEventListener('change', function (e){
    if (e.target && e.target.id === 'file'){
        var ten = e.target.files.length ? e.target.files[0].name : 'Chọn tệp...';
        var l = e.target.parentNode.querySelector('.custom-file-label');
        if (l) l.textContent = ten;
    }
});
(function(){
    if (!window.oSlugTuTieuDe) return;
    window.oSlugTuTieuDe(document.getElementById('dl-title'), document.getElementById('dl-slug'));
})();
</script>
