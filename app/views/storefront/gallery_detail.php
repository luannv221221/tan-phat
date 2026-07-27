<div class="content">
<div class="sf-page">
<div class="container">

    <div class="breadcrumb-sf"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / <a href="{{_WEB_URL.'/thu-vien'}}">Thư viện</a> / {{$gallery['name']}}</div>
    <h1 class="sf-page-title">{{$gallery['name']}}</h1>
    @if (!empty($gallery['description']))
    <p class="text-muted">{{$gallery['description']}}</p>
    @endif

    @if (empty($items))
        <div class="empty-box">Album chưa có nội dung.</div>
    @else
    <div class="row g-3 mt-1">
        @foreach ($items as $it)
        <?php
        $isVideo = ($it['media_type'] === 'video');
        $yt = $isVideo ? youtube_id($it['video_url']) : '';
        ?>
        <div class="col-6 col-md-3">
            @if ($isVideo && $yt !== '')
            <div class="ratio ratio-16x9 rounded overflow-hidden bg-dark">
                <iframe src="https://www.youtube-nocookie.com/embed/{{$yt}}" title="video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
            @else
            <a href="{{media_url($it['image'])}}" target="_blank" class="d-block rounded overflow-hidden border">
                <img src="{{media_url($it['image'])}}" alt="{{!empty($it['caption'])?$it['caption']:$gallery['name']}}" style="width:100%;height:180px;object-fit:cover"/>
            </a>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <div class="mt-3"><a class="btn btn-outline-primary" href="{{_WEB_URL.'/thu-vien'}}"><i class="fa fa-angle-left"></i> Về thư viện</a></div>

</div>
</div>
</div>
