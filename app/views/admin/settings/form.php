<?php
$s = function($key, $default = '') use ($settings){ return isset($settings[$key]) && $settings[$key] !== null ? $settings[$key] : $default; };
?>
<div class="row"><div class="col-lg-9 mx-auto">
    @if (!empty($msg))
    <div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
    @endif
    @if (!empty($msgError))
    <div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-triangle mr-1"></i> {{$msgError}}</div>
    @endif

    <?php /* Nói rõ đang sửa CÁI GÌ. Cùng một màn nhưng gara tổng sửa mặc định
             chung cho mọi gara, còn gara khác chỉ sửa của riêng mình — không
             nói ra thì người dùng không biết mình vừa đổi phạm vi nào. */ ?>
    @if (!empty($laChung))
    <div class="alert alert-warning py-2">
        <i class="fas fa-globe mr-1"></i>
        Đây là <b>cấu hình chung</b> — làm mặc định cho mọi công ty chưa tự khai.
        Công ty nào đã tự đặt thì giữ giá trị của họ.
    </div>
    @else
    <div class="alert alert-info py-2">
        <i class="fas fa-store mr-1"></i>
        Đây là cấu hình <b>riêng của công ty {{$garaTen}}</b> — chỉ đổi trang web và
        biểu mẫu của công ty này. Ô nào để nguyên thì dùng mặc định chung.
    </div>
    @endif

    <form action="{{_WEB_URL.'/admin/settings/save'}}" method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <div class="card card-outline card-primary">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-cog mr-2"></i>Vận hành công ty</h3></div>
            <div class="card-body">
                <?php /* TÊN, LOGO, LIÊN HỆ ĐÃ CHUYỂN SANG "CẤU HÌNH WEBSITE" (12/11/2026) — khách
                         yêu cầu: "cái này phải ở bên Quản lý website". Ở lại đây là những ô
                         quyết định CÁCH GARA LÀM VIỆC chứ không phải trang khách hiển thị
                         gì: lĩnh vực, diện tích xưởng, cách tính giá vốn, lịch cập nhật giá. */ ?>
                <?php /* TÊN VIẾT TẮT — yêu cầu 29/10/2026; 12/11 theo tên + logo sang Cấu
                         hình website, khách ghi 25/11/2026 "chuyển từ website về cấu hình":
                         nó chỉ hiện ở menu trái TRANG QUẢN TRỊ (tên đầy đủ 35 ký tự phải kẹp
                         hai dòng trong sidebar 232px), trang khách không dùng tới. */ ?>
                <div class="form-group">
                    <label>Tên viết tắt doanh nghiệp</label>
                    <input type="text" name="site_short_name" class="form-control"
                           value="{{$s('site_short_name')}}" placeholder="VD: Tân Phát ETEK"/>
                    <small class="form-text text-muted">
                        Hiện ở <b>menu trái trang quản trị</b>, cạnh logo — thay cho tên đầy đủ.
                        Bỏ trống thì lấy tên website.
                    </small>
                </div>
                <?php /* LĨNH VỰC — yêu cầu 29/10/2026, ảnh minh hoạ khoanh đúng
                         chỗ này (cạnh Logo). Quyết định màn Lập báo giá có hiện
                         khối Hãng bảo hiểm / Biển số xe / Số km hay không: chỗ
                         chỉ bán phụ tùng thì bốn ô đó là ô thừa ở mọi phiếu.
                         Mặc định "gara ô tô" — thêm một ô cấu hình mà làm biến
                         mất mấy ô đang dùng là đổi hành vi sau lưng người ta. */ ?>
                <div class="form-group">
                    <label>Lĩnh vực</label>
                    <?php $lv = $s('linh_vuc', 'gara'); ?>
                    <select name="linh_vuc" class="form-control">
                        <?php /* Nhãn theo khách ghi 25/11/2026: "Lĩnh vực sửa thành (sửa chữa,
                                 bảo dưỡng gara và khác)". GIÁ TRỊ LƯU giữ nguyên `gara` /
                                 `phu_tung` — đổi giá trị là gara nào đã chọn sẽ bị đảo lựa chọn. */ ?>
                        <option value="gara" <?php echo $lv !== 'phu_tung' ? 'selected' : ''; ?>>Sửa chữa, bảo dưỡng gara</option>
                        <option value="phu_tung" <?php echo $lv === 'phu_tung' ? 'selected' : ''; ?>>Khác</option>
                    </select>
                    <small class="form-text text-muted">
                        Chọn <b>Sửa chữa, bảo dưỡng gara</b> thì màn Lập báo giá có thêm Hãng bảo hiểm, Bảo hiểm trả,
                        Biển số xe và Số km. Chọn <b>Khác</b> (vd. chỉ bán phụ tùng, thiết bị) thì báo giá chỉ cần thông tin khách hàng.
                    </small>
                </div>
                <?php /* DIỆN TÍCH XƯỞNG — tài liệu khách 05/11/2026, Frontend
                         mục 13. Để trống được: gara chỉ bán phụ tùng thì không
                         có xưởng nào để khai, bắt buộc điền là bắt người ta bịa
                         một con số. */ ?>
                <div class="form-group">
                    <label>Diện tích xưởng (m²)</label>
                    <input type="number" name="dien_tich_xuong" class="form-control" min="0" step="1"
                           value="{{$s('dien_tich_xuong')}}" placeholder="VD: 450"/>
                    <small class="form-text text-muted">
                        Để trống nếu không có xưởng. Dùng cho hồ sơ năng lực của công ty.
                    </small>
                </div>
                <?php /* BẢNG TÍNH GIÁ — khách chốt 11/11/2026, đúng hai dòng
                         trong sơ đồ: "cấu hình giá theo 2 kiểu: bình quân hoặc
                         gần nhất" và "nút tính giá — đặt lịch ngày, tuần,
                         tháng".

                         MẶC ĐỊNH LÀ BÌNH QUÂN vì đó đúng là cách kho đang chạy
                         từ ngày đầu (StocksModel::applyIn). Đặt mặc định khác
                         là đổi giá vốn của mọi gara mà không ai yêu cầu. */ ?>
                <div class="form-group">
                    <label>Kiểu tính giá vốn</label>
                    <?php $cm = $s('cost_method', 'binh_quan'); ?>
                    <select name="cost_method" class="form-control">
                        <option value="binh_quan" <?php echo $cm !== 'gan_nhat' ? 'selected' : ''; ?>>Bình quân gia quyền</option>
                        <option value="gan_nhat" <?php echo $cm === 'gan_nhat' ? 'selected' : ''; ?>>Giá nhập gần nhất</option>
                    </select>
                    <small class="form-text text-muted">
                        <b>Bình quân gia quyền</b>: trung bình theo lượng của mọi lần nhập còn tồn — đây là con số
                        phiếu xuất kho và lãi gộp đang dùng. <b>Giá nhập gần nhất</b>: lấy đơn giá của phiếu nhập
                        đã ghi sổ mới nhất. Đổi ô này chỉ đổi <a href="{{_WEB_URL.'/admin/part-prices'}}">Bảng tính giá</a>,
                        không đụng tới giá vốn đã ghi trên các phiếu cũ.
                    </small>
                </div>
                <?php /* LỊCH TỰ TÍNH LẠI BẢNG GIÁ ĐÃ CHUYỂN SANG MÀN BẢNG TÍNH GIÁ (13/11/2026).
                         Khách: "chỗ này cho thêm cái lịch đặt tự động theo ngày, tuần, tháng".
                         KHÔNG để cả hai nơi: form này điền sẵn giá trị đã trộn, và khi gara
                         tổng bấm Lưu thì giá trị riêng của Tân Phát bị chép thành mặc định
                         cho MỌI gara — đặt "Hằng ngày" cho mình ở màn giá, rồi sửa số tài
                         khoản ở đây là cả hệ thống tự tính lại giá hằng ngày. */ ?>
                <p class="text-muted small mb-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    Tên website, logo, hotline, email, địa chỉ, mã số thuế, Facebook / Zalo / Messenger
                    nằm ở <a href="{{_WEB_URL.'/admin/cau-hinh-website'}}">Quản lý website › Cấu hình website</a>.
                </p>
            </div>
        </div>

        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-university mr-2"></i>Tài khoản ngân hàng (hướng dẫn chuyển khoản)</h3></div>
            <div class="card-body">
                <?php /* NGÂN HÀNG LÀ Ô CHỌN (16/11/2026) — mã BIN của NAPAS, để hoá đơn
                         dựng được mã QR chuyển khoản (VietQR). Gõ tay tên ngân hàng thì
                         không suy ra được mã cho chắc, mà mã sai là tiền đi sai ngân hàng.
                         Không có nút "+ thêm nhanh": BIN do NAPAS cấp, không tự đặt được
                         (xem App\core\VietQr::NGAN_HANG).
                         Gara KHÔNG thừa hưởng tài khoản của Tân Phát: ô trống = gara chưa
                         khai = hoá đơn chưa có mã QR. */ ?>
                <?php $binHt = (string) $s('bank_bin'); $tenCu = (string) $s('bank_name'); ?>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Ngân hàng</label>
                        <select name="bank_bin" class="form-control js-search" data-placeholder="Gõ tên ngân hàng để tìm...">
                            <option value="">— Chưa chọn —</option>
                            <?php foreach ((array) $nganHang as $bin => $nh): ?>
                            <option value="<?php echo e($bin); ?>" <?php echo (string) $bin === $binHt ? 'selected' : ''; ?>><?php echo e($nh['ten'] . ' — ' . $nh['ten_day_du']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($binHt === '' && $tenCu !== ''): ?>
                        <small class="form-text text-danger">
                            Đang ghi tay: <b><?php echo e($tenCu); ?></b>. Chọn lại ngân hàng trong danh sách
                            để hoá đơn in được mã QR chuyển khoản.
                        </small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group col-md-4"><label>Số tài khoản</label><input type="text" name="bank_account" class="form-control" inputmode="numeric" value="<?php echo e($s('bank_account')); ?>"/></div>
                    <div class="form-group col-md-4"><label>Chủ tài khoản</label><input type="text" name="bank_holder" class="form-control" placeholder="VD: CONG TY TNHH ABC" value="<?php echo e($s('bank_holder')); ?>"/></div>
                </div>
                <small class="form-text text-muted">
                    <i class="fas fa-qrcode mr-1"></i>
                    Khai đủ ba ô thì hoá đơn bán in thêm <b>mã QR chuyển khoản</b>: khách mở app ngân hàng quét
                    là ra sẵn số tài khoản, số tiền của hoá đơn và nội dung là số hoá đơn.
                    Để trống số tài khoản thì hoá đơn không in mã QR.
                </small>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu cấu hình</button></div>
        </div>
    </form>
</div></div>
