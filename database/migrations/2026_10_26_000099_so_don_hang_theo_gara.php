<?php
/**
 * SỐ ĐƠN HÀNG KHÔNG TRÙNG TRONG TỪNG GARA — chứ không phải toàn hệ thống.
 *
 * SỬA MỘT LỖI THẬT (khách báo 26/10/2026): khách đặt hàng trên website của một
 * gara thì đơn KHÔNG về màn Đơn hàng. Tái hiện bằng cách đặt một đơn qua tên
 * miền của gara: HTTP 500, log ghi
 *
 *     Duplicate entry 'DH-000001' for key 'orders.uq_order_no'
 *
 * Hai vế lệch nhau:
 *   - OrdersModel::nextNo() đếm qua bangGara(), tức đánh số TRONG TỪNG GARA —
 *     gara thứ hai bắt đầu lại từ DH-000001.
 *   - `orders` vẫn giữ UNIQUE (`order_no`) từ migration 000024, tức duy nhất
 *     TOÀN HỆ THỐNG.
 *
 * Nên gara nào không giữ số lớn nhất toàn hệ thống thì MỌI đơn web đều chết ở
 * câu INSERT, transaction quay lui, không để lại dấu vết nào. Gara tổng vẫn
 * đặt được (số của nó đang là số lớn nhất) nên nhìn bề ngoài tưởng vẫn chạy —
 * đó là lý do lỗi này sống được lâu.
 *
 * Migration 000078 đã làm đúng việc này cho báo giá, hoá đơn và mã hàng.
 * `orders` bị sót vì lúc đó nó CHƯA theo gara — mãi 000089 mới thêm
 * `garage_id` mà không đụng tới chỉ mục.
 *
 * CHỈ MỤC MỚI RỘNG HƠN CHỈ MỤC CŨ nên không bao giờ vướng dữ liệu sẵn có: cái
 * cũ đã cấm trùng `order_no` toàn hệ thống, tức chắc chắn không có hai dòng
 * nào trùng (garage_id, order_no).
 *
 * `garage_id` ĐANG CHO PHÉP NULL và MySQL coi các NULL là khác nhau, nên chỉ
 * mục này không chặn được hai đơn `garage_id` NULL cùng số. Thực tế không xảy
 * ra: Model::addNew() đóng dấu garage_id cho mọi đơn mới và NÉM LỖI nếu không
 * xác định được gara. Không ép cột sang NOT NULL ở đây — đó là một thay đổi
 * khác, và dữ liệu cũ trước 000089 có thể còn dòng NULL.
 */

use App\core\Migration;

return new class extends Migration {

    const BANG = 'orders';
    const CU   = 'uq_order_no';
    const MOI  = 'uq_order_gara_no';
    const COT  = '`garage_id`, `order_no`';

    public function up(){
        if (!$this->hasTable(self::BANG)){
            echo "  (chua co bang `" . self::BANG . "`, bo qua)\n"; return;
        }
        if (!$this->hasColumn(self::BANG, 'garage_id')){
            echo "  (`" . self::BANG . "` chua co `garage_id` — chay migration 000089 truoc)\n"; return;
        }

        /* THÊM TRƯỚC, XOÁ SAU. Xoá trước thì giữa hai câu lệnh bảng không còn
           chốt nào chặn trùng số, mà website vẫn đang nhận đơn. */
        if (!$this->hasIndex(self::BANG, self::MOI)){
            $this->run('ALTER TABLE `' . self::BANG . '` ADD UNIQUE KEY `' . self::MOI . '` (' . self::COT . ')');
            echo "  Da them chi muc `" . self::MOI . "` (gara + so don).\n";
        }
        if ($this->hasIndex(self::BANG, self::CU)){
            $this->run('ALTER TABLE `' . self::BANG . '` DROP INDEX `' . self::CU . '`');
            echo "  Da bo chi muc `" . self::CU . "` (duy nhat toan he thong).\n";
        }
        echo "  So don hang: khong trung TRONG TUNG GARA.\n";
    }

    public function down(){
        if (!$this->hasTable(self::BANG)) return;

        /* Quay lui CÓ THỂ KHÔNG LÀM ĐƯỢC: sau khi mở, hai gara hoàn toàn có thể
           đã cùng có DH-000001 — đó chính là điều migration này cho phép. Lúc
           đó dựng lại chỉ mục cũ là MySQL từ chối, và đúng ra phải như vậy:
           quay lui không được phép lặng lẽ vứt đơn của gara nào cả. */
        try {
            if (!$this->hasIndex(self::BANG, self::CU)){
                $this->run('ALTER TABLE `' . self::BANG . '` ADD UNIQUE KEY `' . self::CU . '` (`order_no`)');
            }
            if ($this->hasIndex(self::BANG, self::MOI)){
                $this->run('ALTER TABLE `' . self::BANG . '` DROP INDEX `' . self::MOI . '`');
            }
            echo "  Da tra chi muc ve duy nhat toan he thong.\n";
        } catch (\Throwable $e){
            echo "  (GIU nguyen `" . self::MOI . "`: da co don trung so giua cac gara,\n"
               . "   tra ve chi muc cu la phai xoa don cua mot ben)\n";
        }
    }

    protected function hasIndex($bang, $ten){
        try { $rows = $this->db->query("SHOW INDEX FROM `$bang`")->fetchAll(\PDO::FETCH_ASSOC); }
        catch (\Throwable $e){ return false; }
        foreach ($rows as $r){ if (isset($r['Key_name']) && $r['Key_name'] === $ten) return true; }
        return false;
    }

    /** SHOW COLUMNS rồi lọc bằng PHP — `SHOW COLUMNS ... LIKE ?` bị lỗi 1064 */
    protected function hasColumn($bang, $cot){
        try { $rows = $this->db->query("SHOW COLUMNS FROM `$bang`")->fetchAll(\PDO::FETCH_ASSOC); }
        catch (\Throwable $e){ return false; }
        foreach ($rows as $r){ if (isset($r['Field']) && $r['Field'] === $cot) return true; }
        return false;
    }
};
