<?php $pg = phan_trang((array) $dataList); $dataList = $pg['rows']; ?>
<?php
$doCo = function($b){
    $b = (float) $b;
    if ($b >= 1048576) return round($b / 1048576, 1) . ' MB';
    if ($b >= 1024)    return round($b / 1024) . ' KB';
    return (int) $b . ' B';
};
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-check-circle mr-1"></i> {{$msg}}
</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}
</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-arrow-down mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            @if (route('admin/'.$routeBase.'/add'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Thêm tệp</a>
            @endif
        </div>
    </div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-4 mb-2">
                <label class="mb-1 small">Tìm theo tên</label>
                <input type="text" name="keyword" class="form-control form-control-sm" value="{{$filterKeyword}}" placeholder="VD: bảng giá"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Trạng thái</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="">— Tất cả —</option>
                    <option value="1" {{$filterStatus==='1'?'selected':''}}>Đang hiện</option>
                    <option value="0" {{$filterStatus==='0'?'selected':''}}>Đang ẩn</option>
                </select>
            </div>
            <div class="form-group col-md-3 mb-2">
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button>
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-sm btn-default">Xoá</a>
            </div>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Tên tệp</th>
                    <th style="width:120px">Dung lượng</th>
                    <th style="width:110px" class="text-center">Lượt tải</th>
                    <th style="width:140px" class="text-center">Ai tải được</th>
                    <th style="width:110px" class="text-center">Trạng thái</th>
                    <th style="width:120px" class="text-center">Sửa</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <tr>
                    <td>
                        <div class="font-weight-bold">{{$item['title']}}</div>
                        <small class="text-muted"><code>/tai-ve/{{$item['slug']}}</code></small>
                    </td>
                    <td><?php echo e($doCo($item['file_size'])); ?></td>
                    <td class="text-center">{{(int)$item['hits']}}</td>
                    <td class="text-center">
                        {!! (int)$item['require_login'] === 1
                            ? '<span class="badge badge-warning">Thành viên</span>'
                            : '<span class="badge badge-light border">Mọi người</span>' !!}
                    </td>
                    <td class="text-center">
                        {!! (int)$item['is_published'] === 1
                            ? '<span class="badge badge-success">Đang hiện</span>'
                            : '<span class="badge badge-secondary">Đang ẩn</span>' !!}
                    </td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-info btn-sm" title="Sửa"><i class="fas fa-pen"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}"
                           onclick="return confirm('Xoá tệp này? Tệp bị xoá khỏi máy chủ và mọi link đã phát ra sẽ ngừng chạy.')"
                           class="btn btn-danger btn-sm" title="Xoá"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="6" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                    Chưa có tệp nào. Thêm bảng giá, catalogue hoặc hồ sơ năng lực để khách tải về.
                </td></tr>
            @endif
            </tbody>
        </table>
    </div>
<?php if ($pg['total'] > 0): ?>
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem">
        {!! phan_trang_html($pg) !!}
    </div>
<?php endif; ?>
</div>
