<?php
$done = !empty($project['completed_at']) ? date('d/m/Y', strtotime($project['completed_at'])) : '';
?>
<div class="crumb"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / <a href="{{_WEB_URL.'/du-an'}}">Dự án</a> / {{$project['name']}}</div>

<div class="card"><div class="bd">
    <h1 style="font-size:26px;margin:0 0 10px">{{$project['name']}}</h1>
    <div class="mt" style="margin-bottom:14px">
        @if (!empty($project['client']))
        <span class="badge">Khách hàng: {{$project['client']}}</span>
        @endif
        @if (!empty($project['location']))
        <span class="badge">Địa điểm: {{$project['location']}}</span>
        @endif
        @if (!empty($done))
        <span class="badge">Hoàn thành: {{$done}}</span>
        @endif
    </div>
    @if (!empty($project['thumbnail']))
    <img src="{{$project['thumbnail']}}" alt="{{$project['name']}}" style="width:100%;border-radius:8px;margin-bottom:16px"/>
    @endif
    @if (!empty($project['summary']))
    <p style="font-weight:600;color:#444">{{$project['summary']}}</p>
    @endif
    <div class="project-content">{!! $project['content'] !!}</div>
</div></div>

@if (!empty($others))
<h2 style="font-size:20px;margin:26px 0 12px">Dự án khác</h2>
<div class="grid">
    @foreach ($others as $o)
    <?php $thumb = !empty($o['thumbnail']) ? '<img src="'.e($o['thumbnail']).'" alt="'.e($o['name']).'" style="width:100%;height:100%;object-fit:cover"/>' : '🏗'; ?>
    <div class="pcard">
        <a class="thumb" href="{{_WEB_URL.'/du-an/'.$o['slug']}}">{!! $thumb !!}</a>
        <div class="info"><a class="pname" href="{{_WEB_URL.'/du-an/'.$o['slug']}}">{{$o['name']}}</a></div>
    </div>
    @endforeach
</div>
@endif

<div class="mt"><a class="btn btn-outline" href="{{_WEB_URL.'/du-an'}}">← Về danh sách dự án</a></div>
