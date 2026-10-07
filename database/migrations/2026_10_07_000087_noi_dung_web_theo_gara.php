<?php
/**
 * BƯỚC 3: NỘI DUNG WEBSITE THEO TỪNG GARA.
 *
 * Mỗi gara có website riêng (bước 1: nhận gara theo tên miền; bước 2: nhận
 * diện riêng). Nhưng NỘI DUNG vẫn dùng chung: tin tức, banner, menu, thư viện
 * ảnh, dự án — gara nào mở web cũng thấy y hệt nhau, và gara này sửa một bài
 * là trang của gara kia đổi theo.
 *
 * Thêm `garage_id` cho 6 bảng nội dung. Dữ liệu đang có gán hết về GARA TỔNG:
 * đó là nội dung của Tân Phát, viết trước khi có khái niệm nhiều gara.
 *
 * `gallery_items` CỐ Ý KHÔNG có `garage_id`: nó luôn thuộc về một `galleries`,
 * mà bảng cha đã mang gara rồi. Thêm cột ở bảng con là hai nguồn sự thật cho
 * cùng một việc, và tới lúc lệch nhau thì không biết tin cái nào.
 *
 * KHOÁ NGOẠI ON DELETE CASCADE: xoá một gara thì nội dung web của gara đó đi
 * theo. Khác với chứng từ (RESTRICT — không cho xoá gara còn chứng từ): bài
 * viết và banner không phải sổ sách, giữ lại cũng không ai đọc.
 *
 * Cột để NULL được, không NOT NULL: migration chạy TRƯỚC khi đẩy code (xem
 * cách deploy của dự án), nên trong khoảng giữa hai bước, code cũ còn đang
 * chạy và vẫn thêm bài mới mà không biết điền gara. NOT NULL ở đây là lỗi ghi
 * ngay lúc đó. Khoá chặt để dành cho một migration sau, khi code mới đã ổn.
 */

use App\core\Migration;

return new class extends Migration {

    /** bảng => tên khoá ngoại */
    const BANG = [
        'news'            => 'fk_news_garage',
        'news_categories' => 'fk_news_cat_garage',
        'banners'         => 'fk_banners_garage',
        'menus'           => 'fk_menus_garage',
        'galleries'       => 'fk_galleries_garage',
        'site_projects'   => 'fk_site_projects_garage',
    ];

    public function up(){
        $garaTong = $this->db->table('garages')->where('is_master', '=', 1)->first();
        if (empty($garaTong)){
            echo "  Khong tim thay gara tong — dung lai, khong doi gi.\n";
            return;
        }
        $idTong = (int) $garaTong['id'];

        foreach (self::BANG as $bang => $fk){
            if (!$this->hasTable($bang)){ echo "  Khong co bang `$bang` — bo qua.\n"; continue; }

            if (!$this->coCot($bang, 'garage_id')){
                $this->run("ALTER TABLE `$bang`
                            ADD COLUMN `garage_id` INT DEFAULT NULL,
                            ADD KEY `idx_{$bang}_garage` (`garage_id`),
                            ADD CONSTRAINT `$fk` FOREIGN KEY (`garage_id`)
                                REFERENCES `garages` (`id`) ON DELETE CASCADE");
                echo "  Da them `$bang`.`garage_id`.\n";
            }

            $r = $this->db->query("UPDATE `$bang` SET `garage_id` = ? WHERE `garage_id` IS NULL", [$idTong]);
            $con = $this->db->query("SELECT COUNT(*) AS c FROM `$bang` WHERE `garage_id` IS NULL")
                            ->fetch(\PDO::FETCH_ASSOC);
            echo "  `$bang`: da gan ve gara tong, con " . (int) $con['c'] . " dong chua co gara.\n";
        }
    }

    public function down(){
        foreach (self::BANG as $bang => $fk){
            if (!$this->hasTable($bang) || !$this->coCot($bang, 'garage_id')) continue;
            $this->run("ALTER TABLE `$bang` DROP FOREIGN KEY `$fk`");
            $this->run("ALTER TABLE `$bang` DROP COLUMN `garage_id`");
            echo "  Da go `$bang`.`garage_id`.\n";
        }
    }

    /* Hai hàm này KHÔNG có sẵn ở core\Migration — các migration khác cũng tự
       khai. Giữ nguyên cách đó để chạy lại không nổ. */
    private function coCot($bang, $cot){
        try {
            $this->db->query("SELECT `$cot` FROM `$bang` LIMIT 1");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
