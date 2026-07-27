<?php
$done = !empty($project['completed_at']) ? date('d/m/Y', strtotime($project['completed_at'])) : '';
$assetImg = _WEB_URL . '/public/assets/storefront/images/';
?>
<div class="content">
<div class="sf-page">
<div class="container">

    <div class="breadcrumb-sf"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / <a href="{{_WEB_URL.'/du-an'}}">Dự án</a> / {{$project['name']}}</div>

    <div class="card border-0 shadow-sm"><div class="card-body">
        <h1 style="font-size:1.7rem" class="mb-2">{{$project['name']}}</h1>
        <div class="mb-3 d-flex flex-wrap gap-2">
            <?php if (!empty($project['client'])): ?><span class="badge bg-secondary">Khách hàng: {{$project['client']}}</span><?php endif; ?>
            <?php if (!empty($project['location'])): ?><span class="badge bg-secondary">Địa điểm: {{$project['location']}}</span><?php endif; ?>
            <?php if (!empty($done)): ?><span class="badge bg-secondary">Hoàn thành: {{$done}}</span><?php endif; ?>
        </div>
        @if (!empty($project['thumbnail']))
        <img src="{{media_url($project['thumbnail'])}}" alt="{{$project['name']}}" class="img-fluid rounded mb-3"/>
        @endif
        @if (!empty($project['summary']))
        <p class="fw-semibold text-secondary">{{$project['summary']}}</p>
        @endif
        <div class="project-content">{!! $project['content'] !!}</div>
    </div></div>

    @if (!empty($others))
    <h2 class="mt-4 mb-3" style="font-size:1.3rem">Dự án khác</h2>
    <div class="row g-3">
        @foreach ($others as $o)
        <?php $thumb = !empty($o['thumbnail']) ? media_url($o['thumbnail']) : ($assetImg . 'placeholder.svg'); ?>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <a href="{{_WEB_URL.'/du-an/'.$o['slug']}}"><img src="{{$thumb}}" class="card-img-top" alt="{{$o['name']}}" style="height:150px;object-fit:cover"/></a>
                <div class="card-body"><a href="{{_WEB_URL.'/du-an/'.$o['slug']}}" class="fw-semibold text-dark">{{$o['name']}}</a></div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div class="mt-3"><a class="btn btn-outline-primary" href="{{_WEB_URL.'/du-an'}}"><i class="fa fa-angle-left"></i> Về danh sách dự án</a></div>

</div>
</div>
</div>
