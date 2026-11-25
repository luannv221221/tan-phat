<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

require_once __DIR__ . '/Doimatkhau.php';

/**
 * HỒ SƠ CỦA TÔI — người đang đăng nhập tự sửa tên, số điện thoại, địa chỉ,
 * ảnh đại diện của CHÍNH mình; cùng trang có phần đổi mật khẩu.
 *
 * Khách ghi 25/11/2026 ở dòng "Xây dựng chức năng đăng xuất, đăng nhập":
 * "Cho sửa profile thay cho đổi mật khẩu, ảnh, tên, địa chỉ…". Trước đó menu
 * tài khoản chỉ có "Đổi mật khẩu"; muốn sửa tên mình phải nhờ người có quyền
 * vào màn Người dùng.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  VÌ SAO KHÔNG CÓ MODULE `ho-so` — cùng lý do với Đổi mật khẩu / Lối tắt
 * ──────────────────────────────────────────────────────────────────────────
 * Sửa hồ sơ của chính mình không phải một quyền để cấp hay thu. Có module là
 * có ô tick ở màn Phân quyền, bỏ tick là cả nhóm không sửa được tên mình nữa.
 * Đường dẫn không khớp module thì RoleMiddleware cho qua, AuthMiddleware vẫn
 * đòi đăng nhập.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  BA ĐIỀU KHÔNG ĐƯỢC SAI
 * ──────────────────────────────────────────────────────────────────────────
 *   1. CHỈ SỬA DÒNG CỦA CHÍNH MÌNH. Mã người dùng lấy từ phiên đăng nhập
 *      (nguoi_dang_nhap()), KHÔNG đọc `id` từ form hay địa chỉ.
 *
 *   2. CHỈ GHI ĐÚNG BỐN CỘT HỒ SƠ (name / phone / address / avatar). Mảng ghi
 *      dựng tay từng cột, không đổ cả form vào: gửi thêm `group_id`,
 *      `garage_id`, `status`, `email`, `password` là tự nâng quyền, tự chuyển
 *      gara, tự đổi tên đăng nhập, đổi mật khẩu không cần mật khẩu cũ.
 *      EMAIL CHỈ ĐỂ XEM: nó là tên đăng nhập cho cả nền tảng (mọi gara), đổi
 *      email là việc của người quản trị ở màn Người dùng, nơi có chốt trùng.
 *
 *   3. ẢNH ĐI QUA upload_image() — danh sách đuôi trắng, ≤ 3MB, getimagesize(),
 *      tên tệp do mình đặt lại hoàn toàn. Thư mục ảnh được web phục vụ thẳng,
 *      nên tên / kiểu tệp người dùng gửi lên không được tin.
 *
 * ĐỔI MẬT KHẨU KHÔNG VIẾT LẠI Ở ĐÂY: form trong trang gửi vào
 * `admin/doi-mat-khau` (Doimatkhau::postDoi) — vẫn hỏi mật khẩu hiện tại, vẫn
 * đá các phiên khác ra, rồi quay về trang này. Một nơi đổi mật khẩu duy nhất.
 */
class Hoso extends Controller {

    /** Họ tên tối thiểu — ĐÚNG BẰNG luật của màn Người dùng (Users: name min:4).
     *  Nới riêng ở đây thì người quản trị mở màn Người dùng bấm Lưu là bị báo
     *  lỗi tên do chính người kia tự đặt — hai luật cho một thứ. */
    const TEN_TOI_THIEU = 4;
    /** Bằng độ dài cột: `users`.`name` VARCHAR(100), `phone` 20, `address` 255. */
    const TEN_TOI_DA     = 100;
    const DT_TOI_DA      = 20;
    const DIA_CHI_TOI_DA = 255;

    /** Thư mục ảnh đại diện (tương đối với gốc dự án, giống upload_image()). */
    const THU_MUC_ANH = 'avatars';

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

        /* Bị từ chối thì hiện lại đúng chữ đã gõ, không bắt gõ lại. */
        $old = Session::flash('old');
        $old = is_array($old) ? $old : [];
        $giaTri = function($k) use ($old, $u){
            if (array_key_exists($k, $old)) return (string) $old[$k];
            return isset($u[$k]) ? (string) $u[$k] : '';
        };

        $c = &$this->__data['content'];
        $c['page_name']  = 'Hồ sơ của tôi';
        $c['nguoiDung']  = $u;
        $c['ten']        = $giaTri('name');
        $c['dienThoai']  = $giaTri('phone');
        $c['diaChi']     = $giaTri('address');
        $c['anh']        = !empty($u['avatar']) ? media_url($u['avatar']) : '';
        $c['toiThieuMk'] = Doimatkhau::DAI_TOI_THIEU;
        $c['msg']        = Session::flash('msg');
        $c['msgError']   = Session::flash('msgError');
        $c['errors']     = Session::flash('errors');
        /* Phần đổi mật khẩu có thông báo RIÊNG, in ngay trong khung của nó: sau
           khi đổi, trang nhảy xuống #doi-mat-khau, thông báo ở đầu trang thì trên
           điện thoại nằm khuất phía trên, người dùng không thấy gì xảy ra. */
        $c['mkMsg']      = Session::flash('mkMsg');
        $c['mkMsgError'] = Session::flash('mkMsgError');
        $c['mkErrors']   = Session::flash('mkErrors');
        unset($c);

