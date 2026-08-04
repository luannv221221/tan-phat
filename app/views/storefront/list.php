<?php
$f = $filters;
$inArr = function($id, $arr){ return in_array((int) $id, array_map('intval', (array) $arr), true); };
$baseQ = $query; unset($baseQ['page']);

// Trang /khuyen-mai dùng chung view này (Shop::promo), chỉ khác nhãn và URL gốc
$isPromo  = !empty($promoPage);
$listPath = $isPromo ? '/khuyen-mai' : '/san-pham';
$listName = $isPromo ? 'Khuyến mãi'  : 'Sản phẩm';

$pageUrl = function($n) use ($baseQ, $listPath){ $q = $baseQ; $q['page'] = $n; return _WEB_URL.$listPath.'?'.http_build_query($q); };
$titleTxt = !empty($f['keyword']) ? 'Kết quả: ' . $f['keyword'] : $listName;
$partsBase = _WEB_URL . '/public/assets/uploads/parts/';
$assetImg  = _WEB_URL . '/public/assets/storefront/images/';
?>
<div class="content">
<div class="products">
<div class="container">

    <div class="breadcrumb-sf"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / {{$listName}}</div>

    <!-- Form lọc để RỖNG và nằm ngoài lưới sản phẩm. Các ô lọc nối vào đây bằng
         thuộc tính form="facetForm" của HTML5.

         Trước đây form này bao cả lưới sản phẩm, khiến form "Thêm vào giỏ" của
         từng thẻ bị LỒNG BÊN TRONG. HTML không cho phép lồng form nên trình duyệt
         vứt bỏ thẻ form bên trong; nút "Thêm vào giỏ" trở thành nút submit của
         form lọc và chỉ tải lại trang danh sách thay vì thêm hàng vào giỏ. -->
    <form method="get" action="{{_WEB_URL.$listPath}}" id="facetForm"></form>
    {!! !empty($f['keyword']) ? '<input form="facetForm" type="hidden" name="q" value="'.e($f['keyword']).'"/>' : '' !!}
    <?php
    // Xe đang chọn do THANH LỌC TRÊN HEADER giữ, không có ô nào trong sidebar.
    // Phải chép sang form facet dưới dạng hidden: form GET chỉ gửi đúng các ô
    // thuộc về nó, nên thiếu mấy dòng này thì tick một danh mục ở sidebar sẽ
    // âm thầm xoá mất lựa chọn xe của khách.
    foreach (['car_brand' => 'carBrandId', 'car_body'  => 'carBodyTypeId',
              'car_model' => 'carModelId', 'car_year'  => 'carYearId'] as $param => $key){
        if (!empty($f[$key])){
            echo '<input form="facetForm" type="hidden" name="' . $param . '" value="' . (int) $f[$key] . '"/>';
        }
    }
    ?>
    <div class="row">

        <!-- Sidebar lọc -->
        <div class="col-lg-3">
            <div class="products__sidebar">
                <div class="products__sidebar--item">
                    <h3>Danh mục</h3>
                    <div class="px-3">
                        @foreach ($catOptions as $c)
                            @if ((int) $c['depth'] <= 1)
                            <div class="form-check" style="padding-left:{{1.5+(int)$c['depth']*1}}rem">
                                <input class="form-check-input" form="facetForm" type="checkbox" id="cat{{(int)$c['id']}}" name="category[]" value="{{(int)$c['id']}}" {{$inArr($c['id'],$f['categoryIds'])?'checked':''}} onchange="document.getElementById('facetForm').submit()"/>
                                <label class="form-check-label" for="cat{{(int)$c['id']}}">{{$c['name']}}</label>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                @if (!empty($brandOptions))
                <div class="products__sidebar--item">
                    <h3>Thương hiệu</h3>
                    <div class="px-3">
                        @foreach ($brandOptions as $b)
                        <div class="form-check">
                            <input class="form-check-input" form="facetForm" type="checkbox" id="br{{(int)$b['id']}}" name="brand[]" value="{{(int)$b['id']}}" {{$inArr($b['id'],$f['brandIds'])?'checked':''}} onchange="document.getElementById('facetForm').submit()"/>
                            <label class="form-check-label" for="br{{(int)$b['id']}}">{{$b['name']}}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if (!empty($originOptions))
                <div class="products__sidebar--item">
                    <h3>Xuất xứ</h3>
                    <div class="px-3">
                        @foreach ($originOptions as $o)
                        <div class="form-check">
                            <input class="form-check-input" form="facetForm" type="checkbox" id="or{{(int)$o['id']}}" name="origin[]" value="{{(int)$o['id']}}" {{$inArr($o['id'],$f['originIds'])?'checked':''}} onchange="document.getElementById('facetForm').submit()"/>
                            <label class="form-check-label" for="or{{(int)$o['id']}}">{{$o['name']}}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="products__sidebar--item">
                    <h3>Khoảng giá (₫)</h3>
                    <div class="px-3 d-flex gap-2">
                        <input type="text" class="form-control form-control-sm" form="facetForm" name="price_min" placeholder="Từ" value="{{$f['priceMin']}}"/>
                        <input type="text" class="form-control form-control-sm" form="facetForm" name="price_max" placeholder="Đến" value="{{$f['priceMax']}}"/>
                    </div>
                </div>

                <div class="products__sidebar--item">
                    <div class="px-3">
                        @if (!$isPromo)
                        <div class="form-check">
                            <input class="form-check-input" form="facetForm" type="checkbox" id="promoChk" name="promo" value="1" {{!empty($f['promo'])?'checked':''}} onchange="document.getElementById('facetForm').submit()"/>
                            <label class="form-check-label" for="promoChk">Chỉ hàng khuyến mãi</label>
                        </div>
                        @endif
                        <button form="facetForm" class="btn btn-primary btn-sm w-100 mt-3" type="submit">Áp dụng lọc</button>
                        <a class="btn btn-outline-secondary btn-sm w-100 mt-2" href="{{_WEB_URL.'/san-pham'}}">Xoá lọc</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 mt-3 mt-lg-0">
                <div>
                    <h1 class="sf-page-title mb-0">{{$titleTxt}}</h1>
                    <div class="text-muted">{{(int)$total}} sản phẩm</div>
                </div>
                <div>
                    <label class="text-muted small me-1">Sắp xếp</label>
                    <select form="facetForm" name="sort" class="form-select form-select-sm d-inline-block" style="width:auto" onchange="document.getElementById('facetForm').submit()">
                        <option value="">Tên A-Z</option>
                        <option value="new" {{($f['sort']==='new')?'selected':''}}>Mới nhất</option>
                        <option value="price_asc" {{($f['sort']==='price_asc')?'selected':''}}>Giá thấp → cao</option>
                        <option value="price_desc" {{($f['sort']==='price_desc')?'selected':''}}>Giá cao → thấp</option>
                    </select>
                </div>
            </div>

            @if (empty($list))
                <div class="empty-box">Không có sản phẩm khớp bộ lọc.</div>
            @else
                <div class="products__list">
                    <div class="row g-3">
                        @foreach ($list as $p)
                        <?php
                        $hasSale = !empty($p['sale_price']);
                        $price = $hasSale ? (float) $p['sale_price'] : (float) $p['price'];
                        $pid = (int) $p['id'];
                        $brandSuffix = !empty($p['brand_name']) ? ' · ' . $p['brand_name'] : '';
                        $st = ($isMember && isset($stockMap[$pid])) ? (float) $stockMap[$pid] : 0;
                        $imgFile = isset($imgMap[$pid]) ? $imgMap[$pid] : '';
                        $imgUrl = $imgFile !== '' ? ($partsBase . $imgFile) : ($assetImg . 'placeholder.svg');
                        $url = _WEB_URL . '/san-pham/' . $p['slug'];
                        ?>
                        <div class="col-6 col-md-4">
                            <div class="products--item h-100">
                                <div class="item__image">
                                    <a href="{{$url}}"><img src="{{$imgUrl}}" alt="{{$p['name']}}" loading="lazy"/></a>
                                    <?php
                                    $off = ($hasSale && (float) $p['price'] > 0)
                                         ? (int) round((1 - $price / (float) $p['price']) * 100) : 0;
                                    if ($hasSale): ?><span class="item--sales"><?php echo $off > 0 ? '-' . $off . '%' : 'KM'; ?></span><?php endif; ?>
                                </div>
                                <div class="item__info">
                                    <h3 class="item--name"><a href="{{$url}}">{{$p['name']}}</a></h3>
                                    <div class="small text-muted mb-1">Mã: {{$p['code']}}{{$brandSuffix}}</div>
                                    <div class="item--price">
                                        <?php if ($hasSale): ?><del>{{number_format((float)$p['price'],0,',','.')}} đ</del><?php endif; ?>
                                        <span>{{number_format($price,0,',','.')}} đ</span>
                                    </div>
                                    @if ($isMember)
                                    <div class="mt-1"><span class="badge {{$st>0?'bg-success':'bg-secondary'}}">Tồn: {{rtrim(rtrim(number_format($st,3,',','.'),'0'),',')}}</span></div>
                                    @endif
                                    <form method="post" action="{{_WEB_URL.'/gio-hang/them'}}" class="mt-2">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="part_id" value="{{$pid}}"/>
                                        <button class="btn btn-primary btn-sm w-100" type="submit"><i class="fa fa-shopping-cart"></i> Thêm vào giỏ</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                @if ($pages > 1)
                <div class="pagination-sf">
                    @for ($i = 1; $i <= $pages; $i++)
                        <a class="{{($i===(int)$page)?'active':''}}" href="{{$pageUrl($i)}}">{{$i}}</a>
                    @endfor
                </div>
                @endif
            @endif
        </div>

    </div>
</div>
</div>
</div>
