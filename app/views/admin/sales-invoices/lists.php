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

<div class="card card-outline card-success">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            @if (route('admin/'.$routeBase.'/add'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Lập hoá đơn</a>
            @endif
        </div>
    </div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Trạng thái</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="">— Tất cả —</option>
                    <option value="0" {{$filterStatus==='0'?'selected':''}}>Nháp</option>
                    <option value="1" {{$filterStatus==='1'?'selected':''}}>Đã ghi sổ</option>
                </select>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Từ ngày</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{$filterFrom}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Đến ngày</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{$filterTo}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button>
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-sm btn-default">Xoá</a>
            </div>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:130px">Số hoá đơn</th>
                    <th style="width:110px">Ngày</th>
                    <th>Khách hàng / Kho</th>
                    <th style="width:15%" class="text-right">Tổng thanh toán</th>
                    <th style="width:120px" class="text-center">Trạng thái</th>
                    <th style="width:80px" class="text-center">Xem</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <tr>
                    <td><code>{{$item['invoice_no']}}</code></td>
                    <td>{{$item['invoice_date']}}</td>
                    <td>
                        {{!empty($item['customer_full']) ? $item['customer_full'] : (!empty($item['customer_name']) ? $item['customer_name'] : 'Khách vãng lai')}}
                        <span class="text-muted small d-block">{{$item['warehouse_name']}}</span>
                    </td>
                    <td class="text-right font-weight-bold">{{number_format((float)$item['total_amount'],0,',','.')}} ₫</td>
                    <td class="text-center">
                        {!! $item['status']==1 ? '<span class="badge badge-primary">Đã ghi sổ</span>' : '<span class="badge badge-secondary">Nháp</span>' !!}
                    </td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-info btn-sm" title="Mở"><i class="fas fa-folder-open"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có hoá đơn nào</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
