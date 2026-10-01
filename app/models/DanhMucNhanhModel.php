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

    protected $_table   = 'part_units';    // đặt tạm; mỗi lần gọi tự trỏ bảng theo $loai
    protected $_primary = 'id';
    protected $_fields  = '*';

    /**
     * Danh mục cho phép thêm nhanh.
     *
     *   bang  — bảng trong CSDL
     *   quyen — màn quản trị tương ứng; người dùng phải có quyền THÊM ở màn đó
     *   nhan  — chữ hiện trên hộp thêm nhanh
     *   cha   — cột cha (danh mục cây); không có thì bỏ qua
     *   gara  — true: danh mục RIÊNG từng gara (có cột garage_id)
     *   slug  — false: bảng không có cột slug, đối chiếu trùng bằng chính tên
     */
    public static $loai = [
        'part-cat'    => ['bang' => 'part_categories',    'quyen' => 'part-categories',        'nhan' => 'danh mục phụ tùng', 'cha' => 'parent_id'],
        'part-unit'   => ['bang' => 'part_units',         'quyen' => 'product-units',          'nhan' => 'đơn vị tính'],
        'part-brand'  => ['bang' => 'part_brands',        'quyen' => 'product-brands',         'nhan' => 'thương hiệu'],
        'part-mnf'    => ['bang' => 'part_manufacturers', 'quyen' => 'product-manufacturers',  'nhan' => 'nhà sản xuất'],
        'part-origin' => ['bang' => 'part_origins',       'quyen' => 'product-origins',        'nhan' => 'xuất xứ'],
        'news-cat'    => ['bang' => 'news_categories',    'quyen' => 'news-categories',        'nhan' => 'danh mục tin'],
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

        /* Danh mục riêng gara thì "trùng" chỉ tính TRONG gara đó: hai gara
           cùng có nhóm "Khách VIP" là hai dòng khác nhau, không phải một. */
        $gara = !empty($ct['gara']) ? (int) self::garaLoc() : 0;

        $q = $this->table($bang)->select('`id`, `name`');
        $q = $coSlug ? $q->where('slug', '=', $slug)
                     : $q->where('name', '=', $ten);
        if ($gara > 0) $q = $q->where('garage_id', '=', $gara);
        $co = $q->first();
        if (!empty($co)) return ['c' => (int) $co['id'], 'n' => $co['name'], 'trung' => true];

        $dat = ['name' => $ten, 'status' => 1, 'create_at' => date('Y-m-d H:i:s')];
        if ($coSlug) $dat['slug'] = $this->slugRanh($bang, $slug);
        if ($gara > 0) $dat['garage_id'] = $gara;

        /* Danh mục cây: cha phải là một dòng có thật TRONG CHÍNH bảng đó, nếu
           không thì cây mọc ra một nhánh trỏ vào hư không. */
        if (!empty($ct['cha']) && (int) $cha > 0){
            $chaCo = $this->table($bang)->select('`id`')->where('id', '=', (int) $cha)->first();
            if (!empty($chaCo)) $dat[$ct['cha']] = (int) $cha;
        }

        /* insert() thẳng, KHÔNG addNew(): bảng dùng chung không có garage_id để
           addNew() gắn vào, còn bảng riêng gara thì đã tự gắn ở trên. */
        $this->insert($bang, $dat);

        return ['c' => (int) $this->lastId(), 'n' => $ten, 'trung' => false];
    }

    /**
     * Slug chưa ai dùng trong bảng đó.
     *
     * Đến được đây nghĩa là không tìm thấy dòng nào trùng theo điều kiện tra
     * cứu — nhưng bảng riêng gara thì tra cứu có kèm gara, nên slug vẫn có thể
     * đang bị gara khác giữ. Cột slug là duy nhất toàn bảng, đâm vào là INSERT
     * đổ.
     */
    private function slugRanh($bang, $slug){
        $thu = $slug;
        for ($i = 2; $i <= 50; $i++){
            if (empty($this->table($bang)->select('`id`')->where('slug', '=', $thu)->first())) return $thu;
            $thu = $slug . '-' . $i;
        }
        return $slug . '-' . substr(md5(uniqid('', true)), 0, 6);
    }
}
