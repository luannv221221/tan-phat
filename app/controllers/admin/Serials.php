<?php

use App\core\Controller;
use App\core\Request;
use App\core\Session;

/**
 * QUẢN LÝ SERIAL — tài liệu khách gửi 29/10/2026.
 *
 * "Có tra cứu serial để biết hàng này mua từ ai, theo đơn nào, ngày mua phụ
 * tùng… mục đích để bảo hành phụ tùng. Các thông tin serial được lấy từ đơn
 * mua."
 *
 * MÀN NÀY CHỈ ĐỌC. Serial khai ở màn Đơn mua, không sửa ở đây: sửa serial rời
 * khỏi đơn là mất đường lần về nguồn mua, mà đó đúng là thứ duy nhất màn này
 * có ích. Cần sửa thì mở đơn mua ra sửa.
 *
 * KHÔNG CÓ ROUTE GHI NÀO — xem routes/web.php. Màn chỉ đọc mà có đường ghi là
 * có đường sửa dữ liệu ngoài chứng từ.
 */
class Serials extends Controller {

    private $__data = [];
    private $__model, $__request;

    private $routeBase = 'serials';
    private $labelMany = 'Quản lý serial';
    private $viewDir   = 'admin/serials';

    function __construct(){
        $this->__model   = $this->model('PurchaseSerialsModel');
        $this->__request = new Request();
    }

    public function index(){
        $f  = $this->__request->getFields();
        $tu = isset($f['q']) ? trim((string) $f['q']) : '';

        $this->__data['sub_content'] = $this->viewDir . '/index';
        $this->__data['page_title']  = $this->labelMany;

        $c = &$this->__data['content'];
        $c['routeBase'] = $this->routeBase;
        $c['page_name'] = $this->labelMany;
        $c['q']         = $tu;
        $c['msg']       = Session::flash('msg');
        $c['msgError']  = Session::flash('msgError');

        /* Chưa gõ gì thì hiện danh sách mới nhất — mở màn ra thấy trống trơn
           thì người dùng không biết có dữ liệu hay không. */
        $c['dataList'] = $tu !== '' ? $this->__model->traCuu($tu) : $this->__model->moiNhat();
        $c['daTra']    = $tu !== '';

        $this->render('layouts/admin/master_admin', $this->__data);
    }
}
