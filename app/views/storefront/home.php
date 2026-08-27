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

    <?php /* Thanh lọc xe. Master dựng sẵn chuỗi HTML rồi truyền xuống đây —
             view con chạy qua eval trong Template::run() nên $this ở đây là
             đối tượng Template, không gọi được $this->render(). Xem khối
             chú thích ở master.php.
             Các trang khác để master in ngay dưới menu; riêng trang chủ có
             băng-rôn nên phải in SAU nó. */ ?>
    {!! $thanhLoc !!}

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

    <?php /* ------------------------------------------------------------------
       VIDEO

       Nguồn là thư viện ảnh/video có sẵn (gallery_items, media_type='video'),
       chỉ lấy video của album ĐÃ ĐĂNG — xem GalleryItemsModel::getVideosPublished().
       Không đẻ thêm bảng mới: đăng video vẫn làm ở admin > Thư viện như cũ.

       Video đầu tiên vào khung lớn, các video còn lại xếp thành danh sách bên
       phải. Bấm vào một dòng thì ĐỔI KHUNG ngay tại chỗ, không tải lại trang.
       ------------------------------------------------------------------ */ ?>
    @if (!empty($videos))
    <?php
    // Bỏ các dòng không rút được mã YouTube — còn lại mới dựng khối.
    $dsVideo = [];
    foreach ($videos as $v){
        $ma = youtube_id($v['video_url']);
        if ($ma === '') continue;
        $dsVideo[] = [
            'ma'    => $ma,
            // caption là ô không bắt buộc; trống thì lùi về tên album
            'ten'   => !empty($v['caption']) ? $v['caption'] : $v['gallery_name'],
            'album' => $v['gallery_slug'],
        ];
    }
    ?>
    @if (!empty($dsVideo))
    <section class="videos pb-5">
        <div class="container">
            <h2 class="text-center section-title">Video</h2>
            <div class="row g-4">
                <div class="col-12 col-lg-7">
                    <div class="videos__khung">
                        <?php /* Không autoplay lúc tải trang: video tự chạy khi mới vào
                                 là kiểu làm phiền, mà trên 3G còn ngốn dữ liệu của khách.
                                 Chỉ bật autoplay khi khách CHỦ ĐỘNG bấm sang video khác. */ ?>
                        <iframe id="videoChinh"
                                src="https://www.youtube-nocookie.com/embed/{{$dsVideo[0]['ma']}}"
                                title="{{$dsVideo[0]['ten']}}"
                                loading="lazy" allowfullscreen
                                allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <ul class="videos__ds" id="dsVideo">
                        @foreach ($dsVideo as $i => $v)
                        <li>
                            <button type="button" class="videos__muc {{$i===0?'dang-xem':''}}"
                                    data-ma="{{$v['ma']}}" data-ten="{{$v['ten']}}">
                                <span class="videos__anh">
                                    <?php /* Ảnh đại diện lấy thẳng từ YouTube — khỏi phải
                                             bắt người đăng tải thêm một tấm nữa cho mỗi video. */ ?>
                                    <img src="https://i.ytimg.com/vi/{{$v['ma']}}/mqdefault.jpg"
                                         alt="{{$v['ten']}}" loading="lazy"/>
                                    <i class="fa fa-play" aria-hidden="true"></i>
                                </span>
                                <span class="videos__ten">{{$v['ten']}}</span>
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <script>
    // Đổi video trong khung lớn, không tải lại trang.
    (function(){
        var khung = document.getElementById('videoChinh');
        var ds    = document.getElementById('dsVideo');
        if (!khung || !ds) return;

        ds.addEventListener('click', function(e){
            var nut = e.target.closest('.videos__muc');
            if (!nut) return;

            var ma = nut.getAttribute('data-ma');
            if (!ma) return;

            // autoplay=1 ở đây là hợp lệ vì đi ngay sau cú bấm của khách.
            khung.src   = 'https://www.youtube-nocookie.com/embed/' + ma + '?autoplay=1';
            khung.title = nut.getAttribute('data-ten') || '';

            ds.querySelectorAll('.videos__muc').forEach(function(x){ x.classList.remove('dang-xem'); });
            nut.classList.add('dang-xem');
        });
    })();
    </script>
    @endif
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
