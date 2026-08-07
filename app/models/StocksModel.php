<?php

use App\core\Model;

/**
 * KHO — Engine tồn kho BÌNH QUÂN GIA QUYỀN tức thời.
 *
 * `stocks`      = số dư tồn tức thời theo (kho x phụ tùng).
 * `stock_cards` = sổ append-only; mỗi nhập/xuất 1 dòng, có số dư luỹ kế.
 *
 * ⚠️ Các hàm applyIn/applyOut/reverseDoc KHÔNG tự mở transaction — controller
 * bọc cả phiên ghi sổ trong 1 transaction để nguyên tử (tồn + thẻ kho + bút toán).
 */
class StocksModel extends Model {

    protected $_table   = 'stocks';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Dòng tồn hiện tại của (kho, phụ tùng) hoặc null */
    public function getRow($warehouseId, $partId){
        return $this->table($this->_table)
                    ->where('warehouse_id', '=', (int) $warehouseId)
                    ->where('part_id', '=', (int) $partId)
                    ->first();
    }

    /**
     * Như getRow() nhưng KHOÁ dòng tới hết transaction (SELECT ... FOR UPDATE).
     *
     * applyIn/applyOut là đọc-tính-ghi đè. Hai người ghi sổ cùng lúc cho cùng
     * một (kho, phụ tùng) mà không khoá thì cả hai đọc ra cùng số dư cũ, người
     * ghi sau đè mất phần của người ghi trước — tồn sai mà không ai biết.
     *
     * Ngoài transaction thì FOR UPDATE chỉ là câu SELECT bình thường, vô hại.
     */
    private function getRowForUpdate($warehouseId, $partId){
        $row = $this->firstRaw(
            'SELECT * FROM `stocks` WHERE `warehouse_id` = ? AND `part_id` = ? FOR UPDATE',
            [(int) $warehouseId, (int) $partId]
        );
        return !empty($row) ? $row : null;
    }

    /** Số lượng tồn hiện tại (0 nếu chưa có) */
    public function available($warehouseId, $partId){
        $r = $this->getRow($warehouseId, $partId);
        return $r ? (float) $r['quantity'] : 0.0;
    }

    /** Đơn giá bình quân hiện tại */
    public function avgCost($warehouseId, $partId){
        $r = $this->getRow($warehouseId, $partId);
        return $r ? (float) $r['avg_cost'] : 0.0;
    }

    /**
     * Bình quân của 1 phụ tùng ở BẤT KỲ kho nào đang còn hàng.
     *
     * Dùng khi kiểm kê phát hiện thừa một mã chưa từng có ở kho đó: lấy giá
     * vốn ở kho khác còn hơn là nhập vào với giá 0 rồi kéo bình quân về 0.
     * Bình quân theo lượng chứ không lấy đại một kho.
     */
    public function avgCostAnyWarehouse($partId){
        $r = $this->table($this->_table)
                  ->select('SUM(`quantity` * `avg_cost`) AS gia_tri, SUM(`quantity`) AS so_luong')
                  ->where('part_id', '=', (int) $partId)
                  ->where('quantity', '>', 0)
                  ->first();

        $sl = (float) ($r['so_luong'] ?? 0);
        return $sl > 0 ? round((float) $r['gia_tri'] / $sl, 2) : 0.0;
    }

    /** Ghi thẳng số dư stocks (upsert theo kho+phụ tùng) */
    private function setBalance($warehouseId, $partId, $qty, $avg){
        $now = date('Y-m-d H:i:s');
        $row = $this->getRow($warehouseId, $partId);
        if (empty($row)){
            $this->insert('stocks', [
                'warehouse_id' => (int) $warehouseId,
                'part_id'      => (int) $partId,
                'quantity'     => $qty,
                'avg_cost'     => $avg,
                'update_at'    => $now,
            ]);
        } else {
            $this->update('stocks',
                ['quantity' => $qty, 'avg_cost' => $avg, 'update_at' => $now],
                '`id` = ?', [(int) $row['id']]);
        }
    }

