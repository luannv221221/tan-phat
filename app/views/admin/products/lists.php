<?php
// Chuỗi query giữ bộ lọc khi chuyển trang (không có echo -> SecurityTest bỏ qua)
$qs = '';
if ($keyword !== '')      $qs .= '&keyword=' . urlencode($keyword);
if (!empty($filterCat))   $qs .= '&category_id=' . (int) $filterCat;
if (!empty($filterLoai))  $qs .= '&tab=' . urlencode($filterLoai);
if (!empty($filterPromo)) $qs .= '&promo=1';
if (!empty($filterAttrId)) $qs .= '&attr_id=' . (int) $filterAttrId;
if (isset($filterAttrVal) && $filterAttrVal !== '') $qs .= '&attr_val=' . urlencode($filterAttrVal);

$exportQs = $qs !== '' ? '?' . ltrim($qs, '&') : '';

/* Link cho từng tab nhóm hàng: giữ nguyên MỌI bộ lọc khác, chỉ đổi item_type.
   Dựng lại từ $qs chứ không ghép tay từng tham số — thêm bộ lọc mới sau này
   là tab tự giữ theo, khỏi phải nhớ sửa ở đây. */
$tabUrl = function ($tab) use ($qs){
    $p = [];
    parse_str(ltrim($qs, '&'), $p);
    unset($p['page']);
    if ($tab === '') unset($p['tab']); else $p['tab'] = $tab;
    $s = http_build_query($p);
    return _WEB_URL . '/admin/products' . ($s !== '' ? '?' . $s : '');
};

/* perPage = 0 nghĩa là "Tất cả": $page*$perPage ra 0 nên min() cho $to = 0
   và dòng "Hiển thị 1-0" là sai. Tách nhánh riêng. */
