<?php

use App\core\Model;

/**
 * TẢI VỀ — tệp cho khách tải (bảng giá, catalogue, hồ sơ năng lực…).
 * Tài liệu khách 05/11/2026: "Phần download, Upload có link trực tiếp và link
 * gián tiếp", kèm Frontend mục 12 "đăng ký thành viên để download báo giá".
 *
 * HAI LOẠI LINK, khác nhau ở chỗ nào:
 *
 *   GIÁN TIẾP  /tai-ve/<slug>
 *       Link dán lên website. Có kiểm `require_login`, có đếm lượt tải.
 *
 *   TRỰC TIẾP  /tai-ve/<slug>?k=<token>
 *       Link gửi riêng qua email cho khách / đối tác. Tải thẳng, bỏ qua kiểm
 *       tra đăng nhập, không tính vào lượt. Đổi token là thu hồi được link đã
 *       phát mà không phải xoá tệp.
 *
 * Cả hai đều đi qua PHP vì tệp nằm trong storage/ (web server không với tới).
 */
class DownloadsModel extends Model {

    protected $_table   = 'downloads';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /* Nội dung website thuộc về MỘT gara. Thiếu cờ này là gara nào mở cũng
       thấy — và tải được — tệp của gara khác. */
    protected $_theoGara = true;

    public function getLists($status = '', $keyword = ''){
        $q = $this->bangGara()->select('*');
        if ($status === '0' || $status === '1') $q = $q->where('is_published', '=', (int) $status);
        if ($keyword !== '') $q = $q->whereLike('title', '%' . $keyword . '%');
        return $q->orderBy('sort_order', 'ASC')->orderBy('id', 'DESC')->get();
    }

    /** Danh sách cho trang khách — chỉ tệp đã đăng */
    public function getPublished(){
        return $this->bangGara()->select('*')
            ->where('is_published', '=', 1)
            ->orderBy('sort_order', 'ASC')->orderBy('id', 'DESC')->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    /**
     * Tìm theo slug để phục vụ lượt tải.
     *
     * KHÔNG lọc `is_published` ở đây: trang tải về cần phân biệt "không có tệp
     * này" với "tệp có nhưng chưa đăng" để báo cho đúng. Người gọi tự quyết.
     */
    public function theoSlug($slug){
        $slug = trim((string) $slug);
        if ($slug === '') return null;
        $r = $this->bangGara()->where('slug', '=', $slug)->first();
        return empty($r) ? null : $r;
    }

    /**
     * Sinh token cho link trực tiếp.
     *
     * random_bytes chứ không phải uniqid()/rand(): token này là thứ duy nhất
     * đứng giữa một người lạ và tệp mà gara chỉ muốn gửi riêng. Đoán được
     * token là bỏ qua luôn lớp kiểm đăng nhập.
     */
    public static function sinhToken(){
        return bin2hex(random_bytes(16));   // 32 ký tự, khớp VARCHAR(32)
    }

    /** Đếm thêm một lượt tải (chỉ cho link gián tiếp) */
    public function themLuot($id){
        $id = (int) $id;
        if ($id <= 0) return false;
        /* Điều kiện gara đi kèm cả ở câu UPDATE: thiếu nó thì ai biết id là
           cộng được lượt cho tệp của gara khác. */
        list($dk, $bd) = $this->dkGara('downloads');
        array_unshift($bd, $id);
        return $this->query(
            'UPDATE `downloads` SET `hits` = `hits` + 1 WHERE `id` = ? AND ' . $dk, $bd);
    }

    public function add($data){
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        return $this->lastId();
    }

    public function edit($data, $id){
        $data['update_at'] = date('Y-m-d H:i:s');
        return $this->updateById($data, $id);
    }

    public function remove($id){ return $this->deleteById($id); }

    /** Đường dẫn tuyệt đối tới tệp trên đĩa (dùng để đọc / xoá) */
    public static function duongDanDia($relPath){
        $rel = ltrim((string) $relPath, '/');
        /* Chỉ chấp nhận đúng thư mục đã định. Dòng này chặn ../ đi ngược lên
           cây thư mục nếu `file_path` trong CSDL bị sửa bằng tay. */
        if ($rel === '' || strpos($rel, 'storage/downloads/') !== 0) return '';
        if (strpos($rel, '..') !== false) return '';
        return dirname(__DIR__, 2) . '/' . $rel;
    }
}
