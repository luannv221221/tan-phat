<?php

use App\core\Model;

/**
 * SERIAL TỪNG CHIẾC HÀNG — tài liệu 29/10/2026.
 *
 * "Có tra cứu serial để biết hàng này mua từ ai, theo đơn nào, ngày mua phụ
 * tùng… mục đích để bảo hành phụ tùng. Các thông tin serial được lấy từ đơn
 * mua."
 *
 * MỘT DÒNG = MỘT CHIẾC. Số lượng 5 thì 5 dòng, chốt với khách 29/10/2026. Gộp
 * cả dòng vào một ô thì tra một serial ra cả năm chiếc, không biết chiếc nào —
 * mà cái cần biết khi khách mang hàng tới bảo hành chính là chiếc này.
 *
 * CHƯA THEO DÕI ĐÃ BÁN CHO AI: phạm vi tài liệu dừng ở nguồn mua. Mở rộng sau
 * thì thêm cột trỏ sang phiếu xuất, không phải dựng lại bảng.
 */
class PurchaseSerialsModel extends Model {

    protected $_table    = 'purchase_serials';
    protected $_fields   = '*';
    protected $_primary  = 'id';
    protected $_theoGara = true;

    /** Serial của một đơn mua, gom theo dòng hàng */
    public function getByPo($poId){
        return $this->bangGara()
            ->where('purchase_serials.po_id', '=', (int) $poId)
            ->orderBy('purchase_serials.poi_id', 'ASC')
            ->orderBy('purchase_serials.id', 'ASC')->get();
    }

