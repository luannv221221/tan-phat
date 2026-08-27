<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * QUẢN LÝ MODULE — đăng ký màn hình admin để phân quyền được.
 *
 * Trước đây bảng `modules` chỉ nạp được bằng migration, nên muốn đưa một màn
 * hình vào bảng phân quyền là phải sửa mã nguồn rồi chạy lại migration. Màn
 * hình này làm đúng việc đó bằng giao diện.
 *
 * ĐĂNG KÝ CHỨ KHÔNG PHẢI TẠO. Thêm một dòng ở đây KHÔNG sinh ra màn hình mới;
 * màn hình phải có sẵn trong mã nguồn. Vì vậy ô "Màn hình" là DANH SÁCH CHỌN
 * lấy từ các thư mục view có thật, không phải ô gõ tự do: gõ sai một chữ thì
 * RoleMiddleware không khớp được URL, mà không khớp thì nó bỏ qua luôn phần
 * kiểm quyền — ai đăng nhập cũng vào được màn hình đó.
 */
class Modules extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'modules';
    private $labelOne  = 'module';
    private $labelMany = 'Quản lý module';
    private $viewDir   = 'admin/modules';

    function __construct(){
        $this->__model    = $this->model('ModulesModel');
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
        $this->__data['content']['page_name']   = $this->labelMany;
        $this->__data['content']['dataList']    = $this->__model->getListsKemQuyen();
        $this->__data['content']['chuaDangKy']  = $this->__model->manHinhChuaDangKy();
        $this->__data['content']['moCoi']       = $this->__model->moduleMoCoi();
        $this->__data['content']['msg']         = Session::flash('msg');
        $this->__data['content']['msgError']    = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name']  = 'Thêm ' . $this->labelOne;
        $this->__data['content']['item']       = null;
        $this->__data['content']['chuaDangKy'] = $this->__model->manHinhChuaDangKy();
        $this->__data['content']['msg']        = Session::flash('msg');
        $this->__data['content']['errors']     = Session::flash('errors');
        $this->__data['content']['old']        = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $f    = $this->__request->getFields();
        $name = isset($f['name']) ? trim($f['name']) : '';
        $link = $this->chuanHoaLink($f);

        $loi = $this->kiemTra($name, $link, 0);
        if (!empty($loi)){ $this->flashErrors($loi, 'add'); return; }

        $this->__model->add(['name' => $name, 'link' => $link]);
        Session::flash('msg', 'Đã đăng ký module "' . $name . '". Vào Nhóm > Phân quyền để cấp quyền cho từng nhóm.');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function edit($id = 0){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name']  = 'Sửa ' . $this->labelOne;
        $this->__data['content']['item']       = $item;
        // Cho chọn lại màn hình: các màn chưa ai giữ, cộng thêm màn hiện tại.
        $ds = $this->__model->manHinhChuaDangKy();
        if (!empty($item['link']) && !in_array($item['link'], $ds, true)) $ds[] = $item['link'];
        sort($ds);
        $this->__data['content']['chuaDangKy'] = $ds;
        $this->__data['content']['msg']        = Session::flash('msg');
        $this->__data['content']['errors']     = Session::flash('errors');
        $this->__data['content']['old']        = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $f    = $this->__request->getFields();
        $name = isset($f['name']) ? trim($f['name']) : '';
        $link = $this->chuanHoaLink($f);

        $loi = $this->kiemTra($name, $link, (int) $id);
        if (!empty($loi)){ $this->flashErrors($loi, 'edit/' . $id); return; }

        $this->__model->edit(['name' => $name, 'link' => $link], $id);
        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function delete($id = 0){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        // Không cho tự xoá chính màn hình này: xoá xong là mất luôn đường vào
        // để đăng ký lại, phải sửa thẳng CSDL mới cứu được.
        if ($item['link'] === $this->routeBase){
            Session::flash('msgError', 'Không thể gỡ chính module "Quản lý module" — gỡ xong sẽ không còn đường vào để đăng ký lại.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $this->__model->remove($id);
        Session::flash('msg', 'Đã gỡ module "' . $item['name'] . '" và toàn bộ phân quyền của nó. '
                            . 'Màn hình /admin/' . $item['link'] . ' giờ KHÔNG còn được kiểm quyền.');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Lấy link từ form: ưu tiên ô chọn, chỉ dùng ô gõ tay khi chọn "— Khác —".
     *
     * Ô gõ tay để dành cho màn hình sắp làm nhưng chưa có thư mục view. Không
     * bỏ hẳn nó vì như vậy là chặn mất trường hợp đó, nhưng cũng không để nó
     * là đường mặc định.
     */
    private function chuanHoaLink(array $f){
        $chon = isset($f['link']) ? trim($f['link']) : '';
        if ($chon !== '' && $chon !== '__khac__') return strtolower($chon);

        $tay = isset($f['link_tay']) ? trim($f['link_tay']) : '';
        return strtolower(trim($tay, '/'));
    }

    private function kiemTra($name, $link, $boQuaId){
        $loi = [];

        if ($name === '') $loi['name'] = 'Tên module không được để trống';
        if ($link === ''){
            $loi['link'] = 'Phải chọn màn hình (hoặc nhập đường dẫn nếu chọn "Khác")';
        } elseif (!preg_match('~^[a-z0-9][a-z0-9-]*$~', $link)){
            // RoleMiddleware ghép thẳng vào 'admin/'.$link.'/*'. Cho ký tự lạ
            // vào đây là hỏng cả biểu thức so khớp.
            $loi['link'] = 'Đường dẫn chỉ gồm chữ thường, số và dấu gạch ngang (vd: goods-receipts)';
        } else {
            $trung = $this->__model->findByLink($link);
            if (!empty($trung) && (int) $trung['id'] !== (int) $boQuaId){
                $loi['link'] = 'Đường dẫn "' . $link . '" đã được module "' . $trung['name'] . '" dùng rồi';
            }
        }

        return $loi;
    }

    private function flashErrors(array $loi, $back){
        Session::flash('errors', $loi);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}
