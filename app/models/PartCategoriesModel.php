<?php

use App\core\Model;

/**
 * Danh mục phụ tùng — cây phân cấp cha-con không giới hạn cấp.
 * VD: Hệ thống phanh > Má phanh > Má phanh trước.
 *
 * parent_id tự tham chiếu, ON DELETE RESTRICT: không xoá được danh mục còn con.
 * parts.category_id ON DELETE SET NULL: xoá danh mục chỉ gỡ liên kết ở phụ tùng.
 *
 * CHUNG VÀ RIÊNG (08/10/2026): `garage_id IS NULL` là danh mục tổng — mọi gara
 * đều thấy và chọn được; `garage_id = X` là nhánh riêng của gara X. Nhờ vậy gara
 * mới mở vẫn có sẵn cây "Phụ tùng / Dịch vụ" để gán hàng vào, mà vẫn thêm được
 * nhánh của riêng mình. Mọi truy vấn TỰ VIẾT trong lớp này phải tự ghép điều
 * kiện gara (locChungVaRieng) — cờ ở lớp cha chỉ với tới getList/getFirst.
 */
class PartCategoriesModel extends Model {

    protected $_table   = 'part_categories';
    protected $_fields  = '*';
    protected $_primary = 'id';
    protected $_chungVaRieng = true;

    /**
     * Danh sách cho bảng quản trị = thứ tự cây (thụt lề theo 'depth').
     * QueryBuilder::wrapField không nhận alias nên KHÔNG self-join lấy tên cha;
     * cây thụt lề đã thể hiện quan hệ cha-con rõ hơn cột "cha".
     */
    public function getLists(){
        return $this->getTree();
    }

    /**
     * Danh sách phẳng theo THỨ TỰ CÂY, mỗi phần tử thêm khoá 'depth'.
     * Dùng cho cả bảng danh sách (thụt lề) lẫn dropdown chọn cha.
     */
    public function getTree(){
        $all = $this->bangChungVaRieng()
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('name', 'ASC')
                    ->get();

        /* DANH MỤC CON MÀ KHÔNG THẤY CHA thì coi như gốc. Gặp khi dữ liệu lỗi,
           và sẽ gặp nếu sau này có ai gán một danh mục riêng của gara làm cha
           cho danh mục của gara khác: lọc gara làm cha biến mất khỏi $all, và
           walk(0) không bao giờ đi tới node đó — nó mất khỏi cây mà không báo
           gì. Đưa về gốc thì vẫn thấy, vẫn sửa được. */
        $coMat = [];
        foreach ($all as $r) $coMat[(int) $r['id']] = true;

        $byParent = [];
        foreach ($all as $r){
            $p = ($r['parent_id'] === null || $r['parent_id'] === '') ? 0 : (int) $r['parent_id'];
            if ($p > 0 && empty($coMat[$p])) $p = 0;
            $byParent[$p][] = $r;
        }

        $out  = [];
        $walk = function ($parentId, $depth) use (&$walk, &$out, &$byParent){
            if (empty($byParent[$parentId])) return;
            foreach ($byParent[$parentId] as $node){
                $node['depth'] = $depth;
                $out[] = $node;
                $walk((int) $node['id'], $depth + 1);
            }
        };
        $walk(0, 0);

        return $out;
    }

    /**
     * Một NHÁNH của cây, tính từ danh mục gốc có slug cho trước (kèm chính nó).
     *
     * Màn hình Dịch vụ chỉ được chọn danh mục nằm dưới nhánh "Dịch vụ" — đổ cả
     * cây ra thì người nhập gán nhầm dịch vụ vào "Hệ thống phanh" và nó biến
     * mất khỏi mọi bộ lọc theo nhóm.
     *
     * `depth` được tính LẠI từ gốc nhánh (gốc = 0) để thụt đầu dòng trong ô
     * chọn không thừa một cấp.
     */
    public function nhanhTheoSlug($slug){
        $cay = $this->getTree();

        $goc = null;
        foreach ($cay as $i => $c){
            if ($c['slug'] === $slug){ $goc = $i; break; }
        }
        if ($goc === null) return [];

        $mocDepth = (int) $cay[$goc]['depth'];
        $out = [];
        for ($i = $goc; $i < count($cay); $i++){
            // getTree() duyệt theo chiều sâu nên cả nhánh nằm LIỀN nhau; gặp
            // node có depth <= gốc (mà không phải chính gốc) là đã sang nhánh khác.
            if ($i > $goc && (int) $cay[$i]['depth'] <= $mocDepth) break;
            $cay[$i]['depth'] = (int) $cay[$i]['depth'] - $mocDepth;
            $out[] = $cay[$i];
        }
        return $out;
    }

