<?php
/**
 * Lớp cha cho mọi migration.
 *
 * Mỗi file migration trả về một anonymous class kế thừa lớp này:
 *
 *   <?php
 *   use App\core\Migration;
 *
 *   return new class extends Migration {
 *       public function up(){
 *           $this->run("CREATE TABLE `xe` (...)");
 *       }
 *       public function down(){
 *           $this->run("DROP TABLE `xe`");
 *       }
 *   };
 *
 * Dùng anonymous class để không phải nghĩ tên class cho 60 bảng
 * và không bao giờ bị trùng tên.
 */

namespace App\core;

abstract class Migration {

    /** @var Database */
    protected $db;

    public function setDb(Database $db){
        $this->db = $db;
        return $this;
    }

    /** Chạy khi migrate lên */
    abstract public function up();

    /** Chạy khi rollback. Phải đảo ngược đúng những gì up() làm. */
    abstract public function down();

    /**
     * Chạy một câu lệnh SQL thô (DDL).
     *
     * Dùng cho CREATE/ALTER/DROP — những lệnh không có tham số người dùng.
     * KHÔNG dùng hàm này với dữ liệu từ bên ngoài.
     */
    protected function run($sql){
        return $this->db->query($sql);
    }

    /** Kiểm tra bảng đã tồn tại chưa (hữu ích khi viết migration cho DB đang chạy) */
    protected function hasTable($table){
        try {
            $this->db->query("SELECT 1 FROM `$table` LIMIT 1");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
