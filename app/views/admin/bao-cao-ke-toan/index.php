<?php
/**
 * KẾ TOÁN — Báo cáo. Bốn thẻ trên một màn, xem ghi chú đầu Baocaoketoan.php.
 *
 * Thẻ chuyển bằng ?tab= chứ không bằng JS: như vậy mỗi thẻ có một địa chỉ
 * riêng, bấm Quay lại của trình duyệt về đúng chỗ, và gửi link cho người khác
 * thì họ mở ra đúng bảng mình đang nói tới.
 */
$tien = function($n){ return number_format((float) $n, 0, ',', '.'); };
$ngayVN = function($d){ $t = date_create((string) $d); return $t ? $t->format('d/m/Y') : (string) $d; };
$qs = function($them) use ($tu, $den, $theo, $tab, $quy){
    return http_build_query(array_merge(
        ['tu' => $tu, 'den' => $den, 'theo' => $theo, 'tab' => $tab, 'quy' => $quy], $them));
};
$nhanKy = function($k) use ($theo){
    if ($theo === 'nam')  return 'Năm ' . $k;
    if ($theo === 'thang'){ $p = explode('-', $k); return 'Tháng ' . ($p[1] ?? '') . '/' . ($p[0] ?? ''); }
    $t = date_create($k);
    return $t ? $t->format('d/m/Y') : $k;
};
$the = ['ky' => 'Thu / chi theo kỳ', 'quy' => 'Sổ quỹ', 'khach' => 'Khách còn nợ',
        'ncc' => 'Nợ nhà cung cấp',  'kd' => 'Kinh doanh'];
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
            <?php /* Thẻ Sổ quỹ không gom theo kỳ mà chọn QUỸ. Giữ `theo` (ẩn) để
                     sang lại thẻ Thu / chi vẫn đúng cách gom đã chọn. */ ?>
            <?php if ($tab === 'quy'): ?>
            <input type="hidden" name="theo" value="<?php echo e($theo); ?>">
            <div class="form-group col-md-3 mb-2">
                <label class="small mb-1">Quỹ</label>
                <select name="quy" class="form-control form-control-sm">
                    <?php foreach ($dsQuy as $k => $q): ?>
                    <option value="<?php echo e($k); ?>" <?php echo $quy === $k ? 'selected' : ''; ?>>
                        <?php echo e($q['ten'] . ' (TK ' . $q['tk'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="quy" value="<?php echo e($quy); ?>">
            <div class="form-group col-md-3 mb-2">
                <label class="small mb-1">Gom theo</label>
                <select name="theo" class="form-control form-control-sm">
                    @foreach ($dsTheoKy as $k => $nhan)
                    <option value="{{$k}}" {{$theo==$k?'selected':''}}>{{$nhan}}</option>
                    @endforeach
                </select>
            </div>
            <?php endif; ?>
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

    <?php // ============ THẺ 6: Sổ quỹ (tiền mặt / tiền gửi ngân hàng) ============ ?>
    <?php if ($tab === 'quy' && !empty($soQuy)): $s = $soQuy; ?>
    <div class="card-body border-bottom py-2">
        <div class="d-flex flex-wrap align-items-center">
            <div class="mr-3 mb-1">
                <i class="fas fa-piggy-bank mr-1 text-muted"></i>
                Số dư ban đầu quỹ <strong><?php echo e($s['ten']); ?></strong>:
                <?php if ($s['ngay_ban_dau'] !== null): ?>
                <strong><?php echo e($tien($s['so_du_ban_dau'])); ?> ₫</strong>
                tính từ ngày <strong><?php echo e($ngayVN($s['ngay_ban_dau'])); ?></strong>
                <?php else: ?>
                <span class="text-muted">chưa khai — sổ tính từ phiếu đầu tiên, bắt đầu từ 0.</span>
                <?php endif; ?>
            </div>
            <?php if ($suaSoDu): ?>
            <button type="button" class="btn btn-sm btn-outline-secondary mb-1"
                    data-toggle="collapse" data-target="#form-so-du">
                <i class="fas fa-edit mr-1"></i> Khai / sửa số dư ban đầu
            </button>
            <?php endif; ?>
        </div>

        <?php /* Gửi sang Phieuthu::postSoDuQuy(): màn báo cáo chỉ có quyền
                 `view`, không có đường ghi. Form chỉ hiện cho người SỬA được cả
                 phiếu thu lẫn phiếu chi — cùng điều kiện mà hàm đó kiểm. Chưa
                 khai thì mở sẵn, để người dùng thấy ngay việc phải làm đầu tiên. */ ?>
        <?php if ($suaSoDu): ?>
        <form method="post" action="<?php echo e(_WEB_URL . '/admin/phieu-thu/so-du-quy'); ?>" id="form-so-du"
              class="collapse form-row align-items-end mt-2 <?php echo $s['ngay_ban_dau'] === null ? 'show' : ''; ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="quy" value="<?php echo e($s['quy']); ?>">
            <input type="hidden" name="tu"  value="<?php echo e($tu); ?>">
            <input type="hidden" name="den" value="<?php echo e($den); ?>">
            <div class="form-group col-md-3 mb-2">
                <label class="small mb-1">Số dư ban đầu — <?php echo e($s['ten']); ?> (₫)</label>
                <input type="text" name="so_du" inputmode="numeric" class="form-control form-control-sm"
                       placeholder="vd. 5.000.000"
                       value="<?php echo e($s['ngay_ban_dau'] !== null ? $tien($s['so_du_ban_dau']) : ''); ?>">
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="small mb-1">Tính từ ngày</label>
                <input type="date" name="ngay" class="form-control form-control-sm"
                       value="<?php echo e((string) $s['ngay_ban_dau']); ?>">
            </div>
            <div class="form-group col-md-2 mb-2">
                <button type="submit" class="btn btn-sm btn-success btn-block">
                    <i class="fas fa-save mr-1"></i> Lưu
                </button>
            </div>
            <div class="col-12">
                <small class="text-muted">
                    Tiền thật có trong quỹ lúc <strong>đầu</strong> ngày đó (đếm két, hoặc số dư trên sao kê
                    ngân hàng). Phiếu trước ngày này coi như đã nằm trong số dư, không cộng lại.
                    Để trống ngày rồi Lưu là bỏ số dư ban đầu.
                </small>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <?php if ($s['truoc_ngay_dau']): ?>
    <div class="card-body">
        <div class="alert alert-info mb-0">
            <i class="fas fa-info-circle mr-1"></i>
            Kỳ đang xem nằm trước ngày số dư ban đầu
            (<?php echo e($ngayVN($s['ngay_ban_dau'])); ?>). Sổ quỹ chỉ tính từ ngày đó —
            trước đó phần mềm không biết quỹ có bao nhiêu tiền.
        </div>
    </div>
    <?php else: ?>

    <?php if ($s['tu_thuc'] !== $s['tu']): ?>
    <div class="card-body py-2 border-bottom">
        <small class="text-muted"><i class="fas fa-info-circle mr-1"></i>
            Sổ bắt đầu từ ngày số dư ban đầu <?php echo e($ngayVN($s['tu_thuc'])); ?> —
            phiếu trước ngày đó đã nằm trong số dư ban đầu.
        </small>
    </div>
    <?php endif; ?>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:100px">Ngày</th>
                    <th style="width:120px">Số phiếu</th>
                    <th>Diễn giải</th>
                    <th>Đối tượng</th>
                    <th style="width:140px" class="text-right">Thu</th>
                    <th style="width:140px" class="text-right">Chi</th>
                    <th style="width:150px" class="text-right">Tồn</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-light">
                    <td colspan="6" class="font-weight-bold">
                        Tồn đầu kỳ (<?php echo e($ngayVN($s['tu_thuc'])); ?>)
                    </td>
                    <td class="text-right font-weight-bold <?php echo $s['dau_ky'] < 0 ? 'text-danger' : ''; ?>">
                        <?php echo e($tien($s['dau_ky'])); ?> ₫
                    </td>
                </tr>
            <?php if (!empty($s['dong'])): foreach ($s['dong'] as $r):
                $url = isset($urlPhieu[$r['loai']]) ? $urlPhieu[$r['loai']] : ''; ?>
                <tr>
                    <td><?php echo e($ngayVN($r['voucher_date'])); ?></td>
                    <td>
                        <?php if ($url !== ''): ?>
                        <a href="<?php echo e($url . (int) $r['id']); ?>"><?php echo e($r['voucher_no']); ?></a>
                        <?php else: ?>
                        <?php echo e($r['voucher_no']); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo e((string) ($r['ly_do'] !== null && $r['ly_do'] !== '' ? $r['ly_do'] : $r['vtype_name'])); ?>
                        <?php if (!empty($r['vtype_name'])): ?>
                        <br><small class="text-muted"><?php echo e($r['vtype_name']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e((string) $r['dt_ten']); ?></td>
                    <td class="text-right text-success"><?php if ($r['thu'] > 0) echo e($tien($r['thu']) . ' ₫'); ?></td>
                    <td class="text-right text-danger"><?php if ($r['chi'] > 0) echo e($tien($r['chi']) . ' ₫'); ?></td>
                    <td class="text-right font-weight-bold <?php echo $r['ton'] < 0 ? 'text-danger' : ''; ?>">
                        <?php echo e($tien($r['ton'])); ?> ₫
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                    Không có phiếu nào của quỹ <?php echo e($s['ten']); ?> trong khoảng ngày này
                </td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="bg-light">
                    <th colspan="4" class="text-right">Cộng phát sinh</th>
                    <th class="text-right text-success"><?php echo e($tien($s['tong_thu'])); ?> ₫</th>
                    <th class="text-right text-danger"><?php echo e($tien($s['tong_chi'])); ?> ₫</th>
                    <th></th>
                </tr>
                <tr class="bg-light">
                    <th colspan="6" class="text-right">Tồn cuối kỳ (<?php echo e($ngayVN($s['den'])); ?>)</th>
                    <th class="text-right <?php echo $s['cuoi_ky'] < 0 ? 'text-danger' : ''; ?>">
                        <?php echo e($tien($s['cuoi_ky'])); ?> ₫
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

    <?php /* Nói rõ phiếu nào vào sổ nào: khách hỏi "tiền NH thì tự nhập" — nhập
             ở đâu thì phải đọc được ngay trên màn này. */ ?>
    <div class="card-body border-top py-2">
        <small class="text-muted">
            Phiếu vào sổ theo <strong>tài khoản trên phiếu</strong>: Nợ <?php echo e($s['tk']); ?>x là thu vào quỹ,
            Có <?php echo e($s['tk']); ?>x là chi ra. Chỉ tính phiếu còn hiệu lực (phiếu đã huỷ không tính).
            <?php if ($s['quy'] === 'ngan_hang'): ?>
            Tiền ngân hàng nhập tay: lập <em>Phiếu thu</em> / <em>Phiếu chi</em> và chọn loại phiếu
            "… qua ngân hàng" (BC01, BN01…, định khoản 1121).
            <?php endif; ?>
            <?php if ($s['dau_ky'] < 0 || $s['cuoi_ky'] < 0): ?>
            <br><span class="text-danger">Tồn âm thường do chưa khai số dư ban đầu, hoặc còn thiếu phiếu thu.</span>
            <?php endif; ?>
        </small>
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
                        Tổng <?php echo $laKhach ? 'khách còn nợ công ty' : 'công ty còn nợ nhà cung cấp'; ?>
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
                <?php /* Từ 25/11/2026 phiếu thu / chi gồm cả tiền ngân hàng nhập tay
                         (loại phiếu BC / BN, 000119) — khối này cộng mọi phiếu. */ ?>
                <h5 class="mb-3"><i class="fas fa-money-bill-wave mr-2"></i>Dòng tiền (tiền mặt + ngân hàng)</h5>
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
