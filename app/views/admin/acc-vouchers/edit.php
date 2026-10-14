<?php
/**
 * KẾ TOÁN — Sửa phiếu thu / phiếu chi.
 *
 * MỘT view cho CẢ HAI màn (controller truyền $loai, $routeBase, nhãn chữ).
 * Gần giống add.php và CỐ Ý chép đôi: view ở dự án này nạp bằng
 * file_get_contents() rồi eval(), nên `__DIR__` bên trong trỏ về core/ chứ
 * không phải thư mục view — tách partial rồi include là không tìm thấy file.
 *
 * Ô ĐỐI TƯỢNG theo đúng tài liệu: "load danh mục khách hàng, nhà cung cấp,
 * nhân viên… hiểu là thu từ đối tượng nào". Ba nguồn khác nhau nên có ba ô,
 * JS chỉ hiện ô tương ứng với lựa chọn — nhưng controller vẫn tự kiểm lại,
 * vì ẩn một ô không ngăn được ai gửi POST.
 */
/* Ưu tiên $old (dữ liệu vừa gõ hỏng lúc lưu) rồi mới tới $item (phiếu đang sửa). */
$gt = function($k, $mac = '') use ($old, $item){
    if (isset($old[$k])) return $old[$k];
    if (!empty($item) && array_key_exists($k, $item) && $item[$k] !== null) return $item[$k];
    return $mac;
};
$laThu = $loai === 'thu';
?>
<div class="card card-outline <?php echo $laThu ? 'card-success' : 'card-danger'; ?>">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-receipt mr-2"></i>{{$page_name}}</h3>
    </div>
    <form action="" method="post">
        <?php echo csrf_field(); ?>
        <div class="card-body">
            @if (!empty($msg))
            <div class="alert alert-danger">{{$msg}}</div>
            @endif

            <?php /* PHIẾU ĐÃ HUỶ: nói ngay ở đầu form, và bỏ nút Lưu ở chân.
                     Không nói thì người dùng sửa cả form rồi bấm Lưu mới nhận
                     câu từ chối — mà lúc đó chữ vừa gõ đã mất. */ ?>
            <?php if ((int) $item['status'] !== 1): ?>
            <div class="alert alert-secondary">
                <i class="fas fa-ban mr-1"></i>
                Phiếu này <strong>đã huỷ</strong> — không sửa được và không tính vào báo cáo.
                Bỏ huỷ ở chân trang nếu cần dùng lại.
            </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Số phiếu <span class="text-danger">*</span></label>
                    <input type="text" name="voucher_no" class="form-control"
                           value="<?php echo e($gt('voucher_no', $soGoiY)); ?>">
                    {!! !empty($errors['voucher_no'])?'<small class="text-danger">'.e($errors['voucher_no']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Ngày <span class="text-danger">*</span></label>
                    <input type="date" name="voucher_date" class="form-control"
                           value="<?php echo e($gt('voucher_date', date('Y-m-d'))); ?>">
                    {!! !empty($errors['voucher_date'])?'<small class="text-danger">'.e($errors['voucher_date']).'</small>':false !!}
                </div>
                <div class="form-group col-md-6">
                    <label>Loại phiếu <span class="text-danger">*</span></label>
                    <?php $vtDang = (int) $gt('vtype_id', 0); ?>
                    <select name="vtype_id" class="form-control">
                        <option value="">— Chọn loại phiếu —</option>
                        <?php foreach ((array) $dsLoaiPhieu as $t): ?>
                        <option value="<?php echo (int) $t['id']; ?>" <?php echo $vtDang === (int) $t['id'] ? 'selected' : ''; ?>>
                            <?php echo e($t['code'] . ' — ' . $t['name']
                                       . (!empty($t['no_code']) ? '  (Nợ ' . $t['no_code'] . ' / Có ' . $t['co_code'] . ')' : '')); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    {!! !empty($errors['vtype_id'])?'<small class="text-danger">'.e($errors['vtype_id']).'</small>':false !!}
                    <small class="form-text text-muted">
                        Cặp tài khoản nợ – có lấy theo loại phiếu và được
                        <strong>chép lại vào phiếu</strong> lúc lưu.
                        Khai thêm loại ở <em>Kế toán › Danh mục loại phiếu</em>.
                    </small>
                </div>
            </div>

            <hr>

            <div class="form-group">
                <label class="d-block">Đối tượng {{$laThu ? 'nộp tiền' : 'nhận tiền'}}</label>
                <?php $dtDang = $gt('dt_loai', 'partner'); ?>
                @foreach ($dsDoiTuong as $k => $nhan)
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" class="custom-control-input doi-tuong-loai" id="dt-{{$k}}"
                           name="dt_loai" value="{{$k}}" <?php echo $dtDang === $k ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="dt-{{$k}}">{{$nhan}}</label>
                </div>
                @endforeach
            </div>

            <div class="form-row">
                <div class="form-group col-md-6 o-doi-tuong" data-cho="partner">
                    <label>Chọn khách hàng / nhà cung cấp</label>
                    <?php $pDang = (int) $gt('partner_id', 0); ?>
                    <select name="partner_id" class="form-control">
                        <option value="">— Chọn —</option>
                        <?php foreach ((array) $dsPartner as $p): ?>
                        <option value="<?php echo (int) $p['id']; ?>" <?php echo $pDang === (int) $p['id'] ? 'selected' : ''; ?>>
                            <?php echo e($p['code'] . ' — ' . $p['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-6 o-doi-tuong" data-cho="user">
                    <label>Chọn nhân viên</label>
                    <?php $uDang = (int) $gt('user_id', 0); ?>
                    <select name="user_id" class="form-control">
                        <option value="">— Chọn —</option>
                        <?php foreach ((array) $dsNhanVien as $u): ?>
                        <option value="<?php echo (int) $u['id']; ?>" <?php echo $uDang === (int) $u['id'] ? 'selected' : ''; ?>>
                            <?php echo e($u['name'] . ' — ' . $u['email']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Họ và tên người {{$laThu ? 'nộp' : 'nhận'}} tiền <span class="text-danger">*</span></label>
                    <input type="text" name="dt_ten" class="form-control"
                           value="<?php echo e($gt('dt_ten')); ?>">
                    {!! !empty($errors['dt_ten'])?'<small class="text-danger">'.e($errors['dt_ten']).'</small>':false !!}
                    <small class="form-text text-muted">
                        Để trống thì lấy theo đối tượng vừa chọn. Gõ đè nếu người
                        {{$laThu ? 'nộp' : 'nhận'}} tiền khác tên đơn vị.
                    </small>
                </div>
                <div class="form-group col-md-6">
                    <label>Địa chỉ</label>
                    <input type="text" name="dt_diachi" class="form-control"
                           value="<?php echo e($gt('dt_diachi')); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-8">
                    <label>Lý do {{$laThu ? 'thu' : 'chi'}}</label>
                    <input type="text" name="ly_do" class="form-control"
                           value="<?php echo e($gt('ly_do')); ?>"
                           placeholder="<?php echo $laThu ? 'Thu tiền sửa xe 30A-123.45' : 'Thanh toán tiền hàng tháng 10'; ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>Số tiền <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" min="0" step="1" name="amount" class="form-control text-right"
                               value="<?php echo e($gt('amount')); ?>" placeholder="1500000">
                        <div class="input-group-append"><span class="input-group-text">₫</span></div>
                    </div>
                    {!! !empty($errors['amount'])?'<small class="text-danger">'.e($errors['amount']).'</small>':false !!}
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Kèm theo</label>
                    <input type="text" name="kem_theo" class="form-control"
                           value="<?php echo e($gt('kem_theo')); ?>" placeholder="01 chứng từ gốc">
                </div>
                <div class="form-group col-md-8">
                    <label>Ghi chú</label>
                    <input type="text" name="note" class="form-control" value="<?php echo e($gt('note')); ?>">
                </div>
            </div>
        </div>
        <div class="card-footer d-flex flex-wrap align-items-center" style="gap:.5rem">
            <?php if ((int) $item['status'] === 1): ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu phiếu</button>
            <?php endif; ?>
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/in/'.$item['id']}}" target="_blank" rel="noopener"
               class="btn btn-default"><i class="fas fa-print mr-1"></i> In phiếu</a>
            <?php /* HUỶ, KHÔNG XOÁ: chứng từ tiền mà biến mất thì báo cáo hôm qua
                     khác hôm nay và không ai giải thích được. */ ?>
            @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
            <?php if ((int) $item['status'] === 1): ?>
            <a onclick="return confirm('Huỷ phiếu {{$item['voucher_no']}}? Phiếu vẫn còn trong danh sách nhưng không tính vào báo cáo nữa.')"
               href="{{_WEB_URL.'/admin/'.$routeBase.'/huy/'.$item['id']}}"
               class="btn btn-outline-danger ml-auto"><i class="fas fa-ban mr-1"></i> Huỷ phiếu</a>
            <?php else: ?>
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/huy/'.$item['id']}}"
               class="btn btn-outline-success ml-auto"><i class="fas fa-undo mr-1"></i> Bỏ huỷ</a>
            <?php endif; ?>
            @endif
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
        </div>
    </form>
</div>

<script>
/* Chỉ hiện ô tương ứng với loại đối tượng đang chọn. Thuần giao diện —
   controller vẫn tự kiểm lại, vì ẩn một ô không ngăn được ai gửi POST. */
(function(){
    var radios = document.querySelectorAll('.doi-tuong-loai');
    var o      = document.querySelectorAll('.o-doi-tuong');
    function ve(){
        var chon = document.querySelector('.doi-tuong-loai:checked');
        var v = chon ? chon.value : 'partner';
        for (var i = 0; i < o.length; i++){
            o[i].style.display = (o[i].getAttribute('data-cho') === v) ? '' : 'none';
        }
    }
    for (var i = 0; i < radios.length; i++) radios[i].addEventListener('change', ve);
    ve();
})();
</script>
