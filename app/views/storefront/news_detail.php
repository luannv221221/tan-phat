<?php
$date = !empty($news['published_at']) ? date('d/m/Y', strtotime($news['published_at'])) : '';
?>
<div class="content">
<div class="news-detail">
<div class="container">

    <div class="breadcrumb-sf"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / <a href="{{_WEB_URL.'/tin-tuc'}}">Tin tức</a> / {{$news['title']}}</div>

    <div class="row">
        <div class="col-12 col-lg-8">
            <h1 class="news-detail__heading">{{$news['title']}}</h1>
            <div class="news-detail__date text-muted">
                <i class="fa fa-clock-o"></i> {{$date}} · <i class="fa fa-eye"></i> {{(int)$news['view_count']}} lượt xem
                <?php if (!empty($news['category_name'])): ?><span class="badge bg-secondary">{{$news['category_name']}}</span><?php endif; ?>
            </div>
            @if (!empty($news['thumbnail']))
            <img src="{{media_url($news['thumbnail'])}}" alt="{{$news['title']}}" class="img-fluid rounded mb-3"/>
            @endif
            @if (!empty($news['summary']))
            <p class="fw-semibold text-secondary">{{$news['summary']}}</p>
            @endif
            <div class="news-content">{!! $news['content'] !!}</div>

            <div class="mt-4"><a class="btn btn-outline-primary" href="{{_WEB_URL.'/tin-tuc'}}"><i class="fa fa-angle-left"></i> Về danh sách tin</a></div>
        </div>

        <aside class="col-12 col-lg-4">
            <h2 class="news-right__heading">Tin mới nhất</h2>
            <div class="news-right__list mb-4">
                @if (!empty($latest))
                    @foreach ($latest as $l)
                    <div class="news-right__item">
                        <a href="{{_WEB_URL.'/tin-tuc/'.$l['slug']}}">{{$l['title']}}</a>
                    </div>
                    @endforeach
                @else
                    <p class="text-muted">Chưa có tin khác.</p>
                @endif
            </div>
            <h2 class="news-right__heading">Danh mục</h2>
            <div class="d-grid gap-2">
                @foreach ($categories as $c)
                <a class="btn btn-sm btn-outline-primary" href="{{_WEB_URL.'/tin-tuc?cat='.$c['slug']}}">{{$c['name']}}</a>
                @endforeach
            </div>
        </aside>
    </div>

</div>
</div>
</div>
