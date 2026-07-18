<?php
$hasSale = !empty($part['sale_price']);
$price = $hasSale ? (float) $part['sale_price'] : (float) $part['price'];
$oemSuffix   = !empty($part['oem_code']) ? ' · OEM: ' . e($part['oem_code']) : '';
$brandBadge  = !empty($part['brand_name']) ? '<span class="badge">' . e($part['brand_name']) . '</span> ' : '';
$originBadge = !empty($part['origin_name']) ? '<span class="badge">Xuất xứ: ' . e($part['origin_name']) . '</span> ' : '';
$catBadge    = !empty($part['category_name']) ? '<span class="badge">' . e($part['category_name']) . '</span>' : '';
$promoBadge  = $hasSale ? '<span class="old" style="font-size:18px;margin-left:8px">' . number_format((float) $part['price'], 0, ',', '.') . ' ₫</span> <span class="badge badge-promo">Khuyến mãi</span>' : '';
$unitName    = !empty($part['unit_name']) ? e($part['unit_name']) : '';
$unitSuffix  = !empty($part['unit_name']) ? '<span class="muted"> / ' . e($part['unit_name']) . '</span>' : '';
$stockNum    = rtrim(rtrim(number_format((float) $stock, 3, ',', '.'), '0'), ',');
?>
<div class="crumb"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / <a href="{{_WEB_URL.'/san-pham'}}">Sản phẩm</a> / {{$part['name']}}</div>

<div class="card"><div class="bd">
<div class="detail">
    <div class="gallery">
        <div class="main-img">🔧</div>
        @if (!empty($images))
        <div class="muted mt" style="font-size:13px">{{count($images)}} hình ảnh sản phẩm</div>
        @endif
    </div>

    <div class="meta">
        <h1>{{$part['name']}}</h1>
        <div class="muted">Mã: <b>{{$part['code']}}</b>{!! $oemSuffix !!}</div>
        <div class="mt">{!! $brandBadge !!}{!! $originBadge !!}{!! $catBadge !!}</div>

        <div class="mt" style="margin-top:18px">
            <span class="big-price">{{number_format($price,0,',','.')}} ₫</span>
            {!! $promoBadge !!}
            {!! $unitSuffix !!}
        </div>

        @if ($isMember)
            <div class="mt"><span class="badge {{$stock>0?'badge-ok':'badge-promo'}}">Tồn kho: {{$stockNum}} {!! $unitName !!}</span></div>
        @else
            <div class="alert alert-info mt" style="margin-top:14px;font-size:14px">🔒 <a href="{{_WEB_URL.'/thanh-vien/dang-nhap'}}"><b>Đăng nhập thành viên</b></a> để xem tồn kho sản phẩm.</div>
        @endif

        <form method="post" action="{{_WEB_URL.'/gio-hang/them'}}" class="mt" style="display:flex;gap:10px;align-items:center;margin-top:18px">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="part_id" value="{{(int)$part['id']}}"/>
            <input type="number" name="qty" value="1" min="1" style="width:80px;padding:9px;border:1px solid #e6e6e6;border-radius:6px"/>
            <button class="btn btn-brand" type="submit">🛒 Thêm vào giỏ / báo giá</button>
        </form>

        @if (!empty($part['warranty_month']))
        <div class="muted mt" style="margin-top:14px">🛡 Bảo hành: {{(int)$part['warranty_month']}} tháng</div>
        @endif
    </div>
</div>
</div></div>

@if (!empty($attrs))
<div class="card mt"><div class="hd">Thông số kỹ thuật</div><div class="bd">
    <table class="spec">
        @foreach ($attrs as $a)
        <?php $unitCell = !empty($a['unit']) ? ' ' . e($a['unit']) : ''; ?>
        <tr><td>{{$a['name']}}</td><td>{{$a['value']}}{!! $unitCell !!}</td></tr>
        @endforeach
    </table>
</div></div>
@endif

@if (!empty($part['description']))
<div class="card mt"><div class="hd">Mô tả</div><div class="bd">{!! nl2br(e($part['description'])) !!}</div></div>
@endif

@if (!empty($fitments))
<div class="card mt"><div class="hd">Xe tương thích</div><div class="bd">
    <div style="display:flex;flex-wrap:wrap;gap:8px">
    @foreach ($fitments as $ft)
        <span class="badge">{{$ft['brand_name'].' '.$ft['model_name'].(!empty($ft['year_name']) ? ' — '.$ft['year_name'] : '')}}</span>
    @endforeach
    </div>
</div></div>
@endif

@if (!empty($related))
<div class="card mt"><div class="hd">Phụ kiện / sản phẩm đi kèm</div><div class="bd">
    <div style="display:flex;flex-wrap:wrap;gap:10px">
    @foreach ($related as $r)
        <a class="btn btn-outline btn-sm" href="{{_WEB_URL.'/san-pham/'.$r['slug']}}">{{$r['name']}}</a>
    @endforeach
    </div>
</div></div>
@endif
