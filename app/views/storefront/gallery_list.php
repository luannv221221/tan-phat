<?php $assetImg = _WEB_URL . '/public/assets/storefront/images/'; ?>
<div class="content">
<div class="sf-page">
<div class="container">

    <div class="breadcrumb-sf"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / Thư viện</div>
    <h1 class="sf-page-title">Thư viện ảnh &amp; video</h1>

    @if (empty($list))
        <div class="empty-box">Chưa có album nào.</div>
    @else
        <div class="row g-3 mt-1">
            @foreach ($list as $g)
            <?php
            $thumb = !empty($g['cover']) ? media_url($g['cover']) : ($assetImg . 'placeholder.svg');
            $url = _WEB_URL . '/thu-vien/' . $g['slug'];
            ?>
            <div class="col-6 col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <a href="{{$url}}"><img src="{{$thumb}}" class="card-img-top" alt="{{$g['name']}}" style="height:190px;object-fit:cover"/></a>
                    <div class="card-body">
                        <a href="{{$url}}" class="fw-semibold text-dark d-block mb-1">{{$g['name']}}</a>
                        <div class="small text-muted">{{!empty($g['description']) ? mb_strimwidth($g['description'],0,90,'...') : ''}}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif

</div>
</div>
</div>
