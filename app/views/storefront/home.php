<div class="wrap" style="display:block">
    <div style="background:linear-gradient(120deg,#164194,#102f6b);color:#fff;border-radius:10px;padding:34px;margin-bottom:24px">
        <h1 style="margin:0 0 8px;font-size:28px">Phụ tùng &amp; thiết bị gara ô tô chính hãng</h1>
        <p style="margin:0 0 16px;opacity:.9">Tra cứu tương thích theo hãng — model — đời xe. Báo giá nhanh cho khách hàng và garage.</p>
        <a class="btn btn-outline" style="background:#fff" href="{{_WEB_URL.'/san-pham'}}">Xem tất cả sản phẩm →</a>
    </div>
    <?php return ob_get_clean();
};

// Danh mục gốc (depth 0) cho lưới danh mục
$rootCats = [];
foreach ($cats as $c){ if ((int) ($c['depth'] ?? 0) === 0) $rootCats[] = $c; }
$rootCats = array_slice($rootCats, 0, 8);
?>
<div class="content">

    <!-- Slider -->
    <section class="slider">
        <div class="own-carousel__container">
            <div class="own-carousel__outer">
                <div class="own-carousel">
                    @if (!empty($banners))
                        @foreach ($banners as $b)
                        <?php $bAlt = !empty($b['title']) ? $b['title'] : 'Banner'; ?>
                        <div class="own-carousel__item">
                            @if (!empty($b['link']))
                            <a href="{{nav_url($b['link'])}}"><img src="{{media_url($b['image'])}}" alt="{{$bAlt}}"/></a>
                            @else
                            <img src="{{media_url($b['image'])}}" alt="{{$bAlt}}"/>
                            @endif
                        </div>
                        @endforeach
                    @else
                        <div class="own-carousel__item"><img src="<?php echo $assetImg; ?>slide02.jpg" alt="Tân Phát"/></div>
                        <div class="own-carousel__item"><img src="<?php echo $assetImg; ?>thiet_bi_garage.png" alt="Thiết bị gara"/></div>
                    @endif
                </div>
            </div>
            <div class="own-carousel__control">
                <button class="control__prev">&laquo;</button>
                <button class="control__next">&raquo;</button>
            </div>
        </div>
    </section>

    <!-- Danh mục sản phẩm -->
    @if (!empty($rootCats))
    <section class="categories pt-4">
        <div class="container">
            <h2 class="text-center section-title">Danh mục sản phẩm</h2>
            <div class="row g-3">
                @foreach ($rootCats as $c)
                <?php $catImg = !empty($c['image']) ? media_url($c['image']) : ($assetImg . 'placeholder.svg'); $catUrl = _WEB_URL . '/san-pham?category[]=' . (int) $c['id']; ?>
                <div class="col-6 col-md-3">
                    <div class="categories__item">
                        <a href="{{$catUrl}}"><img src="{{$catImg}}" alt="{{$c['name']}}"/></a>
                        <a href="{{$catUrl}}"><h2>{{$c['name']}}</h2></a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Sản phẩm khuyến mãi -->
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
    </section>
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
            <div class="products__more text-center pt-4">
                <a href="{{_WEB_URL.'/san-pham'}}" class="btn btn-outline-primary">Xem thêm sản phẩm</a>
            </div>
            @endif
        </div>
    </section>

    <!-- Tra theo hãng xe -->
    @if (!empty($brands))
    <section class="pb-4">
        <div class="container">
            <h2 class="text-center section-title">Tra theo hãng xe</h2>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                @foreach ($brands as $b)
                <a class="btn btn-outline-primary btn-sm" href="{{_WEB_URL.'/san-pham'}}">{{$b['name']}}</a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Tin tức -->
    @if (!empty($news))
    <section class="posts pb-5">
        <div class="container">
            <h2 class="text-center section-title">Tin tức</h2>
            <div class="posts__list">
                <div class="row g-3">
                    @foreach ($news as $n)
                    <?php
                    $nThumb = !empty($n['thumbnail']) ? media_url($n['thumbnail']) : ($assetImg . 'placeholder.svg');
                    $nDate  = !empty($n['published_at']) ? date('d/m/Y', strtotime($n['published_at'])) : '';
                    $nUrl   = _WEB_URL . '/tin-tuc/' . $n['slug'];
                    ?>
                    <div class="col-12 col-md-3">
                        <div class="post--item">
                            <div class="item__image">
                                <a href="{{$nUrl}}"><img src="{{$nThumb}}" alt="{{$n['title']}}" style="width:100%;height:180px;object-fit:cover"/></a>
                            </div>
                            <h3><a href="{{$nUrl}}">{{$n['title']}}</a></h3>
                            @if ($nDate !== '')
                            <time><i class="fa fa-clock-o" aria-hidden="true"></i> {{$nDate}}</time>
                            @endif
                            <p>{{!empty($n['summary']) ? mb_strimwidth($n['summary'],0,110,'...') : ''}}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="posts__more text-center pt-4">
                <a href="{{_WEB_URL.'/tin-tuc'}}" class="btn btn-outline-primary">Xem thêm tin tức</a>
            </div>
        </div>
    </section>
    @endif

</div>
