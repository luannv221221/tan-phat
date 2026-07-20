@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-newspaper mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            @if (route('admin/'.$routeBase.'/add'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Thêm tin</a>
            @endif
        </div>
    </div>
    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Trạng thái</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="">— Tất cả —</option>
                    <option value="1" {{$filterStatus==='1'?'selected':''}}>Đã đăng</option>
                    <option value="0" {{$filterStatus==='0'?'selected':''}}>Nháp</option>
                </select>
            </div>
            <div class="form-group col-md-4 mb-2"><label class="mb-1 small">Tìm tiêu đề</label><input type="text" name="q" class="form-control form-control-sm" value="{{$filterKeyword}}"/></div>
            <div class="form-group col-md-3 mb-2"><button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button> <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-sm btn-default">Xoá</a></div>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th style="width:50px" class="text-center">ID</th><th>Tiêu đề</th><th style="width:160px">Danh mục</th><th style="width:90px" class="text-center">Lượt xem</th><th style="width:120px" class="text-center">Trạng thái</th><th style="width:130px" class="text-center">Thao tác</th></tr></thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <tr>
                    <td class="text-center text-muted">{{$item['id']}}</td>
                    <td><b>{{$item['title']}}</b><div class="text-muted small"><code>{{$item['slug']}}</code></div></td>
                    <td>{{!empty($item['category_name'])?$item['category_name']:'—'}}</td>
                    <td class="text-center">{{(int)$item['view_count']}}</td>
                    <td class="text-center">{!! $item['is_published']==1 ? '<span class="badge badge-success">Đã đăng</span>' : '<span class="badge badge-secondary">Nháp</span>' !!}</td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá tin này?')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có tin nào</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
