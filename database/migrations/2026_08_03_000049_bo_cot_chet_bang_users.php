<?php
/**
 * Bỏ hai cột chết của bảng `users`.
 *
 * `forgot_key` (mã đặt lại mật khẩu) và `active_key` (mã kích hoạt tài khoản)
 * là tàn dư của bộ khung 2021: hai chức năng đó chưa bao giờ được xây, không
 * dòng mã nào trong app/ hay core/ đọc hoặc ghi hai cột này, và cả hai đều
 * rỗng hoàn toàn trên dữ liệu bàn giao.
 *
 * Nếu sau này làm chức năng quên mật khẩu, nên thiết kế lại cho tử tế: token
 * kèm hạn dùng và đánh dấu đã dùng, thay vì một cột varchar(50) trần.
 */

use App\core\Migration;

return new class extends Migration {

    protected $columns = ['forgot_key', 'active_key'];

    public function up(){
        foreach ($this->columns as $c){
            if ($this->hasColumn('users', $c)){
                $this->run("ALTER TABLE `users` DROP COLUMN `$c`");
            }
        }
    }

    public function down(){
        foreach ($this->columns as $c){
            if (!$this->hasColumn('users', $c)){
                $this->run("ALTER TABLE `users` ADD COLUMN `$c` VARCHAR(50) DEFAULT NULL");
            }
        }
    }

    protected function hasColumn($table, $column){
        $r = $this->db->firstRaw(
            "SELECT COUNT(*) AS c FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]);
        return !empty($r['c']);
    }
};
