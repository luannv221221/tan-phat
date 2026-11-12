<?php
$s = function($key, $default = '') use ($settings){ return isset($settings[$key]) && $settings[$key] !== null ? $settings[$key] : $default; };
?>
<div class="row"><div class="col-lg-9 mx-auto">
    @if (!empty($msg))
    <div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
    @endif

    <?php /* Nói rõ đang sửa CÁI GÌ. Cùng một màn nhưng gara tổng sửa mặc định
             chung cho mọi gara, còn gara khác chỉ sửa của riêng mình — không
             nói ra thì người dùng không biết mình vừa đổi phạm vi nào. */ ?>
    @if (!empty($laChung))
    <div class="alert alert-warning py-2">
        <i class="fas fa-globe mr-1"></i>
        Đây là <b>cấu hình chung</b> — làm mặc định cho mọi gara chưa tự khai.
        Gara nào đã tự đặt thì giữ giá trị của họ.
    </div>
    @else
    <div class="alert alert-info py-2">
        <i class="fas fa-store mr-1"></i>
        Đây là cấu hình <b>riêng của gara {{$garaTen}}</b> — chỉ đổi trang web và
        biểu mẫu của gara này. Ô nào để nguyên thì dùng mặc định chung.
    </div>
    @endif

    <form action="{{_WEB_URL.'/admin/settings/save'}}" method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <div class="card card-outline card-primary">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-cog mr-2"></i>Vận hành gara</h3></div>
            <div class="card-body">
                <?php /* TÊN, LOGO, LIÊN HỆ ĐÃ CHUYỂN SANG "CẤU HÌNH WEBSITE" (12/11/2026) — khách
                         yêu cầu: "cái này phải ở bên Quản lý website". Ở lại đây là những ô
                         quyết định CÁCH GARA LÀM VIỆC chứ không phải trang khách hiển thị
                         gì: lĩnh vực, diện tích xưởng, cách tính giá vốn, lịch cập nhật giá. */ ?>
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
                        <option value="gara" <?php echo $lv !== 'phu_tung' ? 'selected' : ''; ?>>Gara ô tô (sửa chữa, bảo dưỡng)</option>
                        <option value="phu_tung" <?php echo $lv === 'phu_tung' ? 'selected' : ''; ?>>Bán phụ tùng, thiết bị</option>
                    </select>
                    <small class="form-text text-muted">
                        Chọn <b>Gara ô tô</b> thì màn Lập báo giá có thêm Hãng bảo hiểm, Bảo hiểm trả,
                        Biển số xe và Số km vào. Chọn <b>Bán phụ tùng</b> thì báo giá chỉ cần thông tin khách hàng.
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
                        Để trống nếu không có xưởng. Dùng cho hồ sơ năng lực của gara.
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
                <div class="form-group">
                    <label>Lịch tự tính lại bảng giá</label>
                    <?php $ps = $s('price_schedule', 'tat'); ?>
                    <select name="price_schedule" class="form-control">
                        <?php foreach (['tat' => 'Không tự chạy', 'ngay' => 'Hằng ngày',
                                        'tuan' => 'Hằng tuần', 'thang' => 'Hằng tháng'] as $k => $ten): ?>
                        <option value="<?php echo e($k); ?>" <?php echo $ps === $k ? 'selected' : ''; ?>><?php echo e($ten); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">
                        Tới kỳ thì bảng giá tự tính lại khi có người mở màn Bảng tính giá. Lúc nào cũng bấm
                        <b>Cập nhật giá</b> ở màn đó được, không cần chờ tới kỳ.
                    </small>
                </div>
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
                <div class="form-row">
                    <div class="form-group col-md-4"><label>Ngân hàng</label><input type="text" name="bank_name" class="form-control" value="{{$s('bank_name')}}"/></div>
                    <div class="form-group col-md-4"><label>Số tài khoản</label><input type="text" name="bank_account" class="form-control" value="{{$s('bank_account')}}"/></div>
                    <div class="form-group col-md-4"><label>Chủ tài khoản</label><input type="text" name="bank_holder" class="form-control" value="{{$s('bank_holder')}}"/></div>
                </div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu cấu hình</button></div>
        </div>
    </form>
</div></div>
