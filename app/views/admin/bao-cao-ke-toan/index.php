<?php
/**
 * KẾ TOÁN — Báo cáo. Bốn thẻ trên một màn, xem ghi chú đầu Baocaoketoan.php.
 *
 * Thẻ chuyển bằng ?tab= chứ không bằng JS: như vậy mỗi thẻ có một địa chỉ
 * riêng, bấm Quay lại của trình duyệt về đúng chỗ, và gửi link cho người khác
 * thì họ mở ra đúng bảng mình đang nói tới.
 */
$tien = function($n){ return number_format((float) $n, 0, ',', '.'); };
$qs = function($them) use ($tu, $den, $theo, $tab){
    return http_build_query(array_merge(
        ['tu' => $tu, 'den' => $den, 'theo' => $theo, 'tab' => $tab], $them));
};
$nhanKy = function($k) use ($theo){
    if ($theo === 'nam')  return 'Năm ' . $k;
    if ($theo === 'thang'){ $p = explode('-', $k); return 'Tháng ' . ($p[1] ?? '') . '/' . ($p[0] ?? ''); }
    $t = date_create($k);
    return $t ? $t->format('d/m/Y') : $k;
};
$the = ['ky' => 'Thu / chi theo kỳ', 'khach' => 'Khách còn nợ',
        'ncc' => 'Nợ nhà cung cấp',  'kd' => 'Kinh doanh'];
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-check-circle mr-1"></i> {{$msg}}
</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>{{$page_name}}</h3>
    </div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
            <div class="form-group col-md-3 mb-2">
                <label class="small mb-1">Từ ngày</label>
                <input type="date" name="tu" class="form-control form-control-sm" value="<?php echo e($tu); ?>">
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="small mb-1">Đến ngày</label>
                <input type="date" name="den" class="form-control form-control-sm" value="<?php echo e($den); ?>">
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="small mb-1">Gom theo</label>
                <select name="theo" class="form-control form-control-sm">
                    @foreach ($dsTheoKy as $k => $nhan)
                    <option value="{{$k}}" {{$theo==$k?'selected':''}}>{{$nhan}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2 mb-2">
                <button type="submit" class="btn btn-sm btn-primary btn-block">
                    <i class="fas fa-filter mr-1"></i> Xem
                </button>
            </div>
        </form>
        <?php /* Hai bảng công nợ KHÔNG dùng "từ ngày": còn nợ bao nhiêu là số
                 luỹ kế TỪ ĐẦU tới ngày chốt. Cắt từ đầu kỳ thì bỏ mất khoản nợ
                 cũ và ra con số nhỏ hơn thực tế. Nói ra ở đây, đừng để người
                 đọc tự đoán vì sao đổi "từ ngày" mà bảng không nhúc nhích. */ ?>
        <small class="text-muted">
            Hai bảng <strong>công nợ</strong> tính luỹ kế <strong>tới ngày</strong> đã chọn —
            ô "Từ ngày" không ảnh hưởng tới chúng.
        </small>
    </div>

    <div class="card-header p-0 pt-1 border-bottom-0">
        <ul class="nav nav-tabs">
            <?php foreach ($the as $k => $nhan): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === $k ? 'active' : ''; ?>"
                   href="<?php echo e(_WEB_URL . '/admin/' . $routeBase . '?' . $qs(['tab' => $k])); ?>">
                    <?php echo e($nhan); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php // ============ THẺ 1+2: Thu / chi theo kỳ ============ ?>
    <?php if ($tab === 'ky'): ?>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th>Kỳ</th>
                    <th style="width:90px" class="text-center">Số phiếu thu</th>
                    <th style="width:160px" class="text-right">Tổng thu</th>
                    <th style="width:90px" class="text-center">Số phiếu chi</th>
                    <th style="width:160px" class="text-right">Tổng chi</th>
                    <th style="width:160px" class="text-right">Chênh lệch</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($theoKy)): foreach ($theoKy as $r): ?>
                <tr>
                    <td class="font-weight-bold"><?php echo e($nhanKy($r['ky'])); ?></td>
                    <td class="text-center text-muted"><?php echo (int) $r['n_thu']; ?></td>
                    <td class="text-right text-success"><?php echo e($tien($r['thu'])); ?> ₫</td>
                    <td class="text-center text-muted"><?php echo (int) $r['n_chi']; ?></td>
                    <td class="text-right text-danger"><?php echo e($tien($r['chi'])); ?> ₫</td>
                    <td class="text-right font-weight-bold <?php echo $r['chenh'] < 0 ? 'text-danger' : ''; ?>">
                        <?php echo e($tien($r['chenh'])); ?> ₫
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i> Không có phiếu nào trong khoảng ngày này
                </td></tr>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($theoKy)): ?>
            <tfoot>
                <tr class="bg-light">
                    <th colspan="2" class="text-right">Cộng</th>
                    <th class="text-right text-success"><?php echo e($tien($tongThu)); ?> ₫</th>
                    <th></th>
                    <th class="text-right text-danger"><?php echo e($tien($tongChi)); ?> ₫</th>
                    <th class="text-right <?php echo ($tongThu - $tongChi) < 0 ? 'text-danger' : ''; ?>">
                        <?php echo e($tien($tongThu - $tongChi)); ?> ₫
                    </th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
    <?php endif; ?>

    <?php // ============ THẺ 3 + 4: Công nợ ============ ?>
    <?php if ($tab === 'khach' || $tab === 'ncc'):
        $laKhach = $tab === 'khach';
        $d = $laKhach ? $khachNo : $nccNo;
    ?>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th><?php echo $laKhach ? 'Khách hàng' : 'Nhà cung cấp'; ?></th>
                    <th style="width:120px">Điện thoại</th>
                    <th style="width:80px" class="text-center"><?php echo $laKhach ? 'Hoá đơn' : 'Phiếu nhập'; ?></th>
                    <th style="width:160px" class="text-right"><?php echo $laKhach ? 'Tiền hàng bán' : 'Tiền hàng mua'; ?></th>
                    <th style="width:160px" class="text-right"><?php echo $laKhach ? 'Đã thu' : 'Đã chi'; ?></th>
                    <th style="width:160px" class="text-right">Còn nợ</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($d['dong'])): foreach ($d['dong'] as $r): ?>
                <tr>
                    <td class="font-weight-bold">
                        <?php echo e($r['ten']); ?>
                        <?php if ($r['ma'] !== ''): ?><br><small class="text-muted"><code><?php echo e($r['ma']); ?></code></small><?php endif; ?>
                    </td>
                    <td><?php echo e($r['dt'] !== null && $r['dt'] !== '' ? $r['dt'] : '—'); ?></td>
                    <td class="text-center text-muted"><?php echo (int) $r['so_ct']; ?></td>
                    <td class="text-right"><?php echo e($tien($r['tien'])); ?> ₫</td>
                    <td class="text-right text-muted"><?php echo e($tien($r['tra'])); ?> ₫</td>
                    <td class="text-right font-weight-bold text-danger"><?php echo e($tien($r['con'])); ?> ₫</td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" class="text-center text-muted py-4">
                    <i class="fas fa-check-circle fa-2x d-block mb-2 text-success"></i>
                    Không còn khoản nợ nào tới ngày <?php echo e(date('d/m/Y', strtotime($den))); ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($d['dong'])): ?>
            <tfoot>
                <tr class="bg-light">
                    <th colspan="5" class="text-right">
                        Tổng <?php echo $laKhach ? 'khách còn nợ gara' : 'gara còn nợ nhà cung cấp'; ?>
                    </th>
                    <th class="text-right text-danger"><?php echo e($tien($d['tong'])); ?> ₫</th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <?php /* TRẢ THỪA / TRẢ TRƯỚC: không im lặng bỏ qua. Phiếu thu nhiều hơn
             tiền hàng thường là gán nhầm người, mà bảng công nợ chính là chỗ
             duy nhất lôi được chuyện đó ra. */ ?>
    <?php if (!empty($d['traThua'])): ?>
    <div class="card-body border-top">
        <h6 class="text-muted"><i class="fas fa-exclamation-triangle mr-1"></i>
            <?php echo $laKhach ? 'Đã thu nhiều hơn tiền hàng' : 'Đã chi nhiều hơn tiền hàng'; ?>
            — kiểm lại xem có phiếu nào gán nhầm đối tượng không
        </h6>
        <table class="table table-sm mb-0">
            <tbody>
            <?php foreach ($d['traThua'] as $r): ?>
                <tr>
                    <td><?php echo e($r['ten']); ?></td>
                    <td class="text-right">Tiền hàng <?php echo e($tien($r['tien'])); ?> ₫</td>
                    <td class="text-right"><?php echo $laKhach ? 'đã thu' : 'đã chi'; ?> <?php echo e($tien($r['tra'])); ?> ₫</td>
                    <td class="text-right text-warning font-weight-bold">thừa <?php echo e($tien(-$r['con'])); ?> ₫</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php // ============ THẺ 5: Kinh doanh ============ ?>
    <?php if ($tab === 'kd'): $k = $kinhDoanh; ?>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-6">
                <h5 class="mb-3"><i class="fas fa-shopping-cart mr-2"></i>Bán hàng</h5>
                <table class="table table-sm">
                    <tbody>
                        <tr><td>Doanh thu (chưa thuế)</td>
                            <td class="text-right"><?php echo e($tien($k['doanh_thu'])); ?> ₫</td></tr>
                        <tr><td class="text-muted">Thuế GTGT</td>
                            <td class="text-right text-muted"><?php echo e($tien($k['thue'])); ?> ₫</td></tr>
                        <tr><td>Giá vốn hàng bán</td>
                            <td class="text-right"><?php echo e($tien($k['gia_von'])); ?> ₫</td></tr>
                        <tr class="bg-light">
                            <th>Lãi gộp</th>
                            <th class="text-right <?php echo $k['lai_gop'] < 0 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo e($tien($k['lai_gop'])); ?> ₫
                            </th>
                        </tr>
                        <tr><td class="text-muted">Số hoá đơn đã ghi sổ</td>
                            <td class="text-right text-muted"><?php echo (int) $k['so_hd']; ?></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="col-lg-6">
                <h5 class="mb-3"><i class="fas fa-money-bill-wave mr-2"></i>Tiền mặt</h5>
                <table class="table table-sm">
                    <tbody>
                        <tr><td>Tổng thu</td>
                            <td class="text-right text-success"><?php echo e($tien($k['thu'])); ?> ₫</td></tr>
                        <tr><td>Tổng chi</td>
                            <td class="text-right text-danger"><?php echo e($tien($k['chi'])); ?> ₫</td></tr>
                        <tr class="bg-light">
                            <th>Chênh lệch</th>
                            <th class="text-right <?php echo $k['chenh'] < 0 ? 'text-danger' : ''; ?>">
                                <?php echo e($tien($k['chenh'])); ?> ₫
                            </th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php /* CHỖ DỄ SAI NHẤT, nói thẳng ra trên màn chứ không giấu trong
                 tài liệu: cộng hai khối lại là đếm doanh thu hai lần. */ ?>
        <div class="alert alert-warning mb-0">
            <i class="fas fa-info-circle mr-1"></i>
            <strong>Hai khối này không cộng vào nhau được.</strong>
            Phiếu thu tiền khách <em>không phải</em> doanh thu mới — nó là thu hồi khoản
            đã ghi doanh thu lúc xuất hoá đơn. Cộng "lãi gộp + chênh lệch tiền mặt"
            là đếm doanh thu hai lần.
        </div>
    </div>
    <?php endif; ?>
</div>
