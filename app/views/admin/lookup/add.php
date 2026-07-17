<div class="container py-3">
    <h3>{{$page_name}}</h3>
    <hr>
    <form action="" method="post">
        <?php echo csrf_field(); ?>
        @if (!empty($msg))
        <div class="alert alert-danger text-center">{{$msg}}</div>
        @endif

        <div class="form-group">
            <label>Tên <span style="color:red">*</span></label>
            <input type="text" class="form-control" name="name" placeholder="Nhập tên..." value="{{!empty($old['name'])?$old['name']:''}}"/>
            {!! !empty($errors['name'])?'<span style="color:red">'.e($errors['name']).'</span>':false !!}
        </div>

        <div class="form-group">
            <label>Đường dẫn (slug)</label>
            <input type="text" class="form-control" name="slug" placeholder="Bỏ trống sẽ tự sinh từ tên" value="{{!empty($old['slug'])?$old['slug']:''}}"/>
            <small class="form-text text-muted">Chỉ gồm chữ thường, số và dấu gạch ngang. Bỏ trống hệ thống tự sinh từ tên.</small>
            {!! !empty($errors['slug'])?'<span style="color:red">'.e($errors['slug']).'</span>':false !!}
        </div>

        @if ($hasHex)
        <div class="form-group">
            <label>Mã màu</label>
            <input type="color" class="form-control" name="hex" style="max-width:120px" value="{{!empty($old['hex'])?$old['hex']:'#ffffff'}}"/>
        </div>
        @endif

        <div class="form-group">
            <label>Thứ tự hiển thị</label>
            <input type="number" class="form-control" name="sort_order" style="max-width:150px" value="{{!empty($old['sort_order'])?$old['sort_order']:'0'}}"/>
        </div>

        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" name="status" id="status" value="1" checked/>
            <label class="form-check-label" for="status">Hiển thị</label>
        </div>

        <button type="submit" class="btn btn-primary">Thêm mới</button>
        <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-secondary">Quay lại</a>
    </form>
</div>
