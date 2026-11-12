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
        /* Ô SỬA NẰM NGAY BÊN PHẢI CÂY MENU (12/11/2026, anh muốn giống màn
           của bên khách). Nên màn danh sách phải nạp sẵn mọi thứ form cần:
           danh sách menu gốc để chọn cha, và ba danh sách đích. Menu của một
           gara chỉ vài chục dòng — nạp hết một lần rẻ hơn mỗi lần bấm một
           mục lại gọi thêm một nhịp mạng. */
        $this->__data['content']['roots']     = $this->__model->getRoots();
        foreach ($this->dsDich() as $k => $v) $this->__data['content'][$k] = $v;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['msgError']  = Session::flash('msgError');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    /**
     * LƯU LẠI THỨ TỰ + CẤP SAU KHI KÉO THẢ.
     *
     * Nhận một mảng JSON [{id, parent_id, sort_order}, ...] — cả cây một lần,
     * không phải từng mục một: kéo một mục lên đầu là đổi thứ tự của mọi mục
     * dưới nó, gửi lẻ thì giữa chừng mất mạng là cây còn lại nửa cũ nửa mới.
     *
     * KIỂM LẠI TỪNG DÒNG Ở ĐÂY, không tin mảng gửi lên:
     *   - `id` phải là mục CÓ THẬT và thuộc gara đang đăng nhập. getDetail()
     *     của model có $_theoGara nên dòng của gara khác trả về rỗng -> bỏ qua.
     *     Thiếu bước này thì một gara gửi tay id của gara khác là xếp lại menu
     *     trên website của người ta.
     *   - cha phải là MỘT MỤC GỐC và khác chính nó. Menu website chỉ hai cấp
     *     (xem buildData()); thả một mục vào mục con thì hạ về gốc chứ không
     *     tạo cấp ba — cấp ba sẽ không hiện ra ở đâu trên trang khách cả.
     */
    public function postSapXep(){

        $f    = $this->__request->getFields();
        $json = isset($f['thuTu']) ? (string) $f['thuTu'] : '';
        $ds   = json_decode($json, true);

        if (!is_array($ds)){
            Session::flash('msgError', 'Không đọc được thứ tự mới');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        /* Mục nào đang là gốc — tra một lần, dùng cho mọi dòng. */
        $goc = [];
        foreach ($this->__model->getRoots() as $r) $goc[(int) $r['id']] = true;

        $soDoi = 0;
        foreach ($ds as $d){
            $id = isset($d['id']) ? (int) $d['id'] : 0;
            if ($id <= 0 || empty($this->__model->getDetail($id))) continue;

            $cha = isset($d['parent_id']) ? (int) $d['parent_id'] : 0;
            if ($cha === $id || empty($goc[$cha])) $cha = 0;

            $this->__model->edit([
                'parent_id'  => $cha > 0 ? $cha : null,
                'sort_order' => isset($d['sort_order']) ? (int) $d['sort_order'] : 0,
            ], $id);
            $soDoi++;
        }

        Session::flash('msg', 'Đã lưu thứ tự menu (' . $soDoi . ' mục)');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name'] = 'Thêm ' . $this->labelOne;
        $this->__data['content']['roots']     = $this->__model->getRoots();
        foreach ($this->dsDich() as $k => $v) $this->__data['content'][$k] = $v;
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
        foreach ($this->dsDich() as $k => $v) $this->__data['content'][$k] = $v;
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

    /**
     * Ba danh sách để chọn đích — tài liệu khách 05/11/2026, Frontend mục 6.
     *
     * Nạp cả ba cho mọi lần mở form, kể cả khi người dùng đang chọn kiểu
     * "đường dẫn tự gõ": ô chọn đổi theo kiểu bằng JS ngay trên trình duyệt,
     * nạp sau theo từng kiểu thì phải thêm một đường API và một nhịp chờ cho
     * ba danh sách mà gara nào cũng chỉ có vài chục dòng.
     */
    private function dsDich(){
        return [
            'dsTin'      => $this->model('NewsCategoriesModel')->getLists(),
            'dsSanPham'  => $this->model('PartCategoriesModel')->getLists(),
            'dsThuVien'  => $this->model('GalleriesModel')->getLists(),
            'kieuDs'     => MenusModel::$kieuLienKet,
        ];
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
        /* KIỂU LIÊN KẾT. Kiểu lạ -> về 'url' (xem MenusModel::kieuHopLe) chứ
           không từ chối: giá trị lạ chỉ tới được từ POST gửi tay, mà lúc đó
           từ chối hay nhận cũng không ai đọc câu lỗi. */
        $kieu = MenusModel::kieuHopLe(isset($f['link_type']) ? $f['link_type'] : '');

        /* ĐÍCH PHẢI CÓ THẬT. Không kiểm thì id gõ bừa vẫn lưu được, và mục
           menu đó im lặng trỏ về trang chủ — không màn nào nói vì sao.
           Đích không hợp lệ thì hạ về kiểu 'url': ít nhất người quản lý mở ra
           sẽ thấy ô đường dẫn trống và hiểu là chưa chọn được gì. */
        $dichId = null;
        if ($kieu !== MenusModel::KIEU_URL){
            $id = !empty($f['target_id']) ? (int) $f['target_id'] : 0;
            if ($id > 0 && !empty($this->__model->dich($kieu, $id))){
                $dichId = $id;
            } else {
                $kieu = MenusModel::KIEU_URL;
            }
        }

        return [
            'parent_id'  => $parent,
            'label'      => trim($f['label']),
            'link_type'  => $kieu,
            'target_id'  => $dichId,
            /* Kiểu khác 'url' thì XOÁ cột url. Giữ lại là có hai nguồn sự thật:
               đổi đích xong mà cột url cũ còn nằm đó, lần sau ai sửa code đọc
               nhầm cột là menu trỏ về chỗ của tháng trước. */
            'url'        => $kieu === MenusModel::KIEU_URL
                            ? (isset($f['url']) ? trim($f['url']) : '') : '',
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
