<?php
/**
 * DANH MỤC BẢO HIỂM — Thêm hãng.
 *
 * Gần giống edit.php và CỐ Ý chép đôi: view ở dự án này nạp bằng
 * file_get_contents() rồi eval(), nên `__DIR__` bên trong trỏ về core/ chứ
 * không phải thư mục view — tách partial rồi include là không tìm thấy file.
 */
$gt = function($k, $mac = '') use ($old){
    return isset($old[$k]) ? $old[$k] : $mac;
};
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>{{$page_name}}</h3>
    </div>
    <form action="" method="post">
        <?php echo csrf_field(); ?>
        <div class="card-body">
            @if (!empty($msg))
            <div class="alert alert-danger">{{$msg}}</div>
            @endif

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Mã hãng <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control"
                           value="<?php echo e($gt('code')); ?>" placeholder="BAOVIET">
                    {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                </div>
                <div class="form-group col-md-6">
                    <label>Tên hãng <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                           value="<?php echo e($gt('name')); ?>" placeholder="Bảo hiểm Bảo Việt">
                    {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Thứ tự hiển thị</label>
                    <input type="number" name="sort_order" class="form-control"
                           value="<?php echo e($gt('sort_order', 0)); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Mã số thuế</label>
                    <input type="text" name="tax_code" class="form-control" value="<?php echo e($gt('tax_code')); ?>">
                </div>
                <div class="form-group col-md-8">
                    <label>Tài khoản ngân hàng</label>
                    <input type="text" name="bank_account" class="form-control"
                           value="<?php echo e($gt('bank_account')); ?>"
                           placeholder="Số TK — Ngân hàng — Chi nhánh">
                </div>
            </div>

            <div class="form-group">
                <label>Địa chỉ</label>
                <input type="text" name="address" class="form-control" value="<?php echo e($gt('address')); ?>">
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Website</label>
                    <input type="text" name="website" class="form-control"
                           value="<?php echo e($gt('website')); ?>" placeholder="https://...">
                </div>
                <div class="form-group col-md-4">
                    <label>Người liên hệ</label>
                    <input type="text" name="contact_name" class="form-control" value="<?php echo e($gt('contact_name')); ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>Điện thoại người liên hệ</label>
                    <input type="text" name="contact_phone" class="form-control" value="<?php echo e($gt('contact_phone')); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="status" name="status" value="1"
                           <?php echo $gt('status', 1) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="status">Đang dùng</label>
                </div>
                <small class="form-text text-muted">Tắt thì không hiện ở ô chọn lúc lập báo giá.</small>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
        </div>
    </form>
</div>
