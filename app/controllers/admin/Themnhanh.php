<?php

use App\core\Controller;
use App\core\Request;

/**
 * THÊM NHANH — một đường duy nhất cho mọi nút + cạnh ô chọn.
 *
 * Luật của hệ thống: giá trị lặp lại giữa các bản ghi thì phải CHỌN từ danh mục,
 * không gõ tay. Luật đó chỉ sống được nếu thêm vào danh mục nhanh hơn việc gõ
 * tay — nếu không, người dùng sẽ tìm đường lách. Nút + gọi vào đây, thêm xong là
 * chọn được ngay, không rời form đang làm.
 *
 * BA CHỖ CHẶN, vì đây là đường ghi dữ liệu mở cho mọi màn:
 *
 *   1. Danh sách trắng loại danh mục (DanhMucNhanhModel::$loai) — không nhận
 *      tên bảng từ người gửi.
 *   2. Quyền THÊM ở đúng màn danh mục tương ứng. Nhân viên không được thêm
 *      "Danh mục phụ tùng" ở màn Danh mục thì cũng không được thêm qua nút +;
 *      nếu không, nút này thành đường vòng qua phân quyền.
 *   3. CSRF — middleware lo, vì đây là POST.
 */
class Themnhanh extends Controller {

    private $__model, $__vehicle, $__request;

    function __construct(){
        $this->__model   = $this->model('DanhMucNhanhModel');
        $this->__vehicle = $this->model('VehiclesModel');
        $this->__request = new Request();
    }

    private function ra($data){
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Danh mục xe có quan hệ riêng (cha con + năm theo khoảng) — nằm ở VehiclesModel */
    private static $xe = [
        'hang'  => 'car-brands',
        'model' => 'car-models',
        'nam'   => 'car-years',
        'mau'   => 'car-colors',
    ];

    public function danhMuc(){
        $f    = $this->__request->getFields();
        $loai = isset($f['loai']) ? (string) $f['loai'] : '';
        $ten  = isset($f['ten'])  ? (string) $f['ten']  : '';
        $cha  = isset($f['cha'])  ? (int) $f['cha']     : 0;

        if (trim($ten) === '') $this->ra(['ok' => false, 'loi' => 'Chưa nhập tên']);

        // --- Danh mục xe: chuyển sang VehiclesModel, quan hệ ở đó khác ---
        if (isset(self::$xe[$loai])){
            if (!$this->duocThem(self::$xe[$loai])){
                $this->ra(['ok' => false, 'loi' => 'Bạn không có quyền thêm vào danh mục này']);
            }
            if (in_array($loai, ['model', 'nam'], true) && $cha <= 0){
                $this->ra(['ok' => false, 'loi' => $loai === 'model' ? 'Chọn hãng trước' : 'Chọn model trước']);
            }
            $kq = $this->__vehicle->themDanhMuc($loai, $ten, $cha);
            $this->ra(empty($kq) ? ['ok' => false, 'loi' => 'Không thêm được — kiểm tra lại tên']
                                 : ['ok' => true] + $kq);
        }

        // --- Danh mục đơn giản ---
        $ct = DanhMucNhanhModel::co($loai);
        if ($ct === null) $this->ra(['ok' => false, 'loi' => 'Loại danh mục không hợp lệ']);

        if (!$this->duocThem($ct['quyen'])){
            $this->ra(['ok' => false, 'loi' => 'Bạn không có quyền thêm ' . $ct['nhan']]);
        }

        $kq = $this->__model->them($loai, $ten, $cha);
        $this->ra(empty($kq) ? ['ok' => false, 'loi' => 'Không thêm được — kiểm tra lại tên']
                             : ['ok' => true] + $kq);
    }

    /**
     * Người đang đăng nhập có quyền THÊM ở màn $man không.
     *
     * Dùng lại đúng route('admin/<man>/add') mà menu và các nút trên màn hình
     * đang dùng: nó chạy RoleMiddleware của chính route đó. Tự đọc bảng quyền ở
     * đây là dựng bản thứ hai của cùng một luật, và hai bản sẽ lệch nhau.
     */
    private function duocThem($man){
        return route('admin/' . $man . '/add') !== false;
    }
}
