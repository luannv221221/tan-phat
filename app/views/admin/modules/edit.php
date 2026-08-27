<?php
// Ưu tiên giá trị vừa gõ hỏng (old) rồi mới tới giá trị trong CSDL.
$vName = !empty($old['name']) ? $old['name'] : $item['name'];
$vLink = !empty($old['link']) ? $old['link'] : $item['link'];
$laChinhNo = ($item['link'] === $routeBase);
?>
<div class="row justify-content-center"><div class="col-md-8 col-lg-7">
    <div class="card card-outline card-warning">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-edit mr-2"></i>{{$page_name}}</h3></div>
        <form action="" method="post">
            <?php echo csrf_field(); ?>
            <div class="card-body">
                @if (!empty($msg))
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                @endif

                @if ($laChinhNo)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Đây là module của <b>chính màn hình này</b>. Đổi đường dẫn của nó là mất đường vào
                    Quản lý module, phải sửa thẳng cơ sở dữ liệu mới quay lại được.
                </div>
                @endif

                <div class="form-group">
                    <label>Tên module <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{$vName}}"/>
                    <small class="form-text text-muted">Tên này hiện trong bảng phân quyền nhóm.</small>
                    {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                </div>

                <div class="form-group">
                    <label>Màn hình <span class="text-danger">*</span></label>
                    <select name="link" class="form-control" id="oChonManHinh">
                        @foreach ($chuaDangKy as $mh)
                        <option value="{{$mh}}" {{($vLink===$mh)?'selected':''}}>/admin/{{$mh}}</option>
                        @endforeach
                        <option value="__khac__" {{($vLink==='__khac__')?'selected':''}}>— Khác (tự nhập) —</option>
                    </select>
                    <small class="form-text text-muted">
                        Gồm màn hình đang gán và các màn hình chưa module nào giữ.
                    </small>
                    {!! !empty($errors['link'])?'<small class="text-danger d-block">'.e($errors['link']).'</small>':false !!}
                </div>

                <div class="form-group" id="oNhapTay" style="{{($vLink==='__khac__')?'':'display:none'}}">
                    <label>Đường dẫn tự nhập</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">/admin/</span></div>
                        <input type="text" name="link_tay" class="form-control"
                               value="{{!empty($old['link_tay'])?$old['link_tay']:''}}" placeholder="goods-receipts"/>
                    </div>
                    <small class="form-text text-muted">Chỉ chữ thường, số và dấu gạch ngang.</small>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Cập nhật</button>
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
            </div>
        </form>
    </div>
</div></div>

<script>
(function(){
    var chon = document.getElementById('oChonManHinh');
    var oTay = document.getElementById('oNhapTay');
    if (!chon || !oTay) return;
    chon.addEventListener('change', function(){
        oTay.style.display = (chon.value === '__khac__') ? '' : 'none';
    });
})();
</script>