$from = $total > 0 ? ($perPage > 0 ? ($page - 1) * $perPage + 1 : 1) : 0;
$to   = $perPage > 0 ? min($page * $perPage, $total) : $total;
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-check-circle mr-1"></i> {{$msg}}
</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}
</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/export'.$exportQs}}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-download mr-1"></i> Xuất catalogue
            </a>
            @if (route('admin/'.$routeBase.'/import'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/import'}}" class="btn btn-success btn-sm">
                <i class="fas fa-file-import mr-1"></i> Import Excel/CSV
            </a>
            @endif
            @if (route('admin/'.$routeBase.'/add'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Thêm {{$labelOne}}
            </a>
            @endif
        </div>
    </div>

    <!-- Ba nhóm hàng: khách muốn nhìn thấy đúng cây Phụ tùng / Thiết bị / Dịch vụ -->
    <ul class="nav nav-tabs px-3 pt-2" style="border-bottom:1px solid #dee2e6">
        <li class="nav-item">
            <a class="nav-link {{empty($filterLoai)?'active':''}}" href="{{$tabUrl('')}}">
                Tất cả <span class="badge badge-light ml-1">{{(int)($demTheoLoai[''] ?? 0)}}</span>
            </a>
        </li>
        @foreach ($loaiHang as $ma => $ten)
        <li class="nav-item">
            <a class="nav-link {{$filterLoai===$ma?'active':''}}" href="{{$tabUrl($ma)}}">
                {{$ten}} <span class="badge badge-light ml-1">{{(int)($demTheoLoai[$ma] ?? 0)}}</span>
            </a>
        </li>
        @endforeach

        <?php /* Tách khỏi 3 tab trên bằng vạch dọc: đây là THUỘC TÍNH (được
                 đăng web) chứ không phải loại hàng thứ tư, nên nó chồng lấn
                 với cả ba — một cái ắc quy vừa là Phụ tùng vừa có thể nằm ở
                 tab này. Để liền mạch là người dùng tưởng 4 tab cộng lại
                 bằng "Tất cả". */ ?>
        <li class="nav-item ml-2 pl-2" style="border-left:1px solid #dee2e6">
            <a class="nav-link {{$filterLoai===$tabWeb?'active':''}}" href="{{$tabUrl($tabWeb)}}"
               title="Hàng hoá được tích Hiển thị website">
                <i class="fas fa-globe mr-1"></i>Sản phẩm website
                <span class="badge badge-light ml-1">{{(int)($demTheoLoai[$tabWeb] ?? 0)}}</span>
            </a>
        </li>
    </ul>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            {!! !empty($filterLoai) ? '<input type="hidden" name="tab" value="'.e($filterLoai).'"/>' : '' !!}
            <div class="form-group col-md-4 mb-2">
                <label class="mb-1 small">Tìm kiếm</label>
                <div class="position-relative">
                    <input type="text" name="keyword" id="parts-search" autocomplete="off"
                           class="form-control form-control-sm" placeholder="Tên, mã, mã OEM..."
                           value="{{$keyword}}"
                           data-url="{{_WEB_URL.'/admin/products/search-json'}}"
                           data-edit="{{_WEB_URL.'/admin/products/edit/'}}"/>
                    <div id="parts-suggest" class="list-group position-absolute w-100 shadow-sm" style="z-index:30;max-height:240px;overflow:auto;display:none"></div>
                </div>
            </div>
            <div class="form-group col-md-4 mb-2">
                <label class="mb-1 small">Danh mục</label>
                <select name="category_id" class="form-control form-control-sm">
                    <option value="">— Tất cả danh mục —</option>
                    @if (!empty($categories))
                        @foreach ($categories as $c)
                        <option value="{{$c['id']}}" {{$filterCat==$c['id']?'selected':''}}>{!! str_repeat('— ', (int)$c['depth']).e($c['name']) !!}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="mb-1 small">Thông số</label>
                <select name="attr_id" class="form-control form-control-sm">
                    <option value="">— Không lọc —</option>
                    @if (!empty($attributes))
                        @foreach ($attributes as $at)
                        <option value="{{$at['id']}}" {{$filterAttrId==$at['id']?'selected':''}}>{{$at['name']}}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="mb-1 small">Giá trị thông số</label>
                <input type="text" name="attr_val" class="form-control form-control-sm" placeholder="chứa..." value="{{$filterAttrVal}}"/>
            </div>
            <div class="form-group col-md-2 mb-2">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="promo" value="1" id="promo" {{!empty($filterPromo)?'checked':''}}/>
                    <label class="custom-control-label small" for="promo">Chỉ khuyến mãi</label>
                </div>
            </div>
            <div class="form-group col-md-2 mb-2">
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button>
                @if ($keyword !== '' || !empty($filterCat) || !empty($filterPromo) || !empty($filterAttrId) || !empty($filterLoai))
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-sm btn-default">Xoá</a>
                @endif
            </div>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:60px" class="text-center">STT</th>
                    <th style="width:80px" class="text-center">Ảnh</th>
                    <th style="width:12%">Mã</th>
                    <th>Tên hàng hoá</th>
                    <th style="width:90px" class="text-center">Loại</th>
                    <th style="width:15%">Danh mục</th>
                    <th style="width:12%">Thương hiệu</th>
                    <th style="width:12%" class="text-right">Giá</th>
                    <th style="width:100px" class="text-center">Trạng thái</th>
                    <th style="width:110px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $key => $item)
                <tr>
                    <td class="text-center text-muted">{{($page-1)*$perPage + $key + 1}}</td>
                    <td class="text-center">
                        {!! !empty($imgMap[$item['id']])
                            ? '<img src="'.e(_WEB_URL.'/public/assets/uploads/parts/'.$imgMap[$item['id']]).'" alt="" style="height:34px;max-width:70px;object-fit:contain">'
                            : '<span class="text-muted">—</span>' !!}
                    </td>
                    <td><code>{{$item['code']}}</code></td>
                    <td class="font-weight-bold">{{$item['name']}}</td>
                    <td class="text-center">
                        {!! $item['item_type'] === 'service'
                            ? '<span class="badge badge-info">Dịch vụ</span>'
                            : ($item['item_type'] === 'equipment'
                                ? '<span class="badge badge-warning">Thiết bị</span>'
                                : '<span class="badge badge-light border">Phụ tùng</span>') !!}
                    </td>
                    <td>{!! !empty($item['category_name']) ? e($item['category_name']) : '<span class="text-muted">—</span>' !!}</td>
                    <td>{!! !empty($item['brand_name']) ? e($item['brand_name']) : '<span class="text-muted">—</span>' !!}</td>
                    <td class="text-right">{{number_format((float)$item['price'], 0, ',', '.')}} ₫</td>
                    <td class="text-center">
                        {!! $item['status']==1 ? '<span class="badge badge-success">Kinh doanh</span>' : '<span class="badge badge-secondary">Ngừng</span>' !!}
                        {!! ($item['status']==1 && $item['show_on_web']!=1)
                            ? '<span class="badge badge-light border text-muted d-block mt-1" title="Vẫn xuất hoá đơn / nhập xuất kho được">Không lên web</span>'
                            : '' !!}
                    </td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a onclick="return confirm('Bạn có chắc chắn muốn xoá hàng hoá này?')" href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" class="btn btn-danger btn-sm" title="Xoá"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x d-block mb-2"></i> Không có hàng hoá nào khớp
                    </td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>

<?php if ($total > 0): ?>
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem">
        {!! phan_trang_html(['total'=>$total,'perPage'=>$perPage,'page'=>$page,'totalPages'=>$totalPages,'from'=>$from,'to'=>$to], _WEB_URL.'/admin/'.$routeBase, 'hàng hoá') !!}
    </div>
<?php endif; ?>
</div>

<script>
/* TASK_91 — gợi ý khi gõ ở ô tìm kiếm hàng hoá */
(function () {
    var input = document.getElementById('parts-search');
    if (!input) return;
    var box     = document.getElementById('parts-suggest');
    var url     = input.getAttribute('data-url');
    var editUrl = input.getAttribute('data-edit');
    var timer   = null;

    function hide() { box.style.display = 'none'; box.innerHTML = ''; }

    input.addEventListener('input', function () {
        var q = this.value.trim();
        clearTimeout(timer);
        if (q.length < 2) { hide(); return; }
        timer = setTimeout(function () {
            fetch(url + '?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (list) {
                    box.innerHTML = '';
                    if (!list || !list.length) { hide(); return; }
                    list.forEach(function (p) {
                        var a = document.createElement('a');
                        a.href = editUrl + p.id;
                        a.className = 'list-group-item list-group-item-action py-1 px-2';
                        var code = document.createElement('code');
                        code.textContent = p.code;
                        a.appendChild(code);
                        a.appendChild(document.createTextNode(' — ' + p.name));
                        box.appendChild(a);
                    });
                    box.style.display = 'block';
                })
                .catch(function () { hide(); });
        }, 250);
    });

    document.addEventListener('click', function (e) {
        if (e.target !== input && !box.contains(e.target)) hide();
    });
})();
</script>
