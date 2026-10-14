<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * KẾ TOÁN — DANH MỤC LOẠI PHIẾU.
 *
 * Đúng màn trong ảnh minh hoạ số 1 của tài liệu: mã phiếu, tên phiếu, loại
 * (Thu / Chi), tài khoản có và tài khoản nợ — hai ô cuối "load từ danh mục tài
 * khoản".
 *
 * Mỗi loại phiếu là một ĐỊNH KHOẢN VIẾT SẴN. Người lập phiếu chỉ chọn loại,
 * không phải tự nghĩ ra cặp nợ – có; và vì phiếu chép lại mã tài khoản ngay lúc
 * lập nên sửa định khoản ở đây KHÔNG làm đổi phiếu đã lập.
 *
 * Tân Phát dựng sẵn bảy loại dùng chung (PT01…PC04, migration 000096); gara
 * thêm loại của mình thì chỉ gara đó thấy.
 */
class Accvouchertypes extends Controller {

    private $__data = [];
    private $__model, $__acc, $__request, $__response;

    private $routeBase = 'acc-voucher-types';
    private $labelOne  = 'loại phiếu';
    private $labelMany = 'Danh mục loại phiếu';
    private $viewDir   = 'admin/acc-voucher-types';

    function __construct(){
        $this->__model    = $this->model('AccVoucherTypesModel');
        $this->__acc      = $this->model('AccAccountsModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function baseData(){
        $c = &$this->__data['content'];
        $c['routeBase'] = $this->routeBase;
        $c['labelOne']  = $this->labelOne;
        $c['dsLoai']    = AccVoucherTypesModel::$loai;
        $c['msg']       = Session::flash('msg');
        $c['msgError']  = Session::flash('msgError');
        $c['errors']    = Session::flash('errors');
        $c['old']       = Session::flash('old');
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();
        $f    = $this->__request->getFields();
        $loai = isset($f['loai']) && isset(AccVoucherTypesModel::$loai[$f['loai']]) ? $f['loai'] : '';

        $this->__data['content']['page_name'] = $this->labelMany;
        $this->__data['content']['dataList']  = $this->__model->getLists($loai);
        $this->__data['content']['loaiDang']  = $loai;

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;

        $this->baseData();
        $this->__data['content']['page_name'] = 'Thêm ' . $this->labelOne;
        $this->__data['content']['item']      = null;
        $this->__data['content']['dsTk']      = $this->__acc->getActive();

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $data = $this->buildData();
        $loi  = $this->kiemTra($data, null);
        if (!empty($loi)){ $this->flash($loi, 'add'); return; }

        $this->__model->add($data);
        Session::flash('msg', 'Thêm ' . $this->labelOne . ' ' . $data['code'] . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function edit($id){
        $item = $this->layCuaToi($id);
        if (empty($item)) return;

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;

        $this->baseData();
        $this->__data['content']['page_name'] = 'Sửa ' . $this->labelOne;
        $this->__data['content']['item']      = $item;
        $this->__data['content']['dsTk']      = $this->__acc->getActive();

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->layCuaToi($id);
        if (empty($item)) return;

        $data = $this->buildData();
        $loi  = $this->kiemTra($data, (int) $id);
        if (!empty($loi)){ $this->flash($loi, 'edit/' . (int) $id); return; }

        $this->__model->edit($data, $id);
        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function delete($id){
        $item = $this->layCuaToi($id);
        if (empty($item)) return;

        $n = $this->__model->demPhieu($id);
        if ($n > 0){
            Session::flash('msgError', 'Không xoá được ' . $item['code'] . ': đã có ' . $n
                . ' phiếu lập theo loại này. Phiếu cũ phải giữ được loại của nó — '
                . 'tắt trạng thái thay vì xoá.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' ' . $item['code'] . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    private function buildData(){
        $f = $this->__request->getFields();
        $loai = isset($f['loai']) && isset(AccVoucherTypesModel::$loai[$f['loai']]) ? $f['loai'] : 'thu';
        return [
            'code'       => isset($f['code']) ? trim($f['code']) : '',
            'name'       => isset($f['name']) ? trim($f['name']) : '',
            'loai'       => $loai,
            'debit_id'   => $this->tkHopLe(isset($f['debit_id']) ? $f['debit_id'] : null),
            'credit_id'  => $this->tkHopLe(isset($f['credit_id']) ? $f['credit_id'] : null),
            'sort_order' => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'     => !empty($f['status']) ? 1 : 0,
        ];
    }

    /**
     * Tài khoản phải là dòng gara đang làm việc THẤY ĐƯỢC, không thì null.
     * Nhận id nguyên văn từ form là gara định khoản vào tài khoản nội bộ của
     * gara khác — và báo cáo sẽ cộng sang nhầm chỗ.
     */
    private function tkHopLe($val){
        $id = (int) $val;
        if ($id <= 0) return null;
        return !empty($this->__acc->getDetail($id)) ? $id : null;
    }

    private function kiemTra($data, $id){
        $loi = [];

        if ($data['code'] === '') $loi['code'] = 'Mã phiếu không được để trống';
        else {
            $co = $this->__model->findByCode($data['code']);
            if (!empty($co) && ($id === null || (int) $co['id'] !== $id)){
                $loi['code'] = 'Mã ' . $data['code'] . ' đã có trong danh mục loại phiếu.';
            }
        }

        if ($data['name'] === '') $loi['name'] = 'Tên phiếu không được để trống';

        /* BẮT BUỘC đủ CẢ HAI tài khoản. Thiếu một vế thì phiếu lập ra mang định
           khoản dở dang, mà nhìn vào danh sách phiếu không thấy gì bất thường —
           tới lúc đối chiếu sổ sách mới lòi ra. */
        if (empty($data['debit_id']))  $loi['debit_id']  = 'Chọn tài khoản nợ';
        if (empty($data['credit_id'])) $loi['credit_id'] = 'Chọn tài khoản có';

        if (!empty($data['debit_id']) && $data['debit_id'] === $data['credit_id']){
            $loi['credit_id'] = 'Tài khoản nợ và tài khoản có phải khác nhau.';
        }
        return $loi;
    }

    /** Loại phiếu $id mà gara làm việc SỬA ĐƯỢC, hoặc null kèm redirect */
    private function layCuaToi($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return null;
        }
        if (!dong_cua_gara($item)){
            Session::flash('msgError', 'Loại phiếu ' . $item['code']
                . ' thuộc danh mục dùng chung, gara không sửa được. '
                . 'Cần định khoản khác thì thêm một loại phiếu của gara.');
            $this->__response->redirect('admin/' . $this->routeBase);
            return null;
        }
        return $item;
    }

    private function flash($loi, $back){
        Session::flash('errors', $loi);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}
