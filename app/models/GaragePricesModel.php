<?php

use App\core\Model;

/**
 * GIÁ RIÊNG CỦA GARA — bảng `garage_part_prices`.
 *
 * Một dòng ở đây mang HAI nghĩa cùng lúc:
 *   1. "Gara này CÓ LÀM mặt hàng đó"   -> nó xuất hiện trong nguồn "Gara hiện tại"
 *   2. "...với giá này"                -> đè lên giá của danh mục tổng
 *
 * Cột `price` để NULL nghĩa là "vẫn làm, nhưng lấy giá tổng". Phân biệt rõ với
 * 0 — 0 là một mức giá hợp lệ (kiểm tra miễn phí, hạng mục khuyến mãi). Lẫn hai
 * thứ này thì hạng mục miễn phí sẽ âm thầm nhảy về giá gốc.
 */
class GaragePricesModel extends Model {

    protected $_table   = 'garage_part_prices';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Mọi mặt hàng gara đã chọn: [part_id => dòng giá] */
    public function theoGara($garageId){
        $rows = $this->table($this->_table)
                     ->where('garage_id', '=', (int) $garageId)
                     ->get();
        $map = [];
        foreach ($rows ?: [] as $r) $map[(int) $r['part_id']] = $r;
        return $map;
    }

    public function mot($garageId, $partId){
        return $this->table($this->_table)
                    ->where('garage_id', '=', (int) $garageId)
                    ->where('part_id', '=', (int) $partId)
                    ->first();
    }

    /**
     * Chọn mặt hàng vào danh mục gara, kèm giá riêng (hoặc null = theo giá tổng).
     *
     * Có rồi thì cập nhật, chưa có thì thêm — không dùng `INSERT ... ON DUPLICATE
     * KEY UPDATE` để câu lệnh sinh ra vẫn đọc được ở mọi bản MySQL.
     */
    public function datGia($garageId, $partId, $gia = null, $giaKM = null, $status = 1){
        $co = $this->mot($garageId, $partId);
        $data = [
            'price'      => $this->soHoacNull($gia),
            'sale_price' => $this->soHoacNull($giaKM),
            'status'     => (int) $status,
        ];

        if (!empty($co)){
            $data['update_at'] = date('Y-m-d H:i:s');
            $this->updateById($data, $co['id']);
            return (int) $co['id'];
        }

        $data['garage_id'] = (int) $garageId;
        $data['part_id']   = (int) $partId;
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        return (int) $this->lastId();
    }

    /** Bỏ mặt hàng khỏi danh mục gara (không đụng tới danh mục tổng) */
    public function boChon($garageId, $partId){
        return $this->delete($this->_table, '`garage_id` = ? AND `part_id` = ?',
                             [(int) $garageId, (int) $partId]);
    }

    public function demTheoGara($garageId){
        $r = $this->table($this->_table)->select('COUNT(*) AS c')
                  ->where('garage_id', '=', (int) $garageId)->first();
        return !empty($r['c']) ? (int) $r['c'] : 0;
    }

    /**
     * Chuỗi rỗng -> NULL (theo giá tổng); còn lại -> số.
     *
     * Người dùng để trống ô giá nghĩa là "theo giá tổng", KHÁC hẳn với gõ số 0.
     * Ép (float) thẳng thì "" thành 0.0 và hàng nào bỏ trống cũng thành miễn phí.
     */
    private function soHoacNull($v){
        if ($v === null) return null;
        // Cùng cách đọc tiền với Quotations::parseMoney(): bỏ mọi thứ không
        // phải chữ số, nên "1.500.000" và "1,500,000" đều ra 1500000.
        $so = preg_replace('/[^\d]/', '', (string) $v);
        return $so === '' ? null : (float) $so;
    }
}
