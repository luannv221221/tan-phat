<?php
$locJs = [];
foreach ($allLocations as $l){
    if ((int) $l['level'] >= $maxLevel) continue; // node đã đủ sâu -> không cho làm cha
    $locJs[] = ['id' => (int) $l['id'], 'wh' => (int) $l['warehouse_id'], 'label' => $l['full_path'] . ' (cấp ' . $l['level'] . ')'];
}
$oldWh   = !empty($old['warehouse_id']) ? (int) $old['warehouse_id'] : 0;
$oldPar  = !empty($old['parent_id']) ? (int) $old['parent_id'] : 0;
?>
@if (!empty($msg))
<div class="alert alert-warning alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>{{$msg}}</div>
@endif

<form action="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" method="post">
    <?php echo csrf_field(); ?>
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>{{$page_name}}</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Kho <span class="text-danger">*</span></label>
                    <select name="warehouse_id" id="wh-select" class="form-control">
                        <option value="">— Chọn kho —</option>
                        @foreach ($warehouses as $w)
                        <option value="{{$w['id']}}" {{$oldWh==$w['id']?'selected':''}}>{{$w['code'].' - '.$w['name']}}</option>
                        @endforeach
                    </select>
                    {!! !empty($errors['warehouse_id'])?'<small class="text-danger">'.e($errors['warehouse_id']).'</small>':false !!}
                </div>
                <div class="form-group col-md-6">
                    <label>Vị trí cha (để trống = cấp gốc)</label>
                    <select name="parent_id" id="parent-select" class="form-control" data-old="{{$oldPar}}">
                        <option value="">— Cấp gốc —</option>
                    </select>
                    {!! !empty($errors['parent_id'])?'<small class="text-danger">'.e($errors['parent_id']).'</small>':false !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Mã vị trí <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" value="{{!empty($old['code'])?$old['code']:''}}" placeholder="VD: A-T2-K3"/>
                    {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                </div>
                <div class="form-group col-md-6">
                    <label>Tên vị trí <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{!empty($old['name'])?$old['name']:''}}" placeholder="VD: Kệ 3"/>
                    {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Thứ tự</label>
                    <input type="number" name="sort_order" class="form-control" value="{{!empty($old['sort_order'])?$old['sort_order']:'0'}}"/>
                </div>
            </div>
            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="status" name="status" value="1" {{(!isset($old['status'])||!empty($old['status']))?'checked':''}}/>
                    <label class="custom-control-label" for="status">Đang hoạt động</label>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
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
