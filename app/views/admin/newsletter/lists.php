@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-envelope-open-text mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools"><span class="badge badge-success p-2">{{$countActive}} đang nhận</span></div>
    </div>
    <div class="card-body border-bottom">
        <form method="get" class="form-row">
            <div class="col-md-5"><input type="text" name="q" class="form-control form-control-sm" placeholder="Tìm email..." value="{{$filterKeyword}}"/></div>
            <div class="col-auto"><button class="btn btn-sm btn-info"><i class="fas fa-search"></i></button></div>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead><tr>
                <th style="width:60px" class="text-center">#</th>
                <th>Email</th>
                <th style="width:120px">Nguồn</th>
                <th style="width:150px">Ngày đăng ký</th>
                <th style="width:100px" class="text-center">Trạng thái</th>
                <th style="width:150px" class="text-center">Thao tác</th>
            </tr></thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $k => $r)
                <tr>
                    <td class="text-center text-muted">{{$k+1}}</td>
                    <td class="font-weight-bold">{{$r['email']}}</td>
                    <td class="text-muted">{{!empty($r['source'])?$r['source']:'—'}}</td>
                    <td class="text-muted">{{$r['create_at']}}</td>
                    <td class="text-center">{!! (int)$r['status']===1 ? '<span class="badge badge-success">Nhận</span>' : '<span class="badge badge-secondary">Tắt</span>' !!}</td>
                    <td class="text-center">
                        @if ((int)$r['status']===1)
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$r['id'].'?status=0'}}" class="btn btn-sm btn-outline-secondary" title="Tắt">Tắt</a>
                        @else
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$r['id'].'?status=1'}}" class="btn btn-sm btn-outline-success" title="Bật">Bật</a>
                        @endif
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$r['id']}}" onclick="return confirm('Xoá email này?')" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có người đăng ký</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
