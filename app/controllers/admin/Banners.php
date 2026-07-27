<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * Quản lý Banner slider trang chủ storefront.
 * Ảnh lưu qua helper upload_image() -> đường dẫn 'public/assets/uploads/banners/<file>'.
 */
class Banners extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'banners';
    private $labelOne  = 'banner';
    private $labelMany = 'Banner';
    private $viewDir   = 'admin/banners';
    private $uploadSub = 'banners';

    function __construct(){
        $this->__model    = $this->model('BannersModel');
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
        $this->__data['content']['dataList']  = $this->__model->getLists();
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['msgError']  = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;

        $this->baseData();
        $this->__data['content']['page_name'] = 'Thêm ' . $this->labelOne;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $data = $this->buildData();

        // Ảnh bắt buộc khi thêm mới
        $up = upload_image('image', $this->uploadSub, !empty($data['title']) ? $data['title'] : 'banner');
        if ($up['status'] === 'error'){
            $this->flashOne(['image' => $up['message']], 'add');
            return;
        }
        if ($up['status'] === 'none'){
            $this->flashOne(['image' => 'Vui lòng chọn ảnh banner'], 'add');
            return;
        }
        $data['image'] = $up['path'];

        $this->__model->add($data);
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
        $this->__data['content']['page_name'] = 'Sửa ' . $this->labelOne;
        $this->__data['content']['item']      = $item;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $data = $this->buildData();

        // Ảnh mới (không bắt buộc — giữ ảnh cũ nếu không chọn)
        $up = upload_image('image', $this->uploadSub, !empty($data['title']) ? $data['title'] : 'banner');
        if ($up['status'] === 'error'){
            $this->flashOne(['image' => $up['message']], 'edit/' . $id);
            return;
        }
        if ($up['status'] === 'ok'){
            $data['image'] = $up['path'];
            if (!empty($item['image']) && is_file($item['image'])){
                @unlink($item['image']);
            }
        }

        $this->__model->edit($data, $id);
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

        $this->__model->remove($id);
        if (!empty($item['image']) && is_file($item['image'])){
            @unlink($item['image']);
        }
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    private function buildData(){
        $f = $this->__request->getFields();
        return [
            'title'      => !empty($f['title']) ? trim($f['title']) : null,
            'link'       => !empty($f['link']) ? trim($f['link']) : null,
            'sort_order' => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'     => !empty($f['status']) ? 1 : 0,
        ];
    }

    private function flashOne($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}
