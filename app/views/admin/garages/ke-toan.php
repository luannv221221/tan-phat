<?php
/**
 * DANH MỤC KẾ TOÁN CỦA MỘT CÔNG TY — Tân Phát gán / bỏ gán từng tài khoản,
 * từng loại phiếu dùng chung (anh chốt 26/11/2026). Xem Garages::keToan(),
 * AccSharedHiddenModel.
 *
 * Tick = công ty này thấy và dùng được. Bỏ tick = công ty này không thấy, không
 * chọn được khi lập phiếu; công ty khác không đổi. Thứ đang dùng thì khoá.
 */
$anTk = array_flip((array) $an['account']);
$anLp = array_flip((array) $an['vtype']);
$khoaTk = (array) $dangDung['account'];
$khoaLp = (array) $dangDung['vtype'];
$tongTk = count((array) $dsTk); $tongLp = count((array) $dsLp);
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
<?php if (!empty($giuLai)): ?>
<div class="alert alert-warning">
    <strong><i class="fas fa-lock mr-1"></i> Một số mục bỏ tick nhưng vẫn được giữ gán:</strong>
    <ul class="mb-0 mt-1">
        <?php foreach ((array) $giuLai as $__g): ?><li><?php echo e($__g); ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
<?php if (empty($coBang)): ?>
<div class="alert alert-danger">Máy chủ chưa chạy migration <code>000124</code> — công ty nào cũng đang thấy đủ, chưa lưu được việc gán.</div>
<?php endif; ?>

<div class="callout callout-info small">
    Tân Phát dựng sẵn bộ danh mục chuẩn. Ở đây chọn <strong>công ty {{$item['name']}}</strong> được dùng những gì:
    <strong>tick</strong> là công ty thấy và chọn được; <strong>bỏ tick</strong> là công ty không thấy, không lập phiếu được
    bằng mục đó. Công ty khác không bị đổi theo. Mặc định công ty nào cũng được gán hết.
    Mục <i class="fas fa-lock"></i> đang được công ty dùng (đã có phiếu…) nên không bỏ gán được.
    Công ty vẫn tự thêm tài khoản / loại phiếu riêng của mình ở màn Kế toán như cũ.
</div>

