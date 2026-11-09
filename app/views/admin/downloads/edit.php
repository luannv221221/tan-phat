<?php
$sel = function($field, $default = '') use ($old, $item){
    return isset($old[$field]) ? $old[$field] : (isset($item[$field]) ? $item[$field] : $default);
};
$co = function($field) use ($old, $item){
    if (!empty($old)) return !empty($old[$field]);
    return !empty($item[$field]);
};

/* HAI LINK — đây là mục khách ghi "có link trực tiếp và link gián tiếp".
   Gián tiếp: dán lên website, có kiểm đăng nhập, có đếm lượt.
   Trực tiếp: gửi riêng qua email, tải thẳng, không kiểm, không đếm. */
$linkGianTiep = _WEB_URL . '/tai-ve/' . $item['slug'];
$linkTrucTiep = $linkGianTiep . '?k=' . $item['token'];

$kb = (float) $item['file_size'];
$coBytes = $kb >= 1048576 ? round($kb / 1048576, 1) . ' MB'
         : ($kb >= 1024 ? round($kb / 1024) . ' KB' : (int) $kb . ' B');
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="row justify-content-center">
    <div class="col-md-9 col-lg-8">

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-link mr-2"></i>Hai link tải về</h3>
                <div class="card-tools"><span class="badge badge-secondary p-2">{{(int)$item['hits']}} lượt tải</span></div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="mb-1">Link gián tiếp <span class="text-muted font-weight-normal">— dán lên website, Facebook, Zalo</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control" readonly value="<?php echo e($linkGianTiep); ?>" id="lk-gt"/>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary js-chep" type="button" data-o="lk-gt"><i class="fas fa-copy mr-1"></i> Chép</button>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        Có kiểm "chỉ thành viên", và <b>mỗi lượt tải được đếm</b>.
                    </small>
                </div>

                <div class="form-group mb-0">
                    <label class="mb-1">Link trực tiếp <span class="text-muted font-weight-normal">— gửi riêng cho khách qua email</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control" readonly value="<?php echo e($linkTrucTiep); ?>" id="lk-tt"/>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary js-chep" type="button" data-o="lk-tt"><i class="fas fa-copy mr-1"></i> Chép</button>
                            <a href="<?php echo e(_WEB_URL . '/admin/' . $routeBase . '/doi-token/' . (int) $item['id']); ?>"
                               onclick="return confirm('Đổi link trực tiếp? Mọi link cũ đã gửi đi sẽ KHÔNG tải được nữa.')"
                               class="btn btn-outline-danger"><i class="fas fa-rotate mr-1"></i> Đổi link</a>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        Tải thẳng, <b>không hỏi đăng nhập</b> và không tính vào lượt tải.
                        Lỡ gửi nhầm thì bấm <b>Đổi link</b> — link cũ chết ngay, tệp vẫn còn.
                    </small>
                </div>
            </div>
        </div>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-pen mr-2"></i>{{$page_name}}</h3>
            </div>

            <form action="" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="card-body">

                    <div class="form-group">
                        <label>Tên tệp <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" value="{{$sel('title')}}"/>
                        {!! !empty($errors['title'])?'<small class="text-danger d-block">'.e($errors['title']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Đường dẫn</label>
                        <input type="text" class="form-control" name="slug" value="{{$sel('slug')}}"/>
                        <small class="form-text text-muted">
                            Đổi đường dẫn là <b>mọi link đã phát ra ngừng chạy</b> — cả hai loại.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Tệp hiện tại</label>
                        <div class="p-2 border rounded bg-light mb-2">
                            <i class="fas fa-file mr-1 text-muted"></i>
                            <code>{{$item['file_name']}}</code>
                            <span class="text-muted ml-2"><?php echo e($coBytes); ?></span>
                        </div>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="file" id="file"/>
                            <label class="custom-file-label" for="file">Thay bằng tệp khác...</label>
                        </div>
                        <small class="form-text text-muted">
                            Để trống thì giữ nguyên tệp cũ. Chọn tệp mới thì tệp cũ bị xoá khỏi máy chủ.
                        </small>
                        {!! !empty($errors['file'])?'<small class="text-danger d-block">'.e($errors['file']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Mô tả ngắn</label>
                        <textarea class="form-control" name="description" rows="2">{{$sel('description')}}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Thứ tự hiển thị</label>
                        <input type="number" class="form-control" name="sort_order" style="max-width:150px" value="{{$sel('sort_order','0')}}"/>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="require_login" id="require_login" value="1" <?php echo $co('require_login') ? 'checked' : ''; ?>/>
                            <label class="custom-control-label" for="require_login">Chỉ thành viên mới tải được</label>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="is_published" id="is_published" value="1" <?php echo $co('is_published') ? 'checked' : ''; ?>/>
                            <label class="custom-control-label" for="is_published">Hiện trên website</label>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('change', function (e){
    if (e.target && e.target.id === 'file'){
        var ten = e.target.files.length ? e.target.files[0].name : 'Thay bằng tệp khác...';
        var l = e.target.parentNode.querySelector('.custom-file-label');
        if (l) l.textContent = ten;
    }
});
/* Nút Chép — tài liệu khách ghi "có link cho copy".
   execCommand('copy') chứ không chỉ navigator.clipboard: clipboard API chỉ
   chạy trên https (hoặc localhost), mà gara nào chưa bật SSL thì mở bằng
   http — ở đó API kia không tồn tại và nút sẽ im lặng không làm gì. */
document.addEventListener('click', function (e){
    var nut = e.target.closest ? e.target.closest('.js-chep') : null;
    if (!nut) return;
    var o = document.getElementById(nut.getAttribute('data-o'));
    if (!o) return;
    o.select(); o.setSelectionRange(0, 99999);
    var xong = false;
    try { xong = document.execCommand('copy'); } catch (err) { xong = false; }
    if (!xong && navigator.clipboard){
        navigator.clipboard.writeText(o.value).then(function(){ bao(nut); });
        return;
    }
    if (xong) bao(nut);
});
function bao(nut){
    var cu = nut.innerHTML;
    nut.innerHTML = '<i class="fas fa-check mr-1"></i> Đã chép';
    setTimeout(function(){ nut.innerHTML = cu; }, 1500);
}
</script>
