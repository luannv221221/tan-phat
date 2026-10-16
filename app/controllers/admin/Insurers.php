<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * DANH MỤC BẢO HIỂM — hãng bảo hiểm dùng khi lập báo giá có phần "bảo hiểm trả".
 *
 * Tân Phát khai sẵn tám hãng phi nhân thọ lớn (migration 000098), dùng chung
 * mọi gara. Gara thêm hãng địa phương của mình thì chỉ gara đó thấy; dòng dùng
 * chung hiện ra có nhãn và KHÔNG có nút sửa.
 *
 * Bản gieo sẵn CHỈ CÓ mã và tên. Tài khoản ngân hàng, người liên hệ, địa chỉ
 * chi nhánh là thứ khác nhau theo từng gara — gara tự điền phần của mình bằng
 * cách thêm một dòng riêng, chứ Tân Phát điền bừa vào bản dùng chung là gara
 * chuyển tiền nhầm số tài khoản vì tin phần mềm.
 */
class Insurers extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'insurers';
    private $labelOne  = 'hãng bảo hiểm';
    private $labelMany = 'Danh mục bảo hiểm';
    private $viewDir   = 'admin/insurers';

    function __construct(){
        $this->__model    = $this->model('InsurersModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function baseData(){
        $c = &$this->__data['content'];
        $c['routeBase'] = $this->routeBase;
        $c['labelOne']  = $this->labelOne;
        $c['msg']       = Session::flash('msg');
        $c['msgError']  = Session::flash('msgError');
        $c['errors']    = Session::flash('errors');
        $c['old']       = Session::flash('old');
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();
        $this->__data['content']['page_name'] = $this->labelMany;
        $this->__data['content']['dataList']  = $this->__model->getLists();

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;

        $this->baseData();
        $this->__data['content']['page_name'] = 'Thêm ' . $this->labelOne;
        $this->__data['content']['item']      = null;

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $data = $this->buildData();
        $loi  = $this->kiemTra($data, null);
        if (!empty($loi)){ $this->flash($loi, 'add'); return; }

        $this->__model->add($data);
        Session::flash('msg', 'Thêm ' . $this->labelOne . ' ' . $data['name'] . ' thành công');
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

        $n = $this->__model->demBaoGia($id);
        if ($n > 0){
            Session::flash('msgError', 'Không xoá được ' . $item['name'] . ': còn ' . $n
                . ' báo giá đang gắn hãng này. Báo giá cũ phải giữ được tên hãng — '
                . 'tắt trạng thái thay vì xoá.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    private function buildData(){
        $f = $this->__request->getFields();
        $lay = function($k) use ($f){
            return isset($f[$k]) && trim($f[$k]) !== '' ? mb_substr(trim($f[$k]), 0, 255) : null;
        };
        return [
            'code'          => isset($f['code']) ? trim($f['code']) : '',
            'name'          => isset($f['name']) ? trim($f['name']) : '',
            'tax_code'      => $lay('tax_code'),
            'bank_account'  => $lay('bank_account'),
            'address'       => $lay('address'),
            'website'       => $lay('website'),
            'contact_name'  => $lay('contact_name'),
            'contact_phone' => $lay('contact_phone'),
            'sort_order'    => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'        => !empty($f['status']) ? 1 : 0,
        ];
    }

    private function kiemTra($data, $id){
        $loi = [];

        if ($data['code'] === '') $loi['code'] = 'Mã hãng không được để trống';
        else {
            $co = $this->__model->findByCode($data['code']);
            if (!empty($co) && ($id === null || (int) $co['id'] !== $id)){
                $loi['code'] = 'Mã ' . $data['code'] . ' đã có trong danh mục.';
            }
        }
        if ($data['name'] === '') $loi['name'] = 'Tên hãng không được để trống';
        return $loi;
    }

    /** Hãng $id mà gara làm việc SỬA ĐƯỢC, hoặc null kèm redirect */
    private function layCuaToi($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return null;
        }
        if (!dong_cua_gara($item)){
            Session::flash('msgError', $item['name']
                . ' thuộc danh mục dùng chung, gara không sửa được. '
                . 'Cần khai số tài khoản hay người liên hệ của riêng mình thì thêm một dòng mới.');
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
