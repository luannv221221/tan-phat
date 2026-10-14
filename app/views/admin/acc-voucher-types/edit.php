<?php
/**
 * KẾ TOÁN — Sửa loại phiếu. Đúng màn trong ảnh minh hoạ số 1 của tài liệu:
 * mã phiếu · tên phiếu · loại (Thu / Chi) · tài khoản có · tài khoản nợ.
 *
 * Gần giống add.php và CỐ Ý chép đôi: view ở dự án này nạp bằng
 * file_get_contents() rồi eval(), nên `__DIR__` bên trong trỏ về core/ chứ
 * không phải thư mục view — tách partial rồi include là không tìm thấy file.
 */
/* Ưu tiên $old (dữ liệu vừa gõ hỏng lúc lưu) rồi mới tới $item (bản ghi đang sửa). */
$gt = function($k, $mac = '') use ($old, $item){
    if (isset($old[$k])) return $old[$k];
    if (!empty($item) && array_key_exists($k, $item)) return $item[$k];
    return $mac;
};
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tags mr-2"></i>{{$page_name}}</h3>
    </div>
    <form action="" method="post">
        <?php echo csrf_field(); ?>
        <div class="card-body">
            @if (!empty($msg))
            <div class="alert alert-danger">{{$msg}}</div>
            @endif

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Mã phiếu <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control"
                           value="<?php echo e($gt('code')); ?>" placeholder="PT01">
                    {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                </div>
                <div class="form-group col-md-6">
                    <label>Tên phiếu <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                           value="<?php echo e($gt('name')); ?>" placeholder="Thu tiền khách hàng">
                    {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Thứ tự hiển thị</label>
                    <input type="number" name="sort_order" class="form-control"
                           value="<?php echo e($gt('sort_order', 0)); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="d-block">Loại <span class="text-danger">*</span></label>
                <?php $loaiDang = $gt('loai', 'thu'); ?>
                @foreach ($dsLoai as $k => $nhan)
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" class="custom-control-input" id="loai-{{$k}}" name="loai" value="{{$k}}"
                           <?php echo $loaiDang === $k ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="loai-{{$k}}">{{$nhan}}</label>
                </div>
                @endforeach
                <small class="form-text text-muted">
                    Quyết định loại phiếu này hiện ở màn <strong>Phiếu thu</strong> hay <strong>Phiếu chi</strong>.
                </small>
            </div>

            <div class="form-row">
                <?php /* "Load từ danh mục tài khoản" — đúng ghi chú trong ảnh của khách.
                         Chỉ liệt kê tài khoản ĐANG DÙNG; tài khoản đã tắt không nên
                         định khoản mới vào. */ ?>
                <div class="form-group col-md-6">
                    <label>Tài khoản nợ <span class="text-danger">*</span></label>
                    <?php $noDang = (int) $gt('debit_id', 0); ?>
                    <select name="debit_id" class="form-control">
                        <option value="">— Chọn tài khoản —</option>
                        <?php foreach ((array) $dsTk as $t): ?>
                        <option value="<?php echo (int) $t['id']; ?>" <?php echo $noDang === (int) $t['id'] ? 'selected' : ''; ?>>
                            <?php echo str_repeat('— ', (int) $t['depth']) . e($t['code'] . ' ' . $t['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    {!! !empty($errors['debit_id'])?'<small class="text-danger">'.e($errors['debit_id']).'</small>':false !!}
                </div>
                <div class="form-group col-md-6">
                    <label>Tài khoản có <span class="text-danger">*</span></label>
                    <?php $coDang = (int) $gt('credit_id', 0); ?>
                    <select name="credit_id" class="form-control">
                        <option value="">— Chọn tài khoản —</option>
                        <?php foreach ((array) $dsTk as $t): ?>
                        <option value="<?php echo (int) $t['id']; ?>" <?php echo $coDang === (int) $t['id'] ? 'selected' : ''; ?>>
                            <?php echo str_repeat('— ', (int) $t['depth']) . e($t['code'] . ' ' . $t['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    {!! !empty($errors['credit_id'])?'<small class="text-danger">'.e($errors['credit_id']).'</small>':false !!}
                </div>
            </div>

            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="status" name="status" value="1"
                           <?php echo $gt('status', 1) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="status">Đang dùng</label>
                </div>
                <small class="form-text text-muted">Tắt thì không hiện ở ô chọn lúc lập phiếu.</small>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
        </div>
    </form>
</div>
