<?php
//Lớp core model kế thừa từ lớp database
//1 số ứng dụng có tên là db_bussiness

namespace App\core;

use App\core\Database;

class Model extends Database {

    protected $_table;   //Gán tên bảng
    protected $_fields;  //Các field cần lấy khi fetch và fetchAll
    protected $_primary; //Trường khoá chính

    /**
     * Bảng này là dữ liệu RIÊNG của từng gara (có cột `garage_id`).
     *
     * Bật lên thì getList / getLimit / getFirst / updateById / deleteById tự
     * thêm điều kiện "thuộc gara đang làm việc", addNew tự ghi gara đó — gara B
     * gõ tay /edit/<id của gara A> nhận "không tìm thấy", không phụ thuộc việc
     * controller có nhớ kiểm hay không.
     *
     * Truy vấn tự viết (getRaw, table()->...) KHÔNG được lớp này chặn: dùng
     * dkGara() để thêm điều kiện.
     */
    protected $_theoGara = false;

    /**
     * Bảng DÙNG CHUNG MÀ CÓ PHẦN RIÊNG — danh mục hàng hoá, thương hiệu, đơn vị
     * tính, xuất xứ, hãng sản xuất, thông số kỹ thuật.
     *
     *     garage_id IS NULL  -> dòng của DANH MỤC TỔNG. Mọi gara đều THẤY (để
     *                           chọn khi khai hàng), nhưng chỉ gara tổng SỬA.
     *     garage_id = X      -> dòng riêng của gara X. Chỉ gara X thấy và sửa.
     *
     * Khác `$_theoGara` ở chỗ phần chung: `$_theoGara` lọc đúng `garage_id = X`
     * nên gara mới mở sẽ thấy một danh mục TRỐNG RỖNG — không chọn được "Phụ
     * tùng", "Dịch vụ", "Cái", "Bộ" nào cả, phải tự khai lại từ đầu mọi thứ.
     *
     * Hai cờ loại trừ nhau: bật cả hai thì điều kiện đọc của cờ này thắng.
     *
     * NULL = danh mục tổng, KHÔNG trỏ về id gara tổng: giống hệt cách `parts`
     * đã làm từ migration 000065, để hai bảng đi cạnh nhau nói cùng một luật.
     */
    protected $_chungVaRieng = false;

    /** Gara ép từ ngoài — chỉ có tác dụng ở dòng lệnh (test, công cụ) */
    private static $__garaEp = null;

    /** Kiểm tra model đã khai báo đủ thuộc tính bắt buộc chưa */
    protected function assertConfigured($needPrimary = false){
        if (empty($this->_fields)) die('Thiếu thuộc tính $_fields');
        if (empty($this->_table))  die('Thiếu thuộc tính $_table');
        if ($needPrimary && empty($this->_primary)) die('Thiếu thuộc tính $_primary');
    }

    /** Ép gara cho dòng lệnh. null = bỏ ép; 0 = giả lập "không xác định được gara". */
    public static function epGara($id){
        self::$__garaEp = $id === null ? null : (int) $id;
    }

    /**
     * Gara đang mượn trong một khối trongGara() — xem hàm đó.
     * null = không mượn ai cả, tức gần như luôn luôn.
     */
    private static $__garaMuon = null;

    /**
     * LÀM MỘT VIỆC DƯỚI DANH NGHĨA GARA KHÁC, rồi trả lại ngay.
     *
     * Gần như mọi thứ trong hệ thống chỉ được đụng vào dữ liệu của gara đang
     * làm việc. Nhưng có việc BẮT BUỘC đụng hai gara trong một giao dịch: gara
     * đặt hàng kho tổng thì phải lập phiếu xuất BÊN KHO TỔNG và phiếu nhập bên
     * gara, cả hai cùng thành công hoặc cùng không.
     *
     * VÌ SAO KHÔNG DÙNG epGara() CHO VIỆC NÀY: epGara() chỉ có tác dụng ở dòng
     * lệnh — garaLoc() trên web luôn đọc gara của phiên đăng nhập và bỏ qua nó.
     * Đã mắc đúng lỗi đó: chạy thử ở dòng lệnh thì hai phiếu vào đúng hai gara,
     * nhưng qua web thì phiếu xuất rơi vào gara của người đang bấm, nghĩa là
     * gara tự lập phiếu xuất trong kho của chính mình.
     *
     * HÀM NÀY AN TOÀN HƠN MỘT CÁI SETTER vì không có cách nào bật mà quên tắt:
     * chỉ nhận một đoạn việc, và trả gara cũ về trong `finally` kể cả khi đoạn
     * việc ném lỗi. Không có API nào đặt giá trị này từ dữ liệu người dùng gửi
     * lên — chỉ mã nguồn gọi được, với một id lấy từ CSDL.
     */
    public static function trongGara($garaId, callable $viec){
        $cu = self::$__garaMuon;
        self::$__garaMuon = (int) $garaId;
        try {
            return $viec();
        } finally {
            self::$__garaMuon = $cu;
        }
    }

