<?php
/**
 * Gán ảnh minh hoạ đã commit vào CSDL: danh mục, băng-rôn, ảnh sản phẩm.
 *
 * VÌ SAO CẦN MIGRATION NÀY
 * Ảnh được tải về bằng tools/tai-anh-commons.php và tools/tai-anh-san-pham.php.
 * Hai script đó ghi THẲNG vào CSDL của máy đang chạy. File ảnh thì đã commit
 * (có ngoại lệ riêng trong .gitignore), nhưng CÁC DÒNG CSDL trỏ tới chúng thì
 * không — mà CSDL không nằm trong repo.
 *
 * Hậu quả nếu thiếu file này: clone repo về máy khác, dựng CSDL từ bản gốc rồi
 * chạy migration — ảnh nằm sẵn trong thư mục nhưng không bản ghi nào trỏ tới,
 * cả trang lại về mấy ô xám. Chạy lại script cũng KHÔNG ra đúng mấy tấm này:
 * kết quả tìm kiếm của Wikimedia Commons đổi theo từng lượt gọi.
 *
 * Nên phần gán ảnh phải được ghim cứng ở đây.
 *
 * AN TOÀN KHI CHẠY LẠI
 *   - Danh mục: chỉ gán khi ô ảnh đang TRỐNG. Ai đã thay ảnh khác thì giữ.
 *   - Băng-rôn: chỉ thêm khi chưa có dòng nào trỏ tới đúng file đó.
 *   - Sản phẩm: chỉ thay các dòng `*-demo.svg`. Ảnh thật do người dùng tải lên
 *     (vd ắc quy GS) không bị đụng tới.
 *   - Thiếu file trên đĩa thì bỏ qua, không ghi đường dẫn chết vào CSDL.
 */

use App\core\Migration;

