<?php
$date = !empty($news['published_at']) ? date('d/m/Y', strtotime($news['published_at'])) : '';
$catBadge = !empty($news['category_name']) ? '<span class="badge">'.e($news['category_name']).'</span>' : '';
?>
<div class="crumb"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / <a href="{{_WEB_URL.'/tin-tuc'}}">Tin tức</a> / {{$news['title']}}</div>

<div class="wrap">
    <section class="content">
        <div class="card"><div class="bd">
            <h1 style="font-size:26px;margin:0 0 8px">{{$news['title']}}</h1>
            <div class="muted" style="margin-bottom:16px">🗓 {{$date}} · 👁 {{(int)$news['view_count']}} lượt xem {!! $catBadge !!}</div>
            @if (!empty($news['thumbnail']))
            <img src="{{media_url($news['thumbnail'])}}" alt="{{$news['title']}}" style="width:100%;border-radius:8px;margin-bottom:16px"/>
            @endif
            @if (!empty($news['summary']))
            <p style="font-weight:600;color:#444">{{$news['summary']}}</p>
            @endif
            <div class="news-content">{!! $news['content'] !!}</div>
        </div></div>
        <div class="mt"><a class="btn btn-outline" href="{{_WEB_URL.'/tin-tuc'}}">← Về danh sách tin</a></div>
    </section>

    <aside class="sidebar">
        <div class="card"><div class="hd">Tin mới nhất</div><div class="bd">
            @if (!empty($latest))
                @foreach ($latest as $l)
                <div style="padding:8px 0;border-bottom:1px solid #eee">
                    <a href="{{_WEB_URL.'/tin-tuc/'.$l['slug']}}" style="font-weight:600;font-size:14px">{{$l['title']}}</a>
                </div>
                @endforeach
            @else
                <p class="muted">Chưa có tin khác.</p>
            @endif
        </div></div>
        <div class="card mt"><div class="hd">Danh mục</div><div class="bd">
            @foreach ($categories as $c)
            <a class="btn btn-sm btn-outline" style="width:100%;margin-bottom:6px" href="{{_WEB_URL.'/tin-tuc?cat='.$c['slug']}}">{{$c['name']}}</a>
            @endforeach
        </div></div>
    </aside>
</div>
