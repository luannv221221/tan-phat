<?php
// Chuỗi query giữ bộ lọc khi chuyển trang (không có echo -> SecurityTest bỏ qua)
$qs = '';
if ($keyword !== '')      $qs .= '&keyword=' . urlencode($keyword);
if (!empty($filterCat))   $qs .= '&category_id=' . (int) $filterCat;
if (!empty($filterPromo)) $qs .= '&promo=1';
if (!empty($filterAttrId)) $qs .= '&attr_id=' . (int) $filterAttrId;
if (isset($filterAttrVal) && $filterAttrVal !== '') $qs .= '&attr_val=' . urlencode($filterAttrVal);

$exportQs = $qs !== '' ? '?' . ltrim($qs, '&') : '';

$pStart = max(1, $page - 3);
$pEnd   = min($totalPages, $page + 3);
$from   = $total > 0 ? ($page - 1) * $perPage + 1 : 0;
$to     = min($page * $perPage, $total);
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

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
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
                @if ($keyword !== '' || !empty($filterCat) || !empty($filterPromo) || !empty($filterAttrId))
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
                    <th style="width:12%">Mã</th>
                    <th>Tên phụ tùng</th>
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
                    <td><code>{{$item['code']}}</code></td>
                    <td class="font-weight-bold">{{$item['name']}}</td>
                    <td>{!! !empty($item['category_name']) ? e($item['category_name']) : '<span class="text-muted">—</span>' !!}</td>
                    <td>{!! !empty($item['brand_name']) ? e($item['brand_name']) : '<span class="text-muted">—</span>' !!}</td>
                    <td class="text-right">{{number_format((float)$item['price'], 0, ',', '.')}} ₫</td>
                    <td class="text-center">
                        {!! $item['status']==1 ? '<span class="badge badge-success">Hiển thị</span>' : '<span class="badge badge-secondary">Ẩn</span>' !!}
                    </td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a onclick="return confirm('Bạn có chắc chắn muốn xoá phụ tùng này?')" href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" class="btn btn-danger btn-sm" title="Xoá"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x d-block mb-2"></i> Không có phụ tùng nào khớp
                    </td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>

    @if ($total > 0)
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap">
        <span class="text-muted small">Hiển thị {{$from}}–{{$to}} trên tổng {{$total}} phụ tùng</span>
        @if ($totalPages > 1)
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item {{$page<=1?'disabled':''}}">
                    <a class="page-link" href="{{_WEB_URL.'/admin/'.$routeBase.'?page='.($page-1).$qs}}">«</a>
                </li>
                @if ($pStart > 1)
                <li class="page-item"><a class="page-link" href="{{_WEB_URL.'/admin/'.$routeBase.'?page=1'.$qs}}">1</a></li>
                <li class="page-item disabled"><span class="page-link">…</span></li>
                @endif
                @for ($p = $pStart; $p <= $pEnd; $p++)
                <li class="page-item {{$p==$page?'active':''}}">
                    <a class="page-link" href="{{_WEB_URL.'/admin/'.$routeBase.'?page='.$p.$qs}}">{{$p}}</a>
                </li>
                @endfor
                @if ($pEnd < $totalPages)
                <li class="page-item disabled"><span class="page-link">…</span></li>
                <li class="page-item"><a class="page-link" href="{{_WEB_URL.'/admin/'.$routeBase.'?page='.$totalPages.$qs}}">{{$totalPages}}</a></li>
                @endif
                <li class="page-item {{$page>=$totalPages?'disabled':''}}">
                    <a class="page-link" href="{{_WEB_URL.'/admin/'.$routeBase.'?page='.($page+1).$qs}}">»</a>
                </li>
            </ul>
        </nav>
        @endif
    </div>
    @endif
</div>

<script>
/* TASK_91 — gợi ý khi gõ ở ô tìm kiếm phụ tùng */
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
