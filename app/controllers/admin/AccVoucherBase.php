<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * KẾ TOÁN — LỚP NỀN CHO PHIẾU THU VÀ PHIẾU CHI.
 *
 * Hai màn hình giống nhau tới từng ô: cùng đối tượng (khách / NCC / nhân viên),
 * cùng cách đánh số, cùng mẫu in, cùng cách vào báo cáo. Khác đúng MỘT hằng số
 * `loai` ('thu' | 'chi') và mấy nhãn chữ.
 *
 * VÌ SAO LẠI DÙNG LỚP NỀN, trong khi dự án đã CỐ Ý bỏ LookupCrudController:
 * mấy màn danh mục bỏ lớp nền vì chúng TRÔI DẦN khỏi nhau — mỗi danh mục thêm
 * một ô riêng, tới lúc lớp nền toàn `if` theo tên lớp con. Phiếu thu và phiếu
 * chi thì không trôi: chúng là cùng một chứng từ, chỉ khác chiều tiền. Chép đôi
 * ba trăm dòng ở đây là đúng kiểu sửa một bên quên bên kia.
 *
 * KHÔNG CÓ ROUTE NÀO TRỎ VÀO FILE NÀY. Router nạp controller theo tên lớp lấy
 * từ route (`admin/phieuthu` -> app/controllers/admin/Phieuthu.php), mà
 * routes/web.php không khai đường nào tới `AccVoucherBase` — lớp con tự
 * `require_once` nó, giống cách LookupModel đang làm.
 *
 * HAI MÀN DÙNG CHUNG MỘT BỘ VIEW (`admin/acc-vouchers`). View ở đây chỉ đọc
 * biến, không có gì khác nhau giữa thu và chi ngoài nhãn chữ — mà nhãn thì
 * truyền xuống được.
 */
abstract class AccVoucherBase extends Controller {

    /** 'thu' | 'chi' — lớp con đặt */
    protected $loai = 'thu';
    protected $routeBase = 'phieu-thu';
    protected $labelOne  = 'phiếu thu';
    protected $labelMany = 'Phiếu thu';

    protected $viewDir = 'admin/acc-vouchers';

    protected $__data = [];
    protected $__model, $__vtype, $__partner, $__user, $__request, $__response;

