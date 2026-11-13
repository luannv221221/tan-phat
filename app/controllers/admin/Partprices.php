<?php

use App\core\Controller;
use App\core\Load;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * HÀNG HOÁ — Bảng tính giá (giá vốn + giá bán theo từng nhóm khách).
 *
 * Yêu cầu khách 11/11/2026. Xem khối chú thích đầu PartPricesModel để biết
 * giá vốn lấy từ đâu và vì sao không có ô nào gõ được giá vốn.
 *
 * MÀN HÌNH NÀY CHỈ ĐỌC. Không có nút Sửa, không có nút Xoá — cố ý. Muốn đổi
 * giá bán thì đổi HỆ SỐ LÃI ở Nhóm khách hàng hoặc ô CHI PHÍ ở Hàng hoá rồi
 * bấm Cập nhật giá; sửa thẳng một ô giá ở đây là lần tính sau mất trắng.
 */
class Partprices extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'part-prices';

    function __construct(){
        $this->__model    = $this->model('PartPricesModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    public function index(){
        /* Lịch khách đặt (ngày / tuần / tháng) chạy ở đây: hosting không có
           cron, nên "tới hạn thì tính lại" được kiểm ngay lúc mở màn. Rẻ —
           một lần đọc cấu hình và một phép so ngày. */
        $daTuChay = $this->__model->chayNeuDenHan();

        $tuKhoa = trim((string) ($_GET['q'] ?? ''));
        $bang   = $this->__model->bang($tuKhoa);

        $this->__data['sub_content'] = 'admin/part-prices/lists';
        $this->__data['page_title']  = 'Bảng tính giá';
        $this->__data['content']['page_name']  = 'Bảng tính giá';
        $this->__data['content']['routeBase']  = $this->routeBase;
        $this->__data['content']['nhom']       = $bang['nhom'];
        $this->__data['content']['dataList']   = $bang['dong'];
        $this->__data['content']['tuKhoa']     = $tuKhoa;
        $this->__data['content']['kieu']       = $this->__model->kieuGiaVon();
        $this->__data['content']['tenKieu']    = PartPricesModel::KIEU;
        $this->__data['content']['chuKy']      = $this->__model->chuKyLich();
        $this->__data['content']['tenChuKy']   = PartPricesModel::LICH;
        $this->__data['content']['tinhLanCuoi']= $this->__model->tinhLanCuoi();
        $this->__data['content']['kyKeTiep']   = $this->__model->kyKeTiep();
        $this->__data['content']['coQuyenTinh']= $this->coQuyenTinh();
        $this->__data['content']['msg']        = Session::flash('msg');
        $this->__data['content']['msgError']   = Session::flash('msgError');

        if ($daTuChay){
            $this->__data['content']['msg'] = 'Đã tới kỳ theo lịch — bảng giá vừa được cập nhật.';
        }

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    /**
     * NÚT TÍNH GIÁ — khách yêu cầu đúng bằng tên đó.
     *
     * POST chứ không phải GET: đây là lệnh GHI (viết lại cả bảng giá của
     * gara). Để ở GET thì chỉ cần dán đường dẫn vào thanh địa chỉ, hoặc một
     * thẻ <img> trên trang khác, là tính lại được — mà kèm CSRF thì GET cũng
     * không còn là đường dẫn chia sẻ được nữa.
     */
    public function postTinhlai(){
        /* ─────────────────────────────────────────────────────────────────
         * CHỐT QUYỀN NGAY Ở ĐÂY, KHÔNG TRÔNG VÀO MIDDLEWARE.
         * ─────────────────────────────────────────────────────────────────
         * `admin/*` trong configs/app.php được bung ra thành từng tuyến một
         * bởi App::updateConfigRouteMiddleware(), mà danh sách tuyến của nhóm
         * CHỈ được Route::get() ghi vào — Route::post() thì không (xem
         * core/Route.php). Bảng middleware đánh khoá theo CHUỖI ĐƯỜNG DẪN chứ
         * không theo phương thức, nên tuyến POST nào trùng tên với một tuyến
         * GET thì vẫn được bảo vệ ké; tuyến CHỈ CÓ POST thì không.
         *
         * `part-prices/edit/tinh-lai` chỉ có POST, nên AuthMiddleware và
         * RoleMiddleware KHÔNG chạy cho nó. Đã thử thật 11/11/2026: tài khoản
         * Staff (chỉ có quyền `view`) gửi POST thẳng vào đây thì bảng giá vẫn
         * bị tính lại.
         *
         * CẬP NHẬT 11/11/2026 (cùng ngày, sau đó): lỗ này đã được VÁ ở gốc —
         * core/Route.php::post() nay ghi tuyến vào nhóm `admin/*`, và
         * RoleMiddleware có nhánh riêng cho đường dẫn không thuộc module nào.
         * Đoạn trên giữ lại để biết VÌ SAO có chốt này; chốt vẫn nên ở đây như
         * lớp thứ hai (phòng ai đó gỡ middleware), nhưng không còn là chốt DUY
         * NHẤT như lúc viết.
         */
        if (!$this->coQuyenTinh()){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }

        $kq = $this->__model->tinhLai();

        if ((int) $kq['dong'] === 0){
            Session::flash('msgError',
                'Chưa tính được dòng nào — gara cần có ít nhất một nhóm khách đang bật và một mặt hàng đang bán.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $tin = 'Đã cập nhật ' . number_format($kq['dong'], 0, ',', '.') . ' dòng giá.';
        if ((int) $kq['thieu_gia_von'] > 0){
            /* NÓI RA chứ không im lặng: mã chưa từng nhập kho thì giá vốn 0,
               giá bán cũng ra 0 — nhìn bảng mà không biết vì sao thì tưởng
               phần mềm hỏng. */
            $tin .= ' Có ' . (int) $kq['thieu_gia_von']
                  . ' mặt hàng chưa có giá vốn (chưa từng nhập kho) nên chưa ra được giá bán.';
        }
        Session::flash('msg', $tin);
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    /**
     * ĐẶT LỊCH TỰ CẬP NHẬT GIÁ (ngày / tuần / tháng) ngay trên màn này — khách
     * yêu cầu 13/11/2026: "chỗ này cho thêm cái lịch đặt tự động theo ngày, tuần,
     * tháng, như cái báo cáo kế toán".
     *
     * CÙNG QUYỀN VỚI VIỆC BẤM "CẬP NHẬT GIÁ" (`edit` trên part-prices): ai tự
     * tay tính lại được bảng giá thì được dặn nó tự tính lại. Ai chỉ có quyền
     * xem thì chỉ thấy lịch, không đặt được.
     *
     * Lệnh đăng ký dưới `part-prices/edit/lich` (không phải `part-prices/lich`)
     * vì RoleMiddleware chỉ đòi quyền `edit` cho đường dẫn có dạng `.../edit/*`;
     * đặt tên khác là nó chỉ đòi quyền `view`. Chốt `coQuyenTinh()` ở đầu hàm là
     * lớp thứ hai, như postTinhlai().
     *
     * KHÔNG nhận mã gara từ form: model ghi cho gara ĐANG LÀM VIỆC của phiên
     * đăng nhập (xem PartPricesModel::datChuKyLich).
     */
    public function postLich(){
        if (!$this->coQuyenTinh()){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }

        $f  = $this->__request->getFields();
        $ck = isset($f['price_schedule']) ? (string) $f['price_schedule'] : '';

        /* Giá trị lạ -> TỪ CHỐI, không lặng lẽ đổi thành "tắt". Người gõ tay một
           giá trị lạ không nên làm tắt lịch mà họ đã đặt từ trước. */
        if (!$this->__model->datChuKyLich($ck)){
            Session::flash('msgError', 'Lịch không hợp lệ — chọn một trong: ' . implode(', ', PartPricesModel::LICH) . '.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        Session::flash('msg', $ck === 'tat'
            ? 'Đã tắt lịch tự cập nhật. Bảng giá chỉ đổi khi bấm "Cập nhật giá".'
            : 'Đã đặt lịch tự cập nhật: ' . mb_strtolower(PartPricesModel::LICH[$ck]) . '.');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    /**
     * Nhóm của người đang đăng nhập có quyền `edit` trên màn này không —
     * CHỈ ĐỂ ẨN/HIỆN CÁI NÚT, không phải chốt chặn.
     *
     * Chốt chặn thật nằm ở đường dẫn: lệnh tính lại đăng ký dưới
     * `part-prices/edit/tinh-lai`, nên RoleMiddleware tự đòi quyền `edit`
     * bằng đúng nhánh `.../edit/*` có sẵn. Ai gõ thẳng đường dẫn vẫn bị chặn
     * dù hàm này có quên trả về đúng hay không.
     *
     * Không dùng route() được: route() tra bảng tuyến theo PHƯƠNG THỨC CỦA
     * REQUEST HIỆN TẠI. Lúc vẽ màn này là GET, mà tuyến tính lại là POST —
     * không khớp tuyến nào thì route() trả về đường dẫn chứ không kiểm quyền,
     * tức nút luôn hiện với mọi người.
     */
    private function coQuyenTinh(){
        $nhom = Load::model('GroupsModel')->getGroupByUser(Session::get('dataUser'));
        $gid  = !empty($nhom['group_id']) ? (int) $nhom['group_id'] : 0;
        if ($gid <= 0) return false;

        $mod = Load::model('ModulesModel')->findByLink($this->routeBase);
        if (empty($mod['id'])) return false;

        return isRole((int) $mod['id'], 'edit',
                      (array) Load::model('PermissionsModel')->getPermission($gid));
    }
}
