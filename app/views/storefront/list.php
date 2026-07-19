<?php
$f = $filters;
$inArr = function($id, $arr){ return in_array((int) $id, array_map('intval', (array) $arr), true); };
$baseQ = $query; unset($baseQ['page']);
$pageUrl = function($n) use ($baseQ){ $q = $baseQ; $q['page'] = $n; return _WEB_URL.'/san-pham?'.http_build_query($q); };
$titleTxt = !empty($f['keyword']) ? 'Kết quả: ' . $f['keyword'] : 'Sản phẩm';
?>
<div class="crumb"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / Sản phẩm</div>

<form method="get" action="{{_WEB_URL.'/san-pham'}}" id="facetForm">
{!! !empty($f['keyword']) ? '<input type="hidden" name="q" value="'.e($f['keyword']).'"/>' : '' !!}
<div class="wrap">
    <aside class="sidebar">
        <div class="card"><div class="bd">
            <div class="facet">
                <h4>Danh mục</h4>
                @foreach ($catOptions as $c)
                    @if ((int) $c['depth'] <= 1)
                    <label style="padding-left:{{(int)$c['depth']*12}}px">
                        <input type="checkbox" name="category[]" value="{{(int)$c['id']}}" {{$inArr($c['id'],$f['categoryIds'])?'checked':''}} onchange="document.getElementById('facetForm').submit()"/>
                        {{$c['name']}}
                    </label>
                    @endif
                @endforeach
            </div>

            @if (!empty($brandOptions))
            <div class="facet">
                <h4>Thương hiệu</h4>
                @foreach ($brandOptions as $b)
                    <label><input type="checkbox" name="brand[]" value="{{(int)$b['id']}}" {{$inArr($b['id'],$f['brandIds'])?'checked':''}} onchange="document.getElementById('facetForm').submit()"/> {{$b['name']}}</label>
                @endforeach
            </div>
            @endif

            @if (!empty($originOptions))
            <div class="facet">
                <h4>Xuất xứ</h4>
                @foreach ($originOptions as $o)
                    <label><input type="checkbox" name="origin[]" value="{{(int)$o['id']}}" {{$inArr($o['id'],$f['originIds'])?'checked':''}} onchange="document.getElementById('facetForm').submit()"/> {{$o['name']}}</label>
                @endforeach
            </div>
            @endif

            <div class="facet">
                <h4>Xe tương thích</h4>
                <select name="car_model" onchange="document.getElementById('facetForm').submit()" style="width:100%;padding:7px;border:1px solid #e6e6e6;border-radius:5px">
                    <option value="">— Mọi xe —</option>
                    @foreach ($carModels as $cm)
                        <option value="{{(int)$cm['id']}}" {{((int)$f['carModelId']===(int)$cm['id'])?'selected':''}}>{{$cm['name']}}</option>
                    @endforeach
                </select>
            </div>

            <div class="facet">
                <h4>Khoảng giá (₫)</h4>
                <div class="price-row">
                    <input type="text" name="price_min" placeholder="Từ" value="{{$f['priceMin']}}"/>
                    <input type="text" name="price_max" placeholder="Đến" value="{{$f['priceMax']}}"/>
                </div>
            </div>

            <div class="facet">
                <label><input type="checkbox" name="promo" value="1" {{!empty($f['promo'])?'checked':''}} onchange="document.getElementById('facetForm').submit()"/> Chỉ hàng khuyến mãi</label>
            </div>

            <button class="btn btn-brand btn-sm" type="submit" style="width:100%">Áp dụng</button>
            <a class="btn btn-sm" href="{{_WEB_URL.'/san-pham'}}" style="width:100%;text-align:center;margin-top:6px">Xoá lọc</a>
        </div></div>
    </aside>

    <section class="content">
        <div class="toolbar">
            <div>
                <h1 class="page-title">{{$titleTxt}}</h1>
                <div class="muted">{{(int)$total}} sản phẩm</div>
            </div>
            <div>
                <label class="muted" style="font-size:13px">Sắp xếp
                <select name="sort" onchange="document.getElementById('facetForm').submit()">
                    <option value="">Tên A-Z</option>
                    <option value="new" {{($f['sort']==='new')?'selected':''}}>Mới nhất</option>
                    <option value="price_asc" {{($f['sort']==='price_asc')?'selected':''}}>Giá thấp → cao</option>
                    <option value="price_desc" {{($f['sort']==='price_desc')?'selected':''}}>Giá cao → thấp</option>
                </select></label>
            </div>
        </div>

        @if (empty($list))
            <div class="card"><div class="bd tc muted" style="padding:50px">Không có sản phẩm khớp bộ lọc.</div></div>
        @else
            <div class="grid">
                @foreach ($list as $p)
                <?php
                $hasSale = !empty($p['sale_price']);
                $price = $hasSale ? (float) $p['sale_price'] : (float) $p['price'];
                $pid = (int) $p['id'];
                $km  = $hasSale ? '<span class="badge badge-promo" style="position:absolute;margin:8px">KM</span>' : '';
                $old = $hasSale ? '<span class="old">'.number_format((float) $p['price'], 0, ',', '.').'</span>' : '';
                $brandSuffix = !empty($p['brand_name']) ? ' · '.e($p['brand_name']) : '';
                $st = ($isMember && isset($stockMap[$pid])) ? (float) $stockMap[$pid] : 0;
                $stockBadge = $isMember
                    ? '<div><span class="badge '.($st > 0 ? 'badge-ok' : '').'">Tồn: '.rtrim(rtrim(number_format($st, 3, ',', '.'), '0'), ',').'</span></div>'
                    : '';
                $imgFile = isset($imgMap[$pid]) ? $imgMap[$pid] : '';
                $thumbInner = $imgFile !== ''
                    ? '<img src="'.e(_WEB_URL.'/public/assets/uploads/parts/'.$imgFile).'" alt="'.e($p['name']).'" loading="lazy"/>'
                    : '🔧';
                ?>
                <div class="pcard">
                    <a class="thumb" href="{{_WEB_URL.'/san-pham/'.$p['slug']}}">{!! $km !!}{!! $thumbInner !!}</a>
                    <div class="info">
                        <a class="pname" href="{{_WEB_URL.'/san-pham/'.$p['slug']}}">{{$p['name']}}</a>
                        <div class="code">Mã: {{$p['code']}}{!! $brandSuffix !!}</div>
                        <div class="price">{{number_format($price,0,',','.')}} ₫ {!! $old !!}</div>
                        {!! $stockBadge !!}
                        <div class="foot">
                            <form method="post" action="{{_WEB_URL.'/gio-hang/them'}}" style="display:inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="part_id" value="{{$pid}}"/>
                                <button class="btn btn-brand btn-sm" type="submit">Thêm vào giỏ</button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            @if ($pages > 1)
            <div class="mt tc">
                @for ($i = 1; $i <= $pages; $i++)
                    <a class="btn btn-sm {{($i===(int)$page)?'btn-brand':'btn-outline'}}" href="{{$pageUrl($i)}}">{{$i}}</a>
                @endfor
            </div>
            @endif
        @endif
    </section>
</div>
</form>
