<?php
/**
 * TÀI LIỆU KHÁCH 05/11/2026 — hai mục cuối còn thiếu.
 *
 *   1. TUYỂN DỤNG (trang 1, "Yêu cầu")
 *      Không cần bảng mới: tuyển dụng là một danh mục bài viết, giống "Tin
 *      công ty" hay "Khuyến mãi". Migration chỉ TẠO SẴN danh mục đó cho mỗi
 *      gara, kèm một mục menu trỏ vào nó.
 *
 *      MỤC MENU TẠO RA Ở TRẠNG THÁI TẮT (`status` = 0).
 *      Bật sẵn thì website của mọi gara lập tức mọc thêm một mục "Tuyển dụng"
 *      dẫn vào trang trắng — chưa ai viết tin tuyển dụng nào cả. Trang trắng
 *      vẫn trả HTTP 200 nên không log nào báo, chỉ có khách của gara nhìn
 *      thấy. Gara nào thật sự tuyển người thì vào bật, một cái tích.
 *
 *      Menu dùng kiểu `news_cat` (có từ 000104) chứ không gõ URL tay: gara đổi
 *      slug danh mục thì menu tự đi theo.
 *
 *   2. TẢI VỀ — link trực tiếp và link gián tiếp (trang 1, "Yêu cầu công nghệ")
 *      Kèm Frontend mục 12: "đăng ký thành viên để download báo giá, khuyến
 *      mại" — đăng ký thành viên đã chạy từ lâu, nhưng chưa có gì để tải.
 *
 *      FILE KHÔNG NẰM TRONG public/.
 *      `storage/` đã bị .htaccess chặn khỏi web từ trước, nên mọi lượt tải đều
 *      buộc phải đi qua PHP. Đó là điều kiện để hai thứ sau có nghĩa:
 *        - đếm được lượt tải
 *        - chặn được người chưa đăng nhập (`require_login`)
 *      Để file trong public/assets/uploads thì ai cũng tải thẳng được bằng URL
 *      đoán ra, và cả hai thứ trên thành trang trí.
 *
 *      `token` là link TRỰC TIẾP: tải thẳng, bỏ qua kiểm tra đăng nhập, không
 *      tính vào lượt. Dùng để gửi riêng cho khách/đối tác qua email. Đổi token
 *      là thu hồi được link đã phát mà không phải xoá file.
 *
 * CHẠY LẠI ĐƯỢC: bảng có rồi thì bỏ qua; danh mục / menu đã tạo thì không tạo
 * trùng (dùng NOT EXISTS).
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        // ------------------------------------------------------ 1. Bảng tải về
        if (!$this->hasTable('downloads')){
            $this->run("
                CREATE TABLE `downloads` (
                    `id`            INT(11) NOT NULL AUTO_INCREMENT,
                    `garage_id`     INT(11) DEFAULT NULL,
                    `title`         VARCHAR(200) NOT NULL,
                    `slug`          VARCHAR(230) NOT NULL,
                    `description`   VARCHAR(500) DEFAULT NULL,

                    /* Đường dẫn TƯƠNG ĐỐI tính từ gốc dự án, luôn bắt đầu bằng
                       storage/downloads/ — ngoài tầm với của web server. */
                    `file_path`     VARCHAR(255) NOT NULL,
                    /* Tên gốc người dùng tải lên: chỉ dùng để ĐẶT TÊN file lúc
                       trả về cho người tải, không dùng để lưu trên đĩa. */
                    `file_name`     VARCHAR(200) NOT NULL,
                    `file_size`     INT(11) NOT NULL DEFAULT 0,
                    `mime`          VARCHAR(100) DEFAULT NULL,

                    /* Link trực tiếp: /tai-ve/<slug>?k=<token> */
                    `token`         VARCHAR(32) NOT NULL,

                    `require_login` TINYINT(1) NOT NULL DEFAULT 0,
                    `hits`          INT(11) NOT NULL DEFAULT 0,
                    `is_published`  TINYINT(1) NOT NULL DEFAULT 0,
                    `sort_order`    INT(11) NOT NULL DEFAULT 0,
                    `create_at`     DATETIME DEFAULT NULL,
                    `update_at`     DATETIME DEFAULT NULL,

                    PRIMARY KEY (`id`),
                    /* Slug chỉ cần duy nhất TRONG MỘT GARA: hai gara đều có
                       quyền đặt tên tệp là 'bang-gia-2026'. */
                    UNIQUE KEY `uq_dl_slug_gara` (`garage_id`, `slug`),
                    UNIQUE KEY `uq_dl_token` (`token`),
                    KEY `idx_dl_gara` (`garage_id`, `is_published`, `sort_order`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        /* ------------------------------ 1b. Đăng ký màn Tải về vào `modules`
         * Thanh menu trái và toàn bộ hệ phân quyền đọc từ bảng này: thiếu
         * dòng ở đây thì màn Tải về không hiện trên menu, và route() trả về
         * false nên không ai vào được — kể cả quản trị viên cao nhất.
         *
         * `chi_tan_phat` = 0: đây là nội dung website của TỪNG GARA, không
         * phải màn riêng của kho tổng.
         */
        if ($this->hasTable('modules')){
            $co = $this->db->query(
                'SELECT `id` FROM `modules` WHERE `link` = ? LIMIT 1', ['downloads']
            )->fetch(\PDO::FETCH_ASSOC);

            if (empty($co)){
                $this->db->query(
                    'INSERT INTO `modules` (`name`, `link`, `create_at`, `chi_tan_phat`)
                     VALUES (?, ?, NOW(), 0)',
                    ['Tệp tải về', 'downloads']
                );
            }
        }

        /* ----------------------------- 1c. Cấp quyền cho màn Tải về
         * Đăng ký vào `modules` thôi thì chưa ai vào được: route() hỏi bảng
         * `permissions`, không có dòng nào là trả false, và menu cũng không vẽ
         * mục đó ra. Không cấp ở đây thì sau khi triển khai, người quản trị
         * phải vào Nhóm > Phân quyền tích tay cho TỪNG nhóm của TỪNG gara —
         * và gần như chắc chắn sẽ nghĩ là màn hình bị lỗi.
         *
         * CẤP ĐÚNG THEO NHÓM NÀO ĐANG CÓ `galleries`, với đúng các vai trò
         * đó. Thư viện ảnh là màn gần nhau nhất về bản chất (kho tệp cho
         * khách) và ai được quản thư viện thì cũng được quản tài liệu. Chép
         * lại một quyết định đã đúng sẵn, thay vì tự nghĩ ra luật mới — và
         * nhờ thế gara nào cố tình KHÔNG cho nhóm Staff quản nội dung thì vẫn
         * giữ nguyên ý đó.
         */
        if ($this->hasTable('permissions') && $this->hasTable('modules')){
            $moduleId = $this->db->query(
                'SELECT `id` FROM `modules` WHERE `link` = ? LIMIT 1', ['downloads']
            )->fetch(\PDO::FETCH_ASSOC);
            $mid = !empty($moduleId) ? (int) $moduleId['id'] : 0;

            if ($mid > 0){
                /* Đọc hết ra PHP rồi mới ghi: MySQL không cho vừa SELECT vừa
                   INSERT trên cùng một bảng trong một câu lệnh. */
                $mau = $this->db->query(
                    'SELECT p.`group_id`, p.`role`
                       FROM `permissions` p
                       JOIN `modules` m ON m.`id` = p.`module_id`
                      WHERE m.`link` = ?', ['galleries']
                )->fetchAll(\PDO::FETCH_ASSOC);

                $daCo = [];
                foreach ((array) $this->db->query(
                            'SELECT `group_id`, `role` FROM `permissions` WHERE `module_id` = ?', [$mid]
                         )->fetchAll(\PDO::FETCH_ASSOC) as $r){
                    $daCo[$r['group_id'] . '|' . $r['role']] = true;
                }

                foreach ((array) $mau as $r){
                    $khoa = $r['group_id'] . '|' . $r['role'];
                    if (isset($daCo[$khoa])) continue;
                    $this->db->query(
                        'INSERT INTO `permissions` (`module_id`, `group_id`, `role`) VALUES (?, ?, ?)',
                        [$mid, (int) $r['group_id'], $r['role']]
                    );
                    $daCo[$khoa] = true;
                }
            }
        }

        // -------------------- 2+3. Danh mục "Tuyển dụng" + mục menu trỏ vào đó
        /*
         * PHẢI LÀM BẰNG PHP, KHÔNG LÀM ĐƯỢC BẰNG MỘT CÂU INSERT…SELECT.
         *
         * `news_categories`.`slug` có chỉ mục DUY NHẤT TOÀN HỆ THỐNG
         * (`uq_news_cat_slug`), không phải duy nhất theo từng gara. Nên ba gara
         * không thể cùng mang slug 'tuyen-dung' — câu INSERT hàng loạt chết
         * ngay ở gara thứ hai với lỗi 1062.
         *
         * Thêm hậu tố -2, -3 là đúng quy ước sẵn có của hệ thống: xem
         * Model::slugRanh(). (Ghi chú: Newscategories::postAdd() hiện gọi
         * slugify() thẳng chứ không qua slugRanh(), nên thêm tay một danh mục
         * trùng tên vẫn nổ lỗi SQL — việc khác, không sửa trong migration này.)
         */
        if ($this->hasTable('news_categories') && $this->hasTable('garages')){
            $garas = $this->db->query('SELECT `id` FROM `garages` ORDER BY `id`')
                              ->fetchAll(\PDO::FETCH_ASSOC);

            foreach ((array) $garas as $g){
                $gid = (int) $g['id'];

                // Gara này đã có danh mục tuyển dụng chưa? (theo TÊN, vì slug
                // có thể đã bị thêm hậu tố)
                $daCo = $this->db->query(
                    'SELECT `id` FROM `news_categories`
                      WHERE `garage_id` = ? AND `name` = ? LIMIT 1',
                    [$gid, 'Tuyển dụng']
                )->fetch(\PDO::FETCH_ASSOC);

                if (!empty($daCo)){
                    $catId = (int) $daCo['id'];
                } else {
                    // Tìm slug còn trống, đúng cách Model::slugRanh() làm
                    $slug = 'tuyen-dung';
                    for ($i = 2; $i <= 200; $i++){
                        $ban = $this->db->query(
                            'SELECT `id` FROM `news_categories` WHERE `slug` = ? LIMIT 1', [$slug]
                        )->fetch(\PDO::FETCH_ASSOC);
                        if (empty($ban)) break;
                        $slug = 'tuyen-dung-' . $i;
                    }

                    $this->db->query(
                        'INSERT INTO `news_categories`
                            (`name`, `slug`, `sort_order`, `status`, `create_at`, `garage_id`)
                         VALUES (?, ?, 90, 1, NOW(), ?)',
                        ['Tuyển dụng', $slug, $gid]
                    );

                    /* Lấy id bằng cách CHỌN LẠI THEO SLUG (slug là duy nhất
                       toàn hệ thống) thay vì lastInsertId(): không phải với
                       vào PDO bên trong lớp Database. */
                    $vua = $this->db->query(
                        'SELECT `id` FROM `news_categories` WHERE `slug` = ? LIMIT 1', [$slug]
                    )->fetch(\PDO::FETCH_ASSOC);
                    $catId = !empty($vua) ? (int) $vua['id'] : 0;
                }

                if ($catId <= 0 || !$this->hasTable('menus')) continue;

                $coMenu = $this->db->query(
                    'SELECT `id` FROM `menus`
                      WHERE `garage_id` = ? AND `link_type` = ? AND `target_id` = ? LIMIT 1',
                    [$gid, 'news_cat', $catId]
                )->fetch(\PDO::FETCH_ASSOC);

                if (empty($coMenu)){
                    $this->db->query(
                        "INSERT INTO `menus`
                            (`parent_id`, `label`, `link_type`, `target_id`, `url`,
                             `target`, `sort_order`, `status`, `create_at`, `garage_id`)
                         VALUES (NULL, ?, 'news_cat', ?, NULL, '_self', 90, 0, NOW(), ?)",
                        ['Tuyển dụng', $catId, $gid]
                    );
                }
            }
        }
    }

    public function down(){
        /* Gỡ theo đúng thứ tự ngược: menu trỏ vào danh mục nên phải đi trước.
           CHỈ gỡ mục menu CÒN TẮT — gara đã bật lên là đã dùng thật, xoá đi
           là xoá việc của người ta. */
        if ($this->hasTable('menus')){
            $this->run("
                DELETE m FROM `menus` m
                  JOIN `news_categories` nc ON nc.`id` = m.`target_id`
                 WHERE m.`link_type` = 'news_cat'
                   AND nc.`slug` = 'tuyen-dung'
                   AND m.`status` = 0
            ");
        }
        if ($this->hasTable('news_categories')){
            /* Danh mục đã có bài viết thì giữ lại — xoá là mất bài. */
            $this->run("
                DELETE nc FROM `news_categories` nc
                 WHERE nc.`slug` = 'tuyen-dung'
                   AND NOT EXISTS (SELECT 1 FROM `news` n WHERE n.`category_id` = nc.`id`)
            ");
        }
        if ($this->hasTable('permissions') && $this->hasTable('modules')){
            $this->db->query(
                'DELETE p FROM `permissions` p
                   JOIN `modules` m ON m.`id` = p.`module_id`
                  WHERE m.`link` = ?', ['downloads']);
        }
        if ($this->hasTable('modules')){
            $this->db->query('DELETE FROM `modules` WHERE `link` = ?', ['downloads']);
        }
        $this->run('DROP TABLE IF EXISTS `downloads`');
    }
};
