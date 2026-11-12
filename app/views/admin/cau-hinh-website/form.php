<?php
/**
 * CẤU HÌNH WEBSITE — những ô CHỈ ảnh hưởng tới trang khách.
 *
 * Tách khỏi màn Cấu hình chung 12/11/2026 theo yêu cầu của khách: "phần cấu
 * hình chung, những thứ liên quan đến website đẩy hết lên Quản lý website".
 *
 * Danh sách ô ở đây phải KHỚP với $khoaWebsite trong Settings.php. Form này
 * chỉ gửi lên những ô của mình, và luu() cũng chỉ ghi đúng những ô ấy — thêm
 * một ô vào đây mà quên khai bên controller thì ô đó gõ xong bấm Lưu là mất,
 * không báo lỗi gì cả.
 */
$s = function($key, $default = '') use ($settings){ return isset($settings[$key]) && $settings[$key] !== null ? $settings[$key] : $default; };
?>
<div class="row"><div class="col-lg-9 mx-auto">
    @if (!empty($msg))
    <div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
    @endif

    <?php /* Nói rõ đang sửa website CỦA AI — giống màn Cấu hình chung. Gara
             tổng sửa mặc định cho mọi gara, gara khác chỉ sửa trang của mình. */ ?>
    @if (!empty($laChung))
    <div class="alert alert-warning py-2">
        <i class="fas fa-globe mr-1"></i>
        Đây là <b>cấu hình chung</b> — làm mặc định cho website của mọi gara chưa tự khai.
        Gara nào đã tự đặt thì giữ giá trị của họ.
    </div>
    @else
    <div class="alert alert-info py-2">
        <i class="fas fa-store mr-1"></i>
        Đây là cấu hình website <b>riêng của gara {{$garaTen}}</b>.
        Ô nào để nguyên thì dùng mặc định chung.
    </div>
    @endif

    <p class="text-muted small">
        <i class="fas fa-info-circle mr-1"></i>
        Tên gara, logo, hotline, địa chỉ, số tài khoản nằm ở
        <a href="{{_WEB_URL.'/admin/settings'}}">Cấu hình chung</a> — mấy ô đó còn in trên
        báo giá và hoá đơn nên không chuyển sang đây.
    </p>

    <form action="{{_WEB_URL.'/admin/cau-hinh-website/save'}}" method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-desktop mr-2"></i>Giao diện website</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <!-- Ô tick không tick thì trình duyệt KHÔNG gửi gì cả. Input hidden
                         cùng tên đứng trước để lúc nào cũng có giá trị gửi lên; tick vào
                         thì giá trị của ô tick ghi đè lên nó. -->
                    <input type="hidden" name="show_car_filter" value="0"/>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" name="show_car_filter" id="show_car_filter" value="1" {{$s('show_car_filter','1')==='1'?'checked':''}}/>
                        <label class="custom-control-label" for="show_car_filter">Hiện thanh lọc xe ở đầu trang</label>
                    </div>
                    <small class="form-text text-muted">
                        Thanh chọn Thương hiệu / Dòng xe / Model / Năm sản xuất kèm ô tìm kiếm,
                        nằm giữa logo và menu chính. Tắt đi thì phần đầu trang chỉ còn logo và menu.
                    </small>
                </div>

                <div class="form-group mb-0">
                    <input type="hidden" name="show_topbar" value="0"/>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" name="show_topbar" id="show_topbar" value="1" {{$s('show_topbar','1')==='1'?'checked':''}}/>
                        <label class="custom-control-label" for="show_topbar">Hiện thanh xanh trên cùng</label>
                    </div>
                    <small class="form-text text-muted">
                        Thanh chứa "Xây dựng báo giá", "Hệ thống chi nhánh" và phần Đăng ký / Đăng nhập.
                        Tắt đi thì link Đăng ký / Đăng nhập (hoặc Tài khoản / Đăng xuất) tự dời xuống
                        hàng logo, cạnh giỏ hàng — khách vẫn đăng nhập được.
                    </small>
                </div>
            </div>
        </div>

        <?php /* SEO MẶC ĐỊNH CỦA CẢ TRANG. Từng bài viết / sản phẩm có ô meta
                 riêng ở màn của nó; ba ô dưới đây là cái dùng khi trang đó bỏ
                 trống. */ ?>
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-search mr-2"></i>SEO mặc định</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Meta description (mô tả SEO mặc định)</label>
                    <textarea name="meta_description" class="form-control" rows="2">{{$s('meta_description')}}</textarea>
                </div>
                <div class="form-group">
                    <label>Meta keywords</label>
                    <textarea name="meta_keywords" class="form-control" rows="2">{{$s('meta_keywords')}}</textarea>
                </div>
                <div class="form-group mb-0">
                    <label>Ảnh chia sẻ mạng xã hội (OG image)</label>
                    {!! !empty($settings['og_image']) ? '<img src="'.e(media_url($settings['og_image'])).'" style="height:60px;border-radius:4px;display:block;margin-bottom:6px"/>' : '' !!}
                    <input type="file" name="og_image_file" accept="image/*" class="form-control-file"/>
                    <input type="text" name="og_image" class="form-control mt-1" placeholder="hoặc URL ảnh" value="{{$s('og_image')}}"/>
                </div>
            </div>
        </div>

        <?php /* CHÂN TRANG — tài liệu khách 05/11/2026, Frontend mục 7:
                 "Phần footer: cũng là 1 bài viết có ckeditor, người dùng tự thêm".
                 Trước đây chân trang là markup cứng, gara muốn đổi một dòng
                 cũng phải nhờ lập trình.
                 Khối logo / địa chỉ / đăng ký nhận tin vẫn giữ nguyên bên dưới;
                 ô này chèn THÊM phần gara tự viết, không thay thế chúng — thay
                 thế là gara nào chưa viết gì sẽ mất trắng chân trang. */ ?>
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-shoe-prints mr-2"></i>Chân trang website</h3></div>
            <div class="card-body">
                <div class="form-group mb-1">
                    <label>Nội dung gara tự soạn</label>
                    <textarea name="footer_html" class="form-control" rows="6">{{$s('footer_html')}}</textarea>
                    <small class="form-text text-muted">
                        Hiện ở <b>chân trang website của gara</b>, phía trên khối địa chỉ.
                        Để trống thì chân trang giữ nguyên như cũ.
                        Thẻ <code>&lt;script&gt;</code> và mã chạy được sẽ bị loại bỏ khi lưu.
                    </small>
                </div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu cấu hình website</button></div>
        </div>
    </form>
</div></div>
<script src="{{asset('public/assets/js/soan-thao.js')}}"></script>
<script>
/* Ô chân trang thành trình soạn thảo có định dạng — khách yêu cầu "1 bài viết
   có ckeditor". Dùng trình tự viết (không CDN), cùng cái đang dùng ở màn Bài viết.
   Có kiểm window.oSoanThao: thiếu file js thì ô vẫn là textarea gõ HTML được,
   không làm hỏng cả màn Cấu hình. */
(function(){
    if (!window.oSoanThao) return;
    window.oSoanThao(document.querySelector('[name="footer_html"]'));
})();
</script>
