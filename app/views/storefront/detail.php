<?php
$base = _WEB_URL . '/public/assets/uploads/parts/';
$assetImg = _WEB_URL . '/public/assets/storefront/images/';
$hasSale = !empty($part['sale_price']);
$price = $hasSale ? (float) $part['sale_price'] : (float) $part['price'];
$stockNum = rtrim(rtrim(number_format((float) $stock, 3, ',', '.'), '0'), ',');
$avg = isset($reviewSummary['avg']) ? (float) $reviewSummary['avg'] : 0;
$avgRound = (int) round($avg);
?>
<div class="content">
<section class="detail">
<div class="container">

    <div class="breadcrumb-sf"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / <a href="{{_WEB_URL.'/san-pham'}}">Sản phẩm</a> / {{$part['name']}}</div>

    <div class="row">
        <!-- Ảnh -->
        <div class="col-12 col-lg-5">
            <div class="detail-img">
                @if (!empty($images))
                <div class="slider">
                    <div id="sync11" class="owl-carousel owl-theme">
                        @foreach ($images as $img)
                        <div><img src="{{$base.$img['image']}}" alt="{{$part['name']}}"/></div>
                        @endforeach
                    </div>
                </div>
                @if (count($images) > 1)
                <div class="slider-nav">
                    <div class="row">
                        <div id="sync21" class="owl-carousel owl-theme">
                            @foreach ($images as $img)
                            <div class="slider-nav__item"><img src="{{$base.$img['image']}}" alt=""/></div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
                @else
                <img src="{{$assetImg.'placeholder.svg'}}" alt="{{$part['name']}}" style="width:100%"/>
                @endif
            </div>
        </div>

        <!-- Thông tin -->
        <div class="col-12 col-lg-7">
            <div class="detail-info">
                <h1 class="detail-info__name">{{$part['name']}}</h1>
                <div class="detail-info__star">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                        <i class="fa <?php echo $s <= $avgRound ? 'fa-star' : 'fa-star-o'; ?>" aria-hidden="true"></i>
                    <?php endfor; ?>
                    @if (!empty($reviewSummary['count']))
                    <span class="text-muted small ms-1">{{$avg}}/5 ({{(int)$reviewSummary['count']}} đánh giá)</span>
                    @endif
                </div>

                <div class="mb-2 text-muted">Mã: <b>{{$part['code']}}</b><?php if (!empty($part['oem_code'])): ?> · OEM: {{$part['oem_code']}}<?php endif; ?></div>
                <div class="mb-3">
                    <?php if (!empty($part['brand_name'])): ?><span class="badge bg-secondary">{{$part['brand_name']}}</span><?php endif; ?>
                    <?php if (!empty($part['origin_name'])): ?><span class="badge bg-secondary">Xuất xứ: {{$part['origin_name']}}</span><?php endif; ?>
                    <?php if (!empty($part['category_name'])): ?><span class="badge bg-secondary">{{$part['category_name']}}</span><?php endif; ?>
                </div>

                <div class="detail-info__price">
                    <span class="price-new">{{number_format($price,0,',','.')}} đ</span>
                    <?php if ($hasSale): ?><span class="price-old">{{number_format((float)$part['price'],0,',','.')}} đ</span><?php endif; ?>
                    <?php if (!empty($part['unit_name'])): ?><span class="text-muted" style="font-size:1rem"> / {{$part['unit_name']}}</span><?php endif; ?>
                </div>

                @if ($isMember)
                    <div class="mt-2"><span class="badge {{$stock>0?'bg-success':'bg-secondary'}}">Tồn kho: {{$stockNum}} {{$part['unit_name']??''}}</span></div>
                @else
                    <div class="alert alert-info mt-3 py-2"><i class="fa fa-lock"></i> <a href="{{_WEB_URL.'/thanh-vien/dang-nhap'}}"><b>Đăng nhập thành viên</b></a> để xem tồn kho.</div>
                @endif

                <form method="post" action="{{_WEB_URL.'/gio-hang/them'}}" class="detail-info__qty">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="part_id" value="{{(int)$part['id']}}"/>
                    <label>Số lượng</label>
                    <input type="number" name="qty" value="1" min="1"/>
                    <button type="submit"><i class="fa fa-shopping-cart"></i> Thêm vào giỏ hàng</button>
                </form>

                @if (!empty($part['warranty_month']))
                <div class="detail-info__des"><i class="fa fa-shield"></i> Bảo hành: {{(int)$part['warranty_month']}} tháng</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabs nội dung -->
    <div class="detail-content-container">
        <div class="detail-content">
            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-desc" type="button" role="tab">Thông tin sản phẩm</button>
                </li>
                @if (!empty($attrs))
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-spec" type="button" role="tab">Thông số kỹ thuật</button>
                </li>
                @endif
                @if (!empty($fitments))
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-fit" type="button" role="tab">Xe tương thích</button>
                </li>
                @endif
            </ul>
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="tab-desc" role="tabpanel">
                    @if (!empty($part['description']))
                        {!! nl2br(e($part['description'])) !!}
                    @else
                        <p class="text-muted">Đang cập nhật thông tin sản phẩm.</p>
                    @endif
                </div>
                @if (!empty($attrs))
                <div class="tab-pane fade" id="tab-spec" role="tabpanel">
                    <table class="table table-bordered">
                        <tbody>
                        @foreach ($attrs as $a)
                        <tr><td style="width:220px;background:#f8f9fa">{{$a['name']}}</td><td>{{$a['value']}}<?php if (!empty($a['unit'])): ?> {{$a['unit']}}<?php endif; ?></td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
                @if (!empty($fitments))
                <div class="tab-pane fade" id="tab-fit" role="tabpanel">
                    <div class="d-flex flex-wrap gap-2">
                    @foreach ($fitments as $ft)
                        <span class="badge bg-secondary">{{$ft['brand_name'].' '.$ft['model_name'].(!empty($ft['year_name']) ? ' — '.$ft['year_name'] : '')}}</span>
                    @endforeach
                    </div>
                </div>
                @endif
            </div>

            @if (!empty($related))
            <h2 class="mt-4" style="font-size:1.2rem">Phụ kiện / sản phẩm đi kèm</h2>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($related as $r)
                <a class="btn btn-outline-primary btn-sm" href="{{_WEB_URL.'/san-pham/'.$r['slug']}}">{{$r['name']}}</a>
                @endforeach
            </div>
            @endif
        </div>

        <div class="socials-share my-3">
            <a class="bg-facebook" href="https://www.facebook.com/sharer/sharer.php?u={{urlencode(_WEB_URL.'/san-pham/'.$part['slug'])}}" target="_blank"><span class="fa fa-facebook"></span> Share</a>
            <a class="bg-twitter" href="https://twitter.com/intent/tweet?url={{urlencode(_WEB_URL.'/san-pham/'.$part['slug'])}}" target="_blank"><span class="fa fa-twitter"></span> Tweet</a>
        </div>
    </div>

    <!-- Đánh giá -->
    <div class="detail-content-container mt-3">
        <h2 style="font-size:1.3rem">Đánh giá sản phẩm</h2>
        @if (!empty($reviewMsg))
        <div class="alert alert-success">{{$reviewMsg}}</div>
        @endif

        @if (!empty($reviews))
            @foreach ($reviews as $rv)
            <div class="py-2 border-bottom">
                <b>{{$rv['author_name']}}</b>
                <span style="color:#febd69">
                    <?php for ($s = 1; $s <= 5; $s++): ?><i class="fa <?php echo $s <= (int)$rv['rating'] ? 'fa-star' : 'fa-star-o'; ?>"></i><?php endfor; ?>
                </span>
                <div class="text-muted small">{{$rv['create_at']}}</div>
                <div>{{$rv['comment']}}</div>
            </div>
            @endforeach
        @else
            <p class="text-muted">Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
        @endif

        <div class="mt-3">
        @if ($isMember)
            <form method="post" action="{{_WEB_URL.'/san-pham/danh-gia'}}">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="part_id" value="{{(int)$part['id']}}"/>
                <div class="mb-2" style="max-width:220px">
                    <label class="form-label fw-semibold mb-1">Chấm điểm</label>
                    <select name="rating" class="form-select form-select-sm">
                        <option value="5">5 — Rất tốt</option>
                        <option value="4">4 — Tốt</option>
                        <option value="3">3 — Bình thường</option>
                        <option value="2">2 — Tạm</option>
                        <option value="1">1 — Kém</option>
                    </select>
                </div>
                <textarea name="comment" rows="3" class="form-control" style="max-width:640px" placeholder="Chia sẻ cảm nhận của bạn..."></textarea>
                <button class="btn btn-primary mt-2" type="submit">Gửi đánh giá</button>
            </form>
        @else
            <div class="alert alert-info mb-0"><i class="fa fa-lock"></i> <a href="{{_WEB_URL.'/thanh-vien/dang-nhap'}}"><b>Đăng nhập thành viên</b></a> để viết đánh giá.</div>
        @endif
        </div>
    </div>

    <?php /* ------------------------------------------------------------------
       SẢN PHẨM LIÊN QUAN

       Khác với khối "Phụ kiện / sản phẩm đi kèm" ở trên: khối kia do admin
       tự tay gắn từng món, khối này máy tự chọn (xem PartsModel::lienQuan).
       Controller đã loại các mã đã hiện ở khối kia nên hai khối không trùng.

       Rỗng thì không in gì cả — một cái tiêu đề "Sản phẩm liên quan" nằm
       trên khoảng trắng còn xấu hơn là không có mục đó.
       ------------------------------------------------------------------ */ ?>
    @if (!empty($lienQuan))
    <?php /* KHÔNG dùng class .related-products: class đó thuộc về khối
             owl-carousel cũ của theme, và luật `.related-products h2` trong
             style.css đè font-size/padding của .section-title. */ ?>
    <section class="products lien-quan mt-4">
        <h2 class="section-title">Sản phẩm liên quan</h2>
        <div class="products__list">
            <div class="row g-3">
            @foreach ($lienQuan as $lq)
            <?php
            $lqId    = (int) $lq['id'];
            $lqSale  = !empty($lq['sale_price']);
            $lqGia   = $lqSale ? (float) $lq['sale_price'] : (float) $lq['price'];
            $lqGoc   = (float) $lq['price'];
            $lqGiam  = ($lqSale && $lqGoc > 0) ? (int) round((1 - $lqGia / $lqGoc) * 100) : 0;
            $lqAnh   = !empty($imgMap[$lqId]) ? ($base . $imgMap[$lqId]) : ($assetImg . 'placeholder.svg');
            $lqUrl   = _WEB_URL . '/san-pham/' . $lq['slug'];
            $lqTon   = ($isMember && isset($stockMap[$lqId])) ? (float) $stockMap[$lqId] : 0;
            $lqHang  = !empty($lq['brand_name']) ? ' · ' . $lq['brand_name'] : '';
            ?>
            <div class="col-6 col-md-3">
                <div class="products--item h-100">
                    <div class="item__image">
                        <a href="{{$lqUrl}}"><img src="{{$lqAnh}}" alt="{{$lq['name']}}" loading="lazy"/></a>
                        <?php if ($lqSale): ?><span class="item--sales">{{$lqGiam > 0 ? '-'.$lqGiam.'%' : 'KM'}}</span><?php endif; ?>
                    </div>
                    <div class="item__info">
                        <h3 class="item--name"><a href="{{$lqUrl}}">{{$lq['name']}}</a></h3>
                        <div class="small text-muted mb-1">Mã: {{$lq['code']}}{{$lqHang}}</div>
                        <div class="item--price">
                            <?php if ($lqSale): ?><del>{{number_format($lqGoc,0,',','.')}} đ</del><?php endif; ?>
                            <span>{{number_format($lqGia,0,',','.')}} đ</span>
                        </div>
                        @if ($isMember)
                        <div class="mt-1"><span class="badge {{$lqTon>0?'bg-success':'bg-secondary'}}">Tồn: {{rtrim(rtrim(number_format($lqTon,3,',','.'),'0'),',')}}</span></div>
                        @endif
                        <form method="post" action="{{_WEB_URL.'/gio-hang/them'}}" class="mt-2">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="part_id" value="{{$lqId}}"/>
                            <button class="btn btn-primary btn-sm w-100" type="submit"><i class="fa fa-shopping-cart"></i> Thêm vào giỏ</button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
            </div>
        </div>
    </section>
    @endif

</div>
</section>
</div>
