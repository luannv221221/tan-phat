<?php
/**
 * HAI YÊU CẦU CỦA KHÁCH NGÀY 12/11/2026 — phần phải sửa trong CSDL.
 *
 *   1. "Đổi tin tức thành bài viết"  -> đổi TÊN hai module trên menu.
 *   2. "Phần cấu hình chung, những thứ liên quan đến website đẩy hết lên
 *      Quản lý website"              -> thêm module `cau-hinh-website`.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  1. ĐỔI TÊN, KHÔNG ĐỔI `link`
 * ──────────────────────────────────────────────────────────────────────────
 *
 * `modules`.`name` là chữ hiện trên menu; `modules`.`link` là KHOÁ PHÂN QUYỀN
 * và là tên tuyến (/admin/news). RoleMiddleware nhận ra màn đang mở bằng cách
 * so đầu đường dẫn với `link`, còn `permissions` thì trỏ vào `module_id`.
 *
 * Nên đổi `name` là việc an toàn tuyệt đối — không một dòng quyền nào lung
 * lay. Đổi `link` mới là chuyện khác: đường dẫn cũ chết, và mọi chỗ gọi
 * route('admin/news/...') trong controller lẫn view đều trỏ vào hư không.
 * Bảng `news`, `news_categories` cũng giữ nguyên tên.
 *
 * CHỈ ĐỔI KHI TÊN VẪN LÀ TÊN CŨ. Màn Quản lý module cho sửa tên, nên trên
 * máy chủ thật có thể đã có người đặt tên khác. Đè lên là xoá mất ý của họ.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  2. MÀN CẤU HÌNH WEBSITE PHẢI CÓ DÒNG MODULE MỚI MỌC RA ĐƯỢC
 * ──────────────────────────────────────────────────────────────────────────
 *
 * Menu chỉ vẽ những `link` có trong bảng `modules` VÀ nhóm của người dùng có
 * quyền. Thêm mỗi tuyến với view mà quên dòng này thì màn mới không hiện ra
 * cho bất kỳ ai, kể cả Admin — mà cũng không báo lỗi gì.
 *
 * CHÉP QUYỀN TỪ `settings`, KHÔNG TỰ NGHĨ RA DANH SÁCH NHÓM. Mấy ô này vừa
 * nằm trong màn Cấu hình chung sáng nay: ai đang sửa được chúng thì sau khi
 * tách vẫn phải sửa được, không hơn không kém. Nhóm nào không được đụng vào
 * cấu hình thì nay cũng không — tự cấp thêm là lặng lẽ nới quyền cho người
 * mà chủ gara đã cố ý không cho.
 *
 * CHẠY LẠI ĐƯỢC: có rồi thì bỏ qua.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        if (!$this->hasTable('modules')) return;

        $this->doiTenModule();
        $this->themManCauHinhWebsite();
    }

    /** "Tin tức" -> "Bài viết" trên menu quản trị. */
    protected function doiTenModule(){

        $doi = [
            'news'            => ['cu' => 'Quản lý tin tức', 'moi' => 'Quản lý bài viết'],
            'news-categories' => ['cu' => 'Danh mục tin',    'moi' => 'Danh mục bài viết'],
        ];

        foreach ($doi as $link => $ten){
            $this->db->query(
                'UPDATE `modules` SET `name` = ? WHERE `link` = ? AND `name` = ?',
                [$ten['moi'], $link, $ten['cu']]
            );
        }
    }

    /** Dòng module cho màn Cấu hình website + chép quyền từ `settings`. */
    protected function themManCauHinhWebsite(){

        $LINK = 'cau-hinh-website';

        $da = $this->db->query('SELECT `id` FROM `modules` WHERE `link` = ? LIMIT 1', [$LINK])
                       ->fetch(\PDO::FETCH_ASSOC);
        if (!empty($da)) return;   // chạy lại -> thôi

        /* Lấy `chi_tan_phat` của `settings` chứ không gán cứng 0: cờ này quyết
           định module có hiện cho gara thường hay chỉ cho kho tổng, và màn mới
           phải theo đúng màn mà nó tách ra. */
        $goc = $this->db->query('SELECT `id`, `chi_tan_phat` FROM `modules` WHERE `link` = ? LIMIT 1', ['settings'])
                        ->fetch(\PDO::FETCH_ASSOC);
        if (empty($goc)) return;

        $this->db->query(
            'INSERT INTO `modules` (`name`, `link`, `create_at`, `chi_tan_phat`) VALUES (?, ?, NOW(), ?)',
            ['Cấu hình website', $LINK, (int) $goc['chi_tan_phat']]
        );

        $moi = $this->db->query('SELECT `id` FROM `modules` WHERE `link` = ? LIMIT 1', [$LINK])
                        ->fetch(\PDO::FETCH_ASSOC);
        if (empty($moi)) return;

        if (!$this->hasTable('permissions')) return;

        /* Đọc hết ra PHP rồi mới ghi: MySQL không cho vừa SELECT vừa INSERT
           trên cùng một bảng trong một câu lệnh. */
        $quyen = (array) $this->db->query(
            'SELECT `group_id`, `role` FROM `permissions` WHERE `module_id` = ?', [(int) $goc['id']]
        )->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($quyen as $r){
            $this->db->query(
                'INSERT INTO `permissions` (`module_id`, `group_id`, `role`) VALUES (?, ?, ?)',
                [(int) $moi['id'], (int) $r['group_id'], $r['role']]
            );
        }
    }

    public function down(){

        /* ĐỔI TÊN THÌ TRẢ LẠI ĐƯỢC — chỉ là chữ, và cũng chỉ trả khi tên hiện
           tại đúng là tên migration này đặt. */
        $this->db->query('UPDATE `modules` SET `name` = ? WHERE `link` = ? AND `name` = ?',
                         ['Quản lý tin tức', 'news', 'Quản lý bài viết']);
        $this->db->query('UPDATE `modules` SET `name` = ? WHERE `link` = ? AND `name` = ?',
                         ['Danh mục tin', 'news-categories', 'Danh mục bài viết']);

        /* MÀN CẤU HÌNH WEBSITE THÌ GỠ HẲN, kèm quyền của nó. Khác với migration
           000108 (ở đó không phân biệt được dòng quyền nào do migration thêm):
           ở đây mọi dòng quyền đều trỏ vào module này, mà module này do chính
           migration tạo ra — gỡ đi là sạch, không cướp quyền của ai trên màn
           nào khác. Gỡ xong, các ô website quay về màn Cấu hình chung. */
        $m = $this->db->query('SELECT `id` FROM `modules` WHERE `link` = ? LIMIT 1', ['cau-hinh-website'])
                      ->fetch(\PDO::FETCH_ASSOC);
        if (empty($m)) return;

        if ($this->hasTable('permissions')){
            $this->db->query('DELETE FROM `permissions` WHERE `module_id` = ?', [(int) $m['id']]);
        }
        $this->db->query('DELETE FROM `modules` WHERE `id` = ?', [(int) $m['id']]);
    }
};
