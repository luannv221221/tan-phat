<?php
/**
 * BIỂN SỐ XE + SỐ KM trên báo giá và hoá đơn bán.
 *
 * Gara sửa xe thì chứng từ phải nói rõ nó cho CHIẾC XE NÀO. Hai xe của cùng
 * một khách, hoặc cùng một biển số nhưng hai lần vào xưởng cách nhau 20.000 km,
 * là hai việc khác hẳn nhau.
 *
 * VÌ SAO LÀ Ô NHẬP TAY CHỨ KHÔNG PHẢI CHỌN TỪ XE CỦA KHÁCH
 * Hệ thống đang có HAI khái niệm khách hàng khác nhau:
 *   quotations.customer_id      -> `partners`  (khách/NCC dùng trên chứng từ)
 *   member_vehicles.member_id   -> `members`   (khách đăng ký / khách ở gara)
 * Xe đang treo ở `members`, còn chứng từ lại trỏ vào `partners`. Nối hai cái
 * đó là một việc riêng và lớn hơn hẳn. Trước mắt là ô nhập tay — vẫn đúng cho
 * mọi trường hợp, kể cả xe của khách vãng lai chưa từng khai vào hệ thống.
 *
 * VẪN LƯU `bien_so_chuan` DÙ CHƯA CÓ MÀN TRA CỨU NÀO
 * Người nhập mỗi lần một kiểu: "40G-474.89", "40g 47489", "40G47489". Không
 * chuẩn hoá ngay lúc lưu thì sau này muốn tra "xe này đã vào xưởng mấy lần"
 * phải quét cả bảng rồi chuẩn hoá tại chỗ — chậm, và không dùng được chỉ mục.
 * Chuẩn hoá lúc ghi thì rẻ; chuẩn hoá lúc đọc thì trả giá mãi mãi.
 *
 * Cả ba cột đều để trống được: bán lẻ phụ tùng qua quầy thì không có xe nào cả.
 */

use App\core\Migration;

return new class extends Migration {

    /** Chứng từ nào cũng cần: báo giá chốt xong là chuyển thành hoá đơn */
    protected $bangs = ['quotations', 'sales_invoices'];

    public function up(){
        foreach ($this->bangs as $bang){
            if (!$this->hasTable($bang)) continue;

            if (!$this->hasColumn($bang, 'bien_so')){
                $this->run("ALTER TABLE `$bang` ADD COLUMN `bien_so` VARCHAR(20) DEFAULT NULL");
                $this->run("ALTER TABLE `$bang` ADD COLUMN `bien_so_chuan` VARCHAR(20) DEFAULT NULL");
                $this->run("ALTER TABLE `$bang` ADD COLUMN `so_km` INT DEFAULT NULL");
                /* Chỉ mục trên cột CHUẨN HOÁ, không phải cột gốc: tra cứu bao
                   giờ cũng so bản chuẩn hoá, còn cột gốc chỉ để in ra cho đẹp. */
                $this->run("ALTER TABLE `$bang` ADD KEY `idx_{$bang}_bien_so` (`bien_so_chuan`)");
                echo "  Da them bien_so / bien_so_chuan / so_km vao `$bang`.\n";
            }
        }
    }

    /** SHOW COLUMNS rồi lọc bằng PHP — `SHOW COLUMNS ... LIKE ?` bị lỗi 1064 */
    protected function hasColumn($bang, $cot){
        try {
            $rows = $this->db->query("SHOW COLUMNS FROM `$bang`")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e){ return false; }
        foreach ($rows as $r){
            if (isset($r['Field']) && $r['Field'] === $cot) return true;
        }
        return false;
    }

    public function down(){
        foreach ($this->bangs as $bang){
            if (!$this->hasTable($bang) || !$this->hasColumn($bang, 'bien_so')) continue;
            try { $this->run("ALTER TABLE `$bang` DROP KEY `idx_{$bang}_bien_so`"); } catch (\Throwable $e){}
            $this->run("ALTER TABLE `$bang` DROP COLUMN `so_km`");
            $this->run("ALTER TABLE `$bang` DROP COLUMN `bien_so_chuan`");
            $this->run("ALTER TABLE `$bang` DROP COLUMN `bien_so`");
        }
    }
};