    /**
     * NHẬP: cộng tồn + cập nhật bình quân gia quyền, ghi 1 dòng thẻ kho.
     *   bq_mới = (SL_cũ*bq_cũ + SL_nhập*giá_nhập) / (SL_cũ + SL_nhập)
     */
    public function applyIn($warehouseId, $partId, $qty, $unitCost, $docType, $docId, $docNo, $date, $note = null){
        $this->chanLuiNgay($warehouseId, $partId, $date);

        $qty      = (float) $qty;
        $unitCost = (float) $unitCost;

        $r      = $this->getRowForUpdate($warehouseId, $partId);
        $oldQty = $r ? (float) $r['quantity'] : 0.0;
        $oldAvg = $r ? (float) $r['avg_cost'] : 0.0;

        $newQty = $oldQty + $qty;
        $newAvg = $newQty > 0 ? (($oldQty * $oldAvg) + ($qty * $unitCost)) / $newQty : 0.0;
        $newAvg = round($newAvg, 2);

        $this->setBalance($warehouseId, $partId, $newQty, $newAvg);
        $this->addCard($warehouseId, $partId, $date, $docType, $docId, $docNo,
                       $qty, 0, $unitCost, $newQty, round($newQty * $newAvg, 2), $note);
    }

    /**
     * XUẤT: trừ tồn theo bình quân hiện tại (bq KHÔNG đổi khi xuất).
     * @return float đơn giá bình quân đã dùng (giá vốn/đơn vị)
     */
    public function applyOut($warehouseId, $partId, $qty, $docType, $docId, $docNo, $date, $note = null){
        $this->chanLuiNgay($warehouseId, $partId, $date);

        $qty = (float) $qty;

        $r      = $this->getRowForUpdate($warehouseId, $partId);
        $oldQty = $r ? (float) $r['quantity'] : 0.0;
        $avg    = $r ? (float) $r['avg_cost'] : 0.0;

        $newQty = $oldQty - $qty;

        /* LƯỚI CUỐI chặn tồn âm.
         *
         * Controller nào cũng đã kiểm tồn trước khi ghi sổ, nhưng kiểm nằm
         * NGOÀI transaction: hai người bấm ghi sổ cùng lúc thì cả hai đều thấy
         * đủ tồn rồi cùng trừ. Chặn ở đây là chốt cuối — ném exception để
         * transaction của controller rollback, thay vì để tồn âm nằm im trong
         * CSDL (tồn âm không có nghĩa gì về nghiệp vụ và rất khó truy lại).
         *
         * Dung sai 1e-9 vì số lượng là DECIMAL(15,3), so bằng 0 tuyệt đối sẽ
         * vướng sai số dấu phẩy động.
         */
        if ($newQty < -1e-9){
            throw new \RuntimeException(
                'Ton kho khong du: kho ' . (int) $warehouseId . ', phu tung ' . (int) $partId
                . ' con ' . $oldQty . ' ma xuat ' . $qty
            );
        }

        $this->setBalance($warehouseId, $partId, $newQty, $avg);
        $this->addCard($warehouseId, $partId, $date, $docType, $docId, $docNo,
                       0, $qty, $avg, $newQty, round($newQty * $avg, 2), $note);
        return $avg;
    }

    /**
     * Ngày phát sinh gần nhất của (kho, phụ tùng), hoặc null nếu chưa có.
     * Lấy theo move_date lớn nhất chứ không theo id: thẻ ghi sau chưa chắc có
     * ngày muộn hơn.
     */
    public function lastMoveDate($warehouseId, $partId){
        $r = $this->table('stock_cards')
                  ->select('MAX(`move_date`) AS ngay')
                  ->where('warehouse_id', '=', (int) $warehouseId)
                  ->where('part_id', '=', (int) $partId)
                  ->first();

        return !empty($r['ngay']) ? substr($r['ngay'], 0, 10) : null;
    }

