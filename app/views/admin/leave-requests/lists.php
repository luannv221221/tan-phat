<?php $badge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger']; ?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plane-departure mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            {!! $pending > 0 ? '<span class="badge badge-warning mr-2">'.(int)$pending.' chờ duyệt</span>' : '' !!}
            @if (route('admin/'.$routeBase.'/add'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Lập đơn</a>
            @endif
        </div>
    </div>
    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Trạng thái</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="">— Tất cả —</option>
                    @foreach ($statuses as $k => $label)
                    <option value="{{$k}}" {{$filterStatus==$k?'selected':''}}>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3 mb-2"><label class="mb-1 small">Từ ngày</label><input type="date" name="from" class="form-control form-control-sm" value="{{$filterFrom}}"/></div>
            <div class="form-group col-md-3 mb-2"><label class="mb-1 small">Đến ngày</label><input type="date" name="to" class="form-control form-control-sm" value="{{$filterTo}}"/></div>
            <div class="form-group col-md-3 mb-2"><button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button> <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-sm btn-default">Xoá</a></div>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead><tr><th>Nhân viên</th><th style="width:130px">Loại</th><th style="width:110px">Từ ngày</th><th style="width:110px">Đến ngày</th><th style="width:70px" class="text-center">Số ngày</th><th style="width:110px" class="text-center">Trạng thái</th><th style="width:200px" class="text-center">Thao tác</th></tr></thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <tr>
                    <td><b>{{$item['emp_name']}}</b> <span class="text-muted small">({{$item['emp_code']}})</span><div class="text-muted small">{{!empty($item['reason'])?$item['reason']:''}}</div></td>
                    <td>{{$types[$item['leave_type']] ?? $item['leave_type']}}</td>
                    <td>{{$item['from_date']}}</td>
                    <td>{{$item['to_date']}}</td>
                    <td class="text-center">{{rtrim(rtrim(number_format((float)$item['days'],1,'.',''),'0'),'.')}}</td>
                    <td class="text-center"><span class="badge badge-{{$badge[$item['status']] ?? 'secondary'}}">{{$statuses[$item['status']] ?? $item['status']}}</span></td>
                    <td class="text-center">
                        @if ($item['status']=='pending' && route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=approved'}}" class="btn btn-success btn-sm" title="Duyệt"><i class="fas fa-check"></i></a>
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=rejected'}}" class="btn btn-secondary btn-sm" title="Từ chối"><i class="fas fa-times"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá đơn này?')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có đơn nghỉ phép</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