    function __construct(){
        $this->__model    = $this->model('AccVouchersModel');
        $this->__vtype    = $this->model('AccVoucherTypesModel');
        $this->__partner  = $this->model('PartnersModel');
        $this->__user     = $this->model('UsersModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    protected function baseData(){
        $c = &$this->__data['content'];
        $c['routeBase'] = $this->routeBase;
        $c['labelOne']  = $this->labelOne;
        $c['labelMany'] = $this->labelMany;
        $c['loai']      = $this->loai;
        $c['dsDoiTuong'] = AccVouchersModel::$doiTuong;
        $c['msg']       = Session::flash('msg');
        $c['msgError']  = Session::flash('msgError');
        $c['errors']    = Session::flash('errors');
        $c['old']       = Session::flash('old');
    }

    /** Dữ liệu cho ô chọn trên form */
    protected function formData(){
        $c = &$this->__data['content'];
        /* CHỈ loại phiếu ĐÚNG CHIỀU và đang bật. Bày cả loại chi trên màn thu
           là người dùng chọn nhầm rồi phiếu thu mang định khoản của phiếu chi —
           báo cáo cộng ngược dấu mà nhìn phiếu không thấy gì sai. */
        $c['dsLoaiPhieu'] = $this->__vtype->getLists($this->loai, true);
        $c['dsPartner']   = $this->__partner->getActive();
        $c['dsNhanVien']  = $this->nhanVienCuaGara();
    }

    /**
     * Nhân viên của GARA ĐANG LÀM VIỆC.
     *
     * `users` cố ý KHÔNG bật $_theoGara (đăng nhập phải tìm khắp các gara), nên
     * phải tự lọc ở đây. Không lọc thì ô chọn người nhận lương bày ra nhân viên
     * của mọi gara khác — vừa lộ danh sách người, vừa chi nhầm.
     */
    protected function nhanVienCuaGara(){
        $gid = gara_hien_tai_id();
        if (empty($gid)) return [];
        return (array) $this->__user->getLists(['users.garage_id' => (int) $gid, 'users.status' => 1]);
    }

    // ================= Danh sách =================

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();
        $f = $this->__request->getFields();

        /* Mặc định xem THÁNG NÀY. Không đặt mặc định thì màn mở ra đổ hết phiếu
           từ ngày khai trương, và con số tổng ở chân bảng vô nghĩa. */
        $loc = [
            'tu'       => !empty($f['tu'])  ? $f['tu']  : date('Y-m-01'),
            'den'      => !empty($f['den']) ? $f['den'] : date('Y-m-t'),
            'vtype_id' => !empty($f['vtype_id']) ? (int) $f['vtype_id'] : 0,
            'keyword'  => isset($f['keyword']) ? trim($f['keyword']) : '',
            'status'   => isset($f['status']) && $f['status'] !== '' ? (int) $f['status'] : '',
        ];

        $ds = $this->__model->getLists($this->loai, $loc);

        $tong = 0.0;
        foreach ($ds as $r) if ((int) $r['status'] === 1) $tong += (float) $r['amount'];

        $c = &$this->__data['content'];
        $c['page_name']   = $this->labelMany;
        $c['dataList']    = $ds;
        $c['loc']         = $loc;
        $c['tong']        = $tong;
        $c['dsLoaiPhieu'] = $this->__vtype->getLists($this->loai);

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    // ================= Thêm =================

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Lập ' . $this->labelOne;

        $this->baseData();
        $this->formData();
        $c = &$this->__data['content'];
        $c['page_name'] = 'Lập ' . $this->labelOne;
        $c['item']      = null;
        $c['soGoiY']    = $this->__model->nextNo($this->loai);

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $data = $this->buildData(null);
        $loi  = $this->kiemTra($data, null);
        if (!empty($loi)){ $this->flash($loi, 'add'); return; }

        $id = $this->__model->add($data);
        Session::flash('msg', 'Đã lập ' . $this->labelOne . ' ' . $data['voucher_no'] . '.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    // ================= Sửa =================

    public function edit($id){
        $item = $this->layPhieu($id);
        if (empty($item)) return;

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;

        $this->baseData();
        $this->formData();
        $c = &$this->__data['content'];
        $c['page_name'] = 'Sửa ' . $this->labelOne . ' ' . $item['voucher_no'];
        $c['item']      = $item;
        $c['soGoiY']    = $item['voucher_no'];

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->layPhieu($id);
        if (empty($item)) return;

        /* PHIẾU ĐÃ HUỶ THÌ KHÔNG SỬA. Sửa một phiếu đã huỷ rồi bỏ huỷ là đường
           vòng để thay nội dung một chứng từ tiền mà không để lại dấu vết nào. */
        if ((int) $item['status'] !== 1){
            Session::flash('msgError', 'Phiếu đã huỷ thì không sửa được. Bỏ huỷ trước nếu cần sửa.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id); return;
        }

        $data = $this->buildData((int) $id);
        $loi  = $this->kiemTra($data, (int) $id);
        if (!empty($loi)){ $this->flash($loi, 'edit/' . (int) $id); return; }

        $this->__model->edit($data, $id);
        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    // ================= Huỷ / bỏ huỷ =================

    /**
     * KHÔNG CÓ XOÁ, chỉ huỷ. Chứng từ tiền mà biến mất thì báo cáo hôm qua khác
     * hôm nay và không ai giải thích được. Phiếu huỷ vẫn nằm đó, nhìn thấy
     * được, và mọi báo cáo bỏ qua nó.
     */
    public function huy($id){
        $item = $this->layPhieu($id);
        if (empty($item)) return;

        if (!route('admin/' . $this->routeBase . '/edit/' . (int) $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }

        $moi = (int) $item['status'] === 1 ? 0 : 1;
        $this->__model->doiTrangThai($id, $moi);
        Session::flash('msg', $moi === 1
            ? 'Đã bỏ huỷ ' . $item['voucher_no'] . ' — phiếu tính lại vào báo cáo.'
            : 'Đã huỷ ' . $item['voucher_no'] . ' — phiếu không còn tính vào báo cáo.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    // ================= In =================

    public function in($id){
        $item = $this->layPhieu($id);
        if (empty($item)) return;

        $settings = cau_hinh_in_an(!empty($item['garage_id']) ? (int) $item['garage_id'] : null);
        $laWord   = !empty($this->__request->getFields()['word']);
        if ($laWord) header_word(($this->loai === 'thu' ? 'phieu-thu-' : 'phieu-chi-') . $item['voucher_no']);

        $urlWord = _WEB_URL . '/admin/' . $this->routeBase . '/in/' . (int) $item['id'] . '?word=1';

        /* require thẳng view in: nó là một trang HTML trọn vẹn (có <html>,
           <head>), không phải mảnh ghép vào layout admin — giống hệt cách
           in_chung_tu() đang làm cho báo giá / hoá đơn. */
        $loaiChu = $this->loai === 'thu' ? 'PHIẾU THU' : 'PHIẾU CHI';
        require 'app/views/admin/print/phieu-thu-chi.php';
        exit;
    }

    // ================= Helper =================

    /** Phiếu $id của gara làm việc, ĐÚNG CHIỀU thu/chi — hoặc null kèm redirect */
    protected function layPhieu($id){
        $item = $this->__model->getDetail($id);

        /* Kiểm cả `loai`: thiếu nó thì /admin/phieu-thu/edit/<id một phiếu chi>
           mở ra được, và sửa bằng form của màn thu — người dùng chỉ được cấp
           màn Phiếu thu vẫn đụng được phiếu chi. */
        if (empty($item) || $item['loai'] !== $this->loai){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return null;
        }
        return $item;
    }

    protected function buildData($id){
        $f = $this->__request->getFields();

        $dtLoai = isset($f['dt_loai']) && isset(AccVouchersModel::$doiTuong[$f['dt_loai']])
                ? $f['dt_loai'] : 'partner';

        $partnerId = null; $userId = null;
        $ten  = isset($f['dt_ten']) ? trim($f['dt_ten']) : '';
        $diaChi = isset($f['dt_diachi']) ? trim($f['dt_diachi']) : '';

        if ($dtLoai === 'partner'){
            $p = !empty($f['partner_id']) ? $this->__partner->getDetail((int) $f['partner_id']) : null;
            if (!empty($p)){
                $partnerId = (int) $p['id'];
                /* BẢN CHỤP lúc lập. Ô tên để trống thì lấy tên trong danh mục;
                   người dùng gõ đè thì giữ nguyên chữ họ gõ — phiếu in ra phải
                   đúng tên người thật sự nộp tiền, có khi khác tên công ty. */
                if ($ten === '')    $ten    = (string) $p['name'];
                if ($diaChi === '') $diaChi = (string) $p['address'];
            }
        } elseif ($dtLoai === 'user'){
            $u = !empty($f['user_id']) ? $this->nhanVienTheoId((int) $f['user_id']) : null;
            if (!empty($u)){
                $userId = (int) $u['id'];
                if ($ten === '') $ten = (string) $u['name'];
            }
        }

        /* Cặp tài khoản CHÉP TỪ LOẠI PHIẾU ngay lúc lập. Sửa định khoản của
           loại phiếu sang năm sau không được làm đổi phiếu đã in và đã ký. */
        $vt = !empty($f['vtype_id']) ? $this->__vtype->chiTietKemTk((int) $f['vtype_id']) : null;

        $data = [
            'voucher_no'   => isset($f['voucher_no']) ? trim($f['voucher_no']) : '',
            'loai'         => $this->loai,
            'vtype_id'     => !empty($vt['id']) ? (int) $vt['id'] : null,
            'voucher_date' => !empty($f['voucher_date']) ? $f['voucher_date'] : date('Y-m-d'),
            'dt_loai'      => $dtLoai,
            'partner_id'   => $partnerId,
            'user_id'      => $userId,
            'dt_ten'       => $ten !== '' ? mb_substr($ten, 0, 255) : null,
            'dt_diachi'    => $diaChi !== '' ? mb_substr($diaChi, 0, 255) : null,
            'ly_do'        => isset($f['ly_do']) ? mb_substr(trim($f['ly_do']), 0, 255) : null,
            'amount'       => $this->doTien(isset($f['amount']) ? $f['amount'] : 0),
            'debit_code'   => !empty($vt['no_code']) ? $vt['no_code'] : null,
            'credit_code'  => !empty($vt['co_code']) ? $vt['co_code'] : null,
            'kem_theo'     => isset($f['kem_theo']) ? mb_substr(trim($f['kem_theo']), 0, 100) : null,
            'note'         => isset($f['note']) ? mb_substr(trim($f['note']), 0, 255) : null,
        ];

        if ($id === null){
            $data['status']     = 1;
            $data['created_by'] = nguoi_dang_nhap_id();
        }
        return $data;
    }

    /** Nhân viên $id, CHỈ trong gara làm việc */
    protected function nhanVienTheoId($id){
        foreach ($this->nhanVienCuaGara() as $u){
            if ((int) $u['id'] === (int) $id) return $u;
        }
        return null;
    }

    protected function kiemTra($data, $id){
        $loi = [];

        if ($data['voucher_no'] === '') $loi['voucher_no'] = 'Số phiếu không được để trống';
        else {
            $co = $this->__model->findByNo($data['voucher_no']);
            if (!empty($co) && ($id === null || (int) $co['id'] !== $id)){
                $loi['voucher_no'] = 'Số phiếu ' . $data['voucher_no'] . ' đã có.';
            }
        }

        if (empty($data['vtype_id'])) $loi['vtype_id'] = 'Chọn loại phiếu';

        if (empty($data['dt_ten'])){
            $loi['dt_ten'] = $this->loai === 'thu'
                ? 'Nhập hoặc chọn người nộp tiền'
                : 'Nhập hoặc chọn người nhận tiền';
        }

        /* SỐ TIỀN PHẢI DƯƠNG. Phiếu 0 đồng không nói lên điều gì mà vẫn chiếm
           một số phiếu; số âm thì báo cáo cộng ngược dấu. Muốn đảo chiều thì
           lập phiếu bên kia. */
        if ($data['amount'] <= 0) $loi['amount'] = 'Số tiền phải lớn hơn 0';

        if (empty($data['voucher_date'])) $loi['voucher_date'] = 'Chọn ngày lập phiếu';

        /* Loại phiếu thiếu định khoản -> phiếu lập ra mang cặp nợ–có dở dang.
           Chặn ngay, đừng để tới lúc đối chiếu sổ mới lòi ra. */
        if (!empty($data['vtype_id']) && (empty($data['debit_code']) || empty($data['credit_code']))){
            $loi['vtype_id'] = 'Loại phiếu này chưa khai đủ tài khoản nợ và có — '
                             . 'sửa ở Kế toán › Danh mục loại phiếu trước.';
        }
        return $loi;
    }

    /** "1.500.000" / "1,500,000" -> 1500000. Xem Tonkhodauky::doSo cho lý do. */
    protected function doTien($s){
        $d = preg_replace('/[^\d]/', '', (string) $s);
        return $d === '' ? 0.0 : (float) $d;
    }

    protected function flash($loi, $back){
        Session::flash('errors', $loi);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}
