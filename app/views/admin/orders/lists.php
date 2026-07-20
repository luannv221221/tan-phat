<?php $badge = ['new' => 'warning', 'confirmed' => 'info', 'shipping' => 'primary', 'completed' => 'success', 'cancelled' => 'danger']; ?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-shopping-bag mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">{!! $newCount > 0 ? '<span class="badge badge-warning">'.(int)$newCount.' đơn mới</span>' : '' !!}</div>
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
            <div class="form-group col-md-4 mb-2"><label class="mb-1 small">Tìm (mã/tên/SĐT)</label><input type="text" name="q" class="form-control form-control-sm" value="{{$filterKeyword}}"/></div>
            <div class="form-group col-md-3 mb-2"><button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button> <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-sm btn-default">Xoá</a></div>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead><tr><th style="width:120px">Mã đơn</th><th style="width:110px">Ngày</th><th>Khách hàng</th><th style="width:120px">Điện thoại</th><th style="width:14%" class="text-right">Tổng tiền</th><th style="width:130px">Thanh toán</th><th style="width:120px" class="text-center">Trạng thái</th><th style="width:70px" class="text-center">Xem</th></tr></thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <tr>
                    <td><code>{{$item['order_no']}}</code></td>
                    <td>{{substr($item['create_at'],0,10)}}</td>
                    <td class="font-weight-bold">{{$item['customer_name']}}</td>
                    <td>{{$item['phone']}}</td>
                    <td class="text-right font-weight-bold">{{number_format((float)$item['total_amount'],0,',','.')}} ₫</td>
                    <td>{{$payments[$item['payment_method']] ?? $item['payment_method']}}</td>
                    <td class="text-center"><span class="badge badge-{{$badge[$item['status']] ?? 'secondary'}}">{{$statuses[$item['status']] ?? $item['status']}}</span></td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-info btn-sm"><i class="fas fa-folder-open"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có đơn hàng</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
