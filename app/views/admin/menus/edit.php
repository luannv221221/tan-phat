<?php
$v = function($field, $default = '') use ($old, $item){ if (isset($old[$field])) return $old[$field]; return isset($item[$field]) && $item[$field] !== null ? $item[$field] : $default; };
$isActive = isset($old['status']) ? !empty($old['status']) : ((int) $item['status'] === 1);
$tgt = $v('target', '_self');
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
                    <label>Nhãn hiển thị <span class="text-danger">*</span></label>
                    <input type="text" name="label" class="form-control" value="{{$v('label')}}"/>
                    {!! !empty($errors['label'])?'<small class="text-danger">'.e($errors['label']).'</small>':false !!}
                </div>
                <div class="form-group">
                    <label>Liên kết (URL)</label>
                    <input type="text" name="url" class="form-control" value="{{$v('url')}}"/>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Menu cha</label>
                        <select name="parent_id" class="form-control">
                            <option value="">— Menu gốc —</option>
                            @foreach ($roots as $r)
                            <option value="{{$r['id']}}" {{$v('parent_id')==$r['id']?'selected':''}}>{{$r['label']}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Mở trong</label>
                        <select name="target" class="form-control">
                            <option value="_self" {{$tgt=='_self'?'selected':''}}>Cùng tab</option>
                            <option value="_blank" {{$tgt=='_blank'?'selected':''}}>Tab mới</option>
                        </select>
                    </div>
                </div>
                <div class="form-row align-items-center">
                    <div class="form-group col-md-4"><label>Thứ tự</label><input type="number" name="sort_order" class="form-control" value="{{$v('sort_order','0')}}"/></div>
                    <div class="form-group col-md-4 mt-4"><div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$isActive?'checked':''}}/>
                        <label class="custom-control-label" for="status">Bật</label>
                    </div></div>
                </div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button> <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a></div>
        </form>
    </div>
</div></div>
