<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * STOREFRONT — TẢI VỀ (tài liệu khách 05/11/2026).
 *
 * "Phần download, Upload có link trực tiếp và link gián tiếp" + Frontend mục
 * 12 "Đăng ký thành viên để download báo giá, khuyến mại".
 *
 * HAI ĐƯỜNG VÀO CÙNG MỘT TỆP:
 *
 *   /tai-ve/<slug>            GIÁN TIẾP — link dán lên website.
 *                             Có kiểm đăng nhập, có đếm lượt.
 *   /tai-ve/<slug>?k=<token>  TRỰC TIẾP — link gửi riêng qua email.
 *                             Tải thẳng, không kiểm, không đếm.
 *
 * Tệp nằm trong storage/ nên web server không phục vụ được — mọi lượt tải
 * buộc phải qua đây. Đó chính là thứ làm cho hai dòng trên có nghĩa.
 */
class Taive extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    function __construct(){
        $this->__model    = $this->model('DownloadsModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    /** Danh sách tệp tải về của gara đang mở */
    public function index(){
        $this->__data['sub_content'] = 'storefront/download_list';
        $this->__data['page_title']  = 'Tải về';
        $this->__data['content']['list']   = $this->__model->getPublished();
        $this->__data['content']['daDangNhap'] = !empty(Session::get('dataMember'));
        $this->__data['content']['msgError']   = Session::flash('msgError');
        $this->render('layouts/storefront/master', $this->__data);
    }

    /** Tải một tệp */
    public function file($slug = ''){
        $item = $this->__model->theoSlug($slug);

        if (empty($item) || (int) $item['is_published'] !== 1){
            $this->khongThay(); return;
        }

        $f     = $this->__request->getFields();
        $token = isset($f['k']) ? (string) $f['k'] : '';

        /* So sánh token bằng hash_equals, không bằng ===.
           Hai chuỗi khác nhau ở ký tự đầu thì === trả lời nhanh hơn khi khác ở
           ký tự cuối. Chênh lệch đó đo được qua mạng và đủ để dò dần ra token.
           hash_equals luôn chạy hết chuỗi. */
        $laTrucTiep = $token !== '' && !empty($item['token'])
                      && hash_equals((string) $item['token'], $token);

        /* Link gián tiếp mới phải qua cửa đăng nhập. Link trực tiếp là thứ
           gara CHỦ ĐỘNG gửi riêng cho ai đó, nên không bắt người ta lập tài
           khoản mới tải được. */
        if (!$laTrucTiep && (int) $item['require_login'] === 1 && empty(Session::get('dataMember'))){
            Session::flash('msgError',
                'Tệp "' . $item['title'] . '" chỉ dành cho thành viên. '
                . 'Mời bạn đăng nhập hoặc đăng ký tài khoản để tải.');
            $this->__response->redirect('thanh-vien/dang-nhap'); return;
        }

        $dia = DownloadsModel::duongDanDia($item['file_path']);
        if ($dia === '' || !is_file($dia)){
            /* Bản ghi còn mà tệp đã mất — báo đúng sự thật thay vì "không tìm
               thấy trang", để người trực web biết mà đi tải lại tệp. */
            $this->khongThay('Tệp này hiện không tải được. Mời bạn liên hệ với chúng tôi.'); return;
        }

        // Chỉ link gián tiếp mới tính vào lượt tải
        if (!$laTrucTiep) $this->__model->themLuot($item['id']);

        $this->day($dia, $item['file_name']);
    }

    // ------------------------------------------------------------------

    /**
     * Đẩy tệp về trình duyệt.
     *
     * Dọn sạch mọi bộ đệm đầu ra trước khi gửi: chỉ cần một dòng trắng lọt ra
     * trước header là tệp tải về hỏng — và hỏng theo kiểu chỉ phát hiện được
     * khi mở tệp, không có lỗi nào trên màn hình.
     */
    private function day($dia, $tenTai){
        $ten = trim((string) $tenTai);
        if ($ten === '') $ten = basename($dia);
        /* Bỏ ký tự điều khiển, dấu nháy và dấu gạch chéo: tên này đi thẳng vào
           header, chèn được xuống dòng là chèn được header giả. */
        $ten = preg_replace('/[\x00-\x1F"\\\\\/]+/', '', $ten);
        if ($ten === '' || $ten === null) $ten = 'tai-ve';

        while (ob_get_level() > 0) @ob_end_clean();

        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($dia));
        header('Content-Disposition: attachment; filename="' . $ten . '"; '
               . "filename*=UTF-8''" . rawurlencode($ten));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($dia);
        exit;
    }

    private function khongThay($loi = ''){
        $this->__data['sub_content'] = 'storefront/notfound';
        $this->__data['page_title']  = $loi !== '' ? 'Không tải được tệp' : 'Không tìm thấy';
        $this->__data['content']     = ['loi' => $loi];
        $this->render('layouts/storefront/master', $this->__data);
    }
}
