<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * HỆ THỐNG — Quản lý gara.
 *
 * Gara là đơn vị kinh doanh: kho, nhân viên, báo giá, hoá đơn đều thuộc về một
 * gara. Một dòng được đánh dấu là gara tổng — chủ sở hữu danh mục tổng.
 *
 * GARA KHÔNG PHẢI LÀ KHO. Một gara có thể có nhiều kho; các màn hình tồn kho,
 * nhập, xuất, kiểm kê vẫn chạy theo `warehouse_id` như cũ, không đụng tới.
 *
 * Ở đây KHÔNG lọc dữ liệu theo gara: đã chốt các gara thấy được của nhau, vì
 * kỹ thuật viên cần biết xe đã thay gì kể cả khi lần trước sửa ở chi nhánh khác.
 */
class Garages extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'garages';
    private $labelOne  = 'gara';
    private $labelMany = 'Quản lý gara';
    private $viewDir   = 'admin/garages';

    function __construct(){
        $this->__model    = $this->model('GaragesModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();
        $c = &$this->__data['content'];
        $c['page_name'] = $this->labelMany;
        $c['dataList']  = $this->__model->getLists();

        /* Số kho / nhân viên / chứng từ của từng gara — lấy một lần cho cả
           trang. Hỏi lại theo từng dòng thì 5 gara là 20 truy vấn thừa. */
        $c['dangDung'] = [];
        foreach ($c['dataList'] as $g){
            $c['dangDung'][(int) $g['id']] = $this->__model->dangDungODau((int) $g['id']);
        }

        $c['msg']      = Session::flash('msg');
        $c['msgError'] = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;

        $this->baseData();
        $c = &$this->__data['content'];
        $c['page_name'] = 'Thêm ' . $this->labelOne;
        $c['item']      = null;
        $c['msg']       = Session::flash('msg');
        $c['errors']    = Session::flash('errors');
        $c['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $this->applyRules();
        if (!$this->__request->validate()){
            $this->flashErrors();
            $this->__response->redirect('admin/' . $this->routeBase . '/add');
            return;
        }

        $data = $this->buildData();
        if (!empty($this->__model->findByCode($data['code']))){
            $this->flashOne('code', 'Mã gara này đã tồn tại', 'add');
            return;
        }

        $id = $this->__model->add($data);
        if (!empty($data['is_master'])) $this->__model->clearMasterExcept($id);

        Session::flash('msg', 'Thêm ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;

        $this->baseData();
        $c = &$this->__data['content'];
        $c['page_name'] = 'Sửa ' . $this->labelOne;
        $c['item']      = $item;
        $c['dangDung']  = $this->__model->dangDungODau((int) $id);
        $c['msg']       = Session::flash('msg');
        $c['errors']    = Session::flash('errors');
        $c['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $this->applyRules();
        if (!$this->__request->validate()){
            $this->flashErrors();
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
            return;
        }

        $data     = $this->buildData();
        $existing = $this->__model->findByCode($data['code']);
        if (!empty($existing) && $existing['id'] != $id){
            $this->flashOne('code', 'Mã gara này đã thuộc về gara khác', 'edit/' . $id);
            return;
        }

        /* Không cho gỡ cờ gara tổng khi nó là gara tổng DUY NHẤT: mất cờ này
           thì không còn ai sở hữu danh mục tổng, và getMaster() phải đoán bừa
           lấy gara có id nhỏ nhất. Muốn chuyển thì đi bật cờ ở gara khác — thao
           tác đó tự gỡ cờ ở đây. */
        if ((int) $item['is_master'] === 1 && empty($data['is_master'])){
            $data['is_master'] = 1;
            Session::flash('msgError',
                'Đây đang là gara tổng duy nhất nên không bỏ đánh dấu được. '
              . 'Muốn chuyển thì mở gara khác rồi đánh dấu ở đó.');
        }

        $this->__model->edit($data, $id);
        if (!empty($data['is_master'])) $this->__model->clearMasterExcept($id);

        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function delete($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        /* Chặn xoá gara tổng: khoá ngoại đặt ON DELETE SET NULL nên MySQL sẽ
           vui vẻ cho xoá, rồi toàn bộ kho / nhân viên / chứng từ mất chủ trong
           im lặng và không còn gara nào sở hữu danh mục tổng. */
        if ((int) $item['is_master'] === 1){
            Session::flash('msgError',
                'Không xoá được gara tổng. Đánh dấu gara khác làm gara tổng trước đã.');
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $dung = $this->__model->dangDungODau((int) $id);
        if (!empty($dung)){
            $mo = [];
            foreach ($dung as $nhan => $n) $mo[] = $n . ' ' . $nhan;
            Session::flash('msgError',
                'Không xoá được: gara này đang có ' . implode(', ', $mo)
              . '. Chuyển những thứ đó sang gara khác trước, hoặc tắt trạng thái hoạt động.');
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    /**
     * Đổi gara đang làm việc.
     *
     * Ghi vào session của CHÍNH người bấm, không đụng tới `users.garage_id` —
     * đổi tạm để xem/lập chứng từ hộ chi nhánh khác, đăng xuất vào lại thì trở
     * về gara của mình. Sửa hẳn thì vào màn Người dùng.
     */
    public function doi($id = 0){
        $item = $this->__model->getDetail((int) $id);
        if (empty($item) || (int) $item['status'] !== 1){
            Session::flash('msgError', 'Gara không hợp lệ hoặc đang tắt.');
        } else {
            Session::set('garage_id', (int) $item['id']);
            Session::flash('msg', 'Đang làm việc tại: ' . $item['name']);
        }

        /* Quay lại đúng trang đang xem. Chỉ nhận đường dẫn nội bộ — nhận bừa
           Referer là mở đường cho người ta dựng link đẩy sang trang ngoài. */
        $this->__response->redirect($this->quayVe());
    }

    private function quayVe(){
        $ref = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';
        if ($ref !== '' && strpos($ref, _WEB_URL . '/admin') === 0){
            $duoi = substr($ref, strlen(_WEB_URL) + 1);
            if (strpos($duoi, '//') === false) return $duoi;
        }
        return 'admin';
    }

    // ===== Helper =====

    private function applyRules(){
        $this->__request->rules([
            'code' => 'required|min:1',
            'name' => 'required|min:1',
        ]);
        $this->__request->message([
            'code.required' => 'Mã gara không được để trống',
            'code.min'      => 'Mã gara không được để trống',
            'name.required' => 'Tên gara không được để trống',
            'name.min'      => 'Tên gara không được để trống',
        ]);
    }

    private function buildData(){
        $f = $this->__request->getFields();
        return [
            'code'       => trim($f['code']),
            'name'       => trim($f['name']),
            'address'    => !empty($f['address']) ? trim($f['address']) : null,
            'phone'      => !empty($f['phone']) ? trim($f['phone']) : null,
            'is_master'  => !empty($f['is_master']) ? 1 : 0,
            'sort_order' => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'     => !empty($f['status']) ? 1 : 0,
        ];
    }

    private function flashErrors(){
        Session::flash('errors', $this->__request->error());
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
    }

    private function flashOne($field, $msg, $back){
        Session::flash('errors', [$field => $msg]);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}
