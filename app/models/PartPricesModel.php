<?php

use App\core\Model;
use App\core\Load;

/**
 * BẢNG TÍNH GIÁ — giá vốn + giá bán theo từng nhóm khách (yêu cầu khách 11/11/2026).
 *
 *      giá bán = (giá vốn + chi phí) × (1 + hệ số lãi của nhóm)
 *
 * Với chi phí = 0 (mặc định) thì ra đúng bằng con số trong sơ đồ khách vẽ:
 * gạt mưa giá vốn 1,2tr, nhóm Lẻ hệ số 20%  ->  1,2tr × 20% + 1,2tr = 1,44tr.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  GIÁ VỐN LẤY TỪ ĐÂU — VÀ VÌ SAO KHÔNG CÓ Ô ĐỂ GÕ
 * ──────────────────────────────────────────────────────────────────────────
 * Khách ghi rõ "giá vốn không được tùy chỉnh". Ở hệ này điều đó vốn đã đúng:
 * giá vốn không phải một ô trong màn Hàng hoá mà là KẾT QUẢ của việc nhập kho.
 *
 *   binh_quan  — bình quân gia quyền tức thời của gara, cộng qua mọi kho còn
 *                hàng (StocksModel::avgCostAnyWarehouse). Đây là cách kho
 *                chạy từ ngày đầu và là con số mọi phiếu xuất đang dùng.
 *   gan_nhat   — đơn giá của phiếu nhập ĐÃ GHI SỔ gần nhất.
 *
 * ⚠️ `parts`.`price` KHÔNG PHẢI GIÁ VỐN — trong hệ này nó là giá bán niêm
 *    yết (và `sale_price` là giá khuyến mãi), cả hai gõ tay ở màn Hàng hoá.
 *    PurchaseOrdersModel::giaNhapGanNhat() có rơi về `parts`.`price` khi chưa
 *    từng nhập — chấp nhận được ở chỗ đó vì thà có một con số còn hơn nhập kho
 *    với giá vốn 0. Ở ĐÂY THÌ KHÔNG: bảng giá mà lấy giá bán làm giá vốn rồi
 *    cộng thêm 20% là mỗi lần bấm "Tính lại giá" giá lại trèo lên một bậc.
 *    Chưa nhập bao giờ thì giá vốn = 0 và dòng đó hiện "chưa có giá vốn".
 */
class PartPricesModel extends Model {

    protected $_table    = 'part_prices';
    protected $_fields   = '*';
    protected $_primary  = 'id';
    protected $_theoGara = true;

    /** Hai kiểu tính giá vốn khách chốt — không nhận kiểu thứ ba */
    const KIEU = ['binh_quan' => 'Bình quân gia quyền', 'gan_nhat' => 'Giá nhập gần nhất'];

    /** Chu kỳ đặt lịch tính lại giá */
    const LICH = ['tat' => 'Không tự chạy', 'ngay' => 'Hằng ngày',
                  'tuan' => 'Hằng tuần', 'thang' => 'Hằng tháng'];

    // ----------------------------------------------------------------- cấu hình

    /** Kiểu tính giá vốn của gara đang làm việc (mặc định bình quân = cách kho vẫn chạy) */
    public function kieuGiaVon(){
        $v = (string) Load::model('GarageSettingsModel')->val('cost_method', 'binh_quan');
        return isset(self::KIEU[$v]) ? $v : 'binh_quan';
    }

    public function chuKyLich(){
        $v = (string) Load::model('GarageSettingsModel')->val('price_schedule', 'tat');
        return isset(self::LICH[$v]) ? $v : 'tat';
    }

    // ------------------------------------------------------------------ giá vốn

    /**
     * Giá vốn của một mặt hàng theo kiểu đang cấu hình. 0 = chưa từng nhập kho.
     */
    public function giaVon($partId, $kieu = null){
        $kieu = $kieu !== null ? $kieu : $this->kieuGiaVon();

        if ($kieu === 'gan_nhat') return $this->giaNhapGanNhat($partId);

        return (float) Load::model('StocksModel')->avgCostAnyWarehouse((int) $partId);
    }

