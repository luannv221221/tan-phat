@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bars mr-2"></i>{{$page_name}}</h3>
        @if (route('admin/'.$routeBase.'/add'))
        <div class="card-tools"><a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Thêm {{$labelOne}}</a></div>
        @endif
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>Nhãn</th><th style="width:30%">Liên kết</th><th style="width:90px" class="text-center">Thứ tự</th><th style="width:100px" class="text-center">Trạng thái</th><th style="width:130px" class="text-center">Thao tác</th></tr></thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <?php $pad = (int) $item['depth'] * 24; ?>
                <tr>
                    <td style="padding-left:{{$pad+12}}px">{!! $item['depth']>0 ? '<i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-1"></i>' : '' !!}<b>{{$item['label']}}</b></td>
                    <td class="text-muted"><code>{{!empty($item['url'])?$item['url']:'/'}}</code>{!! $item['target']==='_blank' ? ' <span class="badge badge-light">tab mới</span>' : '' !!}</td>
                    <td class="text-center">{{$item['sort_order']}}</td>
                    <td class="text-center">{!! $item['status']==1 ? '<span class="badge badge-success">Bật</span>' : '<span class="badge badge-secondary">Ẩn</span>' !!}</td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá mục này (và mục con)?')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có mục menu</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
