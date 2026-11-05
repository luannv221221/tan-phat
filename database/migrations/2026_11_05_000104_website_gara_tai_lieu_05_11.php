<?php
/**
 * TÀI LIỆU WEBSITE 05/11/2026 — các cột cần cho 4 đợt việc.
 *
 * Khách gửi "Nội dung xây dựng website phụ tùng từ gara". Hai mục nặng nhất
 * (kết nối sàn thương mại điện tử, đa ngôn ngữ) khách đã CHỐT BỎ, nên migration
 * này không đụng gì tới chúng.
 *
 * Bốn nhóm cột:
 *
 *   1. `menus`.`link_type` + `target_id` — mục khách ghi "quan trọng nhất".
 *      Menu đang lưu URL gõ tay. Gõ tay thì đổi slug một bài viết là menu trỏ
 *      vào trang 404, mà không ai biết cho tới khi khách bấm phải.
 *      Nay menu CHỌN đích (danh mục bài viết / danh mục sản phẩm / album) và
 *      URL được dựng lại lúc hiện ra, nên slug đổi thì menu đi theo.
 *
 *      MẶC ĐỊNH 'url' CHỨ KHÔNG PHẢI NULL: mọi mục menu đang có đều là URL gõ
 *      tay, và chúng phải chạy y như cũ sau khi chạy migration này. Cột `url`
 *      giữ nguyên, không xoá — kiểu 'url' vẫn đọc thẳng từ đó.
 *
 *   2. `news`.`tags` — ô "Từ khoá" trong ảnh mẫu khách gửi.
 *
 *   3. `parts`.`meta_title` + `meta_description` — tài liệu Frontend mục 5:
 *      "chọn sửa để bổ sung thêm các trường meta seo". Bài viết đã có hai cột
 *      này từ trước; hàng hoá thì chưa, nên trang chi tiết sản phẩm đang lấy
 *      tạm mô tả chung của cả website làm thẻ meta.
 *
 * KHÔNG CÓ CỘT NÀO CHO ĐỢT SERIAL. Tài liệu CRM mục 2 đòi "thêm serial theo
 * phiếu mua (chọn phiếu mua) tránh làm từ lúc tạo đơn mua" — nghe như phải đổi
 * chỗ neo dữ liệu, nhưng không: serial vẫn thuộc đúng dòng đơn mua đó
 * (`purchase_serials`.`poi_id`, đã cho NULL sẵn từ 000102). Thứ phải đổi là
 * LÚC NÀO khai và khai Ở ĐÂU — một màn riêng mở sau khi đơn đã lưu, thay vì
 * mấy chục ô chen trong form lập đơn. Đó là việc của code, không phải của CSDL.
 *
 * CHẠY LẠI ĐƯỢC: cột nào có rồi thì bỏ qua cột đó.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        // ------------------------------------------------ 1. Menu chọn đích
        if ($this->hasTable('menus')){
            if (!$this->hasColumn('menus', 'link_type')){
                $this->run("ALTER TABLE `menus`
                            ADD COLUMN `link_type` VARCHAR(20) NOT NULL DEFAULT 'url' AFTER `label`");
                echo "  Da them `menus`.`link_type` (mac dinh 'url').\n";
            }
            if (!$this->hasColumn('menus', 'target_id')){
                /* NULL được: kiểu 'url' không có đích nào để trỏ. Dùng 0 thay
                   NULL thì không phân biệt được "chưa chọn" với "đã chọn id 0". */
                $this->run("ALTER TABLE `menus`
                            ADD COLUMN `target_id` INT NULL DEFAULT NULL AFTER `link_type`");
                echo "  Da them `menus`.`target_id`.\n";
            }
        }

        // ------------------------------------------------ 2. Từ khoá bài viết
        if ($this->hasTable('news') && !$this->hasColumn('news', 'tags')){
            $this->run("ALTER TABLE `news`
                        ADD COLUMN `tags` VARCHAR(255) NULL DEFAULT NULL AFTER `meta_description`");
            echo "  Da them `news`.`tags`.\n";
        }

        // ------------------------------------------------ 3. Meta SEO hàng hoá
        if ($this->hasTable('parts')){
            if (!$this->hasColumn('parts', 'meta_title')){
                $this->run("ALTER TABLE `parts`
                            ADD COLUMN `meta_title` VARCHAR(255) NULL DEFAULT NULL AFTER `description`");
                echo "  Da them `parts`.`meta_title`.\n";
            }
            if (!$this->hasColumn('parts', 'meta_description')){
                $this->run("ALTER TABLE `parts`
                            ADD COLUMN `meta_description` VARCHAR(500) NULL DEFAULT NULL AFTER `meta_title`");
                echo "  Da them `parts`.`meta_description`.\n";
            }
        }

    }

    /**
     * Quay lui được.
     *
     * MẤT DỮ LIỆU: từ khoá bài viết, meta SEO hàng hoá, và đích của menu. Menu
     * không chết theo: cột `url` vẫn còn nguyên, bỏ `link_type` đi thì mọi mục
     * quay về đọc thẳng `url` như trước 05/11.
     */
    public function down(){
        foreach ([['menus', 'target_id'], ['menus', 'link_type'],
                  ['news', 'tags'],
                  ['parts', 'meta_description'], ['parts', 'meta_title']] as $c){
            list($bang, $cot) = $c;
            if ($this->hasColumn($bang, $cot)){
                $this->run("ALTER TABLE `$bang` DROP COLUMN `$cot`");
                echo "  Da bo `$bang`.`$cot`.\n";
            }
        }
    }

    protected function hasColumn($bang, $cot){
        foreach ($this->moTaCot($bang) as $r){
            if (isset($r['Field']) && $r['Field'] === $cot) return true;
        }
        return false;
    }

    protected function moTaCot($bang){
        try {
            return $this->db->query("SHOW COLUMNS FROM `$bang`")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e){ return []; }
    }
};
