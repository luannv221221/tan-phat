<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * KHO-3 — Vị trí trong kho (cây tối đa 5 cấp). CRUD.
 */
class Warehouselocations extends Controller {

    private $__data = [];
    private $__model, $__warehouse, $__request, $__response;

    private $routeBase = 'warehouse-locations';
    private $labelOne  = 'vị trí trong kho';
    private $labelMany = 'Vị trí trong kho';
    private $viewDir   = 'admin/warehouse-locations';

    function __construct(){
        $this->__model     = $this->model('WarehouseLocationsModel');
        $this->__warehouse = $this->model('WarehousesModel');
        $this->__request   = new Request();
        $this->__response  = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
        $this->__data['content']['maxLevel']  = WarehouseLocationsModel::MAX_LEVEL;
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;
        $this->baseData();

        $f    = $this->__request->getFields();
        $whId = !empty($f['warehouse_id']) ? (int) $f['warehouse_id'] : 0;

        $this->__data['content']['page_name']  = $this->labelMany;
        $this->__data['content']['dataList']   = $this->__model->getLists($whId);
        $this->__data['content']['warehouses'] = $this->__warehouse->getActive();
        $this->__data['content']['filterWh']   = $whId;
        $this->__data['content']['msg']        = Session::flash('msg');
        $this->__data['content']['msgError']   = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name']  = 'Thêm ' . $this->labelOne;
        $this->__data['content']['item']         = null;
        $this->__data['content']['warehouses']   = $this->__warehouse->getActive();
        $this->__data['content']['allLocations'] = $this->__model->getLists(0);
        $this->__data['content']['msg']        = Session::flash('msg');
        $this->__data['content']['errors']     = Session::flash('errors');
        $this->__data['content']['old']        = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validate();
        if (!empty($errors)){ $this->flash($errors, 'add'); return; }
        $data = $this->buildData();
        $this->__model->add($data);
        Session::flash('msg', 'Thêm ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase . '?warehouse_id=' . $data['warehouse_id']);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name']  = 'Sửa ' . $this->labelOne;
        $this->__data['content']['item']       = $item;
        $this->__data['content']['warehouses'] = $this->__warehouse->getActive();
        $this->__data['content']['allLocations'] = $this->__model->getLists(0);
        $this->__data['content']['msg']        = Session::flash('msg');
        $this->__data['content']['errors']     = Session::flash('errors');
        $this->__data['content']['old']        = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $errors = $this->validate($id);
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }
        $data = $this->buildData();
        $this->__model->edit($data, $id);
        $this->__model->reindexChildren($id); // đổi tên/cha -> cập nhật full_path nhánh con
        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase . '?warehouse_id=' . $data['warehouse_id']);
    }

    public function delete($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__model->remove($id); // FK CASCADE tự xoá nhánh con
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' (kèm nhánh con) thành công');
        $this->__response->redirect('admin/' . $this->routeBase . '?warehouse_id=' . $item['warehouse_id']);
    }

    // ===== Helper =====

    private function validate($selfId = 0){
        $f = $this->__request->getFields();
        $errors = [];
        if (empty($f['warehouse_id'])) $errors['warehouse_id'] = 'Chọn kho';
        if (empty(trim($f['name'] ?? ''))) $errors['name'] = 'Nhập tên vị trí';
        if (empty(trim($f['code'] ?? ''))) $errors['code'] = 'Nhập mã vị trí';

        // Cha phải cùng kho + không vượt quá số cấp tối đa + không phải chính mình
        $pid = !empty($f['parent_id']) ? (int) $f['parent_id'] : 0;
        if ($pid > 0){
            $parent = $this->__model->getDetail($pid);
            if (empty($parent) || (int) $parent['warehouse_id'] !== (int) ($f['warehouse_id'] ?? 0)){
                $errors['parent_id'] = 'Vị trí cha không hợp lệ (khác kho)';
            } elseif ($selfId > 0 && (int) $parent['id'] === (int) $selfId){
                $errors['parent_id'] = 'Không thể chọn chính nó làm cha';
            } elseif ((int) $parent['level'] >= WarehouseLocationsModel::MAX_LEVEL){
                $errors['parent_id'] = 'Đã đạt tối đa ' . WarehouseLocationsModel::MAX_LEVEL . ' cấp';
            }
        }
        return $errors;
    }

    private function buildData(){
        $f    = $this->__request->getFields();
        $name = trim($f['name']);
        $pid  = !empty($f['parent_id']) ? (int) $f['parent_id'] : null;
        $resolved = $this->__model->resolvePath($name, $pid);
        return [
            'warehouse_id' => (int) $f['warehouse_id'],
            'parent_id'    => $resolved['parent_id'],
            'code'         => trim($f['code']),
            'name'         => $name,
            'level'        => $resolved['level'],
            'full_path'    => $resolved['full_path'],
            'sort_order'   => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'       => !empty($f['status']) ? 1 : 0,
        ];
    }

    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}
