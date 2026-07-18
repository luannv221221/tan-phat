@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-star mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            @if ($pending > 0)<span class="badge badge-warning">{{(int)$pending}} chờ duyệt</span>@endif
        </div>
    </div>
    <div class="card-body border-bottom">
        <form method="get" class="form-inline">
            <label class="mr-2 small">Lọc:</label>
            <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="">— Tất cả —</option>
                <option value="0" {{$filterStatus==='0'?'selected':''}}>Chờ duyệt</option>
                <option value="1" {{$filterStatus==='1'?'selected':''}}>Đã duyệt</option>
            </select>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th style="width:90px" class="text-center">Sao</th><th>Sản phẩm</th><th>Người đánh giá / Nội dung</th><th style="width:110px">Ngày</th><th style="width:110px" class="text-center">Trạng thái</th><th style="width:180px" class="text-center">Thao tác</th></tr></thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <tr>
                    <td class="text-center text-warning">{{str_repeat('★', (int)$item['rating']).str_repeat('☆', 5-(int)$item['rating'])}}</td>
                    <td><a href="{{_WEB_URL.'/san-pham/'.$item['part_slug']}}" target="_blank"><code>{{$item['part_code']}}</code> {{$item['part_name']}}</a></td>
                    <td><b>{{$item['author_name']}}</b><div class="text-muted small">{{$item['comment']}}</div></td>
                    <td class="small">{{$item['create_at']}}</td>
                    <td class="text-center">{!! $item['status']==1 ? '<span class="badge badge-success">Đã duyệt</span>' : '<span class="badge badge-warning">Chờ duyệt</span>' !!}</td>
                    <td class="text-center">
                        @if ($item['status']==0 && route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/approve/'.$item['id']}}" class="btn btn-success btn-sm" title="Duyệt"><i class="fas fa-check"></i></a>
                        @endif
                        @if ($item['status']==1 && route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/hide/'.$item['id']}}" class="btn btn-secondary btn-sm" title="Ẩn"><i class="fas fa-eye-slash"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá đánh giá này?')" class="btn btn-danger btn-sm" title="Xoá"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có đánh giá</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
