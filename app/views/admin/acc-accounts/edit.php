<?php
/**
 * KẾ TOÁN — Sửa tài khoản.
 *
 * Gần giống add.php, và CỐ Ý chép đôi thay vì tách một partial dùng chung:
 * view ở dự án này được nạp bằng file_get_contents() rồi eval(), nên bên trong
 * `__DIR__` trỏ về thư mục core/ chứ không phải thư mục view — một
 * `include __DIR__ . '/_form.php'` nhìn thì đúng mà chạy là không tìm thấy file.
 * Mọi màn khác trong dự án cũng chép đôi như vậy.
 *
 * Khác add.php đúng một chỗ: lấy giá trị thì ưu tiên $old (dữ liệu vừa gõ hỏng
 * lúc lưu) rồi mới tới $item (bản ghi đang sửa).
 */
$gt = function($k, $mac = '') use ($old, $item){
    if (isset($old[$k])) return $old[$k];
    if (!empty($item) && array_key_exists($k, $item)) return $item[$k];
    return $mac;
};
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-sitemap mr-2"></i>{{$page_name}}</h3>
    </div>
    <form action="" method="post">
        <?php echo csrf_field(); ?>
        <div class="card-body">
            @if (!empty($msg))
            <div class="alert alert-danger">{{$msg}}</div>
            @endif

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Mã tài khoản <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control"
                           value="<?php echo e($gt('code')); ?>" placeholder="1111">
                    {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                    <small class="form-text text-muted">Chữ và số, ví dụ <code>1111</code>, <code>111A</code>.</small>
                </div>
                <div class="form-group col-md-6">
                    <label>Tên tài khoản <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                           value="<?php echo e($gt('name')); ?>" placeholder="Tiền Việt Nam">
                    {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Thứ tự hiển thị</label>
                    <input type="number" name="sort_order" class="form-control"
                           value="<?php echo e($gt('sort_order', 0)); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Tài khoản cha</label>
                    <?php $chaDang = (int) $gt('parent_id', 0); ?>
                    <select name="parent_id" class="form-control">
                        <option value="">— Không có (tài khoản cấp 1) —</option>
                        <?php foreach ((array) $dsCha as $c): ?>
                        <option value="<?php echo (int) $c['id']; ?>" <?php echo $chaDang === (int) $c['id'] ? 'selected' : ''; ?>>
                            <?php echo str_repeat('— ', (int) $c['depth']) . e($c['code'] . ' ' . $c['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    {!! !empty($errors['parent_id'])?'<small class="text-danger">'.e($errors['parent_id']).'</small>':false !!}
                    <small class="form-text text-muted">
                        Để trống là tài khoản cấp 1. Chọn cha để thành tài khoản chi tiết,
                        ví dụ <code>1111</code> nằm dưới <code>111</code>.
                    </small>
                </div>
                <div class="form-group col-md-3">
                    <label class="d-block">Trạng thái</label>
                    <div class="custom-control custom-switch mt-2">
                        <input type="checkbox" class="custom-control-input" id="status" name="status" value="1"
                               <?php echo $gt('status', 1) ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="status">Đang dùng</label>
                    </div>
                    <small class="form-text text-muted">Tắt thì không hiện ở ô chọn khi khai loại phiếu.</small>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
        </div>
    </form>
</div>
