<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * KẾ TOÁN — DANH MỤC TÀI KHOẢN. Cây đệ quy: 111 → 1111 → 111A.
 *
 * Tân Phát dựng sẵn hệ thống tài khoản chuẩn (Thông tư 133 rút gọn, migration
 * 000096) dùng chung cho mọi gara; gara thêm tài khoản chi tiết của mình thì
 * dòng đó mang `garage_id` và chỉ gara đó thấy. Dòng dùng chung hiện ra có
 * nhãn và KHÔNG có nút sửa — xem helper dong_cua_gara().
 */
class Accaccounts extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'acc-accounts';
    private $labelOne  = 'tài khoản';
    private $labelMany = 'Danh mục tài khoản';
    private $viewDir   = 'admin/acc-accounts';

    function __construct(){
        $this->__model    = $this->model('AccAccountsModel');
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
        $this->__data['content']['dataList']  = $this->__model->getTree();

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;

        $this->baseData();
        $this->__data['content']['page_name'] = 'Thêm ' . $this->labelOne;
        $this->__data['content']['item']      = null;
        $this->__data['content']['dsCha']     = $this->__model->getTree();

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

        /* Bỏ CHÍNH NÓ và mọi hậu duệ khỏi ô chọn cha: chọn con mình làm cha là
           cây thành vòng, và getTree() duyệt đệ quy sẽ không bao giờ dừng. */
        $bo  = array_merge([(int) $id], $this->__model->hauDue($id));
        $this->__data['content']['dsCha'] = array_values(array_filter(
            $this->__model->getTree(),
            function($r) use ($bo){ return !in_array((int) $r['id'], $bo, true); }
        ));

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

        /* Kiểm TRƯỚC để báo cho tử tế. Để MySQL ném lỗi khoá ngoại thì người
           dùng nhận một dòng tiếng Anh về constraint, không biết phải làm gì. */
        $con = $this->__model->demCon($id);
        if ($con > 0){
            Session::flash('msgError', 'Không xoá được ' . $item['code'] . ': còn ' . $con
                . ' tài khoản con. Xoá hoặc chuyển các tài khoản con trước.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $dung = $this->__model->demLoaiPhieuDung($id);
        if ($dung > 0){
            Session::flash('msgError', 'Không xoá được ' . $item['code'] . ': còn ' . $dung
                . ' loại phiếu đang dùng tài khoản này làm tài khoản nợ hoặc có.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' ' . $item['code'] . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    private function buildData(){
        $f = $this->__request->getFields();
        return [
            'code'       => isset($f['code']) ? trim($f['code']) : '',
            'name'       => isset($f['name']) ? trim($f['name']) : '',
            'parent_id'  => $this->chaHopLe(isset($f['parent_id']) ? $f['parent_id'] : null),
            'sort_order' => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'     => !empty($f['status']) ? 1 : 0,
        ];
    }

    /**
     * Tài khoản cha phải là dòng gara đang làm việc THẤY ĐƯỢC, không thì null.
     * Nhận nguyên văn từ form là gara treo tài khoản của mình dưới tài khoản
     * nội bộ của gara khác.
     */
    private function chaHopLe($val){
        $id = (int) $val;
        if ($id <= 0) return null;
        return !empty($this->__model->getDetail($id)) ? $id : null;
    }

    /** @return array lỗi theo tên trường (rỗng = hợp lệ) */
    private function kiemTra($data, $id){
        $loi = [];

        if ($data['code'] === '') $loi['code'] = 'Mã tài khoản không được để trống';
        elseif (!preg_match('/^[A-Za-z0-9]{1,20}$/', $data['code'])){
            $loi['code'] = 'Mã tài khoản chỉ gồm chữ và số, tối đa 20 ký tự (ví dụ 1111, 111A).';
        } else {
            $co = $this->__model->findByCode($data['code']);
            if (!empty($co) && ($id === null || (int) $co['id'] !== $id)){
                $loi['code'] = 'Mã ' . $data['code'] . ' đã có trong danh mục.';
            }
        }

        if ($data['name'] === '') $loi['name'] = 'Tên tài khoản không được để trống';

        /* Chọn chính mình làm cha -> cây thành vòng -> getTree() đệ quy không
           dừng. Ô chọn đã lọc sẵn, nhưng POST thì ai cũng tự soạn được. */
        if ($id !== null && $data['parent_id'] !== null){
            if ((int) $data['parent_id'] === $id){
                $loi['parent_id'] = 'Không chọn chính tài khoản này làm tài khoản cha.';
            } elseif (in_array((int) $data['parent_id'], $this->__model->hauDue($id), true)){
                $loi['parent_id'] = 'Không chọn tài khoản con / cháu của chính nó làm cha.';
            }
        }
        return $loi;
    }

    /**
     * Tài khoản $id mà gara làm việc SỬA ĐƯỢC, hoặc null kèm redirect.
     * Nơi gọi chỉ cần `if (empty(...)) return;`.
     */
    private function layCuaToi($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return null;
        }
        /* Dòng của danh mục chung: gara đọc và chọn được, nhưng không sửa.
           Chốt thật nằm ở tầng Model (updateById/deleteById đã lọc theo sở
           hữu) — ở đây chỉ để nói đúng câu từ chối. */
        if (!dong_cua_gara($item)){
            Session::flash('msgError', 'Tài khoản ' . $item['code']
                . ' thuộc hệ thống tài khoản dùng chung, gara không sửa được. '
                . 'Cần tài khoản chi tiết riêng thì thêm mới một tài khoản của gara.');
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