return new class extends Migration {

    private $thuMucDanhMuc = 'public/assets/uploads/categories/';
    private $thuMucBangRon = 'public/assets/uploads/banners/';
    private $thuMucSanPham = 'public/assets/uploads/parts/';

    /** slug danh mục => tên file */
    private $anhDanhMuc = [
        'ac-quy' => 'ac-quy-9bf23468.jpg',
        'bao-duong' => 'bao-duong-6e376f11.jpg',
        'bugi' => 'bugi-3578a708.jpg',
        'cau-nang' => 'cau-nang-7974edb6.jpg',
        'dau-phanh' => 'dau-phanh-284fc9de.jpg',
        'day-curoa' => 'day-curoa-99688f22.jpg',
        'den-xe' => 'den-xe-cf2e47d6.jpg',
        'dia-phanh' => 'dia-phanh-5ec052ee.jpg',
        'dich-vu' => 'dich-vu-44102cac.jpg',
        'dong-co' => 'dong-co-c399a467.jpg',
        'giam-xoc' => 'giam-xoc-b89b66c5.jpg',
        'he-thong-treo' => 'he-thong-treo-ac364df9.jpg',
        'loc-dau' => 'loc-dau-083c1199.jpg',
        'loc-gio' => 'loc-gio-886426af.jpg',
        'ma-phanh' => 'ma-phanh-e76b407f.jpg',
        'may-phat' => 'may-phat-966f7e37.jpg',
        'phu-tung' => 'phu-tung-04202103.jpg',
        'thiet-bi' => 'thiet-bi-427a09b0.jpg',
    ];

    /** [tiêu đề, tên file, thứ tự] */
    private $bangRon = [
        ['Phụ tùng & thiết bị chính hãng cho mọi dòng xe', 'bn-1-0d9ce52b.jpg', 1],
        ['Trung tâm dịch vụ tiêu chuẩn hãng', 'bn-2-ec5ba1b4.jpg', 2],
        ['Gara Tân Phát — kỹ thuật tận tâm', 'bn-3-846b47c5.jpg', 3],
    ];

    /** mã hàng => danh sách tên file (cái đầu là ảnh chính) */
    private $anhSanPham = [
        'PT-0001' => ['ma-phanh-truoc-toyota-vios-pt-0001-7490922b.jpg'],
        'PT-0002' => ['ma-phanh-sau-toyota-camry-pt-0002-47d1c128.jpg'],
        'PT-0003' => ['dia-phanh-truoc-vios-pt-0003-217fd1f5.jpg'],
        'PT-0004' => ['loc-dau-dong-co-toyota-pt-0004-58487a46.jpg'],
        'PT-0005' => ['loc-gio-dong-co-vios-pt-0005-e49d1df7.jpg'],
        'PT-0006' => ['bugi-iridium-ngk-pt-0006-c0c099f0.jpg'],
        'PT-0007' => ['day-curoa-cam-toyota-pt-0007-afd40dd7.jpg'],
        'PT-0008' => ['ac-quy-gs-45ah-pt-0008-02ef907b1b.gif'],
        'PT-0009' => ['den-pha-toyota-vios-led-pt-0009-4792f9ae.jpg'],
        'PT-0010' => ['may-phat-dien-honda-city-pt-0010-55227b3a.jpg'],
        'PT-0011' => ['giam-xoc-truoc-mazda-cx-5-pt-0011-39e8cebc.jpg'],
        'PT-0013' => ['loc-dau-honda-cr-v-pt-0013-a10f68e9.jpg'],
        'PT-0014' => ['loc-gio-honda-city-pt-0014-48b1e98a.jpg'],
        'PT-0015' => ['bugi-ngk-laser-kia-pt-0015-a53bd8bb.jpg'],
        'PT-0016' => ['ma-phanh-truoc-ford-ranger-pt-0016-1a7aa360.jpg'],
        'Test' => ['test-e10f64d6bf.jpg', 'test-31494bab32.jpg', 'test-066d6ff97f.jpg'],
    ];

    public function up(){
        $goc = dirname(dirname(__DIR__)) . '/';
        $now = date('Y-m-d H:i:s');
        $dm = $br = $sp = 0;

        // ---- Danh mục: chỉ điền vào ô đang trống ----
        foreach ($this->anhDanhMuc as $slug => $file){
            if (!is_file($goc . $this->thuMucDanhMuc . $file)) continue;

            $r = $this->db->table('part_categories')->where('slug', '=', $slug)->first();
            if (empty($r)) continue;
            if (!empty($r['image'])) continue;          // ai đó đã đặt ảnh khác -> giữ

            $this->db->update('part_categories',
                ['image' => $this->thuMucDanhMuc . $file], '`id` = ?', [$r['id']]);
            $dm++;
        }

        // ---- Băng-rôn: chỉ thêm cái chưa có ----
        foreach ($this->bangRon as $b){
            list($tieuDe, $file, $thuTu) = $b;
            if (!is_file($goc . $this->thuMucBangRon . $file)) continue;

            $duong = $this->thuMucBangRon . $file;
            $co = $this->db->table('banners')->where('image', '=', $duong)->first();
            if (!empty($co)) continue;

            $this->db->insert('banners', [
                'title' => $tieuDe, 'image' => $duong, 'link' => '',
                'sort_order' => $thuTu, 'status' => 1, 'create_at' => $now,
            ]);
            $br++;
        }

        // ---- Ảnh sản phẩm: chỉ thay dòng demo ----
        foreach ($this->anhSanPham as $ma => $files){
            $p = $this->db->table('parts')->where('code', '=', $ma)->first();
            if (empty($p)) continue;

            $dang = $this->db->getRaw("SELECT `id`, `image` FROM `part_images` WHERE `part_id` = ?", [$p['id']]);

            // Có ảnh THẬT rồi (người dùng tự tải lên) -> không đụng vào
            $chiDemo = !empty($dang);
            foreach ($dang ?: [] as $a){
                if (!preg_match('~-demo\.svg$~i', $a['image'])) $chiDemo = false;
            }
            if (!$chiDemo) continue;

            $conFile = [];
            foreach ($files as $f){
                if (is_file($goc . $this->thuMucSanPham . $f)) $conFile[] = $f;
            }
            if (empty($conFile)) continue;              // thiếu file -> giữ nguyên ảnh demo

            $this->db->delete('part_images', '`part_id` = ?', [$p['id']]);
            $i = 0;
            foreach ($conFile as $f){
                $i++;
                $this->db->insert('part_images', [
                    'part_id' => $p['id'], 'image' => $f,
                    'sort_order' => $i, 'is_primary' => $i === 1 ? 1 : 0,
                    'create_at' => $now,
                ]);
            }
            $sp++;
        }

        echo "  Gan anh: $dm danh muc, $br bang-ron, $sp mat hang.\n";
    }

    public function down(){
        $goc = dirname(dirname(__DIR__)) . '/';

        // Gỡ đúng những gì up() đặt vào, không xoá file trên đĩa.
        foreach ($this->anhDanhMuc as $slug => $file){
            $this->db->update('part_categories', ['image' => null],
                '`slug` = ? AND `image` = ?', [$slug, $this->thuMucDanhMuc . $file]);
        }
        foreach ($this->bangRon as $b){
            $this->db->delete('banners', '`image` = ?', [$this->thuMucBangRon . $b[1]]);
        }
        foreach ($this->anhSanPham as $ma => $files){
            $p = $this->db->table('parts')->where('code', '=', $ma)->first();
            if (empty($p)) continue;
            foreach ($files as $f){
                $this->db->delete('part_images', '`part_id` = ? AND `image` = ?', [$p['id'], $f]);
            }
        }
        echo "  Da go phan gan anh (file tren dia giu nguyen).\n";
    }
};
