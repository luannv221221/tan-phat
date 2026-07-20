<?php
$v = function($field, $default = '') use ($old, $item){
    if (isset($old[$field])) return $old[$field];
    return isset($item[$field]) && $item[$field] !== null ? $item[$field] : $default;
};
$isActive = isset($old['status']) ? !empty($old['status']) : ((int) $item['status'] === 1);
?>
<div class="row justify-content-center"><div class="col-md-8 col-lg-6">
    <div class="card card-outline card-warning">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-edit mr-2"></i>{{$page_name}}</h3></div>
        <form action="" method="post">
            <?php echo csrf_field(); ?>
            <div class="card-body">
                @if (!empty($msg))
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                @endif
                <div class="form-group">
                    <label>Tên nhóm <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{$v('name')}}"/>
                    {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                </div>
                <div class="form-group">
                    <label>Chiết khấu (%)</label>
                    <input type="text" name="discount_percent" class="form-control" style="max-width:150px" value="{{$v('discount_percent','0')}}"/>
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <input type="text" name="note" class="form-control" value="{{$v('note')}}"/>
                </div>
                <div class="form-group">
                    <label>Thứ tự</label>
                    <input type="number" name="sort_order" class="form-control" style="max-width:150px" value="{{$v('sort_order','0')}}"/>
                </div>
                <div class="form-group mb-0"><div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$isActive?'checked':''}}/>
                    <label class="custom-control-label" for="status">Bật</label>
                </div></div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
            </div>
        </form>
    </div>
</div></div>
