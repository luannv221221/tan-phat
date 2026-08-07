<?php
$partsBase = _WEB_URL . '/public/assets/uploads/parts/';
$assetImg  = _WEB_URL . '/public/assets/storefront/images/';

// Thẻ sản phẩm dùng chung (markup theme .products--item)
$pcard = function ($p) use ($partsBase, $assetImg, $imgMap, $isMember, $stockMap){
    $pid = (int) $p['id'];
    $hasSale = !empty($p['sale_price']);
    $price = $hasSale ? (float) $p['sale_price'] : (float) $p['price'];
    $img = !empty($imgMap[$pid]) ? ($partsBase . $imgMap[$pid]) : ($assetImg . 'placeholder.svg');
    $url = _WEB_URL . '/san-pham/' . $p['slug'];

    // % giảm để nhãn KM nói được điều gì đó thay vì chỉ 2 chữ "KM"
    $off = ($hasSale && (float) $p['price'] > 0)
         ? (int) round((1 - $price / (float) $p['price']) * 100) : 0;

    $st = ($isMember && isset($stockMap[$pid])) ? (float) $stockMap[$pid] : 0;
    ob_start(); ?>
    <div class="col-6 col-md-3">
        <div class="products--item">
            <div class="item__image">
                <a href="<?php echo e($url); ?>"><img src="<?php echo e($img); ?>" alt="<?php echo e($p['name']); ?>" loading="lazy"/></a>
                <?php if ($hasSale): ?><span class="item--sales"><?php echo $off > 0 ? '-' . (int) $off . '%' : 'KM'; ?></span><?php endif; ?>
            </div>
            <div class="item__info">
                <h3 class="item--name"><a href="<?php echo e($url); ?>"><?php echo e($p['name']); ?></a></h3>
                <div class="item--price">
                    <?php if ($hasSale): ?><del><?php echo number_format((float) $p['price'], 0, ',', '.'); ?> đ</del><?php endif; ?>
                    <span><?php echo number_format($price, 0, ',', '.'); ?> đ</span>
                </div>
                <?php if ($isMember): ?>
                <div class="mt-1">
                    <span class="badge <?php echo $st > 0 ? 'bg-success' : 'bg-secondary'; ?>">
                        Tồn: <?php echo rtrim(rtrim(number_format($st, 3, ',', '.'), '0'), ','); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
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
                        <div class="own-carousel__item"><img src="<?php echo e($assetImg); ?>slide02.jpg" alt="Tân Phát"/></div>
                        <div class="own-carousel__item"><img src="<?php echo e($assetImg); ?>thiet_bi_garage.png" alt="Thiết bị gara"/></div>
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
    <section class="products pb-4">
        <div class="container">
            <h2 class="text-center section-title">Đang khuyến mãi</h2>
            <div class="products__list">
                <div class="row g-3">
                    @foreach ($promo as $p)
                        {!! $pcard($p) !!}
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Sản phẩm mới -->
    <section class="products pb-4">
        <div class="container">
            <h2 class="text-center section-title">Sản phẩm mới</h2>
            @if (empty($newest))
                <p class="text-center text-muted">Chưa có sản phẩm.</p>
            @else
            <div class="products__list">
                <div class="row g-3">
                    @foreach ($newest as $p)
                        {!! $pcard($p) !!}
                    @endforeach
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
