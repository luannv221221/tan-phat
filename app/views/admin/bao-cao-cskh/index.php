<?php $badge = ['received' => 'secondary', 'processing' => 'warning', 'done' => 'success', 'cancelled' => 'danger']; ?>
<div class="card card-outline card-info">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>{{$page_name}}</h3></div>
    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-3 mb-2"><label class="mb-1 small">Từ ngày</label><input type="date" name="from" class="form-control form-control-sm" value="{{$filterFrom}}"/></div>
            <div class="form-group col-md-3 mb-2"><label class="mb-1 small">Đến ngày</label><input type="date" name="to" class="form-control form-control-sm" value="{{$filterTo}}"/></div>
            <div class="form-group col-md-3 mb-2"><button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button> <a href="{{_WEB_URL.'/admin/bao-cao-cskh'}}" class="btn btn-sm btn-default">Xoá</a></div>
        </form>
    </div>
    <div class="card-body">
        <div class="row text-center mb-3">
            <div class="col-md-6"><div class="text-muted small">Tổng phiếu bảo hành</div><div class="h3 mb-0 text-primary">{{(int)$total}}</div></div>
            <div class="col-md-6"><div class="text-muted small">Tổng phí sửa chữa</div><div class="h3 mb-0">{{number_format((float)$totalFee,0,',','.')}} ₫</div></div>
        </div>
        <table class="table table-bordered mb-0" style="max-width:520px">
            <thead><tr><th>Trạng thái</th><th class="text-right">Số phiếu</th><th class="text-right">Phí</th></tr></thead>
            <tbody>
            @foreach ($statuses as $k => $label)
            <?php $row = isset($counts[$k]) ? $counts[$k] : ['total' => 0, 'fee' => 0]; ?>
            <tr>
                <td><span class="badge badge-{{$badge[$k] ?? 'secondary'}}">{{$label}}</span></td>
                <td class="text-right">{{(int)$row['total']}}</td>
                <td class="text-right">{{number_format((float)$row['fee'],0,',','.')}}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
