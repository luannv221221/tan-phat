<?php
/**
 * KẾ TOÁN — Danh sách phiếu thu / phiếu chi.
 *
 * MỘT view cho CẢ HAI màn: chúng khác nhau đúng mấy nhãn chữ, mà nhãn thì
 * controller truyền xuống ($labelMany, $routeBase, $loai). Chép đôi ra hai thư
 * mục view là sửa một bên quên bên kia.
 */
$pg = phan_trang((array) $dataList); $rows = $pg['rows'];
$tien = function($n){ return number_format((float) $n, 0, ',', '.'); };
$laThu = $loai === 'thu';
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

<div class="card card-outline <?php echo $laThu ? 'card-success' : 'card-danger'; ?>">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-receipt mr-2"></i>{{$page_name}}</h3>
        @if (route('admin/'.$routeBase.'/add'))
        <div class="card-tools">
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Lập {{$labelOne}}
            </a>
        </div>
        @endif
    </div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-2 mb-2">
                <label class="small mb-1">Từ ngày</label>
                <input type="date" name="tu" class="form-control form-control-sm" value="<?php echo e($loc['tu']); ?>">
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="small mb-1">Đến ngày</label>
                <input type="date" name="den" class="form-control form-control-sm" value="<?php echo e($loc['den']); ?>">
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="small mb-1">Loại phiếu</label>
                <select name="vtype_id" class="form-control form-control-sm">
                    <option value="">— Tất cả —</option>
                    <?php foreach ((array) $dsLoaiPhieu as $t): ?>
                    <option value="<?php echo (int) $t['id']; ?>" <?php echo (int) $loc['vtype_id'] === (int) $t['id'] ? 'selected' : ''; ?>>
                        <?php echo e($t['code'] . ' — ' . $t['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="small mb-1">Trạng thái</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="" <?php echo $loc['status'] === '' ? 'selected' : ''; ?>>Còn hiệu lực</option>
                    <option value="0" <?php echo $loc['status'] === 0 ? 'selected' : ''; ?>>Đã huỷ</option>
                </select>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="small mb-1">Tìm</label>
                <input type="text" name="keyword" class="form-control form-control-sm"
                       value="<?php echo e($loc['keyword']); ?>" placeholder="Số phiếu, tên, lý do">
            </div>
            <div class="form-group col-md-1 mb-2">
                <button type="submit" class="btn btn-sm btn-default btn-block"><i class="fas fa-filter"></i></button>
            </div>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:120px">Số phiếu</th>
                    <th style="width:100px">Ngày</th>
                    <th>{{$laThu ? 'Người nộp tiền' : 'Người nhận tiền'}}</th>
                    <th style="width:22%">Loại phiếu</th>
                    <th style="width:110px" class="text-center">Định khoản</th>
                    <th style="width:140px" class="text-right">Số tiền</th>
                    <th style="width:120px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($rows))
                @foreach ($rows as $r)
                <tr <?php echo (int) $r['status'] !== 1 ? 'class="text-muted"' : ''; ?>>
                    <td>
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$r['id']}}"><code>{{$r['voucher_no']}}</code></a>
                        <?php if ((int) $r['status'] !== 1): ?>
                        <span class="badge badge-secondary">đã huỷ</span>
                        <?php endif; ?>
                    </td>
                    <td>{{date('d/m/Y', strtotime($r['voucher_date']))}}</td>
                    <td>
                        {{!empty($r['dt_ten']) ? $r['dt_ten'] : '—'}}
                        <?php if (!empty($r['ly_do'])): ?>
                        <br><small class="text-muted"><?php echo e($r['ly_do']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="small">{{!empty($r['vtype_name']) ? $r['vtype_name'] : '—'}}</td>
                    <td class="text-center small">
                        <?php if (!empty($r['debit_code']) && !empty($r['credit_code'])): ?>
                        <code><?php echo e($r['debit_code']); ?></code>/<code><?php echo e($r['credit_code']); ?></code>
                        <?php else: ?>
                        <span class="text-danger">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right font-weight-bold">
                        <?php echo e($tien($r['amount'])); ?> ₫
                    </td>
                    <td class="text-center">
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/in/'.$r['id']}}" target="_blank" rel="noopener"
                           class="btn btn-default btn-sm" title="In phiếu"><i class="fas fa-print"></i></a>
                        @if (route('admin/'.$routeBase.'/edit/'.$r['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$r['id']}}"
                           class="btn btn-warning btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i> Không có phiếu nào trong khoảng ngày này
                </td></tr>
            @endif
            </tbody>
            <?php /* Tổng của CẢ BỘ LỌC, không phải của trang đang xem: người ta
                     lọc ra để biết tháng này thu bao nhiêu, cộng mỗi trang đầu
                     thì con số đó sai mà nhìn không ra. Chỉ cộng phiếu còn hiệu
                     lực. */ ?>
            @if (!empty($dataList))
            <tfoot>
                <tr class="bg-light">
                    <th colspan="5" class="text-right">Tổng {{$laThu ? 'thu' : 'chi'}} (phiếu còn hiệu lực)</th>
                    <th class="text-right <?php echo $laThu ? 'text-success' : 'text-danger'; ?>">
                        <?php echo e($tien($tong)); ?> ₫
                    </th>
                    <th></th>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
<?php if ($pg['total'] > 0): ?>
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem">
        {!! phan_trang_html($pg) !!}
    </div>
<?php endif; ?>
</div>
