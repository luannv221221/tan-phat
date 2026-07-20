<?php
$v = function($field, $default = '') use ($old, $item){
    if (isset($old[$field])) return $old[$field];
    return isset($item[$field]) && $item[$field] !== null ? $item[$field] : $default;
};
$selfPath = isset($item['full_path']) ? $item['full_path'] : '';
$locJs = [];
foreach ($allLocations as $l){
    if ((int) $l['id'] === (int) $item['id']) continue;                       // không tự làm cha
    if ($selfPath !== '' && strpos((string)$l['full_path'], $selfPath . ' / ') === 0) continue; // loại nhánh con
    if ((int) $l['level'] >= $maxLevel) continue;
    $locJs[] = ['id' => (int) $l['id'], 'wh' => (int) $l['warehouse_id'], 'label' => $l['full_path'] . ' (cấp ' . $l['level'] . ')'];
}
$curWh  = $v('warehouse_id');
$curPar = $v('parent_id');
?>
@if (!empty($msg))
<div class="alert alert-warning alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>{{$msg}}</div>
@endif

<form action="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" method="post">
    <?php echo csrf_field(); ?>
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>{{$page_name}}: <code>{{$item['full_path']}}</code></h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Kho <span class="text-danger">*</span></label>
                    <select name="warehouse_id" id="wh-select" class="form-control">
                        <option value="">— Chọn kho —</option>
                        @foreach ($warehouses as $w)
                        <option value="{{$w['id']}}" {{$curWh==$w['id']?'selected':''}}>{{$w['code'].' - '.$w['name']}}</option>
                        @endforeach
                    </select>
                    {!! !empty($errors['warehouse_id'])?'<small class="text-danger">'.e($errors['warehouse_id']).'</small>':false !!}
                </div>
                <div class="form-group col-md-6">
                    <label>Vị trí cha (để trống = cấp gốc)</label>
                    <select name="parent_id" id="parent-select" class="form-control" data-old="{{$curPar}}">
                        <option value="">— Cấp gốc —</option>
                    </select>
                    {!! !empty($errors['parent_id'])?'<small class="text-danger">'.e($errors['parent_id']).'</small>':false !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Mã vị trí <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" value="{{$v('code')}}"/>
                    {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                </div>
                <div class="form-group col-md-6">
                    <label>Tên vị trí <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{$v('name')}}"/>
                    {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Thứ tự</label>
                    <input type="number" name="sort_order" class="form-control" value="{{$v('sort_order','0')}}"/>
                </div>
            </div>
            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="status" name="status" value="1" {{$v('status','1')?'checked':''}}/>
                    <label class="custom-control-label" for="status">Đang hoạt động</label>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
            @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá vị trí này? Mọi nhánh con cũng bị xoá.')" class="btn btn-outline-danger"><i class="fas fa-trash mr-1"></i> Xoá</a>
            @endif
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
        </div>
    </div>
</form>

<script>
(function(){
    var LOCS = {!! json_encode($locJs, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
    var whSel = document.getElementById('wh-select');
    var pSel  = document.getElementById('parent-select');
    var oldParent = pSel.getAttribute('data-old');
    function fill(){
        var wh = whSel.value;
        pSel.innerHTML = '<option value="">— Cấp gốc —</option>';
        LOCS.forEach(function(l){
            if (String(l.wh) !== String(wh)) return;
            var o = document.createElement('option');
            o.value = l.id; o.textContent = l.label;
            if (String(l.id) === String(oldParent)) o.selected = true;
            pSel.appendChild(o);
        });
    }
    whSel.addEventListener('change', fill);
    fill();
})();
</script>
