<?php $pg = phan_trang((array) $dataList); $dataList = $pg['rows']; ?>
<?php
$badge = ['draft' => 'secondary', 'sent' => 'info', 'accepted' => 'success', 'rejected' => 'danger'];
?>
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
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            @if (route('admin/'.$routeBase.'/add'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Lập báo giá</a>
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
                    <th style="width:130px">Số báo giá</th>
                    <th style="width:110px">Ngày</th>
                    <th>Khách hàng</th>
                    <th style="width:15%" class="text-right">Tổng tiền</th>
                    <th style="width:120px" class="text-center">Trạng thái</th>
                    <th style="width:150px" class="text-center">Hoá đơn</th>
                    <th style="width:80px" class="text-center">Xem</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <tr>
                    <td><code>{{$item['quote_no']}}</code></td>
                    <td>{{$item['quote_date']}}</td>
                    <td>{{!empty($item['customer_full']) ? $item['customer_full'] : (!empty($item['customer_name']) ? $item['customer_name'] : '—')}}</td>
                    <td class="text-right font-weight-bold">{{number_format((float)$item['total_amount'],0,',','.')}} ₫</td>
                    <td class="text-center"><span class="badge badge-{{$badge[$item['status']] ?? 'secondary'}}">{{$statuses[$item['status']] ?? $item['status']}}</span></td>
                    <?php
                    /* BA TRƯỜNG HỢP, viết bằng PHP thuần vì bộ biên dịch view
                       không có @elseif (xem core/Template.php):
                         1. đã thành hoá đơn  -> đường dẫn sang hoá đơn đó
                         2. còn chuyển được   -> nút Chuyển ngay tại đây
                         3. khách đã từ chối  -> gạch ngang, không mời bấm
                       Gác bằng đúng quyền mà Quotations::convert() gác
                       (`sales-invoices/add`), để không hiện nút rồi bấm vào
                       lại nảy ra trang "không có quyền". */
                    $qid = (int) $item['id'];
                    $hd  = isset($hoaDonTheoBaoGia[$qid]) ? $hoaDonTheoBaoGia[$qid] : null;
                    $urlChuyen = route('admin/' . $routeBase . '/convert/' . $qid);
                    $duocChuyen = $urlChuyen !== false && route('admin/sales-invoices/add') !== false
                                  && $item['status'] !== 'rejected';
                    ?>
                    <td class="text-center">
                    <?php if (!empty($hd)): ?>
                        <?php $urlHd = route('admin/sales-invoices/edit/' . (int) $hd['id']); ?>
                        <?php if ($urlHd !== false): ?>
                        <a href="<?php echo e($urlHd); ?>" class="btn btn-success btn-sm" title="Mở hoá đơn <?php echo e($hd['invoice_no']); ?>"><i class="fas fa-file-invoice mr-1"></i> <?php echo e($hd['invoice_no']); ?></a>
                        <?php else: ?>
                        <code><?php echo e($hd['invoice_no']); ?></code>
                        <?php endif; ?>
                    <?php elseif ($duocChuyen): ?>
                        <a href="<?php echo e($urlChuyen); ?>" onclick="return confirm('Tạo hoá đơn bán (nháp) từ báo giá <?php echo e($item['quote_no']); ?>?')" class="btn btn-outline-primary btn-sm" title="Chuyển thành hoá đơn"><i class="fas fa-file-export mr-1"></i> Chuyển</a>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                    </td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-info btn-sm" title="Mở"><i class="fas fa-folder-open"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có báo giá nào</td></tr>
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
