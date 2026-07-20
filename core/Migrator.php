<?php
/**
 * Migrator — chạy/rollback migration, theo dõi bằng bảng `migrations`.
 *
 * Vì sao cần (nguyên nhân đổ vỡ #4 trong sheet Help — "không thống nhất mô hình"):
 * 60 bảng, nhiều người làm, không có cơ chế version DB thì mỗi máy một schema.
 */

namespace App\core;

class Migrator {

    /** @var Database */
    protected $db;

    protected $path;

    /** Thông điệp để CLI in ra */
    protected $output = [];

    public function __construct(Database $db, $path = null){
        $this->db   = $db;
        $this->path = $path ?: dirname(__DIR__) . '/database/migrations';
    }

    public function output(){ return $this->output; }

    protected function say($msg){ $this->output[] = $msg; }

    /**
     * Tạo bảng `migrations` nếu chưa có.
     * Dùng cú pháp chạy được trên cả MySQL lẫn SQLite để test được.
     */
    public function ensureTable(){
        $driver = $this->db->pdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite'){
            $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
                        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                        `migration` VARCHAR(255) NOT NULL,
                        `batch` INTEGER NOT NULL,
                        `ran_at` DATETIME NOT NULL
                    )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `migration` VARCHAR(255) NOT NULL,
                        `batch` INT NOT NULL,
                        `ran_at` DATETIME NOT NULL,
                        UNIQUE KEY `uq_migration` (`migration`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }

        $this->db->query($sql);
    }

    /** Danh sách file migration trên đĩa, sắp theo tên (tên có tiền tố ngày nên = thứ tự chạy) */
    public function filesOnDisk(){
        if (!is_dir($this->path)) return [];

        $files = glob($this->path . '/*.php');
        $names = array_map(function($f){
            return basename($f, '.php');
        }, $files);

        sort($names, SORT_STRING);
        return $names;
    }

    /**
     * Danh sách migration đã chạy.
     * Tự tạo bảng `migrations` nếu chưa có — nếu không, gọi hàm này trên DB trắng
     * sẽ lỗi "table doesn't exist".
     */
    public function ranMigrations(){
        $this->ensureTable();

        $rows = $this->db->getRaw("SELECT `migration` FROM `migrations` ORDER BY `id` ASC");
        return array_column($rows, 'migration');
    }

    /** Migration chưa chạy */
    public function pending(){
        return array_values(array_diff($this->filesOnDisk(), $this->ranMigrations()));
    }

    protected function nextBatch(){
        $row = $this->db->firstRaw("SELECT MAX(`batch`) AS b FROM `migrations`");
        return (int)($row['b'] ?? 0) + 1;
    }

    /** Nạp file migration, trả về object Migration */
    protected function resolve($name){
        $file = $this->path . '/' . $name . '.php';

        if (!is_file($file)){
            throw new \RuntimeException("Khong tim thay file migration: $file");
        }

        $migration = require $file;

        if (!$migration instanceof Migration){
            throw new \RuntimeException("File $name phai `return` mot object ke thua App\\core\\Migration");
        }

        return $migration->setDb($this->db);
    }

    /**
     * Chạy các migration đang chờ.
     *
     * LƯU Ý: KHÔNG bọc DDL trong transaction — MySQL tự động commit ngầm
     * khi gặp CREATE/ALTER/DROP, nên transaction ở đây là ảo giác an toàn.
     * Nếu một migration lỗi giữa chừng, phải sửa tay rồi chạy lại.
     */
    public function up(){
        $this->ensureTable();

        $pending = $this->pending();

        if (empty($pending)){
            $this->say('Khong co migration nao dang cho. DB da moi nhat.');
            return 0;
        }

        $batch = $this->nextBatch();
        $count = 0;

        foreach ($pending as $name){
            $this->say("Dang chay: $name");

            $migration = $this->resolve($name);
            $migration->up();

            $this->db->insert('migrations', [
                'migration' => $name,
                'batch'     => $batch,
                'ran_at'    => date('Y-m-d H:i:s'),
            ]);

            $this->say("   OK: $name");
            $count++;
        }

        $this->say("Xong. Da chay $count migration (batch $batch).");
        return $count;
    }

    /** Rollback batch gần nhất */
    public function rollback(){
        $this->ensureTable();

        $row = $this->db->firstRaw("SELECT MAX(`batch`) AS b FROM `migrations`");
        $batch = (int)($row['b'] ?? 0);

        if ($batch === 0){
            $this->say('Chua co migration nao de rollback.');
            return 0;
        }

        $rows = $this->db->getRaw(
            "SELECT `migration` FROM `migrations` WHERE `batch` = ? ORDER BY `id` DESC",
            [$batch]
        );

        $count = 0;
        foreach ($rows as $r){
            $name = $r['migration'];
            $this->say("Dang rollback: $name");

            $migration = $this->resolve($name);
            $migration->down();

            $this->db->delete('migrations', '`migration` = ?', [$name]);

            $this->say("   OK: $name");
            $count++;
        }

        $this->say("Xong. Da rollback $count migration (batch $batch).");
        return $count;
    }

    /** Trạng thái từng migration */
    public function status(){
        $this->ensureTable();

        $ran   = $this->ranMigrations();
        $files = $this->filesOnDisk();

        if (empty($files)){
            $this->say('Chua co file migration nao trong ' . $this->path);
            return [];
        }

        $result = [];
        foreach ($files as $f){
            $done = in_array($f, $ran, true);
            $result[$f] = $done;
            $this->say(sprintf('  [%s] %s', $done ? 'DA CHAY' : '  CHO  ', $f));
        }

        // Migration da chay nhung file khong con tren dia -> canh bao
        $missing = array_diff($ran, $files);
        foreach ($missing as $m){
            $this->say("  [!! ] $m — da chay nhung KHONG con file tren dia");
        }

        return $result;
    }

    /** Sinh file migration mới */
    public function make($name){
        if (!is_dir($this->path)) @mkdir($this->path, 0775, true);

        $slug = preg_replace('/[^a-z0-9_]+/', '_', strtolower($name));
        $file = date('Y_m_d_His') . '_' . $slug . '.php';
        $full = $this->path . '/' . $file;

        $stub = <<<'PHP'
<?php

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("
            -- Viet lenh CREATE/ALTER o day
        ");
    }

    public function down(){
        $this->run("
            -- Dao nguoc lai up()
        ");
    }
};
PHP;

        file_put_contents($full, $stub);
        $this->say("Da tao: database/migrations/$file");

        return $full;
    }
}
