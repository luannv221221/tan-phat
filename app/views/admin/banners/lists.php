<?php $pg = phan_trang((array) $dataList); $dataList = $pg['rows']; ?>
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
        <h3 class="card-title"><i class="fas fa-images mr-2"></i>{{$page_name}}</h3>
        @if (route('admin/'.$routeBase.'/add'))
        <div class="card-tools">
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Thêm {{$labelOne}}
            </a>
        </div>
        @endif
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:60px" class="text-center">STT</th>
                    <th style="width:180px" class="text-center">Ảnh</th>
                    <th>Tiêu đề</th>
                    <th style="width:22%">Liên kết</th>
                    <th style="width:80px" class="text-center">Thứ tự</th>
                    <th style="width:120px" class="text-center">Trạng thái</th>
                    <th style="width:130px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $key => $item)
                <tr>
                    <td class="text-center text-muted">{{$key+1}}</td>
                    <td class="text-center">
                        {!! !empty($item['image']) ? '<img src="'.e(media_url($item['image'])).'" alt="banner" style="height:44px;max-width:160px;object-fit:cover;border-radius:4px">' : '<span class="text-muted">-</span>' !!}
                    </td>
                    <td class="font-weight-bold">{{!empty($item['title']) ? $item['title'] : '—'}}</td>
                    <td>{!! !empty($item['link']) ? '<a href="'.e($item['link']).'" target="_blank"><code>'.e($item['link']).'</code></a>' : '<span class="text-muted">—</span>' !!}</td>
                    <td class="text-center">{{$item['sort_order']}}</td>
                    <td class="text-center">
                        {!! $item['status']==1 ? '<span class="badge badge-success">Hiển thị</span>' : '<span class="badge badge-secondary">Ẩn</span>' !!}
                    </td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a onclick="return confirm('Bạn có chắc chắn muốn xoá banner này?')" href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" class="btn btn-danger btn-sm" title="Xoá"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có banner nào
                    </td>
                </tr>
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
