<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * TẢI VỀ — quản lý tệp cho khách tải (tài liệu khách 05/11/2026).
 *
 * Tệp lưu trong storage/downloads/ (ngoài tầm web server) — xem
 * upload_tai_lieu() và DownloadsModel để biết vì sao.
 */
class Downloads extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'downloads';
    private $labelOne  = 'tệp tải về';
    private $labelMany = 'Tệp tải về';
    private $viewDir   = 'admin/downloads';

    function __construct(){
        $this->__model    = $this->model('DownloadsModel');
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

        $f = $this->__request->getFields();
        $status  = isset($f['status']) ? trim($f['status']) : '';
        $keyword = isset($f['keyword']) ? trim($f['keyword']) : '';

        $this->baseData();
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
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validate();
        if (!empty($errors)){ $this->flash($errors, 'add'); return; }

        $data = $this->buildData();

        $up = upload_tai_lieu('file', $data['title']);
        if ($up['status'] === 'error'){ $this->flash(['file' => $up['message']], 'add'); return; }
        if ($up['status'] === 'none'){  $this->flash(['file' => 'Chọn tệp để tải lên'], 'add'); return; }

        $data['file_path'] = $up['path'];
        $data['file_name'] = $up['name'];
        $data['file_size'] = $up['size'];
        $data['mime']      = $up['mime'];
        $data['token']     = DownloadsModel::sinhToken();
        $data['slug']      = $this->__model->slugRanh($data['slug']);

        $id = $this->__model->add($data);
        Session::flash('msg', 'Đã thêm ' . $this->labelOne);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
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
        $this->__data['content']['page_name'] = 'Sửa ' . $this->labelOne;
        $this->__data['content']['item']      = $item;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['msgError']  = Session::flash('msgError');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $errors = $this->validate();
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }

        $data = $this->buildData();
        $data['slug'] = $this->__model->slugRanh($data['slug'], (int) $id);

        /* Tệp mới là KHÔNG BẮT BUỘC — sửa tiêu đề thì không phải tải lại tệp.
           Có tệp mới thì xoá tệp cũ, nhưng CHỈ SAU KHI đã ghi đường dẫn mới
           vào CSDL: xoá trước mà ghi hỏng là mất tệp và bản ghi trỏ vào hư
           không. */
        $cu = $item['file_path'];
        $up = upload_tai_lieu('file', $data['title']);
        if ($up['status'] === 'error'){ $this->flash(['file' => $up['message']], 'edit/' . $id); return; }
        if ($up['status'] === 'ok'){
            $data['file_path'] = $up['path'];
            $data['file_name'] = $up['name'];
            $data['file_size'] = $up['size'];
            $data['mime']      = $up['mime'];
        }

        $this->__model->edit($data, $id);

        if ($up['status'] === 'ok' && !empty($cu) && $cu !== $up['path']){
            $dia = DownloadsModel::duongDanDia($cu);
            if ($dia !== '' && is_file($dia)) @unlink($dia);
        }

        Session::flash('msg', 'Đã lưu ' . $this->labelOne);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    /**
     * Đổi token = THU HỒI mọi link trực tiếp đã phát, mà không đụng tới tệp
     * và không ảnh hưởng link gián tiếp trên website.
     */
    public function doiToken($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        $this->__model->edit(['token' => DownloadsModel::sinhToken()], $id);
        Session::flash('msg', 'Đã đổi link trực tiếp. Link cũ đã phát ra không còn tải được.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    public function delete($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__model->remove($id);

        /* Xoá tệp trên đĩa SAU khi bản ghi đã mất. Ngược lại mà xoá bản ghi
           hỏng thì còn dòng trỏ vào tệp không còn. */
        $dia = DownloadsModel::duongDanDia($item['file_path']);
        if ($dia !== '' && is_file($dia)) @unlink($dia);

        Session::flash('msg', 'Đã xoá ' . $this->labelOne);
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ------------------------------------------------------------------
    private function buildData(){
        $f = $this->__request->getFields();
        $ten = isset($f['title']) ? trim($f['title']) : '';
        return [
            'title'         => $ten,
            'slug'          => slugify(!empty($f['slug']) ? trim($f['slug']) : $ten),
            'description'   => isset($f['description']) ? mb_substr(trim($f['description']), 0, 500) : null,
            'require_login' => !empty($f['require_login']) ? 1 : 0,
            'is_published'  => !empty($f['is_published']) ? 1 : 0,
            'sort_order'    => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
        ];
    }

    private function validate(){
        $f = $this->__request->getFields();
        $e = [];
        $ten = isset($f['title']) ? trim($f['title']) : '';
        if ($ten === '') $e['title'] = 'Nhập tên tệp';
        elseif (mb_strlen($ten) > 200) $e['title'] = 'Tên tệp tối đa 200 ký tự';
        /* Slug rỗng SAU KHI bỏ dấu: tên toàn ký tự đặc biệt ("###") cho ra
           chuỗi rỗng, mà slug là thứ dựng nên đường dẫn tải về. */
        if ($ten !== '' && slugify(!empty($f['slug']) ? trim($f['slug']) : $ten) === ''){
            $e['title'] = 'Tên tệp phải có ít nhất một chữ hoặc số';
        }
        return $e;
    }

    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}
