<?php

use App\core\Model;
use App\core\Load;

/** CMS — Menu website (cây 1 cấp submenu). */
class MenusModel extends Model {

    protected $_table   = 'menus';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /* Nội dung website thuộc về MỘT gara — 07/10/2026, khi mỗi gara có trang
       riêng. Thiếu cờ này là gara nào mở web cũng thấy bài của gara khác. */
    protected $_theoGara = true;

    /* ======================================================================
     * KIỂU LIÊN KẾT — tài liệu khách 05/11/2026, Frontend mục 6, khách ghi
     * "(quan trọng nhất)":
     *
     *     "Tạo menu cho chọn các kiểu liên kết
     *        Bài viết : tìm link danh mục bài viết
     *        Sản phẩm : tìm link danh mục sản phẩm
     *        Thư viện : tìm link danh mục video hoặc ảnh
     *        url      : cho link url"
     *
     * VÌ SAO KHÔNG LƯU LUÔN URL ĐÃ DỰNG. Lưu sẵn thì nhanh hơn một nhịp,
     * nhưng đổi đường dẫn một danh mục là mọi menu trỏ vào nó thành 404 —
     * im lặng, không màn nào báo. Mà "chọn thay vì gõ" sinh ra chính là để
     * tránh chuyện đó. Nên lưu ĐÍCH (`link_type` + `target_id`) và dựng URL
     * lúc hiện ra.
     *
     * 'url' GIỮ NGUYÊN ĐƯỜNG CŨ: đọc thẳng cột `url`. Mọi mục menu có từ
     * trước 05/11 đều mang kiểu này (mặc định của cột), nên chúng chạy không
     * khác gì trước.
     * ====================================================================== */
    const KIEU_URL      = 'url';
    const KIEU_TIN      = 'news_cat';
    const KIEU_SAN_PHAM = 'part_cat';
    const KIEU_THU_VIEN = 'gallery';

    /** Kiểu => nhãn hiện trên màn hình */
    public static $kieuLienKet = [
        self::KIEU_URL      => 'Đường dẫn tự gõ',
        self::KIEU_TIN      => 'Danh mục bài viết',
        self::KIEU_SAN_PHAM => 'Danh mục sản phẩm',
        self::KIEU_THU_VIEN => 'Thư viện ảnh / video',
    ];

    /** Kiểu lạ (người gửi POST tay, dữ liệu cũ) -> về 'url' cho an toàn */
    public static function kieuHopLe($kieu){
        $k = (string) $kieu;
        return isset(self::$kieuLienKet[$k]) ? $k : self::KIEU_URL;
    }

    /**
     * Dựng đường dẫn thật của một mục menu.
     *
     * Trả về chuỗi tương đối (hoặc http... nếu người dùng gõ link ngoài) —
     * đúng thứ nav_url() đang nhận, nên nơi gọi không phải đổi gì.
     *
     * ĐÍCH ĐÃ BỊ XOÁ thì trả về '' chứ không phải một URL hỏng. '' đi qua
     * nav_url() thành trang chủ: mục menu vẫn bấm được, chỉ là về trang chủ.
     * Thà thế còn hơn ném người xem vào trang 404 — và màn quản trị có cảnh
     * báo riêng để người quản lý biết mà sửa (xem nhanDich()).
     */
    public function urlCua($dong){
        $kieu = self::kieuHopLe(isset($dong['link_type']) ? $dong['link_type'] : self::KIEU_URL);
        if ($kieu === self::KIEU_URL) return isset($dong['url']) ? (string) $dong['url'] : '';

        $id = !empty($dong['target_id']) ? (int) $dong['target_id'] : 0;
        if ($id <= 0) return '';

        $d = $this->dich($kieu, $id);
        if (empty($d)) return '';

        switch ($kieu){
            case self::KIEU_TIN:
                return empty($d['slug']) ? '' : 'tin-tuc?cat=' . rawurlencode($d['slug']);

            /* Danh mục sản phẩm lọc bằng ID chứ không slug: màn Sản phẩm nhận
               `category[]` là mảng id (nhiều danh mục cùng lúc). Đi theo đúng
               tham số màn đó đang đọc, không đẻ thêm một kiểu tham số thứ hai. */
            case self::KIEU_SAN_PHAM:
                return empty($d['id']) ? '' : 'san-pham?category%5B%5D=' . (int) $d['id'];

            case self::KIEU_THU_VIEN:
                return empty($d['slug']) ? '' : 'thu-vien/' . rawurlencode($d['slug']);
        }
        return '';
    }

