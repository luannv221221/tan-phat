<?php

use App\core\Model;

/**
 * THÊM NHANH MỘT DÒNG DANH MỤC — dùng chung cho mọi form.
 *
 * Mọi ô chọn trên hệ thống đều gặp cùng một chỗ tắc: danh mục không bao giờ đủ.
 * Người dùng đang lập phiếu, gặp một đơn vị tính hay một danh mục phụ tùng chưa
 * có, mà phải bỏ dở sang màn Danh mục rồi quay lại lập từ đầu. Nút + cạnh ô chọn
 * gọi vào đây để thêm ngay tại chỗ.
 *
 * Danh mục nào thêm được khai trong $loai — DANH SÁCH TRẮNG, không nhận tên bảng
 * từ người gửi. Thiếu nó thì một tham số `bang=users` là ghi thẳng vào bảng nào
 * cũng được.
 *
 * TRÙNG THÌ DÙNG LẠI dòng cũ, không tạo bản sao: đối chiếu bằng slug (bỏ dấu,
 * thường hoá, gộp khoảng trắng) nên "Bộ lọc gió", "BỘ LỌC GIÓ" và " bo loc gio "
 * cùng về một dòng. Không có bước này thì ô chọn cũng loạn y như ô gõ tay, chỉ
 * chậm hơn vài tháng.
 *
 * Danh mục xe (hãng / model / năm / màu) KHÔNG nằm đây: quan hệ cha con và năm
 * theo khoảng nằm ở VehiclesModel::themDanhMuc(), controller tự chuyển hướng
 * sang đó.
 */
class DanhMucNhanhModel extends Model {

    /* KHÔNG SỞ HỮU BẢNG NÀO — mỗi lần gọi tự trỏ bảng theo $loai, nên không
       dùng CRUD của lớp cha (getList / getFirst / addNew) ở đây.

       Trước đây có `$_table = 'part_units'` "đặt tạm". Khai một bảng mình không
       sở hữu là nói sai hai lần: bộ test "bảng có garage_id thì model phải bật
       cờ lọc gara" đếm model này là một model của `part_units` và đòi nó bật cờ,
       còn addNew() thì sẽ ghi vào `part_units` nếu có ai gọi nhầm. */
    protected $_primary = 'id';
    protected $_fields  = '*';

    /**
     * Danh mục cho phép thêm nhanh.
     *
     *   bang  — bảng trong CSDL
     *   quyen — màn quản trị tương ứng; người dùng phải có quyền THÊM ở màn đó
     *   nhan  — chữ hiện trên hộp thêm nhanh
     *   cha   — cột cha (danh mục cây); không có thì bỏ qua
     *   gara  — true: danh mục RIÊNG từng gara (có garage_id, không có phần chung)
     *   chung — true: danh mục CHUNG-VÀ-RIÊNG (garage_id NULL = của danh mục
     *           tổng, mọi gara đều thấy; có giá trị = riêng gara đó).
     *           Xem Model::$_chungVaRieng.
     *   slug  — false: bảng không có cột slug, đối chiếu trùng bằng chính tên
     */
    public static $loai = [
        'part-cat'    => ['bang' => 'part_categories',    'quyen' => 'part-categories',        'nhan' => 'danh mục phụ tùng', 'cha' => 'parent_id', 'chung' => true],
        'part-unit'   => ['bang' => 'part_units',         'quyen' => 'product-units',          'nhan' => 'đơn vị tính',       'chung' => true],
        'part-brand'  => ['bang' => 'part_brands',        'quyen' => 'product-brands',         'nhan' => 'thương hiệu',       'chung' => true],
        'part-mnf'    => ['bang' => 'part_manufacturers', 'quyen' => 'product-manufacturers',  'nhan' => 'nhà sản xuất',      'chung' => true],
        'part-origin' => ['bang' => 'part_origins',       'quyen' => 'product-origins',        'nhan' => 'xuất xứ',           'chung' => true],
        'news-cat'    => ['bang' => 'news_categories',    'quyen' => 'news-categories',        'nhan' => 'danh mục tin',      'gara' => true],
        'car-body'    => ['bang' => 'car_body_types',     'quyen' => 'car-body-types',         'nhan' => 'kiểu dáng xe'],
        'car-fuel'    => ['bang' => 'car_fuels',          'quyen' => 'car-fuels',              'nhan' => 'nhiên liệu'],
        'kh-nhom'     => ['bang' => 'customer_groups',    'quyen' => 'customer-groups',        'nhan' => 'nhóm khách', 'gara' => true, 'slug' => false],
    ];

    public static function co($loai){
        return isset(self::$loai[$loai]) ? self::$loai[$loai] : null;
    }

