<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * Hàng hoá (parts) — màn hình nghiệp vụ trung tâm.
 *
 * Đặc thù:
 *   - Nhiều khoá ngoại (danh mục, thương hiệu, hãng SX, xuất xứ, đơn vị)
 *   - Gán 1 hàng hoá cho NHIỀU đời xe (part_fitments) qua PartFitmentsModel::syncForPart
 *   - Phân trang (hàng hoá có thể hàng nghìn dòng)
 */
class Products extends Controller {

    private $__data = [];
    private $__model, $__fitment, $__request, $__response;
    private $__catModel, $__brandModel, $__mnfModel, $__originModel, $__unitModel, $__yearModel, $__imgModel, $__relatedModel;
    private $__attrModel, $__attrValModel;

    // Upload ảnh hàng hoá (TASK_77)
    private $imgDir      = 'public/assets/uploads/parts/';
    private $imgAllowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private $imgMaxBytes = 3145728; // 3MB / ảnh

    private $routeBase = 'products';   // giữ URL cũ admin/products
    private $labelOne  = 'hàng hoá';
    private $labelMany = 'Quản lý hàng hoá';
    private $viewDir   = 'admin/products';

    /** Tab "Sản phẩm website" — lọc theo THUỘC TÍNH được đăng web, không phải loại hàng */
    const TAB_WEB = 'web';

    private $perPage = 20;

    function __construct(){
        $this->__model       = $this->model('PartsModel');
        $this->__fitment     = $this->model('PartFitmentsModel');
        $this->__catModel    = $this->model('PartCategoriesModel');
        $this->__brandModel  = $this->model('ProductBrandsModel');
        $this->__mnfModel    = $this->model('ProductManufacturersModel');
        $this->__originModel = $this->model('ProductOriginsModel');
        $this->__unitModel   = $this->model('ProductUnitsModel');
        $this->__yearModel   = $this->model('CarYearsModel');
        $this->__imgModel    = $this->model('PartImagesModel');
        $this->__relatedModel= $this->model('PartRelatedModel');
        $this->__attrModel   = $this->model('AttributesModel');
        $this->__attrValModel= $this->model('PartAttributeValuesModel');
        $this->__request     = new Request();
        $this->__response    = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
    }

    /** Dropdown + danh sách đời xe (checkbox lắp đặt) cho form */
    private function formData(){
        $this->__data['content']['categories']   = $this->__catModel->getTree();
        $this->__data['content']['brands']       = $this->__brandModel->getLists();
        $this->__data['content']['manufacturers']= $this->__mnfModel->getLists();
        $this->__data['content']['origins']      = $this->__originModel->getLists();
        $this->__data['content']['units']        = $this->__unitModel->getLists();
        $this->__data['content']['carYears']     = $this->__yearModel->getLists(); // kèm brand_name, model_name
        $this->__data['content']['attributes']   = $this->__attrModel->getActive();  // thông số kỹ thuật đang bật
    }

    // ================= Danh sách (có phân trang) =================

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();

        $f       = $this->__request->getFields();
        $keyword = isset($f['keyword']) ? trim($f['keyword']) : '';
        $catId   = isset($f['category_id']) && $f['category_id'] !== '' ? (int) $f['category_id'] : 0;
        $promo   = !empty($f['promo']);
        $attrId  = isset($f['attr_id']) && $f['attr_id'] !== '' ? (int) $f['attr_id'] : 0;
        $attrVal = isset($f['attr_val']) ? trim($f['attr_val']) : '';
        $page    = isset($f['page']) && (int) $f['page'] > 0 ? (int) $f['page'] : 1;

        /* Tab phân nhóm. MỘT tham số `tab` cho cả 4 tab lọc, vì chúng loại trừ
           nhau khi bấm — dùng hai tham số riêng thì phải tự nhớ xoá cái kia.
              part / equipment / service : lọc theo LOẠI hàng
              web                        : lọc theo THUỘC TÍNH được đăng web
           'web' không phải loại hàng thứ tư nên nó chồng lấn với 3 tab trên. */
        $tab = isset($f['tab']) ? $f['tab'] : '';
        if (!isset(PartsModel::$loaiHang[$tab]) && $tab !== self::TAB_WEB) $tab = '';

        $filters = [];
        if ($catId > 0){
            $filters['parts.category_id'] = $catId;
        }
        $filters = array_merge($filters, $this->locTheoTab($tab));