    /**
     * Gara để lọc:
     *   > 0  -> lọc theo gara đó
     *   0    -> ĐÓNG: không khớp dòng nào (web mà không xác định được gara)
     *   null -> không lọc (dòng lệnh chưa ép gara: migrate, gieo dữ liệu, xuất SQL)
     */
    public static function garaLoc(){
        // Đang mượn danh nghĩa gara khác -> tính theo gara đó, cả web lẫn dòng lệnh
        if (self::$__garaMuon !== null) return self::$__garaMuon;

        if (PHP_SAPI === 'cli') return self::$__garaEp;
        $id = function_exists('gara_hien_tai_id') ? gara_hien_tai_id() : null;
        return $id ? (int) $id : 0;
    }

    /** Nhớ is_master theo id gara — hỏi mỗi truy vấn một lần là thừa một câu SQL */
    private static $__laTong = [];

    /**
     * Gara đang lọc có phải GARA TỔNG không — tính theo garaLoc(), KHÔNG theo
     * phiên đăng nhập.
     *
     * Phải đi qua garaLoc() chứ không gọi la_gara_tong(): garaLoc() tôn trọng
     * cả Model::epGara() (dòng lệnh) lẫn Model::trongGara() (mượn danh nghĩa),
     * còn la_gara_tong() luôn đọc tài khoản của phiên. Dùng sai đường là một
     * phần truy vấn nói gara này, phần khác nói gara kia.
     *
     * Dòng lệnh chưa ép gara (garaLoc() = null) coi như GARA TỔNG: nếp cũ của
     * migrate / gieo dữ liệu / xuất SQL là làm việc trên danh mục chung.
     */
    protected function laGaraTongLoc(){
        $g = self::garaLoc();
        if ($g === null) return true;
        if ($g === 0)    return false;
        $g = (int) $g;
        if (!array_key_exists($g, self::$__laTong)){
            $r = $this->firstRaw('SELECT `is_master` FROM `garages` WHERE `id` = ?', [$g]);
            self::$__laTong[$g] = !empty($r) && (int) $r['is_master'] === 1;
        }
        return self::$__laTong[$g];
    }

    /**
     * Gara SỞ HỮU dòng mới ở bảng chung-và-riêng:
     *   null -> dòng của danh mục tổng (gara tổng, hoặc dòng lệnh chưa ép gara)
     *   > 0  -> dòng riêng của gara đó
     *   0    -> không xác định được gara: không được ghi, không được sửa
     */
    protected function garaSoHuu(){
        $g = self::garaLoc();
        if ($g === null) return null;
        if ($g === 0)    return 0;
        return $this->laGaraTongLoc() ? null : (int) $g;
    }

    /**
     * Điều kiện ĐỌC của bảng chung-và-riêng: [sql, bindings].
     * Dùng cho truy vấn tự viết; getList/getFirst đã tự ghép.
     */
    public function dkChungVaRieng($bi = ''){
        $cot = ($bi !== '' ? $this->wrapField($bi) . '.' : '') . '`garage_id`';
        if (self::garaLoc() === null) return ['1 = 1', []];      // dòng lệnh: không lọc, như nếp cũ
        $so = $this->garaSoHuu();
        if ($so === 0)    return ['1 = 0', []];
        if ($so === null) return [$cot . ' IS NULL', []];         // gara tổng: danh mục tổng là của nó
        return ['(' . $cot . ' = ? OR ' . $cot . ' IS NULL)', [$so]];
    }

    /** Điều kiện SỞ HỮU của bảng chung-và-riêng (sửa / xoá): [sql, bindings] */
    public function dkSoHuuRieng($bi = ''){
        $cot = ($bi !== '' ? $this->wrapField($bi) . '.' : '') . '`garage_id`';
        if (self::garaLoc() === null) return ['1 = 1', []];
        $so = $this->garaSoHuu();
        if ($so === 0)    return ['1 = 0', []];
        if ($so === null) return [$cot . ' IS NULL', []];
        return [$cot . ' = ?', [$so]];
    }

    /** Thêm điều kiện đọc chung-và-riêng vào truy vấn QueryBuilder đang dựng */
    protected function locChungVaRieng($q, $bi = null){
        if (self::garaLoc() === null) return $q;
        $bi  = $bi !== null ? $bi : $this->_table;
        $cot = $bi . '.garage_id';
        $so  = $this->garaSoHuu();
        if ($so === 0)    return $q->where($cot, '=', 0);          // không gara nào có id 0
        if ($so === null) return $q->whereNull($cot);
        return $q->where(function($s) use ($cot, $so){
            $s->where($cot, '=', $so);
            $s->whereOrNull($cot);
        });
    }

