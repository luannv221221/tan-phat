@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="row justify-content-center"><div class="col-md-10 col-lg-7">

    <?php /* HỒ SƠ — chỉ của CHÍNH người đang đăng nhập. Không có ô / tham số
             nào mang mã người dùng: controller lấy từ phiên đăng nhập. */ ?>
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-user mr-2"></i>Thông tin cá nhân</h3></div>

        <form action="{{_WEB_URL.'/admin/ho-so'}}" method="post" enctype="multipart/form-data" autocomplete="off">
            <?php echo csrf_field(); ?>
            <div class="card-body">

                <div class="ho-so-anh">
                    <span class="ho-so-anh__khung" id="hoSoAnhKhung">
                        @if (!empty($anh))
                        <img src="{{$anh}}" alt="Ảnh đại diện" id="hoSoAnh"/>
                        @else
                        {!! icon('circle-user') !!}
                        @endif
                    </span>
                    <div class="ho-so-anh__chon">
                        <label class="mb-1">Ảnh đại diện</label>
                        <input type="file" name="avatar_file" id="hoSoAnhTep" class="form-control-file" accept="image/jpeg,image/png,image/gif,image/webp"/>
                        <small class="form-text text-muted">JPG, PNG, GIF hoặc WEBP, tối đa 3MB. Không chọn thì giữ ảnh cũ.</small>
                        {!! !empty($errors['avatar_file'])?'<small class="text-danger d-block">'.e($errors['avatar_file']).'</small>':false !!}
                        @if (!empty($anh))
                        <div class="custom-control custom-checkbox mt-1">
                            <input type="checkbox" class="custom-control-input" id="hoSoXoaAnh" name="xoa_anh" value="1"/>
                            <label class="custom-control-label" for="hoSoXoaAnh">Xoá ảnh, dùng lại biểu tượng mặc định</label>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label>Họ tên <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{$ten}}" maxlength="100" autocomplete="name"/>
                    {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                </div>

                <?php /* EMAIL CHỈ ĐỂ XEM — không có thuộc tính name, nên có gửi
                         gì cũng không tới server. Email là tên đăng nhập cho cả
                         nền tảng; đổi là việc của người quản trị ở màn Người dùng. */ ?>
                <div class="form-group">
                    <label>Email đăng nhập</label>
                    <input type="text" class="form-control" value="{{!empty($nguoiDung['email'])?$nguoiDung['email']:''}}" readonly/>
                    <small class="form-text text-muted">Email là tên đăng nhập, không tự đổi ở đây được. Cần đổi thì nhờ người quản trị sửa ở màn Người dùng.</small>
                </div>

                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="tel" name="phone" class="form-control" value="{{$dienThoai}}" maxlength="20" placeholder="0912 345 678" autocomplete="tel"/>
                    {!! !empty($errors['phone'])?'<small class="text-danger">'.e($errors['phone']).'</small>':false !!}
                </div>

                <div class="form-group mb-0">
                    <label>Địa chỉ</label>
                    <input type="text" name="address" class="form-control" value="{{$diaChi}}" maxlength="255" autocomplete="street-address"/>
                    {!! !empty($errors['address'])?'<small class="text-danger">'.e($errors['address']).'</small>':false !!}
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu hồ sơ</button>
                <a href="{{_WEB_URL.'/admin'}}" class="btn btn-default">Quay lại</a>
            </div>
        </form>
    </div>

    <?php /* ĐỔI MẬT KHẨU — form gửi vào admin/doi-mat-khau (Doimatkhau::postDoi),
             nơi duy nhất đổi mật khẩu: hỏi mật khẩu hiện tại, đá các phiên khác
             ra, rồi quay về đúng khung này (#doi-mat-khau). Thông báo của nó
             in NGAY TRONG KHUNG, không ở đầu trang. */ ?>
    <div class="card card-outline card-primary" id="doi-mat-khau">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-key mr-2"></i>Đổi mật khẩu</h3></div>

        <form action="{{_WEB_URL.'/admin/doi-mat-khau'}}" method="post" autocomplete="off">
            <?php echo csrf_field(); ?>
            <div class="card-body">
                @if (!empty($mkMsg))
                <div class="alert alert-success py-2"><i class="fas fa-check-circle mr-1"></i> {{$mkMsg}}</div>
                @endif
                @if (!empty($mkMsgError))
                <div class="alert alert-danger py-2"><i class="fas fa-exclamation-circle mr-1"></i> {{$mkMsgError}}</div>
                @endif

                <div class="form-group">
                    <label>Mật khẩu hiện tại <span class="text-danger">*</span></label>
                    <input type="password" name="current_password" class="form-control" autocomplete="current-password"/>
                    {!! !empty($mkErrors['current_password'])?'<small class="text-danger">'.e($mkErrors['current_password']).'</small>':false !!}
                </div>

                <div class="form-group">
                    <label>Mật khẩu mới <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" class="form-control" autocomplete="new-password"/>
                    <small class="form-text text-muted">Tối thiểu {{$toiThieuMk}} ký tự.</small>
                    {!! !empty($mkErrors['new_password'])?'<small class="text-danger">'.e($mkErrors['new_password']).'</small>':false !!}
                </div>

                <div class="form-group mb-0">
                    <label>Nhập lại mật khẩu mới <span class="text-danger">*</span></label>
                    <input type="password" name="new_password2" class="form-control" autocomplete="new-password"/>
                    {!! !empty($mkErrors['new_password2'])?'<small class="text-danger">'.e($mkErrors['new_password2']).'</small>':false !!}
                </div>

                <?php /* NÓI TRƯỚC HẬU QUẢ. Đổi mật khẩu xong mà điện thoại tự
                         đăng xuất thì người dùng tưởng hỏng và gọi hỗ trợ. */ ?>
                <div class="alert alert-warning mt-3 mb-0 py-2 small">
                    <i class="fas fa-info-circle mr-1"></i>
                    Đổi xong, mọi thiết bị khác đang đăng nhập tài khoản này sẽ bị đăng xuất.
                    Máy anh/chị đang dùng thì vẫn ở nguyên.
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-lock mr-1"></i> Đổi mật khẩu</button>
            </div>
        </form>
    </div>

</div></div>

<style>
.ho-so-anh { display: flex; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 1rem; }
.ho-so-anh__khung {
    width: 88px; height: 88px; flex: 0 0 88px;
    border-radius: 50%; overflow: hidden;
    background: #e9edf2; color: #7b8494;
    display: flex; align-items: center; justify-content: center;
}
.ho-so-anh__khung img { width: 100%; height: 100%; object-fit: cover; }
.ho-so-anh__khung .ic { width: 56px; height: 56px; stroke-width: 1.25; }
.ho-so-anh__chon { flex: 1 1 220px; min-width: 0; }
</style>

<script>
/* Xem trước ảnh vừa chọn — chỉ trên máy người dùng, chưa gửi gì lên. */
(function(){
    var tep = document.getElementById('hoSoAnhTep');
    var khung = document.getElementById('hoSoAnhKhung');
    if (!tep || !khung || !window.URL || !URL.createObjectURL) return;
    tep.addEventListener('change', function(){
        if (!tep.files || !tep.files[0] || tep.files[0].type.indexOf('image/') !== 0) return;
        var img = document.createElement('img');
        img.alt = 'Ảnh đại diện';
        img.src = URL.createObjectURL(tep.files[0]);
        khung.innerHTML = '';
        khung.appendChild(img);
    });
})();
</script>