    /**
     * Đơn giá của phiếu nhập ĐÃ GHI SỔ gần nhất, trong gara làm việc.
     *
     * Khác PurchaseOrdersModel::giaNhapGanNhat() ở một điểm: KHÔNG rơi về
     * `parts`.`price`. Lý do ở khối chú thích đầu file.
     */
    public function giaNhapGanNhat($partId){
        /* dkGara('gr') CHỨ KHÔNG PHẢI dkGara('goods_receipts'): câu dưới đặt
           bí danh `gr` cho bảng nên điều kiện cũng phải mang bí danh đó. */
        list($dk, $bd) = $this->dkGara('gr');
        $bd[] = (int) $partId;

        $r = $this->firstRaw(
            'SELECT `gri`.`unit_cost`
               FROM `goods_receipt_items` `gri`
               JOIN `goods_receipts` `gr` ON `gr`.`id` = `gri`.`receipt_id`
              WHERE ' . $dk . ' AND `gr`.`status` = 1 AND `gri`.`part_id` = ?
                AND `gri`.`unit_cost` > 0
              ORDER BY `gr`.`receipt_date` DESC, `gr`.`id` DESC
              LIMIT 1', $bd);

        return !empty($r['unit_cost']) ? (float) $r['unit_cost'] : 0.0;
    }

    /**
     * CÔNG THỨC GIÁ BÁN — một chỗ duy nhất.
     *
     * Màn hình, lúc tính lại, và test đều gọi hàm này. Viết lại công thức ở
     * nơi thứ hai là có ngày bảng giá hiện một số còn báo giá lấy một số khác.
     */
    public function tinhGiaBan($giaVon, $chiPhi, $heSo){
        $giaVon = max(0, (float) $giaVon);
        $chiPhi = max(0, (float) $chiPhi);
        $heSo   = (float) $heSo;
        if ($heSo < -100) $heSo = -100;   // bán dưới 0 đồng thì không còn là giá

        return round(($giaVon + $chiPhi) * (1 + $heSo / 100), 0);
    }

    // ------------------------------------------------------------- tính lại giá

    /**
     * TÍNH LẠI TOÀN BỘ bảng giá của gara đang làm việc.
     *
     * Mỗi (mặt hàng × nhóm khách đang bật) một dòng. Mặt hàng chưa từng nhập
     * kho vẫn được ghi dòng với giá vốn 0 — để màn hình chỉ ra được đúng
     * những mã đang thiếu giá vốn, thay vì chúng lặng lẽ vắng mặt.
     *
     * @return array ['dong' => số dòng đã ghi, 'thieu_gia_von' => số mã giá vốn 0]
     */
    public function tinhLai(){
        $kieu = $this->kieuGiaVon();
        $now  = date('Y-m-d H:i:s');
        $gid  = self::garaLoc();

        $nhom = Load::model('CustomerGroupsModel')->getActive();
        if (empty($nhom)) return ['dong' => 0, 'thieu_gia_von' => 0];

        $hang = $this->hangCuaGara()
                     ->select('`id`, `cost_extra`')
                     ->where('status', '=', 1)
                     ->get();
        if (empty($hang)) return ['dong' => 0, 'thieu_gia_von' => 0];

        $dong = 0; $thieu = 0;

        foreach ((array) $hang as $h){
            $pid    = (int) $h['id'];
            $giaVon = $this->giaVon($pid, $kieu);
            $chiPhi = (float) ($h['cost_extra'] ?? 0);
            if ($giaVon <= 0) $thieu++;

            foreach ((array) $nhom as $n){
                $heSo = (float) ($n['markup_percent'] ?? 0);
                $gia  = $this->tinhGiaBan($giaVon, $chiPhi, $heSo);

                $this->ghiMotDong($gid, $pid, (int) $n['id'], $giaVon, $heSo, $chiPhi,
                                  $gia, $kieu, $now);
                $dong++;
            }
        }

        Load::model('GarageSettingsModel')->saveMany(['price_last_run' => $now]);

        return ['dong' => $dong, 'thieu_gia_von' => $thieu];
    }

    /**
     * Upsert một dòng giá.
     *
     * Viết tay thay vì INSERT ... ON DUPLICATE KEY vì `garage_id` có thể NULL
     * (kho tổng), mà trong MySQL `NULL = NULL` là không bằng — chỉ mục duy
     * nhất không bắt được dòng trùng, nên ON DUPLICATE KEY sẽ chèn thêm mãi.
     */
    private function ghiMotDong($gid, $partId, $groupId, $giaVon, $heSo, $chiPhi,
                                $giaBan, $kieu, $now){
        $du = [
            'cost_price'     => $giaVon,
            'markup_percent' => $heSo,
            'cost_extra'     => $chiPhi,
            'sale_price'     => $giaBan,
            'method'         => $kieu,
            'calculated_at'  => $now,
        ];

        $cu = $this->bangGara()
                   ->select('`id`')
                   ->where('part_id', '=', $partId)
                   ->where('group_id', '=', $groupId)
                   ->first();

        if (!empty($cu)){
            $this->update($this->_table, $du, '`id` = ?', [(int) $cu['id']]);
            return;
        }

        $du['garage_id'] = $gid;
        $du['part_id']   = $partId;
        $du['group_id']  = $groupId;
        $this->insert($this->_table, $du);
    }

    /**
     * Chạy lại nếu đã tới hạn theo lịch khách đặt (ngày / tuần / tháng).
     *
     * KHÔNG CÓ CRON trên hosting này, nên lịch chạy theo kiểu "ai mở màn cần
     * giá thì kiểm hộ một cái". Rẻ: một lần đọc cấu hình và một phép so ngày.
     *
     * @return bool có chạy lại hay không
     */
    public function chayNeuDenHan(){
        $chuKy = $this->chuKyLich();
        if ($chuKy === 'tat') return false;

        $lan = (string) Load::model('GarageSettingsModel')->val('price_last_run', '');
        if ($lan === '') { $this->tinhLai(); return true; }

        $moc = strtotime($lan);
        if ($moc === false) { $this->tinhLai(); return true; }

        $han = ['ngay' => 86400, 'tuan' => 7 * 86400, 'thang' => 30 * 86400];
        if (time() - $moc < $han[$chuKy]) return false;

        $this->tinhLai();
        return true;
    }

    // -------------------------------------------------------------- đọc ra dùng

    /**
     * [part_id => giá bán] của MỘT nhóm khách — cho màn lập báo giá / hoá đơn
     * điền sẵn đơn giá theo loại khách đang chọn.
     */
    public function mapTheoNhom($groupId){
        $groupId = (int) $groupId;
        if ($groupId <= 0) return [];

        $rows = $this->bangGara()
                     ->select('`part_id`, `sale_price`')
                     ->where('group_id', '=', $groupId)
                     ->get();

        $map = [];
        foreach ((array) $rows as $r){
            $gia = (float) $r['sale_price'];
            if ($gia > 0) $map[(int) $r['part_id']] = $gia;   // 0 = chưa có giá vốn, đừng đè giá niêm yết
        }
        return $map;
    }

    /**
     * [group_id => [part_id => giá bán]] — CẢ BẢNG trong một câu truy vấn.
     *
     * Màn lập báo giá không biết trước người dùng sẽ chọn khách nào, nên phải
     * mang sẵn giá của mọi nhóm xuống trình duyệt. Hỏi lại máy chủ mỗi lần đổi
     * khách thì mỗi lần đổi là một vòng chờ mạng, ngay giữa lúc đang nhập liệu.
     */
    public function mapMoiNhom(){
        $rows = $this->bangGara()
                     ->select('`part_id`, `group_id`, `sale_price`')
                     ->get();

        $map = [];
        foreach ((array) $rows as $r){
            $gia = (float) $r['sale_price'];
            if ($gia <= 0) continue;   // 0 = chưa có giá vốn, đừng đè giá niêm yết
            $map[(int) $r['group_id']][(int) $r['part_id']] = $gia;
        }
        return $map;
    }

    /**
     * Bảng hiển thị: mỗi mặt hàng một dòng, kèm giá bán của từng nhóm.
     *
     * @return array ['nhom' => [...], 'dong' => [...]]
     */
    public function bang($keyword = ''){
        $nhom = Load::model('CustomerGroupsModel')->getActive();

        $q = $this->hangCuaGara()
                  ->select('`id`, `code`, `name`, `unit_id`, `cost_extra`')
                  ->where('status', '=', 1);

        $keyword = trim((string) $keyword);
        if ($keyword !== ''){
            $q = $q->whereRaw('(`parts`.`name` LIKE ? OR `parts`.`code` LIKE ?)',
                              ['%' . $keyword . '%', '%' . $keyword . '%']);
        }

        $hang = $q->orderBy('name', 'ASC')->get();
        if (empty($hang)) return ['nhom' => (array) $nhom, 'dong' => []];

        /* Một câu cho cả bảng giá, rồi ghép trong PHP — chứ không hỏi CSDL một
           lần cho mỗi dòng (vài nghìn mã là vài nghìn câu truy vấn). */
        $gia = [];
        foreach ((array) $this->bangGara()
                              ->select('`part_id`, `group_id`, `cost_price`, `sale_price`, `calculated_at`')
                              ->get() as $r){
            $gia[(int) $r['part_id']][(int) $r['group_id']] = $r;
        }

        $dong = [];
        foreach ((array) $hang as $h){
            $pid = (int) $h['id'];

            /* Giá vốn giống nhau ở mọi nhóm (nhóm chỉ đổi phần lãi), nên lấy
               dòng đầu tiên nào có là đủ — không cần cộng lại lần nữa. */
            $mau = isset($gia[$pid]) ? reset($gia[$pid]) : null;
            $h['gia_von']       = $mau ? (float) $mau['cost_price'] : 0.0;
            $h['tinh_luc']      = $mau ? $mau['calculated_at'] : null;
            $h['gia_theo_nhom'] = [];
            foreach ((array) $nhom as $n){
                $gidN = (int) $n['id'];
                $h['gia_theo_nhom'][$gidN] = isset($gia[$pid][$gidN])
                    ? (float) $gia[$pid][$gidN]['sale_price'] : null;
            }
            $dong[] = $h;
        }

        return ['nhom' => (array) $nhom, 'dong' => $dong];
    }

    /** Lần tính gần nhất của gara (chuỗi rỗng = chưa tính lần nào) */
    public function tinhLanCuoi(){
        return (string) Load::model('GarageSettingsModel')->val('price_last_run', '');
    }

    /**
     * Truy vấn `parts` trong tầm nhìn của gara làm việc.
     *
     * PHẢI LÀ dkChungVaRieng CHỨ KHÔNG PHẢI dkGara. `parts` là bảng
     * CHUNG-VÀ-RIÊNG (PartsModel::$_chungVaRieng = true): gara bán cả hàng
     * của danh mục tổng (`garage_id IS NULL`) lẫn hàng mình tự khai. Lọc bằng
     * dkGara thì bảng giá bỏ sót đúng những mã bán chạy nhất — hàng kho tổng.
     *
     * bangGara() của model này luôn trỏ vào `part_prices`, nên không dùng được
     * cho `parts`; dkChungVaRieng() trả điều kiện thô cho whereRaw.
     */
    private function hangCuaGara(){
        list($dk, $bd) = $this->dkChungVaRieng('parts');
        return $this->table('parts')->whereRaw($dk, $bd);
    }
}
