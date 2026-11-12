<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * LỐI TẮT TRÊN THANH TRÊN CÙNG — mỗi người tự chọn.
 *
 * Khách yêu cầu 12/11/2026: "làm cả cái nút shortcut trên menu cho anh người
 * ta có thể chủ động set". Trước đó thanh trên có ba nút viết cứng (Bán hàng /
 * Mua hàng / Chăm sóc) — ai cũng thấy đúng ba nút ấy.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  VÌ SAO MÀN NÀY KHÔNG ĐĂNG KÝ VÀO BẢNG `modules`
 * ──────────────────────────────────────────────────────────────────────────
 * Cùng lý do với màn Đổi mật khẩu: lối tắt của CHÍNH MÌNH không phải một
 * quyền để cấp hay thu. Có module là có một ô tick ở màn Phân quyền, và chỉ cần
 * ai đó bỏ tick là cả nhóm không đặt được lối tắt cho mình nữa.
 *
 * Đường dẫn không khớp module thì RoleMiddleware cho qua (Fix B ngày 11/11),
 * AuthMiddleware vẫn đòi đăng nhập. Đúng mức cần.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  BA ĐIỀU KHÔNG ĐƯỢC SAI — mọi POST ở đây đi qua cả ba
 * ──────────────────────────────────────────────────────────────────────────
 *
 *   1. CHỈ SỬA DÒNG CỦA CHÍNH MÌNH. Mã người dùng lấy từ phiên đăng nhập
 *      (nguoi_dang_nhap()), TUYỆT ĐỐI không đọc từ form. Không thì gửi tay
 *      `user_id=…` là sửa được lối tắt của người khác.
 *
 *   2. CHỈ NHẬN KHOÁ TRONG DANH MỤC ĐÓNG (loi_tat_danh_muc). Lối tắt in ra
 *      thành <a href> trên mọi trang của họ, nên nhận URL tự do là cho nhét
 *      `javascript:` vào chỗ họ bấm nhiều nhất.
 *
 *   3. CHỈ NHẬN MÀN MÀ NGƯỜI ĐÓ CÓ QUYỀN VÀO. Cho ghim màn không có quyền thì
 *      nút bấm vào ăn trang từ chối. Lúc vẽ thanh trên còn lọc lần nữa, vì
 *      quyền đổi được sau khi ghim — xem loi_tat_cua_toi().
 */
class Loitat extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    function __construct(){
        $this->__model    = $this->model('UsersModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    /**
     * Những khoá người này ĐƯỢC PHÉP ghim, kèm thông tin để vẽ, theo đúng thứ
     * tự danh mục. Lọc bằng route() — cùng bộ máy phân quyền mà menu dùng.
     */
    private function duocPhep(){
        $dm = loi_tat_danh_muc($this->model('ModulesModel')->getLists());
        $ra = [];
        foreach ($dm as $khoa => $thongTin){
            if (route('admin/' . $khoa)) $ra[$khoa] = $thongTin;
        }
        return $ra;
    }

    public function index(){
        $u = nguoi_dang_nhap();
        if (empty($u)){ $this->__response->redirect('dang-nhap'); return; }

        $duocPhep = $this->duocPhep();

        /* Đang ghim = cột của chính người này, lọc theo cái họ còn được phép:
           một mục đã bị rút quyền thì không tính vào "đã chọn", nếu không bộ
           đếm "x / 6" hiện số lớn hơn số hộp được tích. */
        $dangGhim = [];
        foreach (loi_tat_giai_ma(isset($u['shortcuts']) ? $u['shortcuts'] : null) as $k){
            if (is_string($k) && isset($duocPhep[$k])) $dangGhim[$k] = true;
        }

        $nhom = [];
        foreach ($duocPhep as $khoa => $tt){
            $nhom[$tt['nhom']][$khoa] = $tt;
        }

        $this->__data['sub_content'] = 'admin/loi-tat/form';
        $this->__data['page_title']  = 'Lối tắt của tôi';
        $this->__data['content']['page_name'] = 'Lối tắt của tôi';
        $this->__data['content']['nhom']      = $nhom;
        $this->__data['content']['dangGhim']  = $dangGhim;
        $this->__data['content']['toiDa']     = loi_tat_toi_da();
        $this->__data['content']['daTuyChinh'] = isset($u['shortcuts']) && $u['shortcuts'] !== null && $u['shortcuts'] !== '';
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['msgError']  = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postLuu(){
        $u = nguoi_dang_nhap();
        if (empty($u)){ $this->__response->redirect('dang-nhap'); return; }

        $f    = $this->__request->getFields();
        $chon = (isset($f['loi_tat']) && is_array($f['loi_tat'])) ? $f['loi_tat'] : [];

        $duocPhep = $this->duocPhep();

        /* DUYỆT THEO DANH MỤC chứ không theo thứ tự gửi lên: thứ tự trên thanh
           luôn là thứ tự danh mục, và khoá lạ / khoá không có quyền tự rơi ra
           ngoài mà không cần một nhánh báo lỗi riêng. */
        $giu = [];
        foreach ($duocPhep as $khoa => $tt){
            if (in_array($khoa, $chon, true)) $giu[] = $khoa;
        }

        /* QUÁ SỐ LƯỢNG THÌ TỪ CHỐI, không lặng lẽ cắt bớt. Cắt thì người dùng
           tích bảy mục, bấm Lưu, và mục thứ bảy biến mất mà không ai nói. */
        if (count($giu) > loi_tat_toi_da()){
            Session::flash('msgError', 'Chỉ ghim được tối đa ' . loi_tat_toi_da()
                . ' lối tắt — bạn đã chọn ' . count($giu) . '. Bỏ bớt rồi lưu lại.');
            $this->__response->redirect('admin/loi-tat'); return;
        }

        /* '[]' chứ không phải NULL khi bỏ hết: NULL nghĩa là "chưa tuỳ chỉnh"
           và sẽ mọc lại ba nút mặc định. Xem migration 000111. */
        /* JSON_UNESCAPED_SLASHES: mặc định json_encode viết `/` thành `\/`, và các khoá
           ở đây đầy dấu gạch chéo ("quotations/add"). Đọc lại vẫn đúng, nhưng ai mở
           bảng `users` ra xem thì thấy một đống gạch ngược. */
        $this->luu($u['id'], json_encode($giu, JSON_UNESCAPED_SLASHES));

        Session::flash('msg', empty($giu)
            ? 'Đã bỏ hết lối tắt trên thanh trên cùng.'
            : 'Đã lưu ' . count($giu) . ' lối tắt.');
        $this->__response->redirect('admin/loi-tat');
    }

    public function postMacDinh(){
        $u = nguoi_dang_nhap();
        if (empty($u)){ $this->__response->redirect('dang-nhap'); return; }

        $this->luu($u['id'], null);

        Session::flash('msg', 'Đã về bộ lối tắt mặc định (Bán hàng, Mua hàng, Chăm sóc).');
        $this->__response->redirect('admin/loi-tat');
    }

    /** Ghi cột của ĐÚNG người này. $userId lấy từ phiên, không từ form. */
    private function luu($userId, $json){
        $this->__model->edit(['shortcuts' => $json], (int) $userId);
    }
}
