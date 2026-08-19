<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * DỊCH VỤ — màn hình nhập gọn cho phần việc gara làm bằng tay
 * (thay dầu, bảo dưỡng, sửa chữa...).
 *
 * Dữ liệu vẫn nằm ở bảng `parts` với `item_type = 'service'`, cùng chỗ với
 * phụ tùng và thiết bị. Chỉ khác GIAO DIỆN: ở đây đúng hai ô bắt buộc là tên
 * và tiền, không có mã OEM / thương hiệu / hãng sản xuất / lắp cho đời xe /
 * thông số kỹ thuật — dịch vụ không có mấy thứ đó.
 *
 * Nhờ dùng chung bảng nên dịch vụ tạo ở đây hiện ngay trong ô chọn hàng của
 * báo giá và hoá đơn, và mọi chốt kho vẫn tự động bỏ qua (PartsModel::coKho).
 */
class Services extends Controller {

    /** Tiền tố mã tự sinh khi người dùng để trống ô Mã */
    const TIEN_TO_MA = 'DV-';

    private $__data = [];
    private $__model, $__catModel, $__unitModel, $__request, $__response;

    private $routeBase = 'services';
    private $labelOne  = 'dịch vụ';
    private $labelMany = 'Dịch vụ';
    private $viewDir   = 'admin/services';

    function __construct(){
        $this->__model     = $this->model('PartsModel');
        $this->__catModel  = $this->model('PartCategoriesModel');
        $this->__unitModel = $this->model('ProductUnitsModel');
        $this->__request   = new Request();
        $this->__response  = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
    }

    /** Dropdown cho form. Danh mục CHỈ lấy nhánh "Dịch vụ". */
    private function formData(){
        $this->__data['content']['categories'] = $this->__catModel->nhanhTheoSlug('dich-vu');
        $this->__data['content']['units']      = $this->__unitModel->getLists();
    }

    // ================= Danh sách =================

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();
        $f       = $this->__request->getFields();
        $keyword = isset($f['keyword']) ? trim($f['keyword']) : '';

        $this->__data['content']['page_name'] = $this->labelMany;
        $this->__data['content']['dataList']  = $this->__model->getLists(
            ['parts.item_type' => PartsModel::LOAI_DICH_VU], $keyword);
        $this->__data['content']['keyword']   = $keyword;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['msgError']  = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    // ================= Thêm =================

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;

        $this->baseData();
        $this->formData();
        $this->__data['content']['page_name'] = 'Thêm ' . $this->labelOne;
        $this->__data['content']['maGoiY']    = $this->__model->nextCode(self::TIEN_TO_MA);
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validateInput(null);
        if (!empty($errors)){ $this->flash($errors, 'add'); return; }

        $this->__model->add($this->buildData(null));

        Session::flash('msg', 'Thêm ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ================= Sửa =================

    public function edit($id){
        $item = $this->layDichVu($id);
        if (empty($item)) return;

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;

        $this->baseData();
        $this->formData();
        $this->__data['content']['page_name'] = 'Sửa ' . $this->labelOne;
        $this->__data['content']['item']      = $item;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->layDichVu($id);
        if (empty($item)) return;

        $errors = $this->validateInput($id);
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }

        $this->__model->edit($this->buildData($id), $id);

        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ================= Xoá =================

    public function delete($id){
        $item = $this->layDichVu($id);
        if (empty($item)) return;

        $this->__model->remove($id);

        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ================= Helper =================

    /**
     * Lấy bản ghi và CHẶN nếu nó không phải dịch vụ.
     *
     * Không kiểm tra item_type ở đây thì /admin/services/delete/<id của một
     * phụ tùng> vẫn xoá được, dù người dùng chỉ được cấp màn hình Dịch vụ.
     * Trả về null kèm redirect sẵn; nơi gọi chỉ cần `if (empty(...)) return;`.
     */
    private function layDichVu($id){
        $item = $this->__model->getDetail($id);

        if (empty($item) || $item['item_type'] !== PartsModel::LOAI_DICH_VU){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return null;
        }
        return $item;
    }

    private function validateInput($id){
        $f      = $this->__request->getFields();
        $errors = [];

        if (!isset($f['name']) || trim($f['name']) === ''){
            $errors['name'] = 'Tên dịch vụ không được để trống';
        }

        // Ô Mã được phép bỏ trống — buildData() tự sinh. Có nhập thì phải chưa ai dùng.
        $code = isset($f['code']) ? trim($f['code']) : '';
        if ($code !== ''){
            $existing = $this->__model->findByCode($code);
            if (!empty($existing) && ($id === null || $existing['id'] != $id)){
                $errors['code'] = 'Mã này đã có hàng hoá / dịch vụ khác dùng';
            }
        }

        if (!isset($f['price']) || trim((string) $f['price']) === ''){
            $errors['price'] = 'Nhập giá dịch vụ';
        } elseif ($this->parseMoney($f['price']) < 0){
            $errors['price'] = 'Giá không hợp lệ';
        }

        return $errors;
    }

    private function buildData($id){
        $f    = $this->__request->getFields();
        $ten  = trim($f['name']);
        $code = isset($f['code']) ? trim($f['code']) : '';

        return [
            'code'        => $code !== '' ? $code : $this->__model->nextCode(self::TIEN_TO_MA),
            'name'        => $ten,
            // Cứng 'service', KHÔNG đọc từ form: màn hình này chỉ tạo dịch vụ.
            // Nhận item_type từ POST là mở đường cho một dòng 'part' không có
            // tồn kho lọt vào bằng cửa sau rồi chặn hết mọi hoá đơn chứa nó.
            'item_type'   => PartsModel::LOAI_DICH_VU,
            'slug'        => $this->__model->slugTrong($ten, $id),
            'category_id' => $this->validCat(isset($f['category_id']) ? $f['category_id'] : null),
            'unit_id'     => $this->validUnit(isset($f['unit_id']) ? $f['unit_id'] : null),
            'price'       => $this->parseMoney(isset($f['price']) ? $f['price'] : 0),
            'description' => !empty($f['description']) ? trim($f['description']) : null,
            'status'      => !empty($f['status']) ? 1 : 0,
            'show_on_web' => !empty($f['show_on_web']) ? 1 : 0,
        ];
    }

    /** Danh mục phải nằm trong nhánh "Dịch vụ", ngoài nhánh -> bỏ trống. */
    private function validCat($id){
        $id = (int) $id;
        if ($id <= 0) return null;

        foreach ($this->__catModel->nhanhTheoSlug('dich-vu') as $c){
            if ((int) $c['id'] === $id) return $id;
        }
        return null;
    }

    private function validUnit($id){
        $id = (int) $id;
        if ($id <= 0) return null;
        return !empty($this->__unitModel->getDetail($id)) ? $id : null;
    }

    /** "1.380.000 đ" -> 1380000. Chỉ giữ chữ số, giống các màn hình tiền khác. */
    private function parseMoney($val){
        $d = preg_replace('/[^\d]/', '', (string) $val);
        return $d === '' ? 0 : (float) $d;
    }

    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}
