<?php $assetImg = _WEB_URL . '/public/assets/storefront/images/'; ?>
<div class="content">
<div class="sf-page">
<div class="container">

    <div class="breadcrumb-sf"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / Dự án</div>
    <h1 class="sf-page-title">Dự án đã thực hiện</h1>
    <div class="text-muted mb-3">{{(int)$total}} dự án</div>

    @if (empty($list))
        <div class="empty-box">Chưa có dự án nào.</div>
    @else
        <div class="row g-3">
            @foreach ($list as $pj)
            <?php
            $thumb = !empty($pj['thumbnail']) ? media_url($pj['thumbnail']) : ($assetImg . 'placeholder.svg');
            $meta = trim(($pj['client'] ?? '') . (!empty($pj['location']) ? ' · ' . $pj['location'] : ''), ' ·');
            $url = _WEB_URL . '/du-an/' . $pj['slug'];
            ?>
            <div class="col-6 col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <a href="{{$url}}"><img src="{{$thumb}}" class="card-img-top" alt="{{$pj['name']}}" style="height:190px;object-fit:cover"/></a>
                    <div class="card-body">
                        <a href="{{$url}}" class="fw-semibold text-dark d-block mb-1">{{$pj['name']}}</a>
                        <?php if ($meta !== ''): ?><div class="small text-muted mb-1">{{$meta}}</div><?php endif; ?>
                        <div class="small text-muted">{{!empty($pj['summary']) ? mb_strimwidth($pj['summary'],0,90,'...') : ''}}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if ($pages > 1)
        <div class="pagination-sf">
            @for ($i = 1; $i <= $pages; $i++)
                <a class="{{($i===(int)$page)?'active':''}}" href="{{_WEB_URL.'/du-an?page='.$i}}">{{$i}}</a>
            @endfor
        </div>
        @endif
    @endif

</div>
</div>
</div>
