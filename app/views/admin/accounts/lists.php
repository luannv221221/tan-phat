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
        <h3 class="card-title"><i class="fas fa-landmark mr-2"></i>{{$page_name}}</h3>
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
                    <th style="width:12%">Số hiệu</th>
                    <th>Tên tài khoản</th>
                    <th style="width:15%">Loại</th>
                    <th style="width:100px" class="text-center">Chi tiết</th>
                    <th style="width:110px" class="text-center">Trạng thái</th>
                    <th style="width:130px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $key => $item)
                <tr>
                    <td class="text-center text-muted">{{$key+1}}</td>
                    <td><code>{{$item['code']}}</code></td>
                    <td>
                        <span style="padding-left:{{$item['depth']*22}}px">
                            {!! $item['depth']>0 ? '<i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-1"></i>' : '' !!}
                            {!! $item['depth']==0 ? '<strong>'.e($item['name']).'</strong>' : e($item['name']) !!}
                        </span>
                    </td>
                    <td>{{ isset($types[$item['type']]) ? $types[$item['type']] : $item['type'] }}</td>
                    <td class="text-center">
                        {!! $item['is_detail']==1 ? '<span class="badge badge-info">Chi tiết</span>' : '<span class="badge badge-light border">Tổng hợp</span>' !!}
                    </td>
                    <td class="text-center">
                        {!! $item['status']==1 ? '<span class="badge badge-success">Dùng</span>' : '<span class="badge badge-secondary">Ẩn</span>' !!}
                    </td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a onclick="return confirm('Xoá tài khoản này?')" href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" class="btn btn-danger btn-sm" title="Xoá"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có dữ liệu</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