        $this->__data['sub_content'] = 'admin/ho-so/form';
        $this->__data['page_title']  = 'Hồ sơ của tôi';
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postLuu(){
        $u = nguoi_dang_nhap();
        if (empty($u)){ $this->__response->redirect('dang-nhap'); return; }

        $f       = $this->__request->getFields();
        $ten     = isset($f['name'])    && is_string($f['name'])    ? $f['name']    : '';
        $dt      = isset($f['phone'])   && is_string($f['phone'])   ? $f['phone']   : '';
        $diaChi  = isset($f['address']) && is_string($f['address']) ? $f['address'] : '';
        $xoaAnh  = !empty($f['xoa_anh']);

        $errors = [];
        if ($ten === ''){
            $errors['name'] = 'Nhập họ tên';
        } elseif (mb_strlen($ten) < self::TEN_TOI_THIEU){
            $errors['name'] = 'Họ tên tối thiểu ' . self::TEN_TOI_THIEU . ' ký tự';
        } elseif (mb_strlen($ten) > self::TEN_TOI_DA){
            $errors['name'] = 'Họ tên tối đa ' . self::TEN_TOI_DA . ' ký tự';
        }
        /* Bỏ trống được; có gõ thì phải là số Việt Nam hợp lệ — cùng is_phone()
           với mọi form khác trong hệ thống. */
        if ($dt !== '' && (mb_strlen($dt) > self::DT_TOI_DA || !is_phone($dt))){
            $errors['phone'] = 'Số điện thoại không hợp lệ (vd 0912 345 678)';
        }
        if (mb_strlen($diaChi) > self::DIA_CHI_TOI_DA){
            $errors['address'] = 'Địa chỉ tối đa ' . self::DIA_CHI_TOI_DA . ' ký tự';
        }

        /* Ảnh tải lên SAU khi chữ đã hợp lệ: chữ sai mà vẫn lưu ảnh là để lại
           một tệp mồ côi trên đĩa mỗi lần bấm Lưu. */
        $anhMoi = '';
        if (empty($errors)){
            $up = upload_image('avatar_file', self::THU_MUC_ANH, 'avatar');
            if ($up['status'] === 'ok'){
                $anhMoi = $up['path'];
            } elseif ($up['status'] !== 'none'){
                $errors['avatar_file'] = 'Ảnh đại diện lỗi: ' . $up['message'];
            }
        }

        if (!empty($errors)){
            Session::flash('errors', $errors);
            Session::flash('msgError', 'Vui lòng kiểm tra các lỗi bên dưới');
            Session::flash('old', ['name' => $ten, 'phone' => $dt, 'address' => $diaChi]);
            $this->__response->redirect('admin/ho-so'); return;
        }

        /* DỰNG TAY TỪNG CỘT — xem điều 2 đầu file. Lưu NGUYÊN VĂN, escape lúc in. */
        $data = [
            'name'      => $ten,
            'phone'     => $dt     === '' ? null : $dt,
            'address'   => $diaChi === '' ? null : $diaChi,
            'update_at' => date('Y-m-d H:i:s'),
        ];
        $anhCu = isset($u['avatar']) ? (string) $u['avatar'] : '';
        if ($anhMoi !== '')  $data['avatar'] = $anhMoi;
        elseif ($xoaAnh)     $data['avatar'] = null;

        if (!$this->__model->edit($data, (int) $u['id'])){
            if ($anhMoi !== '') $this->xoaTepAnh($anhMoi);
            Session::flash('msgError', 'Không lưu được hồ sơ, vui lòng thử lại');
            Session::flash('old', ['name' => $ten, 'phone' => $dt, 'address' => $diaChi]);
            $this->__response->redirect('admin/ho-so'); return;
        }

        /* Ảnh cũ đã bị thay hoặc bị xoá thì dọn tệp, không để rác tích dần. */
        if (array_key_exists('avatar', $data) && $anhCu !== '' && $anhCu !== $anhMoi){
            $this->xoaTepAnh($anhCu);
        }

        Session::flash('msg', 'Đã lưu hồ sơ.');
        $this->__response->redirect('admin/ho-so');
    }

    /**
     * Xoá tệp ảnh đại diện — CHỈ khi đường dẫn đúng hình dạng do upload_image()
     * sinh ra trong thư mục ảnh đại diện. Giá trị trong cột có thể đã bị ai đó
     * sửa tay trong CSDL; không kiểm thì một dòng `../../config.php` là xoá
     * được tệp bất kỳ.
     */
    private function xoaTepAnh($duong){
        $duong = (string) $duong;
        if (!preg_match('~^public/assets/uploads/' . self::THU_MUC_ANH . '/[a-z0-9-]+\.(jpg|jpeg|png|gif|webp)$~', $duong)) return;
        if (is_file($duong)) @unlink($duong);
    }
}