        /* Số dòng/trang do người dùng chọn ở chân bảng (?per_page=), 0 = tất cả.
           Màn hình này cắt dưới CSDL bằng LIMIT/OFFSET chứ không cắt trên mảng
           như các danh sách khác — catalogue hàng hoá là bảng dài nhất hệ thống. */
        $perPage    = phan_trang_so_dong($this->perPage);
        $total      = $this->__model->countLists($filters, $keyword, $promo, $attrId, $attrVal);
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
        if ($totalPages < 1) $totalPages = 1;
        if ($page > $totalPages){
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $this->__data['content']['page_name']     = $this->labelMany;
        // getLists() hiểu limit = 0 là "lấy hết" nên "Tất cả" không cần nhánh riêng.
        $dataList = $this->__model->getLists($filters, $keyword, $perPage, $offset, $promo, $attrId, $attrVal);
        $this->__data['content']['dataList']      = $dataList;

        // Ảnh đại diện cho cột "Ảnh". Ảnh nằm ở bảng part_images chứ không phải
        // cột trong `parts`, nên phải map riêng theo id.
        $imgMap = [];
        foreach ($dataList as $row){
            $imgMap[(int) $row['id']] = $this->__imgModel->primaryFor((int) $row['id']);
        }
        $this->__data['content']['imgMap'] = $imgMap;

        $this->__data['content']['categories']    = $this->__catModel->getTree();
        $this->__data['content']['attributes']    = $this->__attrModel->getActive();
        $this->__data['content']['keyword']       = $keyword;
        $this->__data['content']['filterCat']     = $catId;
        $this->__data['content']['filterLoai']    = $tab;

        /* Số lượng từng tab. Đếm theo ĐÚNG bộ lọc đang áp (từ khoá, danh mục,
           khuyến mãi...) nhưng BỎ điều kiện của tab — nếu không thì con số
           trên tab không khớp với số dòng khi bấm vào. */
        $fGoc = $filters;
        unset($fGoc['parts.item_type'], $fGoc['parts.show_on_web']);

        $demTheoLoai = ['' => $this->__model->countLists($fGoc, $keyword, $promo, $attrId, $attrVal)];
        foreach (array_merge(array_keys(PartsModel::$loaiHang), [self::TAB_WEB]) as $ma){
            $demTheoLoai[$ma] = $this->__model->countLists(
                array_merge($fGoc, $this->locTheoTab($ma)), $keyword, $promo, $attrId, $attrVal);
        }

        $this->__data['content']['demTheoLoai'] = $demTheoLoai;
        $this->__data['content']['loaiHang']    = PartsModel::$loaiHang;
        $this->__data['content']['tabWeb']      = self::TAB_WEB;

        $this->__data['content']['filterPromo']   = $promo;
        $this->__data['content']['filterAttrId']  = $attrId;
        $this->__data['content']['filterAttrVal'] = $attrVal;
        $this->__data['content']['page']          = $page;
        $this->__data['content']['perPage']      = $perPage;
        $this->__data['content']['total']        = $total;
        $this->__data['content']['totalPages']   = $totalPages;
        $this->__data['content']['msg']          = Session::flash('msg');
        $this->__data['content']['msgError']     = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    /**
     * Điều kiện lọc của một tab. Tách ra hàm riêng để chỗ ĐẾM và chỗ LỌC dùng
     * chung đúng một luật — viết hai nơi là sớm muộn lệch nhau, rồi số trên
     * tab không khớp số dòng bên dưới.
     */
    private function locTheoTab($tab){
        if ($tab === self::TAB_WEB) return ['parts.show_on_web' => 1];
        if (isset(PartsModel::$loaiHang[$tab])) return ['parts.item_type' => $tab];
        return [];
    }

    // ================= Thêm =================

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;

        $this->baseData();
        $this->formData();
        $this->__data['content']['page_name']     = 'Thêm ' . $this->labelOne;
        $this->__data['content']['selFitments']   = [];
        $this->__data['content']['relatedParts']  = [];
        $this->__data['content']['attrValues']    = [];
        $this->__data['content']['msg']           = Session::flash('msg');
        $this->__data['content']['errors']        = Session::flash('errors');
        $this->__data['content']['old']           = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validateInput(null);
        if (!empty($errors)){
            $this->flash($errors, 'add');
            return;
        }

        $data    = $this->buildData();
        $partId  = $this->__model->add($data);

        $this->syncFitments($partId);
        $this->syncRelated($partId);
        $this->syncAttrs($partId);

        // Ảnh nằm ở bảng part_images nên phải có part_id trước mới lưu được —
        // vì thế xử lý ở ĐÂY, sau khi đã tạo hàng hoá, chứ không thể lưu song song.
        // Trước đây trang Thêm mới không có ô chọn ảnh, phải lưu rồi bấm Sửa
        // mới thêm được ảnh.
        list($ok, $fail) = $this->storeUploadedImages($partId, $data['slug']);

        $msg = 'Thêm ' . $this->labelOne . ' thành công';
        if ($ok > 0)   $msg .= ", đã tải lên $ok ảnh";
        if ($fail > 0) $msg .= ", $fail ảnh bị bỏ qua (sai định dạng hoặc quá 3MB)";

        Session::flash('msg', $msg);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $partId);
    }

    // ================= Sửa =================

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
        $this->formData();
        $this->__data['content']['page_name']   = 'Sửa ' . $this->labelOne;
        $this->__data['content']['item']        = $item;
        $this->__data['content']['selFitments'] = $this->__fitment->getCarYearIds($id);
        $this->__data['content']['images']      = $this->__imgModel->getByPart($id);
        $this->__data['content']['relatedParts']= $this->__relatedModel->getRelatedParts($id);
        $this->__data['content']['attrValues']  = $this->__attrValModel->getValuesMap($id);
        $this->__data['content']['msg']         = Session::flash('msg');
        $this->__data['content']['errors']      = Session::flash('errors');
        $this->__data['content']['old']         = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $errors = $this->validateInput($id);
        if (!empty($errors)){
            $this->flash($errors, 'edit/' . $id);
            return;
        }

        $this->__model->edit($this->buildData(), $id);
        $this->syncFitments($id);
        $this->syncRelated($id);
        $this->syncAttrs($id);

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

        // part_fitments ON DELETE CASCADE nên liên kết tự xoá theo.
        $this->__model->remove($id);

        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ================= Ảnh (TASK_77) =================

    /** Upload nhiều ảnh cho 1 hàng hoá */
    public function postImages($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        // Quản lý ảnh = sửa hàng hoá -> cần quyền edit
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen');
            return;
        }

        if (empty($this->normalizeFiles('images'))){
            Session::flash('msgError', 'Chưa chọn ảnh nào để tải lên');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
            return;
        }

        list($ok, $fail) = $this->storeUploadedImages($id, $item['slug']);

        if ($ok > 0){
            Session::flash('msg', "Đã tải lên $ok ảnh" . ($fail > 0 ? ", $fail ảnh bị bỏ qua (sai định dạng/quá lớn)" : ''));
        } else {
            Session::flash('msgError', 'Không tải được ảnh nào (sai định dạng hoặc quá 3MB)');
        }

        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /**
     * Lưu các file trong $_FILES['images'] vào hàng hoá $partId.
     *
     * Dùng chung cho cả trang Thêm mới lẫn trang Sửa, để hai nơi không lệch
     * nhau về giới hạn dung lượng / định dạng.
     *
     * Ảnh đầu tiên của hàng hoá tự thành ảnh đại diện (PartImagesModel::add).
     *
     * @return array [số ảnh lưu được, số ảnh bị bỏ qua]
     */
    private function storeUploadedImages($partId, $slug){
        $files = $this->normalizeFiles('images');
        if (empty($files)) return [0, 0];

        if (!is_dir($this->imgDir)){
            @mkdir($this->imgDir, 0755, true);
        }

        $ok = 0; $fail = 0;
        foreach ($files as $file){
            $name = $this->saveImage($file, $slug);
            if ($name === null){ $fail++; continue; }
            $this->__imgModel->add($partId, $name);
            $ok++;
        }

        return [$ok, $fail];
    }

    /** Xoá 1 ảnh (kèm file vật lý) */
    public function imageDelete($imageId){
        $img = $this->__imgModel->getDetail($imageId);
        if (empty($img)){
            Session::flash('msgError', 'Không tìm thấy ảnh');
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $partId = $img['part_id'];

        if (!route('admin/' . $this->routeBase . '/delete/' . $partId)){
            $this->__response->redirect('admin/khong-co-quyen');
            return;
        }

        $file = $this->__imgModel->remove($imageId);
        if (!empty($file) && is_file($this->imgDir . $file)){
            @unlink($this->imgDir . $file);
        }

        Session::flash('msg', 'Đã xoá ảnh');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $partId);
    }

    /** Đặt 1 ảnh làm ảnh đại diện */
    public function imagePrimary($imageId){
        $img = $this->__imgModel->getDetail($imageId);
        if (empty($img)){
            Session::flash('msgError', 'Không tìm thấy ảnh');
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        if (!route('admin/' . $this->routeBase . '/edit/' . $img['part_id'])){
            $this->__response->redirect('admin/khong-co-quyen');
            return;
        }

        $this->__imgModel->setPrimary($imageId);
        Session::flash('msg', 'Đã đặt ảnh đại diện');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $img['part_id']);
    }

    /** Chuẩn hoá $_FILES['images'] (multiple) thành mảng từng file */
    private function normalizeFiles($field){
        if (empty($_FILES[$field]) || !isset($_FILES[$field]['name'])){
            return [];
        }

        $f   = $_FILES[$field];
        $out = [];

        if (is_array($f['name'])){
            foreach ($f['name'] as $i => $name){
                if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                $out[] = [
                    'name'     => $name,
                    'type'     => $f['type'][$i],
                    'tmp_name' => $f['tmp_name'][$i],
                    'error'    => $f['error'][$i],
                    'size'     => $f['size'][$i],
                ];
            }
        } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE){
            $out[] = $f;
        }

        return $out;
    }

    /** Lưu 1 file ảnh, trả tên file hoặc null nếu không hợp lệ */
    private function saveImage($file, $slugPrefix){
        if ($file['error'] !== UPLOAD_ERR_OK)            return null;
        if ($file['size'] > $this->imgMaxBytes)          return null;

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->imgAllowed, true))    return null;
        if (getimagesize($file['tmp_name']) === false)   return null;

        $prefix  = $slugPrefix !== '' ? $slugPrefix : 'part';
        $newName = $prefix . '-' . bin2hex(random_bytes(5)) . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $this->imgDir . $newName)){
            return null;
        }

        return $newName;
    }

    // ================= Import Excel/CSV (TASK_78) =================

    /** Header chấp nhận cho từng cột (chuẩn hoá: thường, gạch dưới) */
    private $importMap = [
        'code'              => ['code', 'ma', 'ma_phu_tung'],
        'name'              => ['name', 'ten', 'ten_phu_tung'],
        'slug'              => ['slug', 'duong_dan'],
        'oem_code'          => ['oem_code', 'oem', 'ma_oem'],
        'price'             => ['price', 'gia', 'gia_ban'],
        'sale_price'        => ['sale_price', 'gia_km', 'gia_khuyen_mai'],
        'warranty_month'    => ['warranty_month', 'bao_hanh', 'bao_hanh_thang'],
        'status'            => ['status', 'trang_thai'],
        'category_slug'     => ['category_slug', 'danh_muc', 'slug_danh_muc'],
        'brand_slug'        => ['brand_slug', 'thuong_hieu', 'slug_thuong_hieu'],
        'manufacturer_slug' => ['manufacturer_slug', 'hang_sx', 'hang_san_xuat'],
        'origin_slug'       => ['origin_slug', 'xuat_xu'],
        'unit_slug'         => ['unit_slug', 'don_vi', 'don_vi_tinh'],
        'description'       => ['description', 'mo_ta'],
    ];

    public function import(){
        $this->__data['sub_content'] = $this->viewDir . '/import';
        $this->__data['page_title']  = 'Import hàng hoá';

        $this->baseData();
        $this->__data['content']['page_name'] = 'Import hàng hoá từ Excel / CSV';
        $this->__data['content']['result']    = Session::flash('importResult');
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['msgError']  = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postImport(){
        // Import = thêm/sửa hàng hoá -> cần quyền add
        if (!route('admin/' . $this->routeBase . '/add')){
            $this->__response->redirect('admin/khong-co-quyen');
            return;
        }

        if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE){
            Session::flash('msgError', 'Chưa chọn file để import');
            $this->__response->redirect('admin/' . $this->routeBase . '/import');
            return;
        }

        $file = $_FILES['file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['error'] !== UPLOAD_ERR_OK || !in_array($ext, ['xlsx', 'csv'], true) || $file['size'] > 5242880){
            Session::flash('msgError', 'File không hợp lệ (chỉ .xlsx hoặc .csv, tối đa 5MB)');
            $this->__response->redirect('admin/' . $this->routeBase . '/import');
            return;
        }

        $rows = \App\core\SpreadsheetReader::read($file['tmp_name'], $ext);

        if (count($rows) < 2){
            Session::flash('msgError', 'File rỗng hoặc không có dòng dữ liệu nào');
            $this->__response->redirect('admin/' . $this->routeBase . '/import');
            return;
        }

        $result = $this->processImport($rows);

        Session::flash('importResult', $result);
        Session::flash('msg', "Import xong: {$result['created']} thêm mới, {$result['updated']} cập nhật, "
            . count($result['errors']) . ' lỗi.');
        $this->__response->redirect('admin/' . $this->routeBase . '/import');
    }

    private function processImport($rows){
        // Ánh xạ cột từ dòng tiêu đề
        $header = array_shift($rows);
        $colMap = [];
        foreach ($header as $i => $title){
            $norm = $this->normalizeHeader($title);
            foreach ($this->importMap as $field => $aliases){
                if (in_array($norm, $aliases, true)){
                    $colMap[$field] = $i;
                    break;
                }
            }
        }

        $result = ['created' => 0, 'updated' => 0, 'errors' => []];

        if (!isset($colMap['code']) || !isset($colMap['name'])){
            $result['errors'][] = 'Thiếu cột bắt buộc: cần có "code" và "name" ở dòng tiêu đề.';
            return $result;
        }

        $get = function($row, $field) use ($colMap){
            return isset($colMap[$field]) && isset($row[$colMap[$field]]) ? trim((string) $row[$colMap[$field]]) : '';
        };

        $max = 2000; // chặn file quá lớn
        $line = 1;
        foreach ($rows as $row){
            $line++;
            if ($line - 1 > $max){
                $result['errors'][] = "Chỉ xử lý tối đa $max dòng; phần còn lại bị bỏ qua.";
                break;
            }

            // bỏ dòng trống hoàn toàn
            if (implode('', array_map('strval', $row)) === '') continue;

            $code = $get($row, 'code');
            $name = $get($row, 'name');

            if ($code === ''){ $result['errors'][] = "Dòng $line: thiếu mã hàng hoá — bỏ qua."; continue; }
            if ($name === ''){ $result['errors'][] = "Dòng $line: thiếu tên hàng hoá — bỏ qua."; continue; }

            $data = [
                'code'            => $code,
                'name'            => $name,
                'oem_code'        => $get($row, 'oem_code') ?: null,
                'price'           => $this->parseMoney($get($row, 'price')),
                'sale_price'      => $get($row, 'sale_price') !== '' ? $this->parseMoney($get($row, 'sale_price')) : null,
                'warranty_month'  => $get($row, 'warranty_month') !== '' ? (int) $get($row, 'warranty_month') : null,
                'status'          => $this->parseStatus($get($row, 'status')),
                'description'     => $get($row, 'description') ?: null,
                'category_id'     => $this->fkBySlug($this->__catModel,    $get($row, 'category_slug')),
                'brand_id'        => $this->fkBySlug($this->__brandModel,  $get($row, 'brand_slug')),
                'manufacturer_id' => $this->fkBySlug($this->__mnfModel,    $get($row, 'manufacturer_slug')),
                'origin_id'       => $this->fkBySlug($this->__originModel, $get($row, 'origin_slug')),
                'unit_id'         => $this->fkBySlug($this->__unitModel,   $get($row, 'unit_slug')),
            ];

            $existing = $this->__model->findByCode($code);

            // slug: ưu tiên cột slug, ngược lại sinh từ tên
            $slug = slugify($get($row, 'slug') ?: $name);
            if ($slug === ''){ $result['errors'][] = "Dòng $line ($code): không tạo được slug — bỏ qua."; continue; }

            if (!empty($existing)){
                // cập nhật: né đụng slug với bản ghi KHÁC
                $bySlug = $this->__model->findBySlug($slug);
                if (!empty($bySlug) && $bySlug['id'] != $existing['id']){
                    $slug = slugify($name . '-' . $code);
                }
                $data['slug'] = $slug;
                $this->__model->edit($data, $existing['id']);
                $result['updated']++;
            } else {
                // tạo mới: slug phải chưa tồn tại
                if (!empty($this->__model->findBySlug($slug))){
                    $slug = slugify($name . '-' . $code);
                    if (!empty($this->__model->findBySlug($slug))){
                        $result['errors'][] = "Dòng $line ($code): slug bị trùng — bỏ qua.";
                        continue;
                    }
                }
                $data['slug'] = $slug;
                $this->__model->add($data);
                $result['created']++;
            }
        }

        return $result;
    }

    /** Xuất file CSV mẫu (UTF-8 BOM để Excel mở đúng tiếng Việt) */
    public function importTemplate(){
        $headers = ['code', 'name', 'oem_code', 'price', 'sale_price', 'warranty_month',
                    'status', 'category_slug', 'brand_slug', 'manufacturer_slug',
                    'origin_slug', 'unit_slug', 'description'];
        $sample  = ['PT-0001', 'Lọc gió động cơ Vios', 'OEM-17801', '350000', '', '12',
                    '1', 'loc-gio', 'bosch', '', 'nhat-ban', 'cai', 'Lọc gió chính hãng'];

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="mau-import-phu-tung.csv"');
        echo "\xEF\xBB\xBF"; // BOM
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        fputcsv($out, $sample);
        fclose($out);
        exit;
    }

    /** TASK_85 — Xuất catalogue hàng hoá ra CSV (theo bộ lọc hiện tại) */
    public function export(){
        $f       = $this->__request->getFields();
        $keyword = isset($f['keyword']) ? trim($f['keyword']) : '';
        $catId   = isset($f['category_id']) && $f['category_id'] !== '' ? (int) $f['category_id'] : 0;
        $promo   = !empty($f['promo']);
        $attrId  = isset($f['attr_id']) && $f['attr_id'] !== '' ? (int) $f['attr_id'] : 0;
        $attrVal = isset($f['attr_val']) ? trim($f['attr_val']) : '';

        $filters = [];
        if ($catId > 0){
            $filters['parts.category_id'] = $catId;
        }

        // limit=0 -> lấy tất cả dòng khớp bộ lọc
        $rows = $this->__model->getLists($filters, $keyword, 0, 0, $promo, $attrId, $attrVal);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="catalogue-phu-tung.csv"');
        echo "\xEF\xBB\xBF"; // BOM để Excel mở đúng tiếng Việt
        $out = fopen('php://output', 'w');
        fputcsv($out, ['code', 'oem_code', 'name', 'category', 'brand', 'origin', 'unit',
                       'price', 'sale_price', 'warranty_month', 'status']);
        foreach ($rows as $r){
            fputcsv($out, [
                $r['code'],
                $r['oem_code'],
                $r['name'],
                $r['category_name'] ?? '',
                $r['brand_name'] ?? '',
                $r['origin_name'] ?? '',
                $r['unit_name'] ?? '',
                (int) $r['price'],
                $r['sale_price'] !== null ? (int) $r['sale_price'] : '',
                $r['warranty_month'],
                (int) $r['status'] === 1 ? 'hiện' : 'ẩn',
            ]);
        }
        fclose($out);
        exit;
    }

    private function normalizeHeader($title){
        $s = trim(mb_strtolower((string) $title));
        $s = str_replace([' ', '-'], '_', $s);
        return $s;
    }

    private function parseStatus($val){
        $v = mb_strtolower(trim((string) $val));
        if ($v === '' ) return 1; // mặc định hiển thị
        return in_array($v, ['0', 'no', 'off', 'an', 'ẩn', 'false'], true) ? 0 : 1;
    }

    /** slug -> id (null nếu rỗng/không tìm thấy) */
    private function fkBySlug($model, $slug){
        $slug = trim((string) $slug);
        if ($slug === '') return null;
        $row = $model->findBySlug($slug);
        return !empty($row) ? $row['id'] : null;
    }

    // ================= Helper =================

    /** @return array lỗi (rỗng nếu hợp lệ). $id = bản ghi đang sửa (null nếu thêm) */
    private function validateInput($id){
        $f      = $this->__request->getFields();
        $errors = [];

        $code = isset($f['code']) ? trim($f['code']) : '';
        if ($code === ''){
            $errors['code'] = 'Mã hàng hoá không được để trống';
        } else {
            $existing = $this->__model->findByCode($code);
            if (!empty($existing) && ($id === null || $existing['id'] != $id)){
                $errors['code'] = 'Mã hàng hoá này đã tồn tại';
            }
        }

        if (!isset($f['name']) || trim($f['name']) === ''){
            $errors['name'] = 'Tên hàng hoá không được để trống';
        }

        // slug
        $slug = slugify(!empty($f['slug']) ? $f['slug'] : (!empty($f['name']) ? $f['name'] : ''));
        if ($slug === ''){
            $errors['slug'] = 'Không tự sinh được đường dẫn từ tên. Vui lòng nhập slug thủ công.';
        } else {
            $existing = $this->__model->findBySlug($slug);
            if (!empty($existing) && ($id === null || $existing['id'] != $id)){
                $errors['slug'] = 'Đường dẫn (slug) này đã tồn tại';
            }
        }

        if (isset($f['price']) && $f['price'] !== '' && $this->parseMoney($f['price']) < 0){
            $errors['price'] = 'Giá không hợp lệ';
        }

        return $errors;
    }

    private function buildData(){
        $f = $this->__request->getFields();

        $slug = slugify(!empty($f['slug']) ? $f['slug'] : $f['name']);

        $data = [
            'code'            => trim($f['code']),
            'oem_code'        => !empty($f['oem_code']) ? trim($f['oem_code']) : null,
            'name'            => trim($f['name']),
            'slug'            => $slug,
            // Loại quyết định NGHIỆP VỤ (dịch vụ thì bỏ mọi chốt kho), tách khỏi
            // danh mục vốn chỉ lo hiển thị. Giá trị lạ -> về 'part' cho an toàn:
            // đoán nhầm thành dịch vụ là hàng thật thoát kiểm tồn.
            'item_type'       => isset(PartsModel::$loaiHang[$f['item_type'] ?? ''])
                                 ? $f['item_type'] : PartsModel::LOAI_PHU_TUNG,
            'category_id'     => $this->validFk($this->__catModel,    $f['category_id']     ?? null),
            'brand_id'        => $this->validFk($this->__brandModel,  $f['brand_id']        ?? null),
            'manufacturer_id' => $this->validFk($this->__mnfModel,    $f['manufacturer_id'] ?? null),
            'origin_id'       => $this->validFk($this->__originModel, $f['origin_id']       ?? null),
            'unit_id'         => $this->validFk($this->__unitModel,   $f['unit_id']         ?? null),
            'price'           => $this->parseMoney($f['price'] ?? 0),
            'sale_price'      => (isset($f['sale_price']) && $f['sale_price'] !== '') ? $this->parseMoney($f['sale_price']) : null,
            'warranty_month'  => (isset($f['warranty_month']) && $f['warranty_month'] !== '') ? (int) $f['warranty_month'] : null,
            'description'     => !empty($f['description']) ? trim($f['description']) : null,
            // Hai cờ ĐỘC LẬP: ngừng đăng web không đồng nghĩa ngừng kinh doanh.
            // Hàng tắt show_on_web vẫn phải xuất hoá đơn và nhập/xuất kho được.
            'status'          => !empty($f['status']) ? 1 : 0,
            'show_on_web'     => !empty($f['show_on_web']) ? 1 : 0,
        ];

        return $this->boTruongKhongThuocLoai($data);
    }

    /**
     * Xoá các trường không thuộc loại hàng đã chọn.
     *
     * Form đã ẩn chúng bằng JS, nhưng ẩn KHÔNG phải là không gửi:
     *   - người dùng điền "Mã OEM" rồi mới đổi Loại sang Dịch vụ -> ô ẩn đi
     *     nhưng giá trị vẫn nằm trong form và vẫn được gửi lên
     *   - ai đó gửi thẳng POST thì chẳng có JS nào chặn
     * Không dọn ở đây là dữ liệu rác nằm im trong CSDL, sau này lọc/báo cáo
     * theo mấy cột đó sẽ ra kết quả khó hiểu.
     */
    private function boTruongKhongThuocLoai($data){
        $loai = $data['item_type'];

        // Mã OEM chỉ có nghĩa với phụ tùng thay thế
        if ($loai !== PartsModel::LOAI_PHU_TUNG){
            $data['oem_code'] = null;
        }

        // Dịch vụ không có thương hiệu / hãng sản xuất / xuất xứ
        if ($loai === PartsModel::LOAI_DICH_VU){
            $data['brand_id']        = null;
            $data['manufacturer_id'] = null;
            $data['origin_id']       = null;
        }

        return $data;
    }

    /** Gán hàng hoá cho các đời xe được tick (lọc bỏ id không tồn tại) */
    private function syncFitments($partId){
        $f     = $this->__request->getFields();
        $picked = isset($f['fitments']) && is_array($f['fitments']) ? $f['fitments'] : [];

        // Chỉ PHỤ TÙNG mới lắp cho đời xe. Thiết bị (cầu nâng) và dịch vụ
        // (thay dầu) không gắn xe — khách đã chốt. Truyền mảng rỗng chứ không
        // return sớm: đổi loại từ phụ tùng sang dịch vụ thì phải XOÁ liên kết cũ.
        $loai = isset($f['item_type']) ? $f['item_type'] : PartsModel::LOAI_PHU_TUNG;
        if ($loai !== PartsModel::LOAI_PHU_TUNG){
            $this->__fitment->syncForPart($partId, []);
            return;
        }

        $valid = [];
        foreach ($picked as $yearId){
            $yearId = (int) $yearId;
            if ($yearId > 0 && !empty($this->__yearModel->getDetail($yearId))){
                $valid[] = $yearId;
            }
        }

        $this->__fitment->syncForPart($partId, $valid);
    }

    /** Gán phụ kiện đi kèm được chọn (lọc bỏ id không tồn tại + chính nó) — TASK_81 */
    private function syncRelated($partId){
        $f      = $this->__request->getFields();
        $picked = isset($f['related']) && is_array($f['related']) ? $f['related'] : [];

        $valid = [];
        foreach ($picked as $rid){
            $rid = (int) $rid;
            if ($rid > 0 && $rid !== (int) $partId && !empty($this->__model->getDetail($rid))){
                $valid[] = $rid;
            }
        }

        $this->__relatedModel->syncForPart($partId, $valid);
    }

    /**
     * Lưu giá trị thông số kỹ thuật được nhập (attr[attribute_id] = value) — TASK_90.
     *
     * Chỉ nhận thông số CÓ ÁP cho loại hàng đang lưu. Đổi loại từ Thiết bị
     * sang Dịch vụ thì "Tải trọng" phải mất hẳn, chứ không nằm lại âm thầm
     * rồi lòi ra ở trang chi tiết ngoài web.
     */
    private function syncAttrs($partId){
        $f   = $this->__request->getFields();
        $map = isset($f['attr']) && is_array($f['attr']) ? $f['attr'] : [];

        $loai = isset($f['item_type']) ? $f['item_type'] : PartsModel::LOAI_PHU_TUNG;
        $hopLe = [];
        foreach ($this->__attrModel->getForItemType($loai) as $at){
            $hopLe[(int) $at['id']] = true;
        }

        $loc = [];
        foreach ($map as $attrId => $val){
            if (isset($hopLe[(int) $attrId])) $loc[$attrId] = $val;
        }

        $this->__attrValModel->syncForPart($partId, $loc);
    }

    /** Tìm hàng hoá (JSON) cho ô chọn phụ kiện đi kèm — TASK_81 */
    public function searchJson(){
        $f       = $this->__request->getFields();
        $keyword = isset($f['q']) ? trim($f['q']) : '';
        $exclude = isset($f['exclude']) ? (int) $f['exclude'] : 0;

        $rows = ($keyword === '') ? [] : $this->__model->search($keyword, $exclude, 20);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_map(function($r){
            return ['id' => (int) $r['id'], 'code' => $r['code'], 'name' => $r['name']];
        }, $rows ?: []), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** id hợp lệ (tồn tại) thì giữ, không thì trả null — tránh lỗi khoá ngoại */
    private function validFk($model, $val){
        $id = !empty($val) ? (int) $val : 0;
        if ($id <= 0) return null;
        return !empty($model->getDetail($id)) ? $id : null;
    }

    /** "1.500.000" / "1,500,000" -> 1500000 (giá VND, coi như số nguyên) */
    private function parseMoney($val){
        $digits = preg_replace('/[^\d]/', '', (string) $val);
        return $digits === '' ? 0 : (float) $digits;
    }

    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}
