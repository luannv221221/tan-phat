<?php

use App\core\Model;

/**
 * KẾ TOÁN — DANH MỤC TÀI KHOẢN. Cây đệ quy: 111 → 1111 → 111A.
 *
 * CHUNG VÀ RIÊNG (Model::$_chungVaRieng): `garage_id IS NULL` là hệ thống tài
 * khoản chuẩn Tân Phát dựng sẵn (migration 000096, Thông tư 133 rút gọn) — mọi
 * gara đều thấy và chọn được, chỉ gara tổng sửa. Gara thêm tài khoản chi tiết
 * của mình thì dòng đó mang `garage_id`.
 *
 * Lọc cứng theo gara thì gara mới mở thấy danh mục TRỐNG RỖNG, mà màn Loại
 * phiếu lại bắt chọn tài khoản nợ và có — tức là không lập nổi một phiếu nào
 * cho tới khi tự gõ lại cả hệ thống tài khoản.
 *
 * MỌI TRUY VẤN TỰ VIẾT trong lớp này phải tự ghép điều kiện gara
 * (locChungVaRieng) — cờ ở lớp cha chỉ với tới getList / getFirst /
 * updateById / deleteById.
 */
class AccAccountsModel extends Model {

    protected $_table   = 'acc_accounts';
    protected $_fields  = '*';
    protected $_primary = 'id';
    protected $_chungVaRieng = true;

    /** Danh sách cho bảng quản trị = thứ tự cây, thụt lề theo 'depth' */
    public function getLists(){ return $this->getTree(); }

    /**
     * Danh sách PHẲNG theo THỨ TỰ CÂY, mỗi phần tử thêm khoá 'depth'.
     * Dùng cho cả bảng danh sách (thụt lề) lẫn ô chọn tài khoản cha.
     */
    public function getTree($chiHoatDong = false){
        $q = $this->bangChungVaRieng();
        if ($chiHoatDong) $q = $q->where('status', '=', 1);
        $all = $q->orderBy('code', 'ASC')->get();

        /* Tài khoản con mà KHÔNG THẤY CHA thì coi như gốc. Gặp khi một gara
           treo tài khoản của mình dưới một tài khoản chung rồi tài khoản chung
           đó bị tắt — không đưa về gốc thì cả nhánh biến mất khỏi cây mà không
           báo gì, và người dùng đi tìm tài khoản mình vừa tạo. */
        $coMat = [];
        foreach ($all as $r) $coMat[(int) $r['id']] = true;

        $theoCha = [];
        foreach ($all as $r){
            $p = ($r['parent_id'] === null || $r['parent_id'] === '') ? 0 : (int) $r['parent_id'];
            if ($p > 0 && empty($coMat[$p])) $p = 0;
            $theoCha[$p][] = $r;
        }

        $ra   = [];
        $duyet = function($chaId, $sau) use (&$duyet, &$ra, &$theoCha){
            if (empty($theoCha[$chaId])) return;
            foreach ($theoCha[$chaId] as $node){
                $node['depth'] = $sau;
                $ra[] = $node;
                $duyet((int) $node['id'], $sau + 1);
            }
        };
        $duyet(0, 0);
        return $ra;
    }

    /** Chỉ tài khoản đang bật — cho ô chọn ở màn Loại phiếu */
    public function getActive(){ return $this->getTree(true); }

    public function getDetail($id){ return $this->getFirst($id); }

    /**
     * Tra theo mã — TRONG TẦM THẤY của gara làm việc (chung + riêng).
     *
     * Chỉ mục (garage_id, code) KHÔNG chặn được hai dòng CHUNG trùng mã, vì
     * MySQL coi các NULL là khác nhau. Nên kiểm trùng phải làm ở đây, giống
     * cách `parts` đang làm (PartsModel::findByCode).
     */
    public function findByCode($code){
        return $this->bangChungVaRieng()->where('code', '=', trim((string) $code))->first();
    }

    /** Id của mọi hậu duệ — để loại khỏi ô chọn cha khi sửa (chống cây vòng) */
    public function hauDue($id){
        $all = $this->bangChungVaRieng()->select('`id`, `parent_id`')->get();

        $theoCha = [];
        foreach ($all as $r){
            $p = ($r['parent_id'] === null || $r['parent_id'] === '') ? 0 : (int) $r['parent_id'];
            $theoCha[$p][] = (int) $r['id'];
        }

        $ids  = [];
        $gom = function($pid) use (&$gom, &$ids, &$theoCha){
            if (empty($theoCha[$pid])) return;
            foreach ($theoCha[$pid] as $cid){
                if (in_array($cid, $ids, true)) return;   // dữ liệu lỗi -> chặn vòng lặp
                $ids[] = $cid;
                $gom($cid);
            }
        };
        $gom((int) $id);
        return $ids;
    }

    /** Số tài khoản con — còn con thì không xoá được (khoá ngoại RESTRICT) */
    public function demCon($id){
        $r = $this->table($this->_table)->select('COUNT(*) AS total')
                  ->where('parent_id', '=', (int) $id)->first();
        return (int) (isset($r['total']) ? $r['total'] : 0);
    }

    /** Loại phiếu đang trỏ vào tài khoản này (nợ hoặc có) */
    public function demLoaiPhieuDung($id){
        $r = $this->firstRaw('SELECT COUNT(*) AS c FROM `acc_voucher_types`
                               WHERE `debit_id` = ? OR `credit_id` = ?', [(int) $id, (int) $id]);
        return (int) (isset($r['c']) ? $r['c'] : 0);
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

    /**
     * Xoá. Trả về false khi còn ràng buộc — kiểm trước để báo cho tử tế thay vì
     * để MySQL ném ra lỗi khoá ngoại không ai đọc được.
     */
    public function remove($id){
        if ($this->demCon($id) > 0) return false;
        return $this->deleteById($id);
    }
}