    /** ID của mọi hậu duệ (con, cháu...) — để loại khỏi dropdown chọn cha khi sửa */
    public function getDescendantIds($id){
        $all = $this->bangChungVaRieng()->select('`id`, `parent_id`')->get();

        $byParent = [];
        foreach ($all as $r){
            $p = ($r['parent_id'] === null || $r['parent_id'] === '') ? 0 : (int) $r['parent_id'];
            $byParent[$p][] = (int) $r['id'];
        }

        $ids     = [];
        $collect = function ($pid) use (&$collect, &$ids, &$byParent){
            if (empty($byParent[$pid])) return;
            foreach ($byParent[$pid] as $cid){
                $ids[] = $cid;
                $collect($cid);
            }
        };
        $collect((int) $id);

        return $ids;
    }

    /**
     * Mở rộng danh sách id danh mục thành CHÍNH NÓ + toàn bộ hậu duệ.
     *
     * Phụ tùng được gán vào danh mục lá (vd "Lọc dầu"), không gán vào danh mục
     * gốc (vd "Hệ thống lọc"). Nên lọc storefront theo đúng id đã chọn thì chọn
     * danh mục gốc luôn ra rỗng. Hàm này trả về cả nhánh để lọc ra đúng hàng.
     *
     * Chỉ đọc bảng danh mục 1 lần dù truyền vào bao nhiêu id.
     *
     * @param  array $ids
     * @return array id duy nhất, đã ép kiểu int
     */
    public function expandWithDescendants(array $ids){
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids)) return [];

        $all = $this->bangChungVaRieng()->select('`id`, `parent_id`')->get();

        $byParent = [];
        foreach ($all as $r){
            $p = ($r['parent_id'] === null || $r['parent_id'] === '') ? 0 : (int) $r['parent_id'];
            $byParent[$p][] = (int) $r['id'];
        }

        $out     = [];
        $collect = function ($pid) use (&$collect, &$out, &$byParent){
            if (empty($byParent[$pid])) return;
            foreach ($byParent[$pid] as $cid){
                if (in_array($cid, $out, true)) continue; // chặn vòng lặp nếu dữ liệu lỗi
                $out[] = $cid;
                $collect($cid);
            }
        };

        foreach ($ids as $id){
            if (!in_array($id, $out, true)) $out[] = $id;
            $collect($id);
        }

        return $out;
    }

    /**
     * Số danh mục con. KHÔNG lọc theo gara — đây là câu hỏi "xoá được chưa",
     * mà khoá ngoại parent_id là RESTRICT nên con của gara nào cũng chặn được
     * việc xoá. Lọc theo gara ở đây là trả lời "xoá được" rồi để MySQL ném lỗi.
     */
    public function countChildren($id){
        $r = $this->table($this->_table)
                  ->select('COUNT(*) AS total')
                  ->where('parent_id', '=', $id)
                  ->first();

        return (int) ($r['total'] ?? 0);
    }

    public function getDetail($id){
        return $this->getFirst($id);
    }

    /**
     * Tra theo slug — KHẮP BẢNG, không lọc gara.
     *
     * Dùng để kiểm trùng trước khi ghi, mà `slug` là duy nhất TOÀN BẢNG. Lọc
     * theo gara là báo "slug rảnh" trong khi gara khác đang giữ nó, rồi INSERT
     * đâm vào UNIQUE KEY. Nơi cần "danh mục tôi được thấy" thì dùng getDetail().
     */
    public function findBySlug($slug){
        return $this->table($this->_table)->where('slug', '=', $slug)->first();
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
     * Xoá danh mục.
     * FK parent để RESTRICT nên còn con là không xoá được — kiểm tra trước
     * để báo lỗi tử tế thay vì để MySQL ném exception.
     */
    public function remove($id){
        if ($this->countChildren($id) > 0){
            return false;
        }
        return $this->deleteById($id);
    }
}
