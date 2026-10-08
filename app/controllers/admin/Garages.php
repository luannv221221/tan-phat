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
 * GARA KHÔNG PHẢI LÀ KHO. Một gara có thể có nhiều kho.
 *
 * GARA ĐỘC LẬP (22/09/2026): mỗi gara là một doanh nghiệp riêng, không thấy
 * dữ liệu của nhau. Màn này là của riêng Tân Phát (cờ `chi_tan_phat`): Tân
 * Phát thấy danh sách gara, không thấy dữ liệu bên trong gara.
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

        /* TÊN MIỀN CHÍNH của từng gara. Hiện ngay trên danh sách vì "gara này
           mở ở địa chỉ nào" là câu hỏi hay phải trả lời nhất khi có sự cố, và
           gara KHÔNG có tên miền nào thì website của nó không ai vào được — một
           cột trống ở đây là thứ duy nhất nói ra điều đó. */
        $D = $this->model('GarageDomainsModel');
        $c['tenMien'] = [];
        foreach ($c['dataList'] as $g){
            $c['tenMien'][(int) $g['id']] = $D->theoGara((int) $g['id']);
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
        $loi = '';
        $logo = $this->logoTaiLen($loi);
        if ($logo === null){ $this->flashOne('logo_file', $loi, 'add'); return; }
        if ($logo !== '') $data['logo'] = $logo;

        $id = $this->__model->add($data);
        if (!empty($data['is_master'])) $this->__model->clearMasterExcept($id);

        /* DỰNG BỘ KHUNG — tên miền, kho, nhóm khách, cấu hình web, tài khoản chủ.
           Một dòng trong bảng `garages` thôi thì gara chưa làm được gì: chưa có
           website, chưa nhập hàng được, chưa ai đăng nhập được. Trước đây phải
           đi năm màn khác nhau để khai đủ, quên một thứ thì tới lúc dùng mới
           biết. Gara tổng thì bỏ qua — nó đã có sẵn mọi thứ từ lâu. */
        $them = '';
        if (empty($data['is_master'])){
            $f  = $this->__request->getFields();
            $kq = $this->model('MoGaraModel')->dungBoKhung($id, [
                'name'     => isset($f['chu_name']) ? $f['chu_name'] : '',
                'email'    => isset($f['chu_email']) ? $f['chu_email'] : '',
                'password' => isset($f['chu_password']) ? $f['chu_password'] : '',
            ]);
            if (!empty($kq['da']))    $them .= ' Đã dựng sẵn: ' . implode(', ', $kq['da']) . '.';
            /* Việc phụ không dựng được KHÔNG làm hỏng việc tạo gara — nói ra để
               người dùng biết mà làm nốt, chứ đừng im lặng. */
            if (!empty($kq['thieu'])) $them .= ' CÒN THIẾU: ' . implode(' ', $kq['thieu']);
        }

        Session::flash('msg', 'Thêm ' . $this->labelOne . ' thành công.' . $them);
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
        // Chỉ ghi đè logo khi THỰC SỰ có ảnh mới — sửa tên mà xoá mất logo là sai
        $loi = '';
        $logo = $this->logoTaiLen($loi);
        if ($logo === null){ $this->flashOne('logo_file', $loi, 'edit/' . $id); return; }
        if ($logo !== '') $data['logo'] = $logo;

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
              . '. Gara đã có dữ liệu thì chỉ khoá được (tắt trạng thái hoạt động), không xoá.');
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    /**
     * Khoá / mở khoá gara. Gara đã có dữ liệu thì chỉ khoá được, không xoá:
     * khoá là nhân viên gara đó không đăng nhập được nữa (AuthMiddleware), khách
     * / xe / chứng từ còn nguyên. Không khoá được gara tổng — khoá là Tân Phát tự
     * khoá mình ra ngoài.
     */
    public function toggle($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . (int) $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        if ((int) $item['is_master'] === 1){
            Session::flash('msgError', 'Không khoá được gara tổng.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $moi = (int) $item['status'] === 1 ? 0 : 1;
        $this->__model->edit(['status' => $moi], (int) $id);
        Session::flash('msg', $moi === 1 ? 'Đã mở khoá gara ' . $item['name']
                                         : 'Đã khoá gara ' . $item['name'] . ' — nhân viên gara không đăng nhập được nữa.');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Tên miền của gara =====

    /**
     * TÊN MIỀN CỦA MỘT GARA — nơi quyết định "địa chỉ nào phục vụ gara nào".
     *
     * Trước 08/10/2026 không có màn nào cả: phải gõ SQL tay vào CSDL thật. Mở
     * một gara mới là ba câu INSERT / UPDATE / DELETE gõ đúng thứ tự, và một
     * lần đã suýt hỏng vì đảo thứ tự (DELETE trước UPDATE nên UPDATE không khớp
     * dòng nào, gara mở ra không có tên miền).
     *
     * Màn này của RIÊNG TÂN PHÁT (module `garages` mang cờ `chi_tan_phat`): gara
     * tự khai được host là nó tự nhận request của gara khác.
     */
    public function tenMien($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $D = $this->model('GarageDomainsModel');

        $this->__data['sub_content'] = $this->viewDir . '/ten-mien';
        $this->__data['page_title']  = 'Tên miền của ' . $item['name'];

        $this->baseData();
        $c = &$this->__data['content'];
        $c['page_name'] = 'Tên miền của ' . $item['name'];
        $c['item']      = $item;
        $c['dsTenMien'] = $D->theoGara((int) $id);

        /* TÊN MIỀN GỐC + địa chỉ mà gara mở sau sẽ nhận. Bày ra ở đây vì chính
           chỗ này là nơi người ta đổi tên miền chính của gara tổng, mà việc đó
           đổi luôn tên miền của mọi gara mở sau — không nói thì không ai đoán ra. */
        $goc = $this->model('MoGaraModel')->tenMienGoc();
        $c['tenMienGoc'] = $goc;
        $c['hostGoiY']   = $goc !== '' ? slugify((string) $item['code']) . '.' . $goc : '';

        $c['msg']       = Session::flash('msg');
        $c['msgError']  = Session::flash('msgError');
        $c['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postTenMien($id){
        $item = $this->chanNeuKhongSuaDuoc($id);
        if (empty($item)) return;

        $f = $this->__request->getFields();

        /* Lấy HOST ĐÃ CHUẨN HOÁ từ model, đừng tự chuẩn hoá lại ở đây:
           chuanHoaHost() không cắt giao thức, nên "https://a.etek.vn" ra
           "https" — câu thông báo đọc sai host, và la_host_noi_bo("https") trả
           về true nên nó còn báo thêm "đây là địa chỉ máy nội bộ". */
        list($h, $loi) = $this->model('GarageDomainsModel')->them(
            (int) $id,
            isset($f['host']) ? $f['host'] : '',
            !empty($f['is_primary'])
        );

        if ($loi !== ''){
            Session::flash('msgError', $loi);
            Session::flash('old', $f);
        } else {
            Session::flash('msg', 'Đã khai tên miền ' . $h . ' cho ' . $item['name'] . '.'
                . (la_host_noi_bo($h)
                    ? ' Lưu ý: đây là địa chỉ của máy nội bộ, chỉ dùng để chạy thử — trên máy chủ thật nó vô nghĩa.'
                    : ' Còn hai việc ngoài hệ thống: trỏ DNS và thêm ServerAlias cho Apache.'));
        }
        $this->__response->redirect('admin/' . $this->routeBase . '/ten-mien/' . (int) $id);
    }

    public function tenMienChinh($domainId){
        $D = $this->model('GarageDomainsModel');
        $d = $D->theoId($domainId);
        if (empty($d)){
            Session::flash('msgError', 'Không tìm thấy tên miền.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (empty($this->chanNeuKhongSuaDuoc((int) $d['garage_id']))) return;

        $loi = $D->datLamChinh((int) $domainId);
        if ($loi !== '') Session::flash('msgError', $loi);
        else             Session::flash('msg', 'Đã đặt ' . $d['host'] . ' làm tên miền chính của '
                                             . $d['garage_name'] . '.');
        $this->__response->redirect('admin/' . $this->routeBase . '/ten-mien/' . (int) $d['garage_id']);
    }

    public function tenMienToggle($domainId){
        $D = $this->model('GarageDomainsModel');
        $d = $D->theoId($domainId);
        if (empty($d)){
            Session::flash('msgError', 'Không tìm thấy tên miền.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (empty($this->chanNeuKhongSuaDuoc((int) $d['garage_id']))) return;

        list($moi, $loi) = $D->doiTrangThai((int) $domainId);
        if ($loi !== '') Session::flash('msgError', $loi);
        else             Session::flash('msg', $moi === 1
                            ? 'Đã bật tên miền ' . $d['host'] . '.'
                            : 'Đã tắt tên miền ' . $d['host'] . ' — địa chỉ này không vào được nữa.');
        $this->__response->redirect('admin/' . $this->routeBase . '/ten-mien/' . (int) $d['garage_id']);
    }

    public function tenMienXoa($domainId){
        $D = $this->model('GarageDomainsModel');
        $d = $D->theoId($domainId);
        if (empty($d)){
            Session::flash('msgError', 'Không tìm thấy tên miền.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (empty($this->chanNeuKhongSuaDuoc((int) $d['garage_id']))) return;

        $loi = $D->xoa((int) $domainId);
        if ($loi !== '') Session::flash('msgError', $loi);
        else             Session::flash('msg', 'Đã xoá tên miền ' . $d['host'] . '.');
        $this->__response->redirect('admin/' . $this->routeBase . '/ten-mien/' . (int) $d['garage_id']);
    }

    /**
     * Gara $id, hoặc null KÈM redirect khi không tìm thấy / không có quyền sửa.
     *
     * Khai tên miền là SỬA gara, nên dùng lại đúng quyền `edit` của màn này —
     * không dựng một luật phân quyền thứ hai cho mấy đường này (route chúng là
     * `garages/ten-mien/...` nên RoleMiddleware chỉ kiểm được quyền `view`).
     */
    private function chanNeuKhongSuaDuoc($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return null;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . (int) $id)){
            $this->__response->redirect('admin/khong-co-quyen');
            return null;
        }
        return $item;
    }

    /**
     * Logo tải lên (nếu có). Trả đường dẫn, '' khi không chọn ảnh, hoặc null khi
     * ảnh lỗi (đã báo lỗi vào $loi).
     */
    private function logoTaiLen(&$loi){
        if (empty($_FILES['logo_file']) || (int) $_FILES['logo_file']['error'] === UPLOAD_ERR_NO_FILE) return '';
        $up = upload_image('logo_file', 'garages', 'logo');
        if ($up['status'] === 'ok') return $up['path'];
        $loi = 'Ảnh logo lỗi: ' . $up['message'];
        return null;
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
            // In lên đầu phiếu của gara (cau_hinh_in_an)
            'tax_code'   => !empty($f['tax_code']) ? trim($f['tax_code']) : null,
            'email'      => !empty($f['email']) ? trim($f['email']) : null,
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
