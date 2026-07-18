<?php
$modelsJs = [];
if (!empty($models)){
    foreach ($models as $m){
        $modelsJs[] = ['id' => (int) $m['id'], 'brand_id' => (int) $m['brand_id'], 'name' => $m['name']];
    }
}
$selectedBrand = !empty($old['brand_id']) ? (int) $old['brand_id'] : (int) $selBrand;
$selectedModel = !empty($old['model_id']) ? (int) $old['model_id'] : (int) $item['model_id'];
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>{{$page_name}}</h3>
            </div>

            <form action="" method="post">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    @if (!empty($msg))
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                    @endif

                    <div class="form-group">
                        <label>Hãng xe <span class="text-danger">*</span></label>
                        <select id="cy-brand" name="brand_id" class="form-control">
                            <option value="">— Chọn hãng —</option>
                            @if (!empty($brands))
                                @foreach ($brands as $b)
                                <option value="{{$b['id']}}" {{$selectedBrand==$b['id']?'selected':''}}>{{$b['name']}}</option>
                                @endforeach
                            @endif
                        </select>
                        <small class="form-text text-muted">Chọn hãng để lọc model bên dưới.</small>
                    </div>

                    <div class="form-group">
                        <label>Model xe <span class="text-danger">*</span></label>
                        <select id="cy-model" name="model_id" class="form-control" data-selected="{{ (int)$selectedModel }}">
                            <option value="">— Chọn model —</option>
                        </select>
                        {!! !empty($errors['model_id'])?'<small class="text-danger">'.e($errors['model_id']).'</small>':false !!}
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label>Năm bắt đầu <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="year_from" value="{{isset($old['year_from'])?$old['year_from']:$item['year_from']}}"/>
                            {!! !empty($errors['year_from'])?'<small class="text-danger">'.e($errors['year_from']).'</small>':false !!}
                        </div>
                        <div class="form-group col-6">
                            <label>Năm kết thúc</label>
                            <input type="number" class="form-control" name="year_to" placeholder="Bỏ trống = đến nay" value="{{isset($old['year_to'])?$old['year_to']:(!empty($item['year_to'])?$item['year_to']:'')}}"/>
                            {!! !empty($errors['year_to'])?'<small class="text-danger">'.e($errors['year_to']).'</small>':false !!}
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tên hiển thị</label>
                        <input type="text" class="form-control" name="name" placeholder="Bỏ trống sẽ tự sinh" value="{{isset($old['name'])?$old['name']:(!empty($item['name'])?$item['name']:'')}}"/>
                    </div>

                    <div class="form-group mb-0">
                        <label class="d-block">Trạng thái</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$item['status']==1?'checked':''}}/>
                            <label class="custom-control-label" for="status">Hiển thị</label>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Cập nhật</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var MODELS = {!! json_encode($modelsJs, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
    var brandSel = document.getElementById('cy-brand');
    var modelSel = document.getElementById('cy-model');

    function fill(brandId, keep) {
        var cur = (keep !== null && keep !== undefined) ? keep : modelSel.value;
        modelSel.innerHTML = '<option value="">— Chọn model —</option>';
        MODELS.filter(function (m) { return String(m.brand_id) === String(brandId); })
              .forEach(function (m) {
                  var o = document.createElement('option');
                  o.value = m.id;
                  o.textContent = m.name;
                  if (String(m.id) === String(cur)) o.selected = true;
                  modelSel.appendChild(o);
              });
    }

    brandSel.addEventListener('change', function () { fill(this.value, null); });

    var pre = modelSel.getAttribute('data-selected');
    if (brandSel.value) fill(brandSel.value, pre);
})();
</script>
