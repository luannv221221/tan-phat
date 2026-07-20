<div class="wrap" style="display:block">
    <div style="background:linear-gradient(120deg,#c0392b,#96271b);color:#fff;border-radius:10px;padding:34px;margin-bottom:24px">
        <h1 style="margin:0 0 8px;font-size:28px">Phụ tùng &amp; thiết bị gara ô tô chính hãng</h1>
        <p style="margin:0 0 16px;opacity:.9">Tra cứu tương thích theo hãng — model — đời xe. Báo giá nhanh cho khách hàng và garage.</p>
        <a class="btn btn-outline" style="background:#fff" href="{{_WEB_URL.'/san-pham'}}">Xem tất cả sản phẩm →</a>
    </div>

    @if (!empty($promo))
    <div style="display:flex;justify-content:space-between;align-items:center;margin:6px 0 12px">
        <h2 style="margin:0;font-size:20px">🔥 Đang khuyến mãi</h2>
        <a class="muted" href="{{_WEB_URL.'/san-pham?promo=1'}}">Xem tất cả</a>
    </div>
    <div class="grid" style="margin-bottom:28px">
        @foreach ($promo as $p)
        <?php
        $price = !empty($p['sale_price']) ? (float) $p['sale_price'] : (float) $p['price'];
        $pid = (int) $p['id'];
        $imgFile = isset($imgMap[$pid]) ? $imgMap[$pid] : '';
        $thumbInner = $imgFile !== ''
            ? '<img src="'.e(_WEB_URL.'/public/assets/uploads/parts/'.$imgFile).'" alt="'.e($p['name']).'" loading="lazy"/>'
            : '<svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';
        ?>
        <div class="pcard">
            <a class="thumb" href="{{_WEB_URL.'/san-pham/'.$p['slug']}}"><span class="badge badge-promo" style="position:absolute;margin:8px">KM</span>{!! $thumbInner !!}</a>
            <div class="info">
                <a class="pname" href="{{_WEB_URL.'/san-pham/'.$p['slug']}}">{{$p['name']}}</a>
                <div class="code">Mã: {{$p['code']}}</div>
                <div class="price">{{number_format($price,0,',','.')}} ₫ <span class="old">{{number_format((float)$p['price'],0,',','.')}}</span></div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div style="display:flex;justify-content:space-between;align-items:center;margin:6px 0 12px">
        <h2 style="margin:0;font-size:20px">Sản phẩm mới</h2>
        <a class="muted" href="{{_WEB_URL.'/san-pham?sort=new'}}">Xem tất cả</a>
    </div>
    <div class="grid">
        @if (!empty($newest))
            @foreach ($newest as $p)
            <?php
            $hasSale = !empty($p['sale_price']);
            $price = $hasSale ? (float) $p['sale_price'] : (float) $p['price'];
            $km = $hasSale ? '<span class="badge badge-promo" style="position:absolute;margin:8px">KM</span>' : '';
            $old = $hasSale ? '<span class="old">'.number_format((float) $p['price'], 0, ',', '.').'</span>' : '';
            $pid = (int) $p['id'];
            $imgFile = isset($imgMap[$pid]) ? $imgMap[$pid] : '';
            $thumbInner = $imgFile !== ''
                ? '<img src="'.e(_WEB_URL.'/public/assets/uploads/parts/'.$imgFile).'" alt="'.e($p['name']).'" loading="lazy"/>'
                : '<svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';
            ?>
            <div class="pcard">
                <a class="thumb" href="{{_WEB_URL.'/san-pham/'.$p['slug']}}">{!! $km !!}{!! $thumbInner !!}</a>
                <div class="info">
                    <a class="pname" href="{{_WEB_URL.'/san-pham/'.$p['slug']}}">{{$p['name']}}</a>
                    <div class="code">Mã: {{$p['code']}}</div>
                    <div class="price">{{number_format($price,0,',','.')}} ₫ {!! $old !!}</div>
                </div>
            </div>
            @endforeach
        @else
            <p class="muted">Chưa có sản phẩm.</p>
        @endif
    </div>

    @if (!empty($brands))
    <h2 style="font-size:20px;margin:30px 0 12px">Tra theo hãng xe</h2>
    <div style="display:flex;flex-wrap:wrap;gap:10px">
        @foreach ($brands as $b)
        <a class="btn btn-outline" href="{{_WEB_URL.'/san-pham'}}">{{$b['name']}}</a>
        @endforeach
    </div>
    @endif
</div>
