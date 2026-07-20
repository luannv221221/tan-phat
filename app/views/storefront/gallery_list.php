<div class="crumb"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / Thư viện</div>
<h1 class="page-title">Thư viện ảnh &amp; video</h1>

@if (empty($list))
    <div class="card"><div class="bd tc muted" style="padding:50px">Chưa có album nào.</div></div>
@else
    <div class="grid" style="margin-top:14px">
        @foreach ($list as $g)
        <?php $thumb = !empty($g['cover']) ? '<img src="'.e(media_url($g['cover'])).'" alt="'.e($g['name']).'" style="width:100%;height:100%;object-fit:cover"/>' : '🖼'; ?>
        <div class="pcard">
            <a class="thumb" href="{{_WEB_URL.'/thu-vien/'.$g['slug']}}">{!! $thumb !!}</a>
            <div class="info">
                <a class="pname" href="{{_WEB_URL.'/thu-vien/'.$g['slug']}}">{{$g['name']}}</a>
                <div class="muted" style="font-size:13px">{{!empty($g['description'])?$g['description']:''}}</div>
            </div>
        </div>
        @endforeach
    </div>
@endif
