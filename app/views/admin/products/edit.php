<?php
// Nhóm đời xe + trạng thái tick sẵn
$fitGroups = [];
if (!empty($carYears)){
    foreach ($carYears as $cy){
        $g = $cy['brand_name'] . ' — ' . $cy['model_name'];
        $fitGroups[$g][] = $cy;
    }
}
$sel = !empty($selFitments) ? $selFitments : [];
if (!empty($old['fitments']) && is_array($old['fitments'])){
    $sel = array_map('intval', $old['fitments']);
}

// giá trị mặc định (ưu tiên $old khi vừa lỗi validate, ngược lại lấy $item)
$vCode    = isset($old['code'])     ? $old['code']     : $item['code'];
$vOem     = isset($old['oem_code']) ? $old['oem_code'] : $item['oem_code'];
$vName    = isset($old['name'])     ? $old['name']     : $item['name'];
$vSlug    = isset($old['slug'])     ? $old['slug']     : $item['slug'];
$vPrice   = isset($old['price'])    ? $old['price']    : (int) $item['price'];
$vSale    = isset($old['sale_price']) ? $old['sale_price'] : ($item['sale_price'] !== null ? (int) $item['sale_price'] : '');
$vWar     = isset($old['warranty_month']) ? $old['warranty_month'] : $item['warranty_month'];
$vDesc    = isset($old['description']) ? $old['description'] : $item['description'];
$selCat   = isset($old['category_id'])     ? $old['category_id']     : $item['category_id'];
$selUnit  = isset($old['unit_id'])         ? $old['unit_id']         : $item['unit_id'];
$selBrand = isset($old['brand_id'])        ? $old['brand_id']        : $item['brand_id'];
$selMnf   = isset($old['manufacturer_id']) ? $old['manufacturer_id'] : $item['manufacturer_id'];
$selOrig  = isset($old['origin_id'])       ? $old['origin_id']       : $item['origin_id'];
?>
<form action="" method="post">
    <?php echo csrf_field(); ?>

    @if (!empty($msg))
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            <div class="card card-outline card-warning">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-edit mr-2"></i>{{$page_name}}</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Mã phụ tùng <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" value="{{$vCode}}"/>
                            {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-6">
                            <label>Mã OEM</label>
                            <input type="text" class="form-control" name="oem_code" value="{{!empty($vOem)?$vOem:''}}"/>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tên phụ tùng <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{$vName}}"/>
                        {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Đường dẫn (slug)</label>
                        <input type="text" class="form-control" name="slug" value="{{$vSlug}}"/>
                        {!! !empty($errors['slug'])?'<small class="text-danger">'.e($errors['slug']).'</small>':false !!}
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Danh mục</label>
                            <select name="category_id" class="form-control">
                                <option value="">— Không phân loại —</option>
                                @if (!empty($categories))
                                    @foreach ($categories as $c)
                                    <option value="{{$c['id']}}" {{$selCat==$c['id']?'selected':''}}>{!! str_repeat('— ', (int)$c['depth']).e($c['name']) !!}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Đơn vị tính</label>
                            <select name="unit_id" class="form-control">
                                <option value="">—</option>
                                @if (!empty($units))
                                    @foreach ($units as $u)
                                    <option value="{{$u['id']}}" {{$selUnit==$u['id']?'selected':''}}>{{$u['name']}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Thương hiệu</label>
                            <select name="brand_id" class="form-control">
                                <option value="">—</option>
                                @if (!empty($brands))
                                    @foreach ($brands as $b)
                                    <option value="{{$b['id']}}" {{$selBrand==$b['id']?'selected':''}}>{{$b['name']}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Hãng sản xuất</label>
                            <select name="manufacturer_id" class="form-control">
                                <option value="">—</option>
                                @if (!empty($manufacturers))
                                    @foreach ($manufacturers as $m)
                                    <option value="{{$m['id']}}" {{$selMnf==$m['id']?'selected':''}}>{{$m['name']}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Xuất xứ</label>
                            <select name="origin_id" class="form-control">
                                <option value="">—</option>
                                @if (!empty($origins))
                                    @foreach ($origins as $o)
                                    <option value="{{$o['id']}}" {{$selOrig==$o['id']?'selected':''}}>{{$o['name']}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label>Giá bán (₫) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="price" value="{{$vPrice}}"/>
                            {!! !empty($errors['price'])?'<small class="text-danger">'.e($errors['price']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-4">
                            <label>Giá khuyến mãi (₫)</label>
                            <input type="text" class="form-control" name="sale_price" value="{{$vSale}}"/>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Bảo hành (tháng)</label>
                            <input type="number" class="form-control" name="warranty_month" value="{{!empty($vWar)?$vWar:''}}"/>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea class="form-control" name="description" rows="3">{{!empty($vDesc)?$vDesc:''}}</textarea>
                    </div>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$item['status']==1?'checked':''}}/>
                            <label class="custom-control-label" for="status">Hiển thị</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card card-outline card-info">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-link mr-2"></i>Lắp cho đời xe</h3></div>
                <div class="card-body">
                    @if (empty($fitGroups))
                    <p class="text-muted mb-0"><i class="fas fa-info-circle mr-1"></i> Chưa có đời xe nào. Hãy tạo <b>Hãng → Model → Đời xe</b> trước.</p>
                    @else
                    <input type="text" id="fit-filter" class="form-control form-control-sm mb-2" placeholder="Lọc nhanh: gõ hãng / model / năm..."/>
                    <div style="max-height:460px;overflow:auto">
                        @foreach ($fitGroups as $gname => $rows)
                        <div class="fit-group mb-2 border rounded p-2">
                            <div class="font-weight-bold text-primary small mb-1">{{$gname}}</div>
                            @foreach ($rows as $cy)
                                <?php
                                $label   = $cy['year_from'] . (!empty($cy['year_to']) ? '–' . $cy['year_to'] : '+');
                                $checked = in_array((int) $cy['id'], $sel, true) ? 'checked' : '';
                                ?>
                                <div class="custom-control custom-checkbox fit-item" data-label="{{ strtolower($gname.' '.$label) }}">
                                    <input type="checkbox" class="custom-control-input" name="fitments[]" value="{{$cy['id']}}" id="fit-{{$cy['id']}}" {!! $checked !!}/>
                                    <label class="custom-control-label" for="fit-{{$cy['id']}}">{{$label}}</label>
                                </div>
                            @endforeach
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Cập nhật</button>
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
        </div>
    </div>
</form>

<!-- Thư viện ảnh (TASK_77) — form riêng, không nằm trong form sửa phụ tùng -->
<div class="card card-outline card-success">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-images mr-2"></i>Thư viện ảnh</h3></div>
    <div class="card-body">
        @if (!empty($images))
        <div class="row">
            @foreach ($images as $img)
            <div class="col-6 col-md-3 col-lg-2 mb-3">
                <div class="border rounded p-1 text-center {{$img['is_primary']==1?'border-success':''}}">
                    <img src="{{_WEB_URL.'/public/assets/uploads/parts/'.$img['image']}}" alt="ảnh" style="width:100%;height:110px;object-fit:contain"/>
                    <div class="mt-1 small">
                        @if ($img['is_primary']==1)
                        <span class="badge badge-success">Đại diện</span>
                        @else
                        <a href="{{_WEB_URL.'/admin/products/image-primary/'.$img['id']}}" class="badge badge-light border">Đặt đại diện</a>
                        @endif
                        <a href="{{_WEB_URL.'/admin/products/image-delete/'.$img['id']}}" onclick="return confirm('Xoá ảnh này?')" class="badge badge-danger">Xoá</a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-muted">Chưa có ảnh nào.</p>
        @endif

        <form action="{{_WEB_URL.'/admin/products/images/'.$item['id']}}" method="post" enctype="multipart/form-data" class="form-inline mt-2">
            <?php echo csrf_field(); ?>
            <div class="custom-file mr-2" style="max-width:360px">
                <input type="file" name="images[]" id="imgs" class="custom-file-input" accept="image/*" multiple/>
                <label class="custom-file-label" for="imgs">Chọn ảnh (có thể nhiều)...</label>
            </div>
            <button type="submit" class="btn btn-success"><i class="fas fa-upload mr-1"></i> Tải lên</button>
        </form>
        <small class="text-muted d-block mt-1">JPG, PNG, GIF, WEBP · tối đa 3MB mỗi ảnh · chọn nhiều ảnh cùng lúc.</small>
    </div>
</div>

<script>
document.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'imgs') {
        var n = e.target.files.length;
        var lbl = e.target.parentNode.querySelector('.custom-file-label');
        if (lbl) lbl.textContent = n > 0 ? (n + ' ảnh đã chọn') : 'Chọn ảnh (có thể nhiều)...';
    }
});
</script>

<script>
(function () {
    var box = document.getElementById('fit-filter');
    if (!box) return;
    box.addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        document.querySelectorAll('.fit-item').forEach(function (el) {
            el.style.display = el.getAttribute('data-label').indexOf(q) !== -1 ? '' : 'none';
        });
        document.querySelectorAll('.fit-group').forEach(function (g) {
            var any = g.querySelector('.fit-item:not([style*="none"])');
            g.style.display = any ? '' : 'none';
        });
    });
})();
</script>