    /**
     * CHẶN GHI SỔ LÙI NGÀY.
     *
     * balance_qty trên thẻ là số dư luỹ kế theo THỨ TỰ GHI SỔ. Ghi sổ một phiếu
     * đề ngày cũ hơn phát sinh cuối cùng thì thẻ mới mang ngày cũ nhưng số dư
     * lại tính sau — mọi báo cáo cắt theo move_date (tồn đầu kỳ của thẻ kho,
     * biến động theo ngày) đọc ra số sai.
     *
     * Chốt 04/08/2026: chặn hẳn thay vì dựng lại số dư theo ngày.
     *
     * Gọi ở ĐẦU applyIn/applyOut, TRƯỚC khi đụng vào số dư. Đặt ở addCard()
     * là quá muộn: setBalance() chạy trước nó, nên ném lỗi ở đó sẽ để lại tồn
     * đã sửa mà không có thẻ kho tương ứng. Ngoài transaction thì trạng thái
     * hỏng đó nằm lại luôn.
     */
    private function chanLuiNgay($warehouseId, $partId, $date){
        $cuoi = $this->lastMoveDate($warehouseId, $partId);
        if ($cuoi !== null && substr($date, 0, 10) < $cuoi){
            throw new \RuntimeException(
                'Khong ghi so lui ngay duoc: phu tung ' . (int) $partId . ' o kho ' . (int) $warehouseId
                . ' da co phat sinh ngay ' . $cuoi . ', phieu nay de ngay ' . substr($date, 0, 10)
            );
        }
    }

    /**
     * Kiểm trước xem phiếu có bị lùi ngày không, để controller báo lỗi tử tế
     * thay vì để exception của addCard() bắn ra trang lỗi.
     *
     * @return array mô tả từng mã vướng; rỗng nghĩa là ghi sổ được
     */
    public function kiemLuiNgay($warehouseId, array $partIds, $date, $partModel = null){
        $ngay = substr($date, 0, 10);
        $loi  = [];

        foreach (array_unique(array_map('intval', $partIds)) as $pid){
            if ($pid <= 0) continue;
            $cuoi = $this->lastMoveDate($warehouseId, $pid);
            if ($cuoi !== null && $ngay < $cuoi){
                $ten = '#' . $pid;
                if ($partModel !== null){
                    $p = $partModel->getDetail($pid);
                    if (!empty($p)) $ten = $p['code'] . ' - ' . $p['name'];
                }
                $loi[] = $ten . ' (đã có phát sinh ngày ' . $cuoi . ')';
            }
        }
        return $loi;
    }

