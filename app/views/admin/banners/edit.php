<?php $val = function($k, $def = '') use ($item, $old){ return isset($old[$k]) ? $old[$k] : (isset($item[$k]) ? $item[$k] : $def); }; ?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>{{$page_name}}</h3>
            </div>

            <form action="" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    @if (!empty($msg))
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                    @endif

                    <div class="form-group">
                        <label>Tiêu đề</label>
                        <input type="text" class="form-control" name="title" placeholder="VD: Khuyến mãi tháng 7" value="{{$val('title')}}"/>
                    </div>

                    <div class="form-group">
                        <label>Ảnh hiện tại</label>
                        <div class="mb-2">
                            @if (!empty($item['image']))
                            <img src="{{media_url($item['image'])}}" alt="banner" style="max-height:120px;max-width:100%;border-radius:6px;border:1px solid #dee2e6"/>
                            @else
                            <span class="text-muted">Chưa có ảnh</span>
                            @endif
                        </div>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="image" id="image" accept="image/*"/>
                            <label class="custom-file-label" for="image">Chọn ảnh mới...</label>
                        </div>
                        <small class="form-text text-muted">Để trống sẽ giữ ảnh hiện tại. JPG, PNG, GIF, WEBP · tối đa 3MB.</small>
                        {!! !empty($errors['image'])?'<small class="text-danger d-block">'.e($errors['image']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Liên kết khi bấm</label>
                        <input type="text" class="form-control" name="link" placeholder="VD: /san-pham hoặc https://..." value="{{$val('link')}}"/>
                    </div>

                    <div class="form-group">
                        <label>Thứ tự hiển thị</label>
                        <input type="number" class="form-control" name="sort_order" style="max-width:150px" value="{{$val('sort_order','0')}}"/>
                    </div>

                    <div class="form-group mb-0">
                        <label class="d-block">Trạng thái</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{(isset($old['status'])?!empty($old['status']):$item['status']==1)?'checked':''}}/>
                            <label class="custom-control-label" for="status">Hiển thị</label>
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

<script>
document.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'image') {
        var name = e.target.files.length ? e.target.files[0].name : 'Chọn ảnh mới...';
        var lbl = e.target.parentNode.querySelector('.custom-file-label');
        if (lbl) lbl.textContent = name;
    }
});
</script>
