<?php
// Nhóm đời xe theo "Hãng — Model" cho picker lắp đặt
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
?>
<form action="" method="post">
    <?php echo csrf_field(); ?>

    @if (!empty($msg))
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
    @endif

    <div class="row">
        <!-- Cột trái: thông tin phụ tùng -->
        <div class="col-lg-7">
            <div class="card card-outline card-primary">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-box mr-2"></i>{{$page_name}}</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Mã phụ tùng <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" placeholder="VD: PT-0001" value="{{!empty($old['code'])?$old['code']:''}}"/>
                            {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-6">
                            <label>Mã OEM</label>
                            <input type="text" class="form-control" name="oem_code" value="{{!empty($old['oem_code'])?$old['oem_code']:''}}"/>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tên phụ tùng <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="VD: Lọc gió động cơ" value="{{!empty($old['name'])?$old['name']:''}}"/>
                        {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Đường dẫn (slug)</label>
                        <input type="text" class="form-control" name="slug" placeholder="Bỏ trống sẽ tự sinh từ tên" value="{{!empty($old['slug'])?$old['slug']:''}}"/>
                        {!! !empty($errors['slug'])?'<small class="text-danger">'.e($errors['slug']).'</small>':false !!}
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Danh mục</label>
                            <select name="category_id" class="form-control">
                                <option value="">— Không phân loại —</option>
                                @if (!empty($categories))
                                    @foreach ($categories as $c)
                                    <option value="{{$c['id']}}" {{(!empty($old['category_id']) && $old['category_id']==$c['id'])?'selected':''}}>{!! str_repeat('— ', (int)$c['depth']).e($c['name']) !!}</option>
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
                                    <option value="{{$u['id']}}" {{(!empty($old['unit_id']) && $old['unit_id']==$u['id'])?'selected':''}}>{{$u['name']}}</option>
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
                                    <option value="{{$b['id']}}" {{(!empty($old['brand_id']) && $old['brand_id']==$b['id'])?'selected':''}}>{{$b['name']}}</option>
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
                                    <option value="{{$m['id']}}" {{(!empty($old['manufacturer_id']) && $old['manufacturer_id']==$m['id'])?'selected':''}}>{{$m['name']}}</option>
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
                                    <option value="{{$o['id']}}" {{(!empty($old['origin_id']) && $old['origin_id']==$o['id'])?'selected':''}}>{{$o['name']}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label>Giá bán (₫) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="price" placeholder="VD: 350000" value="{{!empty($old['price'])?$old['price']:''}}"/>
                            {!! !empty($errors['price'])?'<small class="text-danger">'.e($errors['price']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-4">
                            <label>Giá khuyến mãi (₫)</label>
                            <input type="text" class="form-control" name="sale_price" value="{{!empty($old['sale_price'])?$old['sale_price']:''}}"/>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Bảo hành (tháng)</label>
                            <input type="number" class="form-control" name="warranty_month" value="{{!empty($old['warranty_month'])?$old['warranty_month']:''}}"/>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea class="form-control" name="description" rows="3">{{!empty($old['description'])?$old['description']:''}}</textarea>
                    </div>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" checked/>
                            <label class="custom-control-label" for="status">Hiển thị</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cột phải: lắp cho đời xe -->
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

    <!-- Phụ kiện đi kèm (TASK_81) — nằm trong form chính, gửi qua related[] -->
    <div class="card card-outline card-secondary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-square mr-2"></i>Phụ kiện đi kèm</h3></div>
        <div class="card-body">
            <div class="position-relative" style="max-width:520px">
                <input type="text" id="rel-search" class="form-control" autocomplete="off"
                       placeholder="Gõ tên hoặc mã phụ tùng để thêm..."
                       data-url="{{_WEB_URL.'/admin/products/search-json'}}" data-exclude="0"/>
                <div id="rel-results" class="list-group position-absolute w-100 shadow-sm" style="z-index:30;max-height:240px;overflow:auto;display:none"></div>
            </div>
            <div id="rel-selected" class="mt-2 d-flex flex-wrap">
                @if (!empty($relatedParts))
                    @foreach ($relatedParts as $rp)
                    <span class="badge badge-light border p-2 mr-1 mb-1 rel-chip" data-id="{{$rp['id']}}">
                        {{$rp['code'].' — '.$rp['name']}}
                        <input type="hidden" name="related[]" value="{{$rp['id']}}"/>
                        <a href="#" class="text-danger ml-1 rel-remove">&times;</a>
                    </span>
                    @endforeach
                @endif
            </div>
            <small class="text-muted">Chọn các phụ tùng gợi ý bán kèm sản phẩm này.</small>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Thêm phụ tùng</button>
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
        </div>
    </div>
</form>

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

<script>
(function () {
    var input = document.getElementById('rel-search');
    if (!input) return;
    var results  = document.getElementById('rel-results');
    var selected = document.getElementById('rel-selected');
    var url      = input.getAttribute('data-url');
    var exclude  = input.getAttribute('data-exclude') || '0';
    var timer    = null;

    function selectedIds() {
        return Array.prototype.map.call(
            selected.querySelectorAll('input[name="related[]"]'),
            function (i) { return i.value; }
        );
    }
    function hideResults() { results.style.display = 'none'; results.innerHTML = ''; }
    function addChip(id, label) {
        if (selectedIds().indexOf(String(id)) !== -1) return;
        var span = document.createElement('span');
        span.className = 'badge badge-light border p-2 mr-1 mb-1 rel-chip';
        span.setAttribute('data-id', id);
        span.appendChild(document.createTextNode(label + ' '));
        var inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'related[]'; inp.value = id;
        span.appendChild(inp);
        var rm = document.createElement('a');
        rm.href = '#'; rm.className = 'text-danger ml-1 rel-remove'; rm.textContent = '×';
        span.appendChild(rm);
        selected.appendChild(span);
    }

    input.addEventListener('input', function () {
        var q = this.value.trim();
        clearTimeout(timer);
        if (q === '') { hideResults(); return; }
        timer = setTimeout(function () {
            fetch(url + '?q=' + encodeURIComponent(q) + '&exclude=' + encodeURIComponent(exclude))
                .then(function (r) { return r.json(); })
                .then(function (list) {
                    results.innerHTML = '';
                    if (!list || !list.length) { hideResults(); return; }
                    list.forEach(function (p) {
                        var a = document.createElement('a');
                        a.href = '#';
                        a.className = 'list-group-item list-group-item-action py-1 px-2';
                        a.textContent = p.code + ' — ' + p.name;
                        a.addEventListener('click', function (e) {
                            e.preventDefault();
                            addChip(p.id, p.code + ' — ' + p.name);
                            input.value = ''; hideResults();
                        });
                        results.appendChild(a);
                    });
                    results.style.display = 'block';
                })
                .catch(function () { hideResults(); });
        }, 250);
    });

    selected.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('rel-remove')) {
            e.preventDefault();
            var chip = e.target.closest('.rel-chip');
            if (chip) chip.remove();
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target !== input && !results.contains(e.target)) hideResults();
    });
})();
</script>
