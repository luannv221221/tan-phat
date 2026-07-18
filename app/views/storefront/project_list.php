<div class="crumb"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / Dự án</div>
<h1 class="page-title">Dự án đã thực hiện</h1>
<div class="muted" style="margin-bottom:14px">{{(int)$total}} dự án</div>

@if (empty($list))
    <div class="card"><div class="bd tc muted" style="padding:50px">Chưa có dự án nào.</div></div>
@else
    <div class="grid">
        @foreach ($list as $pj)
        <?php
        $thumb = !empty($pj['thumbnail'])
            ? '<img src="'.e($pj['thumbnail']).'" alt="'.e($pj['name']).'" style="width:100%;height:100%;object-fit:cover"/>'
            : '🏗';
        $meta = trim(($pj['client'] ?? '') . (!empty($pj['location']) ? ' · ' . $pj['location'] : ''), ' ·');
        $done = !empty($pj['completed_at']) ? $pj['completed_at'] : '';
        ?>
        <div class="pcard">
            <a class="thumb" href="{{_WEB_URL.'/du-an/'.$pj['slug']}}">{!! $thumb !!}</a>
            <div class="info">
                <a class="pname" href="{{_WEB_URL.'/du-an/'.$pj['slug']}}">{{$pj['name']}}</a>
                <div class="code">{{$meta}}</div>
                <div class="muted" style="font-size:13px">{{!empty($pj['summary'])?$pj['summary']:''}}</div>
            </div>
        </div>
        @endforeach
    </div>

    @if ($pages > 1)
    <div class="mt tc">
        @for ($i = 1; $i <= $pages; $i++)
            <a class="btn btn-sm {{($i===(int)$page)?'btn-brand':'btn-outline'}}" href="{{_WEB_URL.'/du-an?page='.$i}}">{{$i}}</a>
        @endfor
    </div>
    @endif
@endif
