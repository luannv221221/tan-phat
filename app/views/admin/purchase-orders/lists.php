<?php
/** ĐƠN MUA — danh sách. Xem ghi chú đầu Purchaseorders.php. */
$pg = phan_trang((array) $dataList); $rows = $pg['rows'];
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
        @if (route('admin/'.$routeBase.'/add'))
        <div class="card-tools">
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Lập đơn mua
            </a>
        </div>
        @endif
    </div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Trạng thái</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="">— Tất cả —</option>
                    @foreach ($trangThai as $k => $nhan)
                    <option value="{{$k}}" {{(string)$loc['status']===(string)$k?'selected':''}}>{{$nhan}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Từ ngày</label>
                <input type="date" name="tu" class="form-control form-control-sm" value="{{$loc['tu']}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Đến ngày</label>
                <input type="date" name="den" class="form-control form-control-sm" value="{{$loc['den']}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button>
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-sm btn-default">Xoá</a>
            </div>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:120px">Số đơn</th>
                    <th style="width:110px">Ngày mua</th>
                    <th>Nhà cung cấp</th>
                    <th style="width:16%">HĐ nhà cung cấp</th>
                    <th style="width:110px" class="text-center">Trạng thái</th>
                    <th style="width:150px">Phiếu nhập kho</th>
                    <th style="width:110px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($rows))
                @foreach ($rows as $item)
                <tr>
                    <td class="font-weight-bold">{{$item['po_no']}}</td>
                    <td>{{$item['order_date']}}</td>
                    <td>
                        {{!empty($item['partner_full']) ? $item['partner_full'] : $item['partner_name']}}
                    </td>
                    <td class="small">
                        <?php if (!empty($item['invoice_no'])): ?>
                        <?php echo e($item['invoice_no']); ?>
                        <?php endif; ?>
                        <?php if (!empty($item['invoice_url'])): ?>
                        <br><a href="<?php echo e($item['invoice_url']); ?>" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-external-link-alt mr-1"></i>Link</a>
                        <?php endif; ?>
                        <?php if (!empty($item['invoice_file'])): ?>
                        <br><a href="<?php echo e(media_url($item['invoice_file'])); ?>" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-paperclip mr-1"></i>Bản chụp</a>
                        <?php endif; ?>
                        <?php if (empty($item['invoice_no']) && empty($item['invoice_url']) && empty($item['invoice_file'])): ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        {!! (int)$item['status'] === 1
                            ? '<span class="badge badge-success">Đã chốt</span>'
                            : '<span class="badge badge-secondary">Nháp</span>' !!}
                    </td>
                    <td class="small">
                        <?php if (!empty($item['pnk_no'])): ?>
                        <a href="{{_WEB_URL.'/admin/goods-receipts/edit/'.(int)$item['goods_receipt_id']}}">
                            <i class="fas fa-dolly-flatbed mr-1"></i>{{$item['pnk_no']}}</a>
                        <?php else: ?>
                        <span class="text-muted">chưa nhập kho</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}"
                           class="btn btn-warning btn-sm" title="Mở đơn"><i class="fas fa-edit"></i></a>
                        @endif
                        <?php /* Xoá CHỈ khi chưa sinh phiếu nhập — controller chặn lần
                                 nữa, nhưng ẩn nút đi thì người dùng không phải thử rồi
                                 nhận thông báo từ chối. */ ?>
                        @if (empty($item['goods_receipt_id']) && route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a onclick="return confirm('Xoá đơn mua {{$item['po_no']}}? Serial đã nhập theo đơn này cũng mất.')"
                           href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}"
                           class="btn btn-danger btn-sm" title="Xoá"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có đơn mua nào
                </td></tr>
            @endif
            </tbody>
        </table>
    </div>
<?php if ($pg['total'] > 0): ?>
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem">
        {!! phan_trang_html($pg) !!}
    </div>
<?php endif; ?>
    <div class="card-footer text-muted small">
        Đơn mua ghi lại <strong>mua gì của ai</strong>, kèm serial từng chiếc và hoá đơn nhà cung cấp.
        Hàng về tới nơi thì mở đơn ra bấm <em>Tạo phiếu nhập kho</em> — tồn kho đổi ở bước đó,
        không phải lúc khai đơn.
    </div>
</div>
