@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-id-badge mr-2"></i>{{$page_name}}</h3>
        @if (route('admin/'.$routeBase.'/add'))
        <div class="card-tools"><a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Thêm {{$labelOne}}</a></div>
        @endif
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th style="width:60px" class="text-center">STT</th><th>Tên chức vụ</th><th>Mô tả</th><th style="width:110px" class="text-center">Trạng thái</th><th style="width:130px" class="text-center">Thao tác</th></tr></thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $key => $item)
                <tr>
                    <td class="text-center text-muted">{{$key+1}}</td>
                    <td class="font-weight-bold">{{$item['name']}}</td>
                    <td class="text-muted">{{!empty($item['description'])?$item['description']:'—'}}</td>
                    <td class="text-center">{!! $item['status']==1 ? '<span class="badge badge-success">Bật</span>' : '<span class="badge badge-secondary">Ẩn</span>' !!}</td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá?')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có chức vụ</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