    /**
     * TRA CỨU — đây là việc chính của màn Quản lý serial.
     *
     * Trả về đủ thứ cần cho việc bảo hành: mặt hàng gì, mua của ai, theo đơn
     * nào, ngày nào, và phiếu nhập kho tương ứng nếu đã nhập.
     *
     * TÌM KHỚP ĐÚNG TRƯỚC, GẦN ĐÚNG SAU. Người ở quầy gõ nguyên chuỗi serial
     * đọc trên tem thì phải ra đúng một dòng; gõ mấy ký tự cuối (tem mờ, đọc
     * không hết) thì vẫn tìm được. Chỉ LIKE không thì gõ đủ serial vẫn có thể
     * ra nhiều dòng, mà lúc đó người dùng đang cần một câu trả lời dứt khoát.
     */
    public function traCuu($tuKhoa, $gioiHan = 50){
        $tuKhoa = trim((string) $tuKhoa);
        if ($tuKhoa === '') return [];

        /* THỨ TỰ THAM SỐ PHẢI ĐÚNG THỨ TỰ DẤU `?` TRONG CÂU, không phải thứ tự
           mình nghĩ ra. Dấu `?` ĐẦU TIÊN nằm ở SELECT (cột khop_dung), rồi mới
           tới garage_id ở WHERE, cuối cùng là LIKE.

           Bản đầu nạp [gara, chính xác, gần đúng] theo đúng trình tự suy nghĩ
           — và thành ra `garage_id = 'ZZSN-002'`, tra ra 0 kết quả. Không lỗi
           SQL nào, chỉ là không thấy gì: đúng kiểu hỏng mà nhìn như "chưa có
           dữ liệu". Thêm điều kiện vào câu này thì nhớ đếm lại dấu `?`. */
        list($dk, $bdGara) = $this->dkGara('ps');
        $bd = array_merge([$tuKhoa], $bdGara, ['%' . $tuKhoa . '%']);

        return (array) $this->getRaw(
            'SELECT `ps`.*, `p`.`code` AS part_code, `p`.`name` AS part_name,
                    `pu`.`name` AS unit_name,
                    `po`.`po_no`, `po`.`order_date`, `po`.`invoice_no`,
                    `po`.`partner_name`, `pa`.`name` AS partner_full,
                    `gr`.`receipt_no` AS pnk_no,
                    (`ps`.`serial` = ?) AS khop_dung
               FROM `purchase_serials` `ps`
               JOIN `parts` `p`            ON `p`.`id`  = `ps`.`part_id`
          LEFT JOIN `part_units` `pu`      ON `pu`.`id` = `p`.`unit_id`
               JOIN `purchase_orders` `po` ON `po`.`id` = `ps`.`po_id`
          LEFT JOIN `partners` `pa`        ON `pa`.`id` = `po`.`partner_id`
          LEFT JOIN `goods_receipts` `gr`  ON `gr`.`id` = `po`.`goods_receipt_id`
              WHERE ' . $dk . ' AND `ps`.`serial` LIKE ?
              ORDER BY `khop_dung` DESC, `po`.`order_date` DESC, `ps`.`id` DESC
              LIMIT ' . (int) $gioiHan, $bd);
    }

    /** Danh sách mới nhất — màn Quản lý serial lúc chưa gõ gì */
    public function moiNhat($gioiHan = 200){
        list($dk, $bd) = $this->dkGara('ps');
        return (array) $this->getRaw(
            'SELECT `ps`.*, `p`.`code` AS part_code, `p`.`name` AS part_name,
                    `po`.`po_no`, `po`.`order_date`, `po`.`partner_name`,
                    `pa`.`name` AS partner_full, `gr`.`receipt_no` AS pnk_no
               FROM `purchase_serials` `ps`
               JOIN `parts` `p`            ON `p`.`id`  = `ps`.`part_id`
               JOIN `purchase_orders` `po` ON `po`.`id` = `ps`.`po_id`
          LEFT JOIN `partners` `pa`        ON `pa`.`id` = `po`.`partner_id`
          LEFT JOIN `goods_receipts` `gr`  ON `gr`.`id` = `po`.`goods_receipt_id`
              WHERE ' . $dk . '
              ORDER BY `ps`.`id` DESC
              LIMIT ' . (int) $gioiHan, $bd);
    }

    /**
     * Ghi serial cho một đơn: xoá hết của đơn đó rồi ghi lại.
     *
     * @param array $rows [['poi_id'=>, 'part_id'=>, 'serial'=>, 'note'=>], ...]
     * @return array danh sách serial BỊ TỪ CHỐI vì trùng, kèm lý do
     *
     * TRẢ VỀ DANH SÁCH TRÙNG CHỨ KHÔNG NÉM LỖI. Serial trùng là gõ nhầm — việc
     * thường gặp khi nhập hai chục cái tem liền tay. Ném lỗi thì cả đơn không
     * lưu được và người dùng mất hết những gì vừa gõ; trả về danh sách thì
     * controller lưu được phần đúng và chỉ đúng mấy dòng hỏng.
     */
    public function syncForPo($poId, array $rows){
        $poId = (int) $poId;
        $gara = self::garaLoc();

        $this->delete($this->_table, '`po_id` = ?', [$poId]);

        $tuChoi = [];
        $daGhi  = [];
        $now    = date('Y-m-d H:i:s');

        foreach ($rows as $r){
            $sn = isset($r['serial']) ? trim((string) $r['serial']) : '';
            if ($sn === '') continue;                       // ô trống -> chưa có tem, bỏ qua
            $sn = mb_substr($sn, 0, 100);

            /* Trùng NGAY TRONG đơn đang nhập — bắt trước khi đụng tới CSDL để
               thông báo nói rõ là trùng trong chính đơn này. */
            $khoa = mb_strtoupper($sn, 'UTF-8');
            if (isset($daGhi[$khoa])){
                $tuChoi[] = ['serial' => $sn, 'ly_do' => 'trùng ngay trong đơn này'];
                continue;
            }

            /* Trùng với đơn KHÁC của cùng gara — chỉ mục uq_serial_gara sẽ chặn,
               nhưng hỏi trước để báo được TÊN ĐƠN kia, chứ không phải một dòng
               "1062 Duplicate entry" mà người dùng không hiểu gì. */
            $co = $this->firstRaw(
                'SELECT `po`.`po_no` FROM `purchase_serials` `ps`
                   JOIN `purchase_orders` `po` ON `po`.`id` = `ps`.`po_id`
                  WHERE `ps`.`garage_id` <=> ? AND `ps`.`serial` = ? LIMIT 1',
                [$gara, $sn]);
            if (!empty($co['po_no'])){
                $tuChoi[] = ['serial' => $sn, 'ly_do' => 'đã có ở đơn ' . $co['po_no']];
                continue;
            }

            $this->addNew([
                'po_id'   => $poId,
                'poi_id'  => !empty($r['poi_id']) ? (int) $r['poi_id'] : null,
                'part_id' => (int) $r['part_id'],
                'serial'  => $sn,
                'note'    => isset($r['note']) && $r['note'] !== '' ? mb_substr($r['note'], 0, 255) : null,
                'create_at' => $now,
            ]);
            $daGhi[$khoa] = true;
        }
        return $tuChoi;
    }

    public function demTheoPo($poId){
        $r = $this->firstRaw('SELECT COUNT(*) AS c FROM `purchase_serials` WHERE `po_id` = ?',
                             [(int) $poId]);
        return (int) (isset($r['c']) ? $r['c'] : 0);
    }
}