    /** Bảng của model, ĐÃ lọc kiểu chung-và-riêng */
    protected function bangChungVaRieng(){
        return $this->locChungVaRieng($this->table($this->_table));
    }

    /**
     * Slug chưa ai dùng trong bảng của model, sinh từ $slug bằng đuôi -2, -3...
     *
     * TRA KHẮP BẢNG, không lọc theo gara — cột `slug` là duy nhất TOÀN BẢNG
     * (nó là địa chỉ trên website). Lọc theo gara ở đây là hỏng theo kiểu khó
     * đoán nhất: gara Sài Gòn đã có "loc-gio-abc", gara Đà Nẵng gõ cùng tên,
     * truy vấn có lọc không thấy gì nên báo "slug rảnh", rồi INSERT đâm vào
     * UNIQUE KEY và trang đổ ra lỗi CSDL.
     *
     * @param string   $slug    slug gốc, đã slugify
     * @param int|null $boQuaId chính bản ghi đang sửa (slug của nó không tính là trùng)
     */
    public function slugRanh($slug, $boQuaId = null){
        $this->assertConfigured();
        $goc = (string) $slug;
        if ($goc === '') return '';

        $thu = $goc;
        for ($i = 2; $i <= 200; $i++){
            $co = $this->firstRaw('SELECT `' . $this->_primary . '` FROM ' . $this->wrapField($this->_table)
                                . ' WHERE `slug` = ? LIMIT 1', [$thu]);
            if (empty($co)) return $thu;
            if ($boQuaId !== null && (int) $co[$this->_primary] === (int) $boQuaId) return $thu;
            $thu = $goc . '-' . $i;
        }
        return $goc . '-' . substr(md5(uniqid('', true)), 0, 6);
    }

    /**
     * Điều kiện gara cho truy vấn tự viết: [sql, bindings].
     *   dkGara('q') -> ["`q`.`garage_id` = ?", [5]]
     * Không lọc -> ['1 = 1', []]; đóng -> ['1 = 0', []].
     */
    public function dkGara($bi = ''){
        $g = self::garaLoc();
        if ($g === null) return ['1 = 1', []];
        if ($g === 0)    return ['1 = 0', []];
        $cot = ($bi !== '' ? $this->wrapField($bi) . '.' : '') . '`garage_id`';
        return [$cot . ' = ?', [$g]];
    }

    /**
     * Thêm điều kiện gara vào truy vấn QueryBuilder đang dựng:
     *   $this->locGara($this->table('quotations'), 'quotations.garage_id')
     * $cot mặc định `<bảng của model>.garage_id`. Dòng lệnh chưa ép gara thì
     * không thêm gì; ĐÓNG (garaLoc = 0) thì so với 0 — không gara nào có id 0,
     * nên không khớp dòng nào.
     *
     * Gọi NGAY SAU table(), trước mọi orWhere ở tầng ngoài cùng: `WHERE gara = ?
     * OR x` là thủng.
     */
    protected function locGara($q, $cot = null){
        $g = self::garaLoc();
        if ($g === null) return $q;
        return $q->where($cot !== null ? $cot : $this->_table . '.garage_id', '=', $g);
    }

    /**
     * Id các kho CỦA GARA làm việc — cho bảng không có cột `garage_id` mà đi
     * theo kho (stocks, stock_cards, warehouse_locations).
     *   null  -> không lọc (dòng lệnh chưa ép gara)
     *   []    -> đóng (không xác định được gara)
     */
    public function khoCuaGara(){
        $g = self::garaLoc();
        if ($g === null) return null;
        if ($g === 0) return [];
        return array_map('intval', array_column(
            $this->getRaw('SELECT `id` FROM `warehouses` WHERE `garage_id` = ?', [$g]), 'id'));
    }

    /** Kho $id có thuộc gara làm việc không (không lọc thì luôn đúng) */
    public function khoThuocGara($id){
        $ds = $this->khoCuaGara();
        return $ds === null || in_array((int) $id, $ds, true);
    }

    /** Giới hạn truy vấn đang dựng vào các kho của gara: $cotKho IN (...) */
    protected function locKhoGara($q, $cotKho){
        $ds = $this->khoCuaGara();
        return $ds === null ? $q : $q->whereIn($cotKho, $ds);
    }

    /** Bảng của model, ĐÃ lọc theo gara — điểm bắt đầu cho truy vấn QueryBuilder */
    protected function bangGara(){
        return $this->locGara($this->table($this->_table));
    }

