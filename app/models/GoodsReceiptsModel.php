<?php

use App\core\Model;

/**
 * KHO — Phiếu nhập kho (đầu chứng từ).
 *
 * Loại: nhap_mua (có công nợ NCC) / nhap_khac / nhap_tra (nhập trả lại).
 * status: 0 nháp / 1 đã ghi sổ. Ghi sổ -> cập nhật tồn + sinh bút toán (acc_voucher_id).
 */
class GoodsReceiptsModel extends Model {

    protected $_table   = 'goods_receipts';
    protected $_fields  = '*';
    protected $_primary = 'id';
    protected $_theoGara = true;   // RIÊNG từng gara; số phiếu đánh riêng trong mỗi gara

    public static $types = [
        'nhap_mua'   => 'Nhập mua',
        'nhap_khac'  => 'Nhập khác',
        'nhap_tra'   => 'Nhập trả lại',
        /* TỒN KHO ĐẦU KỲ (12/10/2026) — số gara đang có lúc bắt đầu dùng phần
           mềm. Là phiếu nhập để đi qua đúng bộ máy kho đang có, và để ĐẢO
           NGƯỢC ĐƯỢC khi lỡ import hai lần. Xem controller Tonkhodauky. */
        'ton_dau_ky' => 'Tồn kho đầu kỳ',
    ];

    /**
     * Loại phiếu người dùng TỰ CHỌN được ở form Thêm phiếu nhập.
     *
     * `ton_dau_ky` KHÔNG nằm trong này: nó chỉ sinh ra từ màn Tồn kho đầu kỳ,
     * nơi có các chốt riêng (mỗi kho một lần, mặt hàng chưa có phát sinh). Cho
     * chọn tay ở đây là đi vòng qua đúng những chốt đó — mà vẫn mang nhãn "Tồn
     * kho đầu kỳ" nên nhìn vào tưởng là đã qua.
     *
     * Vẫn giữ trong $types để danh sách và bộ lọc hiện ĐÚNG TÊN loại phiếu.
     */
    public static function loaiTuChon(){
        $r = self::$types;
        unset($r['ton_dau_ky']);
        return $r;
    }

    /** Phiếu tồn đầu kỳ của một kho (bất kể trạng thái) — để chặn lập hai lần */
    public function tonDauKyCuaKho($warehouseId){
        return $this->bangGara()
                    ->where('receipt_type', '=', 'ton_dau_ky')
                    ->where('warehouse_id', '=', (int) $warehouseId)
                    ->orderBy('id', 'DESC')->first();
    }

    /**
     * TIỀN HÀNG ĐÃ NHẬP MUA từ từng nhà cung cấp, để tính "gara còn nợ nhà cung
     * cấp bao nhiêu" (báo cáo kế toán, tài liệu 14/10/2026).
     *
     * CHỈ loại `nhap_mua` và CHỈ phiếu ĐÃ GHI SỔ. Hai điều kiện đều cần:
     *   - Phiếu nháp chưa phải khoản phải trả — tính vào là gara tưởng mình nợ
     *     một món chưa từng nhập.
     *   - `nhap_khac` / `nhap_tra` / `ton_dau_ky` KHÔNG sinh công nợ: nhập khác
     *     là điều chỉnh nội bộ, nhập trả là hàng trả lại, tồn đầu kỳ là số dư
     *     lúc mở sổ. Gộp chúng vào là gara "nợ" chính mình.
     *
     * @return array [partner_id => ['ten' => , 'tien' => , 'so_pn' => ]]
     */
    public function tienMuaTheoNcc($den = null){
        list($dkGara, $bd) = $this->dkGara('goods_receipts');
        $sql = 'SELECT `goods_receipts`.`partner_id` AS `pid`,
                       `partners`.`name` AS `ten`, `partners`.`code` AS `ma`,
                       `partners`.`phone` AS `dt`,
                       COALESCE(SUM(`goods_receipts`.`total_amount`), 0) AS `tien`,
                       COUNT(*) AS `so_pn`
                  FROM `goods_receipts`
                  JOIN `partners` ON `partners`.`id` = `goods_receipts`.`partner_id`
                 WHERE ' . $dkGara . ' AND `goods_receipts`.`status` = 1
                   AND `goods_receipts`.`receipt_type` = ?';
        $bd[] = 'nhap_mua';
        if ($den !== null){ $sql .= ' AND `goods_receipts`.`receipt_date` <= ?'; $bd[] = $den; }

        $ra = [];
        foreach ((array) $this->getRaw($sql . ' GROUP BY `goods_receipts`.`partner_id`', $bd) as $r){
            $ra[(int) $r['pid']] = ['ten' => $r['ten'], 'ma' => $r['ma'], 'dt' => $r['dt'],
                                    'tien' => (float) $r['tien'], 'so_pn' => (int) $r['so_pn']];
        }
        return $ra;
    }

    /** Mọi phiếu tồn đầu kỳ của gara, mới nhất trước */
    public function dsTonDauKy(){
        return $this->bangGara()
            ->select('`goods_receipts`.*, `warehouses`.`name` AS warehouse_name')
            ->joinOn('warehouses', 'goods_receipts.warehouse_id', 'warehouses.id')
            ->where('goods_receipts.receipt_type', '=', 'ton_dau_ky')
            ->orderBy('goods_receipts.id', 'DESC')->get();
    }

    public function getLists($type = '', $from = '', $to = ''){
        $q = $this->bangGara()
            ->select('`goods_receipts`.*, `warehouses`.`name` AS warehouse_name, '
                   . '`partners`.`name` AS partner_full')
            ->joinOn('warehouses', 'goods_receipts.warehouse_id', 'warehouses.id')
            ->leftJoinOn('partners', 'goods_receipts.partner_id', 'partners.id');

        if ($type !== '') $q = $q->where('goods_receipts.receipt_type', '=', $type);
        if ($from !== '') $q = $q->where('goods_receipts.receipt_date', '>=', $from);
        if ($to !== '')   $q = $q->where('goods_receipts.receipt_date', '<=', $to);

        return $q->orderBy('goods_receipts.receipt_date', 'DESC')
                 ->orderBy('goods_receipts.id', 'DESC')->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    public function findByNo($no){
        return $this->bangGara()->where('receipt_no', '=', $no)->first();
    }

    /** Sinh số phiếu kế tiếp PNK-000001 */
    public function nextNo(){
        $row = $this->bangGara()
                    ->select('`receipt_no`')
                    ->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['receipt_no'], $m)){
            $n = (int) $m[1];
        }
        return 'PNK-' . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
    }

    public function add($data){
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        return $this->lastId();
    }

    public function edit($data, $id){
        $data['update_at'] = date('Y-m-d H:i:s');
        return $this->updateById($data, $id);
    }

    public function remove($id){
        return $this->deleteById($id); // items CASCADE
    }
}
