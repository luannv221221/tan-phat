<?php
/**
 * Controller CRUD dùng chung cho MỌI danh mục tra cứu đơn giản.
 *
 * Song song với LookupModel: 7 danh mục (kiểu dáng xe, nhiên liệu, màu xe,
 * thương hiệu, xuất xứ, hãng sản xuất, đơn vị tính) có cùng thao tác,
 * nên chỉ viết 1 lần ở đây thay vì chép 7 lần.
 *
 * Lớp con chỉ cần khai báo 4 thuộc tính:
 *
 *   class Carfuels extends \App\core\LookupCrudController {
 *       protected $modelName  = 'CarFuelsModel';
 *       protected $routeBase  = 'car-fuels';
 *       protected $labelOne   = 'nhiên liệu';
 *       protected $labelMany  = 'Nhiên liệu (động cơ xe)';
 *   }
 *
 * Đặt ở core/ (không phải app/controllers/) để URL không với tới được —
 * App::handleUrl() chỉ tìm file trong app/controllers/.
 */

namespace App\core;

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

abstract class LookupCrudController extends Controller {

    /** Tên class model, vd 'CarFuelsModel' */
    protected $modelName;

    /** Đoạn URL, vd 'car-fuels' -> admin/car-fuels */
    protected $routeBase;

    /** Nhãn số ít, dùng trong câu thông báo: "Thêm {labelOne} thành công" */
    protected $labelOne;

    /** Nhãn tiêu đề trang */
    protected $labelMany;

    /** Danh mục này có cột `hex` (màu xe) không */
    protected $hasHex = false;

    protected $__data = [];
    protected $__model, $__request, $__response;

    public function __construct(){
        $this->__model    = $this->model($this->modelName);
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    /** Dữ liệu chung cho mọi view của controller này */
    protected function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
        $this->__data['content']['hasHex']    = $this->hasHex;
    }

    // ================= Danh sách =================

    public function index(){
        $this->__data['sub_content'] = 'admin/lookup/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();
        $this->__data['content']['page_name'] = $this->labelMany;
        $this->__data['content']['dataList']  = $this->__model->getLists();
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['msgError']  = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    // ================= Thêm =================

    public function add(){
        $this->__data['sub_content'] = 'admin/lookup/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;

        $this->baseData();
        $this->__data['content']['page_name'] = 'Thêm ' . $this->labelOne;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');

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

        // Tên toàn ký tự đặc biệt (vd "###") -> slugify ra chuỗi rỗng.
        // Không chặn thì slug='' sẽ đụng UNIQUE KEY ở bản ghi thứ hai.
        if ($data['slug'] === ''){
            Session::flash('errors', ['slug' => 'Không tự sinh được đường dẫn từ tên này. Vui lòng nhập slug thủ công.']);
            Session::flash('old', $this->__request->getFields());
            Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
            $this->__response->redirect('admin/' . $this->routeBase . '/add');
            return;
        }

        // slug phải duy nhất — kiểm tra trước để báo lỗi tử tế,
        // thay vì để UNIQUE KEY của MySQL ném exception ra trang trắng.
        if (!empty($this->__model->findBySlug($data['slug']))){
            Session::flash('errors', ['slug' => 'Đường dẫn (slug) này đã tồn tại']);
            Session::flash('old', $this->__request->getFields());
            Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
            $this->__response->redirect('admin/' . $this->routeBase . '/add');
            return;
        }

        $this->__model->add($data);

        Session::flash('msg', 'Thêm ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ================= Sửa =================

    public function edit($id){
        $item = $this->__model->getDetail($id);

        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $this->__data['sub_content'] = 'admin/lookup/edit';
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

        if (empty($this->__model->getDetail($id))){
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

        $data = $this->buildData();

        if ($data['slug'] === ''){
            Session::flash('errors', ['slug' => 'Không tự sinh được đường dẫn từ tên này. Vui lòng nhập slug thủ công.']);
            Session::flash('old', $this->__request->getFields());
            Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
            return;
        }

        // slug trùng với bản ghi KHÁC
        $existing = $this->__model->findBySlug($data['slug']);
        if (!empty($existing) && $existing['id'] != $id){
            Session::flash('errors', ['slug' => 'Đường dẫn (slug) này đã thuộc về bản ghi khác']);
            Session::flash('old', $this->__request->getFields());
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
            return;
        }

        $this->__model->edit($data, $id);

        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ================= Xoá =================

    public function delete($id){

        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $result = $this->__model->remove($id);

        // Model trả false nghĩa là còn dữ liệu con (khoá ngoại RESTRICT).
        if ($result === false){
            Session::flash('msgError',
                'Không xoá được: ' . $this->labelOne . ' này đang được dữ liệu khác sử dụng');
        } else {
            Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        }

        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ================= Helper =================

    /**
     * Chỉ `name` là bắt buộc.
     *
     * `slug` KHÔNG bắt buộc — form ghi rõ "Bỏ trống sẽ tự sinh từ tên",
     * nên bắt buộc nó ở đây là tự mâu thuẫn với chính giao diện.
     * buildData() sẽ tự sinh slug từ name khi để trống.
     */
    protected function applyRules(){
        $this->__request->rules([
            'name' => 'required|min:1',
        ]);

        $this->__request->message([
            'name.required' => 'Tên không được để trống',
            'name.min'      => 'Tên không được để trống',
        ]);
    }

    protected function flashErrors(){
        Session::flash('errors', $this->__request->error());
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
    }

    /** Gom dữ liệu từ form. Lớp con override nếu cần cột riêng. */
    protected function buildData(){
        $f = $this->__request->getFields();

        $data = [
            'name'       => trim($f['name']),
            'slug'       => slugify(!empty($f['slug']) ? $f['slug'] : $f['name']),
            'sort_order' => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'     => !empty($f['status']) ? 1 : 0,
        ];

        if ($this->hasHex){
            $data['hex'] = !empty($f['hex']) ? trim($f['hex']) : null;
        }

        return $data;
    }
}
