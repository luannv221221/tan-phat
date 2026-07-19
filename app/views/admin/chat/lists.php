@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-comments mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">{!! $unread > 0 ? '<span class="badge badge-danger">'.(int)$unread.' chưa đọc</span>' : '' !!}</div>
    </div>
    <div class="card-body border-bottom">
        <form method="get" class="form-inline">
            <label class="mr-2 small">Lọc:</label>
            <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="">— Tất cả —</option>
                <option value="open" {{$filterStatus=='open'?'selected':''}}>Đang mở</option>
                <option value="closed" {{$filterStatus=='closed'?'selected':''}}>Đã đóng</option>
            </select>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th style="width:60px" class="text-center">#</th><th>Khách</th><th style="width:160px">Tin cuối</th><th style="width:110px" class="text-center">Trạng thái</th><th style="width:90px" class="text-center">Xem</th></tr></thead>
            <tbody>
            @if (!empty($convs))
                @foreach ($convs as $c)
                <tr class="{{$c['unread']==1 && $c['status']=='open' ? 'font-weight-bold' : ''}}">
                    <td class="text-center text-muted">{{$c['id']}}</td>
                    <td>
                        {!! $c['unread']==1 && $c['status']=='open' ? '<i class="fas fa-circle text-danger mr-1" style="font-size:8px"></i>' : '' !!}
                        {{!empty($c['member_name']) ? $c['member_name'] : (!empty($c['guest_name']) ? $c['guest_name'] : 'Khách')}}
                        <span class="text-muted small">{{!empty($c['guest_phone']) ? ' · '.$c['guest_phone'] : ''}}</span>
                    </td>
                    <td class="small text-muted">{{$c['last_message_at']}}</td>
                    <td class="text-center">{!! $c['status']=='open' ? '<span class="badge badge-success">Mở</span>' : '<span class="badge badge-secondary">Đóng</span>' !!}</td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/1'))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/view/'.$c['id']}}" class="btn btn-info btn-sm"><i class="fas fa-comment-dots"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có hội thoại</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