    private function addCard($warehouseId, $partId, $date, $docType, $docId, $docNo,
                             $qtyIn, $qtyOut, $unitCost, $balanceQty, $balanceValue, $note){


        $this->insert('stock_cards', [
            'warehouse_id'  => (int) $warehouseId,
            'part_id'       => (int) $partId,
            'move_date'     => $date,
            'doc_type'      => $docType,
            'doc_id'        => (int) $docId,
            'doc_no'        => $docNo,
            'qty_in'        => $qtyIn,
            'qty_out'       => $qtyOut,
            'unit_cost'     => $unitCost,
            'balance_qty'   => $balanceQty,
            'balance_value' => $balanceValue,
            'note'          => $note,
            'create_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Phiếu này có phải PHÁT SINH CUỐI CÙNG của (kho, phụ tùng)?
     * Chỉ khi đúng mới cho huỷ ghi sổ (bình quân gia quyền không đảo ngược được
     * nếu đã có nhập/xuất khác chen sau).
     */
    public function isLastMovement($warehouseId, $partId, $docType, $docId){
        $globalMax = $this->table('stock_cards')
            ->select('`id`')
            ->where('warehouse_id', '=', (int) $warehouseId)
            ->where('part_id', '=', (int) $partId)
            ->orderBy('id', 'DESC')->first();
        if (empty($globalMax)) return true; // không có thẻ nào -> không chặn

        $myMax = $this->table('stock_cards')
            ->select('`id`')
            ->where('warehouse_id', '=', (int) $warehouseId)
            ->where('part_id', '=', (int) $partId)
            ->where('doc_type', '=', $docType)
            ->where('doc_id', '=', (int) $docId)
            ->orderBy('id', 'DESC')->first();
        if (empty($myMax)) return true; // phiếu chưa ghi thẻ cho phụ tùng này

        return (int) $globalMax['id'] === (int) $myMax['id'];
    }

    /**
     * Đảo phát sinh của 1 phiếu cho (kho, phụ tùng): xoá thẻ kho của phiếu,
     * khôi phục tồn về số dư của thẻ liền trước. Giả định đã kiểm isLastMovement.
     */
    public function reverseDoc($warehouseId, $partId, $docType, $docId){
        $this->delete('stock_cards',
            '`warehouse_id` = ? AND `part_id` = ? AND `doc_type` = ? AND `doc_id` = ?',
            [(int) $warehouseId, (int) $partId, $docType, (int) $docId]);

        $prev = $this->table('stock_cards')
            ->where('warehouse_id', '=', (int) $warehouseId)
            ->where('part_id', '=', (int) $partId)
            ->orderBy('id', 'DESC')->first();

        if (empty($prev)){
            // Không còn phát sinh -> tồn 0
            $this->setBalance($warehouseId, $partId, 0, 0);
        } else {
            $qty = (float) $prev['balance_qty'];
            $val = (float) $prev['balance_value'];
            $avg = $qty > 0 ? round($val / $qty, 2) : 0.0;
            $this->setBalance($warehouseId, $partId, $qty, $avg);
        }
    }

    /**
     * Khoá mọi dòng tồn của các phụ tùng này tới hết transaction.
     *
     * Dùng khi cần "kiểm rồi mới ghi" mà không được để hai phiên chen nhau:
     * hai khách cùng đặt cái cuối cùng thì cả hai đều thấy còn hàng nếu không
     * khoá. Khoá xong phải ĐỌC LẠI tồn rồi mới quyết định.
     *
     * Chỉ có tác dụng khi đang trong transaction.
     */
    public function lockParts(array $partIds){
        $ids = array_values(array_unique(array_map('intval', $partIds)));
        if (empty($ids)) return;

        $holes = implode(',', array_fill(0, count($ids), '?'));
        $this->getRaw('SELECT `id` FROM `stocks` WHERE `part_id` IN (' . $holes . ') FOR UPDATE', $ids);
    }

    /** Tổng tồn của 1 phụ tùng trên MỌI kho (cho storefront — TASK_79) */
    public function totalByPart($partId){
        $r = $this->table($this->_table)
                  ->select('SUM(`quantity`) AS total')
                  ->where('part_id', '=', (int) $partId)->first();
        return (float) ($r['total'] ?? 0);
    }

    /**
     * Tồn KHẢ DỤNG bán = tổng tồn - tổng đang giữ (đơn web chưa xuất hoá đơn).
     * Dùng cho hiển thị tồn ở storefront để không bán quá phần đã giữ.
     */
    public function sellableByPart($partId){
        $total = $this->totalByPart($partId);
        $r = $this->table('stock_reservations')
                  ->select('SUM(`quantity`) AS total')
                  ->where('part_id', '=', (int) $partId)->first();
        $reserved = (float) ($r['total'] ?? 0);
        $avail = $total - $reserved;
        return $avail > 0 ? $avail : 0.0;
    }

    // ---------- Báo cáo ----------

    /** Tồn kho hiện tại kèm thông tin phụ tùng; lọc theo kho + từ khoá */
    public function getStockList($warehouseId = 0, $keyword = ''){
        $q = $this->table($this->_table)
            ->select('`stocks`.*, `parts`.`code` AS part_code, `parts`.`name` AS part_name, '
                   . '`parts`.`oem_code` AS oem_code, `part_units`.`name` AS unit_name, '
                   . '`warehouses`.`name` AS warehouse_name, `warehouses`.`code` AS warehouse_code')
            ->joinOn('parts', 'stocks.part_id', 'parts.id')
            ->joinOn('warehouses', 'stocks.warehouse_id', 'warehouses.id')
            ->leftJoinOn('part_units', 'parts.unit_id', 'part_units.id');

        if ($warehouseId > 0){
            $q = $q->where('stocks.warehouse_id', '=', (int) $warehouseId);
        }
        if ($keyword !== ''){
            $q = $q->where(function($sub) use ($keyword){
                $like = '%' . $keyword . '%';
                $sub->whereLike('parts.name', $like);
                $sub->whereOrLike('parts.code', $like);
                $sub->whereOrLike('parts.oem_code', $like);
            });
        }

        return $q->orderBy('parts.name', 'ASC')->get();
    }

    /** Thẻ kho 1 phụ tùng trong khoảng ngày (tăng dần để cộng dồn) */
    public function getCards($partId, $warehouseId = 0, $from = '', $to = ''){
        $q = $this->table('stock_cards')
            ->select('`stock_cards`.*, `warehouses`.`name` AS warehouse_name')
            ->joinOn('warehouses', 'stock_cards.warehouse_id', 'warehouses.id')
            ->where('stock_cards.part_id', '=', (int) $partId);

        if ($warehouseId > 0) $q = $q->where('stock_cards.warehouse_id', '=', (int) $warehouseId);
        if ($from !== '')     $q = $q->where('stock_cards.move_date', '>=', $from);
        if ($to !== '')       $q = $q->where('stock_cards.move_date', '<=', $to);

        return $q->orderBy('stock_cards.id', 'ASC')->get();
    }

    /**
     * KHO-3 — Hàng tồn lâu: mỗi dòng tồn (qty>0) kèm NGÀY PHÁT SINH GẦN NHẤT
     * và số ngày nằm kho tới $asOf. Lọc theo kho + ngưỡng tồn tối thiểu $minDays.
     * @return array các dòng, đã sắp số ngày giảm dần.
     */
    public function getAging($warehouseId = 0, $minDays = 0, $asOf = null){
        $asOf = $asOf ?: date('Y-m-d');

        $rows = $this->getStockList($warehouseId, '');
        $out = [];
        foreach ($rows ?: [] as $r){
            $qty = (float) $r['quantity'];
            if ($qty <= 0) continue;

            // Ngày phát sinh gần nhất của (kho, phụ tùng)
            $last = $this->table('stock_cards')
                ->select('`move_date`')
                ->where('warehouse_id', '=', (int) $r['warehouse_id'])
                ->where('part_id', '=', (int) $r['part_id'])
                ->orderBy('move_date', 'DESC')
                ->orderBy('id', 'DESC')->first();

            $lastDate = !empty($last) ? $last['move_date'] : null;
            $days = ($lastDate !== null) ? $this->daysBetween($lastDate, $asOf) : null;
            if ($days !== null && $days < 0) $days = 0;

            if ($minDays > 0 && ($days === null || $days < $minDays)) continue;

            $r['last_move_date'] = $lastDate;
            $r['days_idle']      = $days;
            $r['value']          = round($qty * (float) $r['avg_cost'], 2);
            $out[] = $r;
        }

        usort($out, function($a, $b){
            return ($b['days_idle'] ?? -1) <=> ($a['days_idle'] ?? -1);
        });
        return $out;
    }

    /**
     * KHO-3 — Biến động tồn theo ngày cho 1 phụ tùng (dựng biểu đồ).
     * Gộp nhập/xuất theo ngày + số dư cuối ngày (cộng dồn từ tồn đầu kỳ).
     * @return array ['opening'=>float, 'rows'=>[['date','in','out','balance'],...]]
     */
    public function getMovementByDay($partId, $warehouseId = 0, $from = '', $to = ''){
        $partId = (int) $partId;

        // Tồn đầu kỳ (trước 'from')
        $opening = 0.0;
        if ($from !== ''){
            if ($warehouseId > 0){
                $opening = $this->getBalanceBefore($partId, (int) $warehouseId, $from)['qty'];
            } else {
                $whs = $this->table('stock_cards')->select('DISTINCT `warehouse_id`')
                            ->where('part_id', '=', $partId)->get();
                foreach ($whs ?: [] as $w){
                    $opening += $this->getBalanceBefore($partId, (int) $w['warehouse_id'], $from)['qty'];
                }
            }
        }

        $q = $this->table('stock_cards')
                  ->select('`move_date`, `qty_in`, `qty_out`')
                  ->where('part_id', '=', $partId);
        if ($warehouseId > 0) $q = $q->where('warehouse_id', '=', (int) $warehouseId);
        if ($from !== '')     $q = $q->where('move_date', '>=', $from);
        if ($to !== '')       $q = $q->where('move_date', '<=', $to);
        $cards = $q->orderBy('move_date', 'ASC')->orderBy('id', 'ASC')->get();

        $byDay = [];
        foreach ($cards ?: [] as $c){
            $d = substr($c['move_date'], 0, 10);
            if (!isset($byDay[$d])) $byDay[$d] = ['in' => 0.0, 'out' => 0.0];
            $byDay[$d]['in']  += (float) $c['qty_in'];
            $byDay[$d]['out'] += (float) $c['qty_out'];
        }
        ksort($byDay);

        $rows = [];
        $bal  = $opening;
        foreach ($byDay as $d => $v){
            $bal += $v['in'] - $v['out'];
            $rows[] = ['date' => $d, 'in' => $v['in'], 'out' => $v['out'], 'balance' => round($bal, 3)];
        }
        return ['opening' => round($opening, 3), 'rows' => $rows];
    }

    /** Số ngày lịch giữa 2 mốc (chỉ tính phần ngày, tránh lệch do DST) */
    private function daysBetween($fromDate, $toDate){
        try {
            $d1 = new \DateTime(substr($fromDate, 0, 10));
            $d2 = new \DateTime(substr($toDate, 0, 10));
        } catch (\Exception $e){
            return null;
        }
        $days = (int) $d1->diff($d2)->days;
        return ($d2 < $d1) ? -$days : $days;
    }

    /**
     * Số dư (số lượng, giá trị) của phụ tùng NGAY TRƯỚC ngày $from — cho tồn đầu kỳ
     * của báo cáo thẻ kho. Lấy thẻ cuối cùng có move_date < $from.
     */
    public function getBalanceBefore($partId, $warehouseId, $from){
        if ($from === '') return ['qty' => 0.0, 'value' => 0.0];

        /* Kho = 0 nghĩa là "mọi kho" -> phải CỘNG số dư của từng kho.
         *
         * Bản cũ chỉ bỏ điều kiện warehouse_id rồi lấy thẻ mới nhất, mà
         * balance_qty là số dư luỹ kế RIÊNG của kho ghi thẻ đó — nên phụ tùng
         * nằm ở 2 kho sẽ trả về tồn của đúng một kho và coi đó là tổng.
         * Hiện Thekho tự chọn kho mặc định nên chưa lộ, nhưng bỏ mặc định đi
         * là sai ngay.
         */
        if ((int) $warehouseId <= 0){
            $whs = $this->table('stock_cards')->select('DISTINCT `warehouse_id`')
                        ->where('part_id', '=', (int) $partId)->get();

            $qty = 0.0; $val = 0.0;
            foreach ($whs ?: [] as $w){
                $r = $this->getBalanceBefore($partId, (int) $w['warehouse_id'], $from);
                $qty += $r['qty'];
                $val += $r['value'];
            }
            return ['qty' => $qty, 'value' => $val];
        }

        $row = $this->table('stock_cards')
            ->where('part_id', '=', (int) $partId)
            ->where('move_date', '<', $from)
            ->where('warehouse_id', '=', (int) $warehouseId)
            ->orderBy('id', 'DESC')->first();

        if (empty($row)) return ['qty' => 0.0, 'value' => 0.0];
        return ['qty' => (float) $row['balance_qty'], 'value' => (float) $row['balance_value']];
    }
}
