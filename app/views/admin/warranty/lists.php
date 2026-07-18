<?php
$badge = ['received' => 'secondary', 'processing' => 'warning', 'done' => 'success', 'cancelled' => 'danger'];
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tools mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            @if (route('admin/'.$routeBase.'/add'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Lập phiếu</a>
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
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Tìm (số phiếu / khách / SĐT / serial)</label>
                <input type="text" name="q" class="form-control form-control-sm" value="{{$filterKeyword}}"/>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="mb-1 small">Từ ngày</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{$filterFrom}}"/>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="mb-1 small">Đến ngày</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{$filterTo}}"/>
            </div>
            <div class="form-group col-md-2 mb-2">
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button>
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-sm btn-default">Xoá</a>
            </div>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:120px">Số phiếu</th>
                    <th style="width:110px">Tiếp nhận</th>
                    <th>Khách hàng</th>
                    <th>Thiết bị / SP</th>
                    <th style="width:110px">Hẹn trả</th>
                    <th style="width:120px" class="text-center">Trạng thái</th>
                    <th style="width:70px" class="text-center">Xem</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <tr>
                    <td><code>{{$item['request_no']}}</code></td>
                    <td>{{$item['received_date']}}</td>
                    <td>{{!empty($item['partner_full']) ? $item['partner_full'] : (!empty($item['customer_name']) ? $item['customer_name'] : '—')}}<span class="text-muted small d-block">{{$item['phone']}}</span></td>
                    <td>{{!empty($item['product_name']) ? $item['product_name'] : '—'}}<span class="text-muted small">{{!empty($item['serial_no']) ? ' · SN: '.$item['serial_no'] : ''}}</span></td>
                    <td>{{!empty($item['appointment_date']) ? $item['appointment_date'] : '—'}}</td>
                    <td class="text-center"><span class="badge badge-{{$badge[$item['status']] ?? 'secondary'}}">{{$statuses[$item['status']] ?? $item['status']}}</span></td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-info btn-sm"><i class="fas fa-folder-open"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có phiếu bảo hành nào</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
