<div class="row justify-content-center"><div class="col-md-8 col-lg-7">
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>{{$page_name}}</h3></div>
        <form action="" method="post">
            <?php echo csrf_field(); ?>
            <div class="card-body">
                @if (!empty($msg))
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                @endif

                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-1"></i>
                    Đăng ký một màn hình đã có sẵn để nó xuất hiện trong bảng phân quyền.
                    <u>Không tạo ra màn hình mới.</u>
                </div>

                <div class="form-group">
                    <label>Tên module <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                           placeholder="vd: Quản lý phiếu nhập kho"
                           value="{{!empty($old['name'])?$old['name']:''}}"/>
                    <small class="form-text text-muted">Tên này hiện trong bảng phân quyền nhóm.</small>
                    {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                </div>

                <?php /* CHỌN, không gõ tay.
                         RoleMiddleware ghép 'admin/'.$link.'/*' rồi so với URL đang mở.
                         Gõ sai một chữ là không khớp, mà không khớp thì nó BỎ QUA phần
                         kiểm quyền — ai đăng nhập cũng vào được màn hình đó. */ ?>
                <div class="form-group">
                    <label>Màn hình <span class="text-danger">*</span></label>
                    @if (!empty($chuaDangKy))
                    <select name="link" class="form-control" id="oChonManHinh">
                        <option value="">— Chọn màn hình —</option>
                        @foreach ($chuaDangKy as $mh)
                        <option value="{{$mh}}" {{(!empty($old['link']) && $old['link']===$mh)?'selected':''}}>/admin/{{$mh}}</option>
                        @endforeach
                        <option value="__khac__" {{(!empty($old['link']) && $old['link']==='__khac__')?'selected':''}}>— Khác (tự nhập) —</option>
                    </select>
                    <small class="form-text text-muted">
                        Danh sách chỉ gồm các màn hình <b>có thật</b> mà <b>chưa module nào giữ</b>.
                    </small>
                    @else
                    <div class="alert alert-secondary mb-2">
                        <i class="fas fa-check-circle mr-1"></i>
                        Mọi màn hình hiện có đều đã được đăng ký — không còn màn hình nào để chọn.
                        Chỉ dùng ô "tự nhập" bên dưới nếu anh vừa thêm màn hình mới vào mã nguồn.
                    </div>
                    <input type="hidden" name="link" value="__khac__"/>
                    @endif
                    {!! !empty($errors['link'])?'<small class="text-danger d-block">'.e($errors['link']).'</small>':false !!}
                </div>

                <div class="form-group" id="oNhapTay" style="{{(empty($chuaDangKy) || (!empty($old['link']) && $old['link']==='__khac__'))?'':'display:none'}}">
                    <label>Đường dẫn tự nhập</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">/admin/</span></div>
                        <input type="text" name="link_tay" class="form-control"
                               placeholder="goods-receipts"
                               value="{{!empty($old['link_tay'])?$old['link_tay']:''}}"/>
                    </div>
                    <small class="form-text text-muted">
                        Chỉ chữ thường, số và dấu gạch ngang. Dùng khi màn hình vừa được thêm vào mã nguồn
                        nhưng chưa có trong danh sách trên.
                    </small>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Đăng ký</button>
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
            </div>
        </form>
    </div>
</div></div>

<script>
// Hiện ô gõ tay khi và chỉ khi chọn "— Khác —".
(function(){
    var chon = document.getElementById('oChonManHinh');
    var oTay = document.getElementById('oNhapTay');
    if (!chon || !oTay) return;
    chon.addEventListener('change', function(){
        oTay.style.display = (chon.value === '__khac__') ? '' : 'none';
    });
})();
</script>
