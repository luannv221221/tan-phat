<?php
/**
 * DỌN CẤU HÌNH GARA BỊ CHÉP NHẦM TỪ CẤU HÌNH CHUNG.
 *
 * Đi cùng bản sửa Settings::chiKhacMacDinh() (29/10/2026). Bản sửa đó chặn
 * việc chép nhầm TỪ GIỜ TRỞ ĐI; migration này dọn phần đã chép trước đó.
 *
 * CHUYỆN GÌ ĐÃ XẢY RA. Form Cấu hình cố ý điền sẵn giá trị đã trộn (riêng đè
 * lên chung) để gara nhìn là biết mình đang thừa hưởng gì. Nhưng lúc Lưu thì
 * mọi ô đều được gửi lên, kể cả ô gara chưa hề đụng tới — nên một lần bấm Lưu
 * là chép nguyên nhận diện của Tân Phát thành giá trị RIÊNG của gara: số tài
 * khoản ngân hàng, chủ tài khoản, mã số thuế, email.
 *
 * Trước 26/10/2026 mấy dòng đó nằm im vì biểu mẫu in không đọc
 * `garage_settings`. Từ khi cau_hinh_in_an() đọc nó, hoá đơn của gara bắt đầu
 * in số tài khoản của Tân Phát — khách chuyển tiền theo tờ đó là chuyển nhầm
 * người. Đó là lý do phải dọn chứ không để đó.
 *
 * QUY TẮC DỌN: xoá dòng riêng nào có giá trị ĐÚNG BẰNG Cấu hình chung.
 *
 * AN TOÀN TUYỆT ĐỐI, và đây là điểm đáng nói nhất: xoá một dòng như vậy KHÔNG
 * LÀM ĐỔI thứ gara đang nhìn thấy. GarageSettingsModel::map() lấy chung làm
 * nền rồi đè phần riêng — phần riêng bằng đúng phần nền thì có hay không cũng
 * ra cùng một kết quả. Khác biệt duy nhất: từ nay gara đó THỪA HƯỞNG ô đó
 * thật, nên Tân Phát sửa Cấu hình chung là họ theo, thay vì đứng yên ở một bản
 * chụp chép nhầm.
 *
 * KHÔNG đụng tới dòng gara khai KHÁC mặc định — đó là thứ họ cố ý đặt.
 *
 * CHẠY LẠI ĐƯỢC: lần hai không còn dòng nào trùng, xoá 0 dòng.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        if (!$this->hasTable('garage_settings') || !$this->hasTable('site_settings')){
            echo "  (chua co bang cau hinh, bo qua)\n"; return;
        }

        $chung = [];
        foreach ((array) $this->db->getRaw("SELECT `skey`, `svalue` FROM `site_settings`") as $r){
            $chung[$r['skey']] = (string) $r['svalue'];
        }
        if (empty($chung)){ echo "  (Cau hinh chung trong, khong co gi de so)\n"; return; }

        $rieng = (array) $this->db->getRaw("SELECT `id`, `garage_id`, `skey`, `svalue` FROM `garage_settings`");
        $xoa = [];
        foreach ($rieng as $r){
            $k = $r['skey'];
            if (!array_key_exists($k, $chung)) continue;          // khong co mac dinh -> giu
            if ((string) $r['svalue'] !== $chung[$k]) continue;   // khai khac -> co y, giu
            $xoa[] = (int) $r['id'];
        }

        if (empty($xoa)){
            echo "  Khong co dong nao trung Cau hinh chung — khong phai don gi.\n"; return;
        }

        /* Đếm riêng mấy khoá NHẠY CẢM để in ra cho người chạy thấy mức độ: đây
           là những ô mà chép nhầm thì ra giấy tờ sai người nhận tiền. */
        $nhayCam = ['bank_account', 'bank_holder', 'bank_name', 'tax_code', 'site_name', 'email'];
        $demNhay = 0;
        foreach ($rieng as $r){
            if (in_array((int) $r['id'], $xoa, true) && in_array($r['skey'], $nhayCam, true)) $demNhay++;
        }

        foreach (array_chunk($xoa, 200) as $lo){
            $this->db->query("DELETE FROM `garage_settings` WHERE `id` IN (" . implode(',', $lo) . ")");
        }

        echo "  Da don " . count($xoa) . " dong cau hinh gara trung voi Cau hinh chung.\n";
        if ($demNhay > 0){
            echo "  Trong do $demNhay dong thuoc nhom nhay cam (so tai khoan / MST / ten / email)\n"
               . "  — day chinh la may dong tung lam hoa don cua gara in nhan dien Tan Phat.\n";
        }
        echo "  Gara khong thay gi doi: o nao thua huong thi van ra dung gia tri do.\n";
    }

    /**
     * KHÔNG quay lui.
     *
     * Dựng lại mấy dòng đã xoá nghĩa là chép lại nhận diện Tân Phát xuống gara
     * — đúng cái lỗi vừa dọn. Mà kết quả gara nhìn thấy cũng không đổi, nên
     * quay lui chỉ có hại, không có lợi.
     */
    public function down(){
        echo "  (khong quay lui: dung lai may dong do chinh la chep nham lan nua)\n";
    }
};
