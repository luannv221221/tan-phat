<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * HÀNG HOÁ — Danh mục của gara.
 *
 * Nơi một gara dựng danh mục riêng của mình. Danh mục đó gồm hai phần:
 *
 *   1. HÀNG TỔNG GARA CHỌN LÀM — tick từ danh mục chung, đặt giá riêng nếu muốn.
 *      Bỏ trống ô giá = vẫn làm mặt hàng đó, nhưng lấy giá tổng.
 *
 *   2. HÀNG RIÊNG — phụ tùng mua ngoài, dịch vụ đặc thù chỉ gara đó có.
 *
 * Form thêm hàng riêng CỐ Ý gọn (tên, loại, đơn vị, giá): phần lớn thứ gara tự
 * thêm là công thợ — "Thay dầu 30 phút", "Vệ sinh kim phun". Bắt điền danh mục,
 * hãng, xuất xứ, ảnh, thông số kỹ thuật cho một dòng công thợ là bắt làm việc
 * vô nghĩa. Cần khai đầy đủ thì vẫn có màn Hàng hoá như cũ.
 *
 * Màn này luôn làm việc trên GARA ĐANG CHỌN (ô đổi gara ở thanh đầu trang),
 * không có ô chọn gara riêng — hai chỗ chọn gara trên cùng một trang là công
 * thức để người dùng sửa nhầm danh mục của chi nhánh khác.
 */
class Garagecatalog extends Controller {

    private $__data = [];
    private $__part, $__gia, $__gara, $__unit, $__request, $__response;

    private $routeBase = 'garage-catalog';
    private $labelMany = 'Danh mục của gara';
    private $viewDir   = 'admin/garagecatalog';

