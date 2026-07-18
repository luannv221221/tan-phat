<?php
$baseCatQ = !empty($cat) ? '?cat=' . urlencode($cat['slug']) . '&' : '?';
$listTitle = !empty($cat) ? $cat['name'] : 'Tin tức';
?>
<div class="crumb"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / Tin tức</div>

<div class="wrap">
    <aside class="sidebar">
        <div class="card"><div class="hd">Danh mục tin</div><div class="bd">
            <div class="facet">
                <a class="btn btn-sm {{empty($cat)?'btn-brand':'btn-outline'}}" style="width:100%;margin-bottom:6px" href="{{_WEB_URL.'/tin-tuc'}}">Tất cả</a>
                @foreach ($categories as $c)
                <a class="btn btn-sm {{(!empty($cat) && $cat['id']==$c['id'])?'btn-brand':'btn-outline'}}" style="width:100%;margin-bottom:6px" href="{{_WEB_URL.'/tin-tuc?cat='.$c['slug']}}">{{$c['name']}}</a>
                @endforeach
            </div>
        </div></div>
    </aside>

    <section class="content">
        <h1 class="page-title">{{$listTitle}}</h1>
        <div class="muted" style="margin-bottom:14px">{{(int)$total}} bài viết</div>

        @if (empty($list))
            <div class="card"><div class="bd tc muted" style="padding:50px">Chưa có bài viết.</div></div>
        @else
            <div class="grid">
                @foreach ($list as $n)
                <?php
                $thumb = !empty($n['thumbnail'])
                    ? '<img src="'.e($n['thumbnail']).'" alt="'.e($n['title']).'" style="width:100%;height:100%;object-fit:cover"/>'
                    : '📰';
                $date = !empty($n['published_at']) ? date('d/m/Y', strtotime($n['published_at'])) : '';
                $catSuffix = !empty($n['category_name']) ? ' · '.e($n['category_name']) : '';
                ?>
                <div class="pcard">
                    <a class="thumb" href="{{_WEB_URL.'/tin-tuc/'.$n['slug']}}">{!! $thumb !!}</a>
                    <div class="info">
                        <a class="pname" href="{{_WEB_URL.'/tin-tuc/'.$n['slug']}}">{{$n['title']}}</a>
                        <div class="code">{{$date}}{!! $catSuffix !!}</div>
                        <div class="muted" style="font-size:13px">{{!empty($n['summary'])?$n['summary']:''}}</div>
                    </div>
                </div>
                @endforeach
            </div>

            @if ($pages > 1)
            <div class="mt tc">
                @for ($i = 1; $i <= $pages; $i++)
                    <a class="btn btn-sm {{($i===(int)$page)?'btn-brand':'btn-outline'}}" href="{{_WEB_URL.'/tin-tuc'.$baseCatQ.'page='.$i}}">{{$i}}</a>
                @endfor
            </div>
            @endif
        @endif
    </section>
</div>
