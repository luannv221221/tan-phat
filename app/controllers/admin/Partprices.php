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
         * Đây là cách 15 tuyến POST khác trong hệ đang tự giữ mình (xem
         * Settings::save, Themnhanh::danhMuc). Vá ở core/Route.php thì sửa
         * được cả 15 cùng lúc, nhưng KHÔNG phải một dòng: có tuyến không khớp
         * module nào (`them-nhanh/danh-muc`), mà RoleMiddleware gặp đường dẫn
         * không khớp module thì trả về null = CHẶN — bật lên là gãy nút thêm
         * nhanh ở mọi màn. Việc đó tách riêng, không làm lẫn vào đây.
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