    function __construct(){
        $this->__part     = $this->model('PartsModel');
        $this->__gia      = $this->model('GaragePricesModel');
        $this->__gara     = $this->model('GaragesModel');
        $this->__unit     = $this->model('ProductUnitsModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    /** Gara đang làm việc, hoặc null nếu hệ thống chưa khai gara nào */
    private function gara(){
        return gara_hien_tai();
    }

    private function baseData(){
        $c = &$this->__data['content'];
        $c['routeBase'] = $this->routeBase;
        $c['gara']      = $this->gara();
        $c['msg']       = Session::flash('msg');
        $c['msgError']  = Session::flash('msgError');
    }

    public function index(){
        $gara = $this->gara();
        $gid  = !empty($gara['id']) ? (int) $gara['id'] : 0;

        $this->baseData();
        $c = &$this->__data['content'];
        $c['page_name'] = $this->labelMany;

        // Danh mục gara đang có (hàng riêng + hàng tổng đã chọn), giá đã tính sẵn
        $c['dsDanhMuc']    = $gid > 0 ? $this->__part->theoNguon(PartsModel::NGUON_GARA, $gid) : [];
        // Danh mục tổng để tick chọn thêm
        $c['dsTong']    = $this->__part->theoNguon(PartsModel::NGUON_TONG);
        $c['dangChon']  = $gid > 0 ? $this->__gia->theoGara($gid) : [];
        $c['dsDonVi']   = $this->__unit->getLists();
        $c['errors']    = Session::flash('errors');
        $c['old']       = Session::flash('old');

        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    /**
     * Lưu bảng chọn: tick mặt hàng nào, giá bao nhiêu.
     *
     * Nhận CẢ danh sách mặt hàng hiển thị trên trang (`co_mat[]`) chứ không chỉ
     * các ô đã tick. Không có nó thì không phân biệt được "người dùng bỏ tick"
     * với "mặt hàng đó không nằm trên trang này" — và bỏ tick sẽ không bao giờ
     * có tác dụng.
     */
    public function postChon(){
        $gara = $this->gara();
        if (empty($gara['id'])){
            Session::flash('msgError', 'Chưa có gara nào để gán danh mục.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $gid = (int) $gara['id'];

        $f      = $this->__request->getFields();
        $coMat  = isset($f['co_mat']) && is_array($f['co_mat']) ? $f['co_mat'] : [];
        $chon   = isset($f['chon']) && is_array($f['chon']) ? $f['chon'] : [];
        $giaArr = isset($f['gia']) && is_array($f['gia']) ? $f['gia'] : [];

        $them = 0; $bo = 0; $suaGia = 0;
        foreach ($coMat as $pid){
            $pid = (int) $pid;
            if ($pid <= 0) continue;

            if (!empty($chon[$pid])){
                $truoc = $this->__gia->mot($gid, $pid);
                $this->__gia->datGia($gid, $pid, isset($giaArr[$pid]) ? $giaArr[$pid] : null);
                if (empty($truoc)) $them++; else $suaGia++;
            } else {
                if (!empty($this->__gia->mot($gid, $pid))){
                    $this->__gia->boChon($gid, $pid);
                    $bo++;
                }
            }
        }

        Session::flash('msg', sprintf(
            'Đã cập nhật danh mục của %s: thêm %d, bỏ %d, cập nhật giá %d mặt hàng.',
            $gara['name'], $them, $bo, $suaGia));
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    /** Thêm một mặt hàng CHỈ gara này có */
    public function postThemRieng(){
        $gara = $this->gara();
        if (empty($gara['id'])){
            Session::flash('msgError', 'Chưa có gara nào để thêm hàng riêng.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $gid = (int) $gara['id'];

        $f     = $this->__request->getFields();
        $ten   = isset($f['name']) ? trim($f['name']) : '';
        $loai  = isset($f['item_type']) ? $f['item_type'] : 'service';
        $donVi = !empty($f['unit_id']) ? (int) $f['unit_id'] : null;
        $gia   = isset($f['price']) ? preg_replace('/[^\d]/', '', (string) $f['price']) : '';

        $errors = [];
        if ($ten === '') $errors['name'] = 'Nhập tên mặt hàng';
        if (!in_array($loai, ['part', 'equipment', 'service'], true)) $loai = 'service';

        if (!empty($errors)){
            Session::flash('errors', $errors);
            Session::flash('old', ['name' => $ten, 'item_type' => $loai,
                                   'unit_id' => $donVi, 'price' => $gia]);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        /* Mã và slug tự sinh: form gọn không có hai ô đó, mà cả hai cột đều
           NOT NULL và slug phải duy nhất. Tiền tố theo mã gara để nhìn mã là
           biết hàng của chi nhánh nào. */
        $tienTo = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $gara['code'])) . '-';

        $this->__part->add([
            'code'        => $this->__part->nextCode($tienTo),
            'name'        => $ten,
            'slug'        => $this->__part->slugTrong($ten),
            'item_type'   => $loai,
            'unit_id'     => $donVi,
            'price'       => $gia === '' ? 0 : (float) $gia,
            'garage_id'   => $gid,
            'status'      => 1,
            /* Hàng riêng KHÔNG lên website: trang bán hàng là một trang chung
               cho cả hệ thống, đăng hàng của một chi nhánh lên đó thì khách ở
               chi nhánh khác đặt phải thứ nơi mình không có. */
            'show_on_web' => 0,
        ]);

        Session::flash('msg', 'Đã thêm "' . $ten . '" vào danh mục riêng của ' . $gara['name']);
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    /** Xoá một mặt hàng riêng của gara */
    public function xoaRieng($id = 0){
        $gara = $this->gara();
        $item = $this->__part->getDetail((int) $id);

        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy mặt hàng.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        /* CHỈ xoá được hàng riêng CỦA CHÍNH GARA ĐANG CHỌN.
           Thiếu chốt này thì sửa id trên thanh địa chỉ là xoá được hàng của
           chi nhánh khác — hoặc tệ hơn, xoá luôn một mặt hàng của danh mục
           tổng mà mọi gara đang dùng. */
        if (empty($item['garage_id']) || (int) $item['garage_id'] !== (int) $gara['id']){
            Session::flash('msgError',
                'Mặt hàng này không thuộc danh mục riêng của ' . $gara['name'] . '.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $ket = $this->__part->remove((int) $id);
        if ($ket === false){
            Session::flash('msgError',
                'Không xoá được: mặt hàng này đã nằm trên báo giá hoặc hoá đơn. '
              . 'Tắt trạng thái hoạt động thay vì xoá.');
        } else {
            Session::flash('msg', 'Đã xoá "' . $item['name'] . '" khỏi danh mục riêng.');
        }
        $this->__response->redirect('admin/' . $this->routeBase);
    }
}
