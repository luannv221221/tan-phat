<?php
/**
 * QUẢN LÝ SERIAL — tra cứu để bảo hành. Xem ghi chú đầu Serials.php.
 *
 * MÀN CHỈ ĐỌC: không nút Thêm, không nút Sửa, không nút Xoá. Serial khai ở màn
 * Đơn mua — sửa ở đây là tách serial khỏi nguồn mua, mà nguồn mua đúng là thứ
 * duy nhất màn này có ích.
 */
$pg = phan_trang((array) $dataList); $rows = $pg['rows'];
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-check-circle mr-1"></i> {{$msg}}
</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-barcode mr-2"></i>{{$page_name}}</h3>
    </div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-6 mb-0">
                <label class="mb-1 small">Số serial</label>
                <input type="text" name="q" class="form-control" autofocus
                       value="<?php echo e($q); ?>"
                       placeholder="Gõ số serial đọc trên tem — gõ vài ký tự cuối cũng tìm được"/>
            </div>
            <div class="form-group col-md-6 mb-0">
                <button type="submit" class="btn btn-info"><i class="fas fa-search mr-1"></i> Tra cứu</button>
                <?php if ($q !== ''): ?>
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Xoá tìm</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if ($daTra && empty($rows)): ?>
    <div class="card-body">
        <div class="alert alert-warning mb-0">
            <i class="fas fa-exclamation-circle mr-1"></i>
            Không tìm thấy serial <b><?php echo e($q); ?></b>.
            Serial chỉ có khi đã khai ở <em>Mua hàng › Đơn mua</em> — hàng nhập thẳng bằng
            phiếu nhập kho thì không có serial.
        </div>
    </div>
    <?php endif; ?>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:16%">Serial</th>
                    <th>Hàng hoá</th>
                    <th style="width:18%">Mua của</th>
                    <th style="width:120px">Đơn mua</th>
                    <th style="width:110px">Ngày mua</th>
                    <th style="width:14%">Phiếu nhập</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($rows))
                @foreach ($rows as $item)
                <tr>
                    <td>
                        <?php /* Dòng khớp ĐÚNG nguyên chuỗi được tô đậm và đẩy lên đầu:
                                 người ở quầy gõ cả serial trên tem thì cần một câu trả
                                 lời dứt khoát, không phải một danh sách gần đúng. */ ?>
                        <?php if (!empty($item['khop_dung'])): ?>
                        <span class="badge badge-success mr-1">khớp</span>
                        <code class="font-weight-bold"><?php echo e($item['serial']); ?></code>
                        <?php else: ?>
                        <code><?php echo e($item['serial']); ?></code>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="text-muted"><?php echo e($item['part_code']); ?></span>
                        <?php echo e($item['part_name']); ?>
                    </td>
                    <td>{{!empty($item['partner_full']) ? $item['partner_full'] : $item['partner_name']}}</td>
                    <td>
                        @if (route('admin/purchase-orders'))
                        <a href="{{_WEB_URL.'/admin/purchase-orders/edit/'.(int)$item['po_id']}}">{{$item['po_no']}}</a>
                        @else
                        {{$item['po_no']}}
                        @endif
                    </td>
                    <td>{{$item['order_date']}}</td>
                    <td class="small">
                        <?php if (!empty($item['pnk_no'])): ?>
                        {{$item['pnk_no']}}
                        <?php else: ?>
                        <span class="text-muted">chưa nhập kho</span>
                        <?php endif; ?>
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="6" class="text-center text-muted py-4">
                    <i class="fas fa-barcode fa-2x d-block mb-2"></i>
                    <?php echo $daTra ? 'Không có kết quả' : 'Chưa có serial nào — khai ở Mua hàng › Đơn mua'; ?>
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
        Tra một số serial ra <strong>mua của ai, theo đơn nào, ngày nào</strong> — để biết phụ tùng
        khách mang tới có còn trong thời gian bảo hành không.
        <?php if (!$daTra): ?>Đang hiện những serial mới khai gần đây.<?php endif; ?>
    </div>
</div>
