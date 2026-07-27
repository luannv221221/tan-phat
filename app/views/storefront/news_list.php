<?php
$baseCatQ = !empty($cat) ? '?cat=' . urlencode($cat['slug']) . '&' : '?';
$listTitle = !empty($cat) ? $cat['name'] : 'Tin tức';
$assetImg  = _WEB_URL . '/public/assets/storefront/images/';
?>
<div class="content">
<div class="news">
<div class="container">

    <div class="breadcrumb-sf"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / Tin tức</div>

    <div class="row">
        <div class="col-12 col-lg-8">
            <h1 class="news-left__heading">{{$listTitle}}</h1>
            <div class="text-muted mb-3">{{(int)$total}} bài viết</div>

            @if (empty($list))
                <div class="empty-box">Chưa có bài viết.</div>
            @else
                <div class="row g-4">
                    @foreach ($list as $n)
                    <?php
                    $thumb = !empty($n['thumbnail']) ? media_url($n['thumbnail']) : ($assetImg . 'placeholder.svg');
                    $date  = !empty($n['published_at']) ? date('d/m/Y', strtotime($n['published_at'])) : '';
                    $url   = _WEB_URL . '/tin-tuc/' . $n['slug'];
                    ?>
                    <div class="col-12 col-md-6">
                        <div class="news-left__item">
                            <a href="{{$url}}" class="item__img">
                                <img src="{{$thumb}}" alt="{{$n['title']}}" style="height:200px"/>
                                <?php if ($date !== ''): ?><span class="item__img--date">{{$date}}</span><?php endif; ?>
                            </a>
                            <a href="{{$url}}" class="item__name">{{$n['title']}}</a>
                            <div class="item__content text-muted">{{!empty($n['summary']) ? mb_strimwidth($n['summary'],0,140,'...') : ''}}</div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if ($pages > 1)
                <div class="pagination-sf">
                    @for ($i = 1; $i <= $pages; $i++)
                        <a class="{{($i===(int)$page)?'active':''}}" href="{{_WEB_URL.'/tin-tuc'.$baseCatQ.'page='.$i}}">{{$i}}</a>
                    @endfor
                </div>
                @endif
            @endif
        </div>

        <aside class="col-12 col-lg-4">
            <h2 class="news-right__heading">Danh mục tin</h2>
            <div class="d-grid gap-2">
                <a class="btn btn-sm {{empty($cat)?'btn-primary':'btn-outline-primary'}}" href="{{_WEB_URL.'/tin-tuc'}}">Tất cả</a>
                @foreach ($categories as $c)
                <a class="btn btn-sm {{(!empty($cat) && $cat['id']==$c['id'])?'btn-primary':'btn-outline-primary'}}" href="{{_WEB_URL.'/tin-tuc?cat='.$c['slug']}}">{{$c['name']}}</a>
                @endforeach
            </div>
        </aside>
    </div>

</div>
</div>
</div>
