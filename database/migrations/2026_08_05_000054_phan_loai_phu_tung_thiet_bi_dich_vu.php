<?php
/**
 * TÁCH HÀNG HOÁ THÀNH 3 NHÓM: phụ tùng / thiết bị / dịch vụ.
 *
 * Khách chốt 05/08/2026:
 *   - Có xuất hoá đơn & báo giá cho DỊCH VỤ
 *   - THIẾT BỊ vẫn nhập kho, theo dõi tồn y như phụ tùng
 *   - DỊCH VỤ không gắn với xe, một mức giá chung
 *
 * Vì sao cần CỘT chứ không chỉ cần danh mục: toàn hệ thống coi mọi thứ là
 * `parts`, và các luồng bán hàng đều chặn khi tồn không đủ. Dịch vụ ("thay
 * dầu") tồn luôn bằng 0 nên nếu không có cờ phân biệt thì MỌI hoá đơn có
 * dòng dịch vụ đều bị chặn không ghi sổ được.
 *
 * Danh mục lo phần HIỂN THỊ (cây 3 nhánh), cột `item_type` lo phần NGHIỆP VỤ.
 * Hai thứ tách bạch: đổi danh mục không làm hàng hoá đột nhiên hết kiểm tồn.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $now = date('Y-m-d H:i:s');

        // ---------- 1. Cột phân loại ----------
        // Mặc định 'part': mọi hàng đang có đều là phụ tùng, không cần vá tay.
        $this->run("ALTER TABLE `parts`
                    ADD COLUMN `item_type` ENUM('part','equipment','service')
                    NOT NULL DEFAULT 'part' AFTER `name`");

        $this->run("ALTER TABLE `parts` ADD KEY `idx_parts_item_type` (`item_type`)");

        // ---------- 2. Dựng 3 danh mục gốc ----------
        // "Thiết Bị" đã có sẵn (ai đó thêm trước) -> DÙNG LẠI, không tạo trùng.
        $goc = [
            'phu-tung' => ['Phụ tùng', 0],
            'thiet-bi' => ['Thiết bị', 1],
            'dich-vu'  => ['Dịch vụ',  2],
        ];
        $idGoc = [];
        foreach ($goc as $slug => $info){
            $co = $this->db->table('part_categories')->where('slug', '=', $slug)->first();
            if (!empty($co)){
                $idGoc[$slug] = (int) $co['id'];
                $this->db->update('part_categories',
                    ['parent_id' => null, 'name' => $info[0], 'sort_order' => $info[1], 'update_at' => $now],
                    '`id` = ?', [$idGoc[$slug]]);
            } else {
                $this->db->insert('part_categories', [
                    'parent_id' => null, 'name' => $info[0], 'slug' => $slug,
                    'sort_order' => $info[1], 'status' => 1, 'create_at' => $now,
                ]);
                $idGoc[$slug] = (int) $this->db->lastId();
            }
        }

        // ---------- 3. Bốn nhóm phụ tùng cũ tụt xuống làm con của "Phụ tùng" ----------
        // Chỉ đụng đúng 4 slug này, không quét "mọi gốc còn lại" — chạy lại lần
        // hai mà quét kiểu đó là lôi luôn cả 3 gốc mới vào làm con của nhau.
        foreach (['he-thong-phanh', 'dong-co', 'he-thong-dien', 'he-thong-treo'] as $slug){
            $c = $this->db->table('part_categories')->where('slug', '=', $slug)->first();
            if (!empty($c)){
                $this->db->update('part_categories',
                    ['parent_id' => $idGoc['phu-tung'], 'update_at' => $now],
                    '`id` = ?', [(int) $c['id']]);
            }
        }

        // ---------- 4. Gieo danh mục dịch vụ ----------
        $dv = ['Thay dầu' => 'thay-dau', 'Bảo dưỡng' => 'bao-duong',
               'Thay má phanh' => 'thay-ma-phanh', 'Sửa chữa' => 'sua-chua'];
        $i = 0;
        foreach ($dv as $ten => $slug){
            $co = $this->db->table('part_categories')->where('slug', '=', $slug)->first();
            if (empty($co)){
                $this->db->insert('part_categories', [
                    'parent_id' => $idGoc['dich-vu'], 'name' => $ten, 'slug' => $slug,
                    'sort_order' => $i++, 'status' => 1, 'create_at' => $now,
                ]);
            }
        }

        // ---------- 5. Đồng bộ item_type cho hàng đang có ----------
        // Hàng nằm dưới nhánh Thiết bị -> equipment, dưới nhánh Dịch vụ -> service.
        // Cây chỉ 2 tầng nên đi 1 bước là đủ; sâu hơn thì cột vẫn mặc định 'part'
        // và người dùng sửa tay ở màn hình hàng hoá.
        foreach (['thiet-bi' => 'equipment', 'dich-vu' => 'service'] as $slug => $loai){
            // db->query() chứ không phải run(): run() chỉ nhận SQL, không bind được.
            $this->db->query(
                "UPDATE `parts` SET `item_type` = ?
                 WHERE `category_id` IN (
                     SELECT `id` FROM (
                         SELECT `id` FROM `part_categories` WHERE `id` = ? OR `parent_id` = ?
                     ) AS t
                 )",
                [$loai, $idGoc[$slug], $idGoc[$slug]]
            );
        }
    }

    public function down(){
        $this->run("ALTER TABLE `parts` DROP INDEX `idx_parts_item_type`");
        $this->run("ALTER TABLE `parts` DROP COLUMN `item_type`");

        // Trả 4 nhóm phụ tùng về làm gốc như cũ
        foreach (['he-thong-phanh', 'dong-co', 'he-thong-dien', 'he-thong-treo'] as $slug){
            $c = $this->db->table('part_categories')->where('slug', '=', $slug)->first();
            if (!empty($c)) $this->db->update('part_categories', ['parent_id' => null], '`id` = ?', [(int) $c['id']]);
        }

        // Xoá danh mục do migration này tạo. KHÔNG xoá 'thiet-bi' vì nó có từ trước.
        foreach (['thay-dau', 'bao-duong', 'thay-ma-phanh', 'sua-chua', 'dich-vu', 'phu-tung'] as $slug){
            $this->db->delete('part_categories', '`slug` = ?', [$slug]);
        }
    }
};
