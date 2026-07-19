@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif

<div class="row justify-content-center"><div class="col-lg-8">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-envelope mr-2"></i>{{!empty($item['subject'])?$item['subject']:'(Không tiêu đề)'}}</h3>
            <div class="card-tools">{!! $item['status']==='handled' ? '<span class="badge badge-success p-2">Đã xử lý</span>' : '<span class="badge badge-warning p-2">Mới</span>' !!}</div>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Người gửi</dt><dd class="col-sm-9">{{$item['name']}}</dd>
                <dt class="col-sm-3">Điện thoại</dt><dd class="col-sm-9">{{!empty($item['phone'])?$item['phone']:'—'}}</dd>
                <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{!empty($item['email'])?$item['email']:'—'}}</dd>
                <dt class="col-sm-3">Thời gian</dt><dd class="col-sm-9">{{$item['create_at']}}</dd>
                <dt class="col-sm-3">Nội dung</dt><dd class="col-sm-9" style="white-space:pre-line">{{$item['message']}}</dd>
            </dl>
        </div>
        <div class="card-footer">
            @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                @if ($item['status']!=='handled')
                <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=handled'}}" class="btn btn-success"><i class="fas fa-check mr-1"></i> Đánh dấu đã xử lý</a>
                @else
                <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=new'}}" class="btn btn-outline-secondary">Đánh dấu chưa xử lý</a>
                @endif
            @endif
            @if (!empty($item['phone']))
            <a href="tel:{{$item['phone']}}" class="btn btn-outline-info"><i class="fas fa-phone mr-1"></i> Gọi</a>
            @endif
            @if (!empty($item['email']))
            <a href="mailto:{{$item['email']}}" class="btn btn-outline-info"><i class="fas fa-reply mr-1"></i> Trả lời email</a>
            @endif
            @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá liên hệ này?')" class="btn btn-outline-danger"><i class="fas fa-trash mr-1"></i> Xoá</a>
            @endif
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">← Về hộp thư</a>
        </div>
    </div>
</div></div>