    /**
     * Bản ghi đích của một mục menu, hoặc [] nếu đã bị xoá.
     *
     * KHÔNG ÉP KIỂU `(array) $x`. getDetail() trả về `false` khi không tìm
     * thấy, mà `(array) false` ra `[false]` — một mảng KHÔNG RỖNG. Nơi gọi
     * hỏi empty() thấy "có đích", đi tiếp, rồi đọc $d['slug'] không tồn tại
     * và dựng ra `tin-tuc?cat=` — một đường dẫn cụt dẫn vào trang trống, mà
     * trang trống vẫn trả HTTP 200 nên không log nào báo. Bộ test bắt được
     * đúng chỗ này.
     */
    public function dich($kieu, $id){
        $id = (int) $id;
        if ($id <= 0) return [];

        switch (self::kieuHopLe($kieu)){
            case self::KIEU_TIN:       $m = 'NewsCategoriesModel'; break;
            case self::KIEU_SAN_PHAM:  $m = 'PartCategoriesModel'; break;
            case self::KIEU_THU_VIEN:  $m = 'GalleriesModel';      break;
            default: return [];
        }
        $d = Load::model($m)->getDetail($id);
        return is_array($d) ? $d : [];
    }

    /**
     * Câu mô tả đích cho màn danh sách admin.
     *
     * Nói rõ khi đích đã mất. Mục menu trỏ vào danh mục vừa bị xoá trông y hệt
     * mục bình thường trong danh sách — người quản lý chỉ phát hiện khi có
     * khách bấm phải, mà khách thì không báo lại.
     */
    public function nhanDich($dong){
        $kieu = self::kieuHopLe(isset($dong['link_type']) ? $dong['link_type'] : self::KIEU_URL);
        if ($kieu === self::KIEU_URL){
            $u = isset($dong['url']) ? trim((string) $dong['url']) : '';
            return $u === '' ? 'Trang chủ' : $u;
        }
        $d = $this->dich($kieu, isset($dong['target_id']) ? $dong['target_id'] : 0);
        if (empty($d)) return '⚠ Đích đã bị xoá — chọn lại';
        return self::$kieuLienKet[$kieu] . ': ' . (isset($d['name']) ? $d['name'] : '');
    }

    /**
     * Phẳng theo thứ tự cây + depth (cho danh sách admin).
     *
     * Kèm `nhan_dich` — chữ mô tả đích để màn danh sách khỏi phải tự tra.
     * KHÔNG ghi đè `url` như getActiveTree: màn Sửa đọc đúng cột thô để điền
     * lại vào ô người dùng đã gõ.
     */
    public function getTree(){
        $all = $this->bangGara()->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get();
        $byParent = [];
        foreach ($all ?: [] as $r){
            $r['nhan_dich'] = $this->nhanDich($r);
            $p = ($r['parent_id'] === null || $r['parent_id'] === '') ? 0 : (int) $r['parent_id'];
            $byParent[$p][] = $r;
        }
        $out = [];
        $walk = function ($pid, $depth) use (&$walk, &$out, &$byParent){
            if (empty($byParent[$pid])) return;
            foreach ($byParent[$pid] as $n){ $n['depth'] = $depth; $out[] = $n; $walk((int) $n['id'], $depth + 1); }
        };
        $walk(0, 0);
        return $out;
    }

    /**
     * Cây menu ĐANG BẬT cho storefront: [root => [...,'children'=>[...]]]
     *
     * `url` trả ra ở đây là ĐƯỜNG DẪN ĐÃ DỰNG, không phải cột thô: mục kiểu
     * "danh mục bài viết" có cột `url` rỗng, dựng ra mới thành
     * `tin-tuc?cat=...`. Ghi đè ngay tại đây để giao diện khách không phải
     * biết gì về chuyện kiểu liên kết — nó vẫn chỉ đọc $item['url'] như cũ.
     *
     * Màn quản trị thì KHÔNG dùng hàm này (nó gọi getTree), nên ô "Liên kết"
     * lúc sửa vẫn hiện đúng chữ người dùng đã gõ.
     */
    public function getActiveTree(){
        $all = $this->bangGara()->where('status', '=', 1)
                    ->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get();
        $byParent = [];
        foreach ($all ?: [] as $r){
            $r['url'] = $this->urlCua($r);
            $p = ($r['parent_id'] === null || $r['parent_id'] === '') ? 0 : (int) $r['parent_id'];
            $byParent[$p][] = $r;
        }
        $roots = $byParent[0] ?? [];
        foreach ($roots as &$root){
            $root['children'] = $byParent[(int) $root['id']] ?? [];
        }
        return $roots;
    }

    /** Menu gốc đang bật (cho dropdown chọn cha) */
    public function getRoots(){
        return $this->bangGara()->whereNull('parent_id')
                    ->orderBy('sort_order', 'ASC')->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }
    public function add($data){ $data['create_at'] = date('Y-m-d H:i:s'); $this->addNew($data); return $this->lastId(); }
    public function edit($data, $id){ $data['update_at'] = date('Y-m-d H:i:s'); return $this->updateById($data, $id); }
    public function remove($id){ return $this->deleteById($id); }
}
