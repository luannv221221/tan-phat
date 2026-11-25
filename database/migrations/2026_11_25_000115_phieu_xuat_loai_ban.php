<?php
/**
 * PHIẾU XUẤT MANG LOẠI 'ban' -> 'xuat_ban'.
 *
 * Đặt hàng nội bộ (gara đặt hàng kho tổng, DatHangNoiBoModel) ghi phiếu xuất ở
 * kho tổng với `issue_type` = 'ban' — mã không có trong GoodsIssuesModel::$types.
 * Danh sách phiếu xuất hiện chữ "ban" trần trụi thay vì "Xuất bán (giá vốn)",
 * mở sửa thì ô Loại nhảy về lựa chọn đầu tiên. Code đã sửa ghi 'xuat_ban';
 * migration này sửa các phiếu đã lỡ ghi.
 *
 * AN TOÀN KHI CHẠY TRƯỚC HAY SAU KHI ĐẨY CODE: chỉ đổi nhãn loại, không có logic
 * nào rẽ nhánh theo loại phiếu xuất. Chạy lại được.
 *
 * Chạy tay trong phpMyAdmin (nếu không chạy migrate.php):
 *
 *   UPDATE goods_issues SET issue_type = 'xuat_ban' WHERE issue_type = 'ban';
 *
 * Khong doi cau truc CSDL.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        if (!$this->hasTable('goods_issues')) return;
        $r = $this->db->query("SELECT COUNT(*) AS c FROM `goods_issues` WHERE `issue_type` = 'ban'")
                      ->fetch(\PDO::FETCH_ASSOC);
        $n = (int) $r['c'];
        if ($n === 0){ echo "  Khong co phieu xuat loai 'ban' — bo qua.\n"; return; }
        $this->db->query("UPDATE `goods_issues` SET `issue_type` = 'xuat_ban' WHERE `issue_type` = 'ban'");
        echo "  Da doi $n phieu xuat loai 'ban' -> 'xuat_ban'.\n";
    }

    /* Không hoàn tác: 'ban' là nhãn hỏng, không có lý do đưa về. */
    public function down(){}
};
