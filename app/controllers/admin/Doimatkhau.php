<?php

use App\core\Controller;
use App\core\Hash;
use App\core\Load;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * ĐỔI MẬT KHẨU của chính tài khoản đang đăng nhập.
 *
 * Khách nhắn qua Zalo sau buổi họp 11/11/2026: "thiếu đổi password em nhé".
 * Đúng — menu tài khoản góc phải chỉ có "Xem trang chủ" và "Đăng xuất". Muốn
 * đổi mật khẩu thì phải nhờ người có quyền vào màn Người dùng sửa hộ, tức là
 * mật khẩu mới đi qua tay người khác. Trang khách (Member) đã có chỗ đổi từ
 * lâu; trang quản trị thì chưa.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  VÌ SAO MÀN NÀY KHÔNG ĐĂNG KÝ VÀO BẢNG `modules`
 * ──────────────────────────────────────────────────────────────────────────
 * Đổi mật khẩu của CHÍNH MÌNH không phải một quyền để cấp hay thu. Cho vào
 * `modules` là sinh ra một ô tick ở màn Phân quyền, và chỉ cần ai đó bỏ tick
 * là cả nhóm đó không đổi được mật khẩu nữa — một cái bẫy không có lý do tồn
 * tại.
 *
 * Đường dẫn không khớp module nào thì RoleMiddleware đi qua mà không chặn
 * (đã thử thật: `admin/dia-gioi/tinh` chạy bình thường với tài khoản Staff),
 * còn AuthMiddleware vẫn đòi đăng nhập. Đúng mức cần: ai đăng nhập được thì
 * đổi được mật khẩu của mình, không hơn.
 *
 * TUYẾN POST ĐẶT TRÙNG TÊN TUYẾN GET (`doi-mat-khau`) — CÓ CHỦ Ý. Bảng
 * middleware đánh khoá theo chuỗi đường dẫn chứ không theo phương thức, mà
 * Route::post() không ghi tuyến vào nhóm `admin/*` (chỉ Route::get() ghi).
 * Đặt tên khác, ví dụ `doi-mat-khau/luu`, thì tuyến POST đó KHÔNG có
 * AuthMiddleware — người chưa đăng nhập gửi thẳng POST vào cũng chạy tới
 * controller. Trùng tên thì nó mượn được middleware của tuyến GET.
 */
class Doimatkhau extends Controller {

    /** Tối thiểu 6 ký tự — ĐÚNG BẰNG luật của màn Người dùng (Users::rules).
     *  Đặt chặt hơn ở riêng đây thì người dùng bị từ chối mật khẩu mà màn kia
     *  vừa chấp nhận cho chính họ — hai luật cho một thứ. */
    const DAI_TOI_THIEU = 6;

    private $__data = [];
    private $__model, $__request, $__response;

    function __construct(){
        $this->__model    = $this->model('UsersModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    public function index(){
        $u = nguoi_dang_nhap();
        if (empty($u)){ $this->__response->redirect('dang-nhap'); return; }

        $this->__data['sub_content'] = 'admin/doi-mat-khau/form';
        $this->__data['page_title']  = 'Đổi mật khẩu';
        $this->__data['content']['page_name'] = 'Đổi mật khẩu';
        $this->__data['content']['nguoiDung'] = $u;
        $this->__data['content']['toiThieu']  = self::DAI_TOI_THIEU;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['msgError']  = Session::flash('msgError');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postDoi(){
        $u = nguoi_dang_nhap();
        if (empty($u)){ $this->__response->redirect('dang-nhap'); return; }

        $f      = $this->__request->getFields();
        $hienTai = isset($f['current_password']) ? (string) $f['current_password'] : '';
        $moi     = isset($f['new_password'])     ? (string) $f['new_password']     : '';
        $lai     = isset($f['new_password2'])    ? (string) $f['new_password2']    : '';

        $errors = [];

        /* KIỂM MẬT KHẨU HIỆN TẠI TRƯỚC MỌI THỨ KHÁC. Không hỏi lại thì ai ngồi
           vào máy đang mở sẵn phiên là chiếm được tài khoản vĩnh viễn — chỉ
           cần đặt mật khẩu mới rồi chủ máy không vào được nữa. */
        if ($hienTai === ''){
            $errors['current_password'] = 'Nhập mật khẩu hiện tại';
        } elseif (!Hash::check($hienTai, $u['password'])){
            $errors['current_password'] = 'Mật khẩu hiện tại không đúng';
        }

        if ($moi === ''){
            $errors['new_password'] = 'Nhập mật khẩu mới';
        } elseif (mb_strlen($moi) < self::DAI_TOI_THIEU){
            $errors['new_password'] = 'Mật khẩu mới tối thiểu ' . self::DAI_TOI_THIEU . ' ký tự';
        } elseif ($moi === $hienTai){
            /* Không phải bắt bẻ vặt: người đổi mật khẩu vì nghi bị lộ mà gõ
               lại đúng mật khẩu cũ thì màn hình báo "đổi thành công" trong khi
               chẳng có gì đổi cả. */
            $errors['new_password'] = 'Mật khẩu mới phải khác mật khẩu hiện tại';
        }

        if ($lai !== $moi){
            $errors['new_password2'] = 'Mật khẩu nhập lại không khớp';
        }

        if (!empty($errors)){
            Session::flash('errors', $errors);
            Session::flash('msgError', 'Vui lòng kiểm tra các lỗi bên dưới');
            $this->__response->redirect('admin/doi-mat-khau'); return;
        }

        $this->__model->edit(['password' => Hash::make($moi)], (int) $u['id']);

        /* ĐÁ MỌI PHIÊN KHÁC RA, giữ lại phiên đang thao tác.
           Cookie "Ghi nhớ đăng nhập" sống 30 ngày, nên không làm việc này thì
           máy lạ nào đó còn vào được tài khoản tới một tháng nữa — đúng điều
           người ta đổi mật khẩu để ngăn. */
        $tokenId = Session::get('dataToken');
        if (!empty($tokenId)){
            Load::model('LoginToken')->removeKhacPhien((int) $u['id'], (int) $tokenId);
        }

        /* Cấp session id mới: mật khẩu vừa đổi thì phiên cũng nên mới. */
        Session::regenerate();

        Session::flash('msg', 'Đã đổi mật khẩu. Các thiết bị khác đang đăng nhập tài khoản này đã bị đăng xuất.');
        $this->__response->redirect('admin/doi-mat-khau');
    }
}
