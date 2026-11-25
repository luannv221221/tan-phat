<?php
/**
 * TÊN MÀN HÌNH: "GARA" -> "CÔNG TY".
 *
 * Khách ghi 25/11/2026: "đổi gara thành công ty (gara chỉ là 1 việc của công
 * ty)"; anh chốt cùng ngày: đổi toàn bộ chữ trên giao diện. Phần chữ trong code
 * đã đổi cùng đợt; hai tên màn hình nằm trong bảng `modules` (menu trái và trang
 * Phân quyền đọc tên từ đây) nên phải đổi bằng migration.
 *
 * CHỈ ĐỔI khi tên còn đúng như bản gốc — ai đã tự đặt tên khác ở màn Quản lý
 * module thì giữ của họ. `link` (garages, garage-catalog) KHÔNG đổi: nó là đường
 * dẫn + khoá phân quyền.
 *
 * AN TOÀN KHI CHẠY TRƯỚC HAY SAU KHI ĐẨY CODE: chỉ đổi chữ hiển thị. Chạy lại
 * được.
 *
 * Chạy tay trong phpMyAdmin (nếu không chạy migrate.php):
 *
 *   UPDATE modules SET name = 'Quản lý công ty'      WHERE link = 'garages'        AND name = 'Quản lý gara';
 *   UPDATE modules SET name = 'Danh mục của công ty' WHERE link = 'garage-catalog' AND name = 'Danh mục của gara';
 *
 * Khong doi cau truc CSDL.
 */

use App\core\Migration;

return new class extends Migration {

    const DOI = [
        'garages'        => ['Quản lý gara', 'Quản lý công ty'],
        'garage-catalog' => ['Danh mục của gara', 'Danh mục của công ty'],
    ];

    public function up(){
        if (!$this->hasTable('modules')) return;
        foreach (self::DOI as $link => $ten){
            $this->db->query('UPDATE `modules` SET `name` = ? WHERE `link` = ? AND `name` = ?',
                             [$ten[1], $link, $ten[0]]);
        }
        echo "  Da doi ten man hinh gara -> cong ty (neu con ten goc).\n";
    }

    public function down(){
        if (!$this->hasTable('modules')) return;
        foreach (self::DOI as $link => $ten){
            $this->db->query('UPDATE `modules` SET `name` = ? WHERE `link` = ? AND `name` = ?',
                             [$ten[0], $link, $ten[1]]);
        }
    }
};