<form method="post" action="{{_WEB_URL.'/admin/'.$routeBase.'/ke-toan/'.$item['id']}}" id="form-gan">
    <?php echo csrf_field(); ?>
    <div class="row">
        <div class="col-lg-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sitemap mr-2"></i>Danh mục tài khoản
                        <span class="text-muted small">(gán <?php echo (int) ($tongTk - count($anTk)); ?>/<?php echo (int) $tongTk; ?>)</span></h3>
                    <div class="card-tools text-nowrap">
                        <button type="button" class="btn btn-xs btn-default text-nowrap" data-chon="tk" data-gia-tri="1">Chọn hết</button>
                        <button type="button" class="btn btn-xs btn-default text-nowrap" data-chon="tk" data-gia-tri="0">Bỏ hết</button>
                    </div>
                </div>
                <div class="card-body p-2">
                    <?php foreach ((array) $dsTk as $t): $__id = (int) $t['id']; $__khoa = isset($khoaTk[$__id]); ?>
                    <div class="custom-control custom-checkbox" style="margin-left:<?php echo (int) $t['depth'] * 22; ?>px">
                        <input type="checkbox" class="custom-control-input o-tk" id="tk<?php echo (int) $__id; ?>" name="tk[]"
                               value="<?php echo (int) $__id; ?>" data-id="<?php echo (int) $__id; ?>"
                               data-cha="<?php echo (int) $t['parent_id']; ?>"
                               <?php echo (!isset($anTk[$__id]) || $__khoa) ? 'checked' : ''; ?>
                               <?php echo $__khoa ? 'data-khoa="1" disabled' : ''; ?>>
                        <label class="custom-control-label font-weight-normal" for="tk<?php echo (int) $__id; ?>">
                            <code><?php echo e($t['code']); ?></code> <?php echo e($t['name']); ?>
                            <?php if ($__khoa): ?><span class="text-muted small" title="<?php echo e($khoaTk[$__id]); ?>"><i class="fas fa-lock ml-1"></i> <?php echo e($khoaTk[$__id]); ?></span><?php endif; ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($dsTk)): ?><p class="text-muted mb-0">Chưa có tài khoản dùng chung.</p><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-receipt mr-2"></i>Danh mục loại phiếu
                        <span class="text-muted small">(gán <?php echo (int) ($tongLp - count($anLp)); ?>/<?php echo (int) $tongLp; ?>)</span></h3>
                    <div class="card-tools text-nowrap">
                        <button type="button" class="btn btn-xs btn-default text-nowrap" data-chon="lp" data-gia-tri="1">Chọn hết</button>
                        <button type="button" class="btn btn-xs btn-default text-nowrap" data-chon="lp" data-gia-tri="0">Bỏ hết</button>
                    </div>
                </div>
                <div class="card-body p-2">
                    <?php foreach ((array) $dsLp as $v): $__id = (int) $v['id']; $__khoa = isset($khoaLp[$__id]); ?>
                    <div class="custom-control custom-checkbox mb-1">
                        <input type="checkbox" class="custom-control-input o-lp" id="lp<?php echo (int) $__id; ?>" name="lp[]"
                               value="<?php echo (int) $__id; ?>"
                               <?php echo (!isset($anLp[$__id]) || $__khoa) ? 'checked' : ''; ?>
                               <?php echo $__khoa ? 'data-khoa="1" disabled' : ''; ?>>
                        <label class="custom-control-label font-weight-normal" for="lp<?php echo (int) $__id; ?>">
                            <code><?php echo e($v['code']); ?></code> <?php echo e($v['name']); ?>
                            <span class="badge <?php echo $v['loai'] === 'thu' ? 'badge-success' : 'badge-danger'; ?>"><?php echo $v['loai'] === 'thu' ? 'Thu' : 'Chi'; ?></span>
                            <span class="text-muted small">Nợ <?php echo e($v['no_code']); ?> · Có <?php echo e($v['co_code']); ?></span>
                            <?php if ($__khoa): ?><span class="text-muted small"><i class="fas fa-lock ml-1"></i> <?php echo e($khoaLp[$__id]); ?></span><?php endif; ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($dsLp)): ?><p class="text-muted mb-0">Chưa có loại phiếu dùng chung.</p><?php endif; ?>
                    <p class="text-muted small mb-0 mt-2">Loại phiếu còn gán thì tài khoản nợ / có của nó cũng được giữ gán.</p>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">← Về danh sách công ty</a>
</form>

<script>
/* Cây tài khoản: bỏ tick cha thì bỏ cả nhánh con; tick con thì tick lại cha.
   Máy chủ cũng tự giữ đúng như vậy khi lưu — đây chỉ cho thấy trước. */
(function(){
    var o = [].slice.call(document.querySelectorAll('.o-tk'));
    var theoId = {}; o.forEach(function(x){ theoId[x.getAttribute('data-id')] = x; });
    function con(id){ return o.filter(function(x){ return x.getAttribute('data-cha') === id; }); }
    function boNhanh(id){ con(id).forEach(function(x){ if (!x.disabled) x.checked = false; boNhanh(x.getAttribute('data-id')); }); }
    function coKhoaDuoi(id){ return con(id).some(function(x){ return x.disabled || coKhoaDuoi(x.getAttribute('data-id')); }); }
    o.forEach(function(x){
        x.addEventListener('change', function(){
            var id = x.getAttribute('data-id');
            if (!x.checked){
                if (coKhoaDuoi(id)){ x.checked = true; return; }   // nhánh có tài khoản đang dùng
                boNhanh(id);
            } else {
                var cha = theoId[x.getAttribute('data-cha')];
                while (cha){ cha.checked = true; cha = theoId[cha.getAttribute('data-cha')]; }
            }
        });
    });
    [].forEach.call(document.querySelectorAll('[data-chon]'), function(n){
        n.addEventListener('click', function(){
            var bat = n.getAttribute('data-gia-tri') === '1';
            [].forEach.call(document.querySelectorAll('.o-' + n.getAttribute('data-chon')), function(x){
                if (!x.disabled) x.checked = bat;
            });
        });
    });
})();
</script>
