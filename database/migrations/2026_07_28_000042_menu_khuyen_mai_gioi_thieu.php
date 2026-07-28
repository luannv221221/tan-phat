<?php
/**
 * Dọn lại menu website.
 *
 * 1. "Khuyến mãi" đang trỏ tới `san-pham?promo=1` (danh sách sản phẩm có bật
 *    bộ lọc). Nay đã có trang riêng /khuyen-mai (Shop::promo) nên đổi URL.
 *
 * 2. Thêm "Giới thiệu". Mục này trước đây được viết cứng trong
 *    layouts/storefront/master.php cùng với "Trang chủ" — mà bảng `menus`
 *    cũng đã có "Trang chủ", nên thanh nav hiện mục này 2 lần. View đã bỏ
 *    phần viết cứng, migration này đưa "Giới thiệu" vào bảng cho không mất mục.
 *
 * 3. Đánh lại sort_order cho đúng thứ tự mong muốn.
 *
 * Chỉ đụng vào dòng khớp đúng label/url nên chạy trên DB đã sửa tay vẫn an toàn.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        // 1. Trỏ "Khuyến mãi" sang trang riêng
        $this->db->update(
            'menus',
            ['url' => 'khuyen-mai', 'update_at' => date('Y-m-d H:i:s')],
            '`url` = ?',
            ['san-pham?promo=1']
        );

        // 2. Thêm "Giới thiệu" nếu chưa có
        $existed = $this->db->table('menus')->where('url', '=', 'gioi-thieu')->first();
        if (empty($existed)){
            $this->db->insert('menus', [
                'parent_id'  => null,
                'label'      => 'Giới thiệu',
                'url'        => 'gioi-thieu',
                'target'     => '_self',
                'sort_order' => 1,
                'status'     => 1,
                'create_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        // 3. Thứ tự hiển thị
        $order = [
            ''            => 0,   // Trang chủ
            'gioi-thieu'  => 1,
            'san-pham'    => 2,
            'khuyen-mai'  => 3,
            'du-an'       => 4,
            'thu-vien'    => 5,
            'tin-tuc'     => 6,
        ];
        foreach ($order as $url => $sort){
            $this->db->update('menus', ['sort_order' => $sort], '`url` = ?', [$url]);
        }
    }

    public function down(){
        $this->db->delete('menus', '`url` = ?', ['gioi-thieu']);
        $this->db->update('menus', ['url' => 'san-pham?promo=1'], '`url` = ?', ['khuyen-mai']);
    }
};
