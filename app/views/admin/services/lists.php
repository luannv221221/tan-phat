<?php $pg = phan_trang((array) $dataList); $dataList = $pg['rows']; ?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-screwdriver-wrench mr-2"></i>{{$page_name}}</h3>
        @if (route('admin/'.$routeBase.'/add'))
        <div class="card-tools"><a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Thêm {{$labelOne}}</a></div>
        @endif
    </div>

    <div class="card-body border-bottom py-2">
        <form method="get" action="{{_WEB_URL.'/admin/'.$routeBase}}" class="form-inline">
            <input type="text" name="keyword" class="form-control form-control-sm mr-2" style="min-width:260px"
                   placeholder="Tìm theo tên hoặc mã dịch vụ..." value="{{$keyword}}"/>
            <button class="btn btn-sm btn-outline-primary mr-2" type="submit"><i class="fas fa-search mr-1"></i> Tìm</button>
            @if ($keyword !== '')
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-sm btn-link">Xoá tìm kiếm</a>
            @endif
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:60px" class="text-center">STT</th>
                    <th style="width:14%">Mã</th>
                    <th>Tên dịch vụ</th>
                    <th style="width:18%">Nhóm</th>
                    <th style="width:16%" class="text-right">Giá</th>
                    <th style="width:100px" class="text-center">Áp dụng</th>
                    <th style="width:100px" class="text-center">Lên web</th>
                    <th style="width:120px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $key => $item)
                <tr>
                    <td class="text-center text-muted">{{$key+1}}</td>
                    <td><code>{{$item['code']}}</code></td>
                    <td class="font-weight-bold">{{$item['name']}}</td>
                    <td>{{!empty($item['category_name'])?$item['category_name']:'—'}}</td>
                    <td class="text-right">{{number_format((float)$item['price'],0,',','.')}} ₫</td>
                    <td class="text-center">{!! $item['status']==1 ? '<span class="badge badge-success">Có</span>' : '<span class="badge badge-secondary">Ngừng</span>' !!}</td>
                    <td class="text-center">{!! $item['show_on_web']==1 ? '<span class="badge badge-info">Có</span>' : '<span class="badge badge-light">Không</span>' !!}</td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a onclick="return confirm('Xoá dịch vụ này?')" href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" class="btn btn-danger btn-sm" title="Xoá"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có dịch vụ nào</td></tr>
            @endif
            </tbody>
        </table>
    </div>
<?php if ($pg['total'] > 0): ?>
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem">
        {!! phan_trang_html($pg, _WEB_URL.'/admin/'.$routeBase, 'dịch vụ') !!}
    </div>
<?php endif; ?>
</div>