    /**
     * Thêm một dòng, trùng thì dùng lại.
     *
     * Trả ['c' => id, 'n' => tên, 'trung' => true nếu dùng lại dòng có sẵn],
     * hoặc null khi tên rỗng / loại không hợp lệ.
     */
    public function them($loai, $ten, $cha = 0){
        $ct = self::co($loai);
        if ($ct === null) return null;

        $ten = trim(preg_replace('/\s+/u', ' ', (string) $ten));
        if ($ten === '') return null;

        $bang   = $ct['bang'];
        $coSlug = !isset($ct['slug']) || $ct['slug'] !== false;
        $slug   = $coSlug ? slugify($ten) : '';
        if ($coSlug && $slug === '') return null;

        /* BA KIỂU CHIA THEO GARA, ba cách tra "đã có chưa":
         *
         *   không chia  (danh mục xe) -> tra khắp bảng
         *   riêng gara  (nhóm khách)  -> chỉ trong gara đó: hai gara cùng có
         *                                nhóm "Khách VIP" là hai dòng khác nhau
         *   chung-và-riêng (danh mục phụ tùng) -> trong gara đó CỘNG danh mục
         *                                tổng. Gara gõ "Bosch" mà kho tổng đã
         *                                có thì DÙNG LẠI dòng đó, không tạo
         *                                bản riêng — nếu không, ô chọn hiện hai
         *                                dòng "Bosch" y hệt nhau.
         */
        $chung = !empty($ct['chung']);
        $gara  = !empty($ct['gara']) ? (int) self::garaLoc() : 0;

        $q = $this->table($bang)->select('`id`, `name`');
        $q = $coSlug ? $q->where('slug', '=', $slug)
                     : $q->where('name', '=', $ten);
        if ($gara > 0)  $q = $q->where('garage_id', '=', $gara);
        if ($chung)     $q = $this->locChungVaRieng($q, $bang);
        $co = $q->first();
        if (!empty($co)) return ['c' => (int) $co['id'], 'n' => $co['name'], 'trung' => true];

        $dat = ['name' => $ten, 'status' => 1, 'create_at' => date('Y-m-d H:i:s')];
        if ($coSlug) $dat['slug'] = $this->slugRanhTrongBang($bang, $slug);
        if ($gara > 0) $dat['garage_id'] = $gara;
        if ($chung && self::garaLoc() !== null) $dat['garage_id'] = $this->garaSoHuu();

        /* Danh mục cây: cha phải là một dòng có thật TRONG CHÍNH bảng đó, nếu
           không thì cây mọc ra một nhánh trỏ vào hư không. Bảng chung-và-riêng
           thì cha còn phải là dòng gara này THẤY được — nhận id cha từ form mà
           không lọc là gara treo nhánh của mình vào danh mục nội bộ gara khác. */
        if (!empty($ct['cha']) && (int) $cha > 0){
            $qc = $this->table($bang)->select('`id`')->where('id', '=', (int) $cha);
            if ($chung) $qc = $this->locChungVaRieng($qc, $bang);
            if ($gara > 0) $qc = $qc->where('garage_id', '=', $gara);
            if (!empty($qc->first())) $dat[$ct['cha']] = (int) $cha;
        }

        /* insert() thẳng, KHÔNG addNew(): addNew() đọc cờ $_theoGara /
           $_chungVaRieng của CHÍNH model này, mà model này không sở hữu bảng nào
           — mỗi lần gọi nó trỏ sang một bảng khác. Gara đã tự gắn ở trên. */
        $this->insert($bang, $dat);

        return ['c' => (int) $this->lastId(), 'n' => $ten, 'trung' => false];
    }

    /**
     * Slug chưa ai dùng trong bảng đó — TRA KHẮP BẢNG, không lọc gara.
     *
     * Đến được đây nghĩa là không tìm thấy dòng nào trùng theo điều kiện tra
     * cứu — nhưng bảng chia theo gara thì tra cứu có kèm gara, nên slug vẫn có
     * thể đang bị gara khác giữ. Cột slug là duy nhất toàn bảng, đâm vào là
     * INSERT đổ.
     *
     * Tên có đuôi `TrongBang` vì nhận bảng từ ngoài — khác
     * Model::slugRanh($slug) làm cùng việc trên bảng của model. Hai hàm cùng
     * tên khác chữ ký là lỗi "Access level ... must be public" lúc nạp class,
     * tức trang trắng.
     */
    private function slugRanhTrongBang($bang, $slug){
        $thu = $slug;
        for ($i = 2; $i <= 50; $i++){
            if (empty($this->table($bang)->select('`id`')->where('slug', '=', $thu)->first())) return $thu;
            $thu = $slug . '-' . $i;
        }
        return $slug . '-' . substr(md5(uniqid('', true)), 0, 6);
    }
}