    /**
     * Ghép điều kiện gara vào $where của các hàm có sẵn (bind của gara đứng SAU).
     *
     * @param bool $soHuu true = điều kiện SỞ HỮU (sửa / xoá), false = ĐỌC.
     *   Hai điều kiện chỉ khác nhau ở bảng chung-và-riêng: đọc được cả dòng của
     *   danh mục tổng, nhưng sửa thì không.
     */
    private function voiGara($where, array $bindings, $soHuu = false){
        if ($this->_chungVaRieng){
            list($dk, $b) = $soHuu ? $this->dkSoHuuRieng($this->_table)
                                   : $this->dkChungVaRieng($this->_table);
        } elseif ($this->_theoGara){
            list($dk, $b) = $this->dkGara($this->_table);
        } else {
            return [$where, $bindings];
        }
        $where = $where !== '' ? '(' . $where . ') AND ' . $dk : $dk;
        return [$where, array_merge($bindings, $b)];
    }

    /**
     * Lấy tất cả bản ghi.
     *
     * @param string $where    Mệnh đề WHERE dùng placeholder `?`, vd: 'group_id = ?'
     * @param array  $bindings Giá trị cho các `?`
     *
     * CẢNH BÁO: $where là chuỗi raw. Chỉ viết tên cột + `?`,
     * không nối giá trị người dùng vào đây.
     */
    public function getList($where='', array $bindings = []){
        $this->assertConfigured();
        list($where, $bindings) = $this->voiGara((string) $where, $bindings);

        $sql = "SELECT $this->_fields FROM ".$this->wrapField($this->_table);

        if (!empty($where)){
            $sql .= ' WHERE '.$where;
        }

        return $this->getRaw($sql, $bindings);
    }

    /**
     * Lấy danh sách theo giới hạn (phân trang).
     * $limit/$start ép kiểu int nên an toàn.
     */
    public function getLimit($limit, $start=0, $where='', array $bindings = []){
        $this->assertConfigured();
        list($where, $bindings) = $this->voiGara((string) $where, $bindings);

        $limit = (int)$limit;
        $start = (int)$start;

        $sql = "SELECT $this->_fields FROM ".$this->wrapField($this->_table);

        if (!empty($where)){
            $sql .= ' WHERE '.$where;
        }

        $sql .= " LIMIT $start, $limit";

        return $this->getRaw($sql, $bindings);
    }

    /** Lấy 1 bản ghi theo khoá chính */
    public function getFirst($id){
        $this->assertConfigured(true);
        list($where, $bindings) = $this->voiGara($this->wrapField($this->_primary).' = ?', [$id]);

        $sql = "SELECT $this->_fields FROM ".$this->wrapField($this->_table).' WHERE '.$where;

        return $this->firstRaw($sql, $bindings);
    }

    /** Thêm bản ghi */
    public function addNew($data){
        if ($this->_chungVaRieng){
            $g = self::garaLoc();
            if ($g === 0){
                throw new \RuntimeException('Khong xac dinh duoc gara lam viec — khong ghi du lieu.');
            }
            /* Gara tổng (và dòng lệnh chưa ép gara) ghi vào DANH MỤC TỔNG, tức
               `garage_id` để NULL. Gara khác ghi dòng riêng của mình. */
            $so = $this->garaSoHuu();
            if ($g !== null) $data['garage_id'] = $so;   // null = danh mục tổng
        } elseif ($this->_theoGara){
            $g = self::garaLoc();
            if ($g === 0){
                throw new \RuntimeException('Khong xac dinh duoc gara lam viec — khong ghi du lieu.');
            }
            if ($g !== null) $data['garage_id'] = $g;
        }
        return $this->insert($this->_table, $data);
    }

    /** Sửa bản ghi theo khoá chính — chỉ dòng gara làm việc SỞ HỮU */
    public function updateById($data, $id){
        $this->assertConfigured(true);

        /* Không cho form chuyển dữ liệu sang gara khác */
        if (($this->_theoGara || $this->_chungVaRieng) && self::garaLoc() !== null){
            unset($data['garage_id']);
        }

        list($where, $bindings) = $this->voiGara($this->wrapField($this->_primary).' = ?', [$id], true);

        return $this->update($this->_table, $data, $where, $bindings);
    }

    /** Xoá bản ghi theo khoá chính — chỉ dòng gara làm việc SỞ HỮU */
    public function deleteById($id){
        $this->assertConfigured(true);
        list($where, $bindings) = $this->voiGara($this->wrapField($this->_primary).' = ?', [$id], true);

        return $this->delete($this->_table, $where, $bindings);
    }
}
