<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/** CMS — Thư viện ảnh/video (album + ảnh/video bên trong). */
class Galleries extends Controller {

    private $__data = [];
    private $__model, $__item, $__request, $__response;
    private $routeBase = 'galleries';
    private $labelOne  = 'album';
    private $labelMany = 'Thư viện ảnh/video';
    private $viewDir   = 'admin/galleries';

    function __construct(){
        $this->__model    = $this->model('GalleriesModel');
        $this->__item     = $this->model('GalleryItemsModel');
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
        $f = $this->__request->getFields();
        $status  = isset($f['status']) ? trim($f['status']) : '';
        $keyword = isset($f['q']) ? trim($f['q']) : '';
        $this->__data['content']['page_name']     = $this->labelMany;
        $this->__data['content']['dataList']      = $this->__model->getLists($status, $keyword);
        $this->__data['content']['filterStatus']  = $status;
        $this->__data['content']['filterKeyword'] = $keyword;
        $this->__data['content']['msg']           = Session::flash('msg');
        $this->__data['content']['msgError']      = Session::flash('msgError');
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
        $this->__request->rules(['name' => 'required|min:1']);
        $this->__request->message(['name.required' => 'Nhập tên album', 'name.min' => 'Nhập tên album']);
        if (!$this->__request->validate()){ $this->flashErr('add'); return; }
        $data = $this->buildData();
        if (!empty($this->__model->findBySlug($data['slug']))){ $this->flashOne('slug', 'Slug đã tồn tại', 'add'); return; }
        $up = upload_image('cover_file', 'galleries', $data['name']);
        if ($up['status'] === 'error'){ $this->flashOne('cover_file', $up['message'], 'add'); return; }
        if ($up['status'] === 'ok') $data['cover'] = $up['path'];
        $id = $this->__model->add($data);
        Session::flash('msg', 'Đã tạo album. Thêm ảnh/video bên dưới.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name'] = 'Sửa ' . $this->labelOne;
        $this->__data['content']['item']      = $item;
        $this->__data['content']['items']     = $this->__item->getByGallery($id);
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__request->rules(['name' => 'required|min:1']);
        $this->__request->message(['name.required' => 'Nhập tên album', 'name.min' => 'Nhập tên album']);
        if (!$this->__request->validate()){ $this->flashErr('edit/' . $id); return; }
        $data = $this->buildData();
        $ex = $this->__model->findBySlug($data['slug']);
        if (!empty($ex) && $ex['id'] != $id){ $this->flashOne('slug', 'Slug đã thuộc album khác', 'edit/' . $id); return; }
        $up = upload_image('cover_file', 'galleries', $data['name']);
        if ($up['status'] === 'error'){ $this->flashOne('cover_file', $up['message'], 'edit/' . $id); return; }
        if ($up['status'] === 'ok') $data['cover'] = $up['path'];
        $this->__model->edit($data, $id);
        Session::flash('msg', 'Cập nhật album thành công');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** Upload nhiều ảnh vào album */
    public function postImages($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy album'); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){ $this->__response->redirect('admin/khong-co-quyen'); return; }

        $count = 0; $err = '';
        if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])){
            $n = count($_FILES['images']['name']);
            for ($i = 0; $i < $n; $i++){
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                // reshape thành 1 file để dùng upload_image
                $_FILES['__one'] = [
                    'name' => $_FILES['images']['name'][$i], 'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i], 'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i],
                ];
                $up = upload_image('__one', 'galleries', $item['slug']);
                if ($up['status'] === 'ok'){ $this->__item->addImage($id, $up['path']); $count++; }
                elseif ($up['status'] === 'error'){ $err = $up['message']; }
            }
        }
        Session::flash($err !== '' && $count === 0 ? 'msgError' : 'msg',
            $count > 0 ? ('Đã thêm ' . $count . ' ảnh') : ('Không thêm được ảnh' . ($err !== '' ? ': ' . $err : '')));
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** Thêm 1 video (URL YouTube) */
    public function addVideo($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy album'); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){ $this->__response->redirect('admin/khong-co-quyen'); return; }
        $f = $this->__request->getFields();
        $url = !empty($f['video_url']) ? trim($f['video_url']) : '';
        if ($url === '' || youtube_id($url) === ''){
            Session::flash('msgError', 'URL YouTube không hợp lệ');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }
        $this->__item->addVideo($id, $url, !empty($f['caption']) ? trim($f['caption']) : null);
        Session::flash('msg', 'Đã thêm video');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function itemDelete($itemId){
        $it = $this->__item->getDetail($itemId);
        if (empty($it)){ $this->__response->redirect('admin/' . $this->routeBase); return; }
        if (!route('admin/' . $this->routeBase . '/edit/' . $it['gallery_id'])){ $this->__response->redirect('admin/khong-co-quyen'); return; }
        $this->__item->remove($itemId);
        Session::flash('msg', 'Đã xoá');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $it['gallery_id']);
    }

    public function delete($id){
        if (empty($this->__model->getDetail($id))){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====
    private function buildData(){
        $f = $this->__request->getFields();
        return [
            'name'         => trim($f['name']),
            'slug'         => slugify(!empty($f['slug']) ? $f['slug'] : $f['name']),
            'description'  => !empty($f['description']) ? trim($f['description']) : null,
            'is_published' => !empty($f['is_published']) ? 1 : 0,
            'sort_order'   => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
        ];
    }
    private function flashErr($back){
        Session::flash('errors', $this->__request->error());
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
    private function flashOne($field, $msg, $back){
        Session::flash('errors', [$field => $msg]);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}
