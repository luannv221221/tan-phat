<div class="crumb"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / <a href="{{_WEB_URL.'/thu-vien'}}">Thư viện</a> / {{$gallery['name']}}</div>
<h1 class="page-title">{{$gallery['name']}}</h1>
@if (!empty($gallery['description']))
<p class="muted">{{$gallery['description']}}</p>
@endif

@if (empty($items))
    <div class="card"><div class="bd tc muted" style="padding:50px">Album chưa có nội dung.</div></div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;margin-top:14px">
    @foreach ($items as $it)
    <?php
    $isVideo = ($it['media_type'] === 'video');
    $yt = $isVideo ? youtube_id($it['video_url']) : '';
    ?>
    @if ($isVideo && $yt !== '')
    <div style="background:#000;border-radius:8px;overflow:hidden;aspect-ratio:16/9">
        <iframe width="100%" height="100%" src="https://www.youtube-nocookie.com/embed/{{$yt}}" title="video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div>
    @else
    <a href="{{media_url($it['image'])}}" target="_blank" style="display:block;aspect-ratio:1/1;border-radius:8px;overflow:hidden;border:1px solid #e6e6e6">
        <img src="{{media_url($it['image'])}}" alt="{{!empty($it['caption'])?$it['caption']:$gallery['name']}}" style="width:100%;height:100%;object-fit:cover"/>
    </a>
    @endif
    @endforeach
</div>
@endif

<div class="mt"><a class="btn btn-outline" href="{{_WEB_URL.'/thu-vien'}}">← Về thư viện</a></div>
