<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/** CMS — Menu website (CRUD, cây 1 cấp). */
class Menus extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;
    private $routeBase = 'menus';
    private $labelOne  = 'mục menu';
    private $labelMany = 'Menu website';
    private $viewDir   = 'admin/menus';

    function __construct(){
        $this->__model    = $this->model('MenusModel');
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
        $this->__data['content']['page_name'] = $this->labelMany;
        $this->__data['content']['dataList']  = $this->__model->getTree();
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['msgError']  = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name'] = 'Thêm ' . $this->labelOne;
        $this->__data['content']['roots']     = $this->__model->getRoots();
        $this->__data['content']['item']      = null;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $this->__request->rules(['label' => 'required|min:1']);
        $this->__request->message(['label.required' => 'Nhập nhãn menu', 'label.min' => 'Nhập nhãn menu']);
        if (!$this->__request->validate()){ $this->flashErr('add'); return; }
        $this->__model->add($this->buildData(0));
        Session::flash('msg', 'Thêm ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name'] = 'Sửa ' . $this->labelOne;
        // loại chính nó khỏi danh sách cha (tránh tự làm cha mình)
        $roots = [];
        foreach ($this->__model->getRoots() as $r){ if ((int) $r['id'] !== (int) $id) $roots[] = $r; }
        $this->__data['content']['roots']  = $roots;
        $this->__data['content']['item']   = $item;
        $this->__data['content']['msg']    = Session::flash('msg');
        $this->__data['content']['errors'] = Session::flash('errors');
        $this->__data['content']['old']    = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        if (empty($this->__model->getDetail($id))){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__request->rules(['label' => 'required|min:1']);
        $this->__request->message(['label.required' => 'Nhập nhãn menu', 'label.min' => 'Nhập nhãn menu']);
        if (!$this->__request->validate()){ $this->flashErr('edit/' . $id); return; }
        $this->__model->edit($this->buildData($id), $id);
        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function delete($id){
        if (empty($this->__model->getDetail($id))){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__model->remove($id); // con CASCADE
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    private function buildData($selfId){
        $f = $this->__request->getFields();
        $parentId = !empty($f['parent_id']) ? (int) $f['parent_id'] : 0;
        // parent phải tồn tại, khác chính nó, và là menu gốc
        if ($parentId > 0 && $parentId !== (int) $selfId){
            $p = $this->__model->getDetail($parentId);
            $parent = (!empty($p) && ($p['parent_id'] === null || $p['parent_id'] === '')) ? $parentId : null;
        } else {
            $parent = null;
        }
        return [
            'parent_id'  => $parent,
            'label'      => trim($f['label']),
            'url'        => isset($f['url']) ? trim($f['url']) : '',
            'target'     => (!empty($f['target']) && $f['target'] === '_blank') ? '_blank' : '_self',
            'sort_order' => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'     => !empty($f['status']) ? 1 : 0,
        ];
    }
    private function flashErr($back){
        Session::flash('errors', $this->__request->error());
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}
