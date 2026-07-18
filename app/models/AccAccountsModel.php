<?php

use App\core\Model;

/**
 * KT-1 — Danh mục tài khoản (chart of accounts), cây cha-con.
 * parent_id RESTRICT: không xoá tài khoản còn tài khoản con.
 */
class AccAccountsModel extends Model {

    protected $_table   = 'acc_accounts';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public static $types = [
        'asset'     => 'Tài sản',
        'liability' => 'Nợ phải trả',
        'equity'    => 'Vốn chủ sở hữu',
        'revenue'   => 'Doanh thu',
        'expense'   => 'Chi phí',
        'other'     => 'Khác',
    ];

    public function getLists(){
        return $this->getTree();
    }

    /** Phẳng theo thứ tự cây, thêm 'depth' */
    public function getTree(){
        $all = $this->table($this->_table)
                    ->orderBy('code', 'ASC')
                    ->get();

        $byParent = [];
        foreach ($all as $r){
            $p = ($r['parent_id'] === null || $r['parent_id'] === '') ? 0 : (int) $r['parent_id'];
            $byParent[$p][] = $r;
        }
        $out  = [];
        $walk = function ($pid, $depth) use (&$walk, &$out, &$byParent){
            if (empty($byParent[$pid])) return;
            foreach ($byParent[$pid] as $n){
                $n['depth'] = $depth;
                $out[] = $n;
                $walk((int) $n['id'], $depth + 1);
            }
        };
        $walk(0, 0);
        return $out;
    }

    /** Tài khoản chi tiết đang bật — cho dropdown định khoản */
    public function getDetailAccounts(){
        return $this->table($this->_table)
                    ->where('is_detail', '=', 1)
                    ->where('status', '=', 1)
                    ->orderBy('code', 'ASC')
                    ->get();
    }

    /** Tài khoản quỹ tiền (111/112...) đang bật — cho chọn quỹ ở phiếu thu/chi */
    public function getCashAccounts(){
        return $this->table($this->_table)
                    ->where('is_detail', '=', 1)
                    ->where('status', '=', 1)
                    ->where(function($q){
                        $q->whereLike('code', '111%');
                        $q->orWhere('code', 'LIKE', '112%');
                    })
                    ->orderBy('code', 'ASC')
                    ->get();
    }

    /** id các tài khoản có mã bắt đầu bằng $prefix (vd '131', '331') */
    public function getIdsByCodePrefix($prefix){
        $rows = $this->table($this->_table)
                     ->select('`id`')
                     ->whereLike('code', $prefix . '%')
                     ->get();
        return array_map(function($r){ return (int) $r['id']; }, $rows ?: []);
    }

    public function getDescendantIds($id){
        $all = $this->table($this->_table)->select('`id`, `parent_id`')->get();
        $byParent = [];
        foreach ($all as $r){
            $p = ($r['parent_id'] === null || $r['parent_id'] === '') ? 0 : (int) $r['parent_id'];
            $byParent[$p][] = (int) $r['id'];
        }
        $ids = [];
        $collect = function ($pid) use (&$collect, &$ids, &$byParent){
            if (empty($byParent[$pid])) return;
            foreach ($byParent[$pid] as $cid){ $ids[] = $cid; $collect($cid); }
        };
        $collect((int) $id);
        return $ids;
    }

    public function countChildren($id){
        $r = $this->table($this->_table)->select('COUNT(*) AS total')
                  ->where('parent_id', '=', $id)->first();
        return (int) ($r['total'] ?? 0);
    }

    public function getDetail($id){ return $this->getFirst($id); }

    public function findByCode($code){
        return $this->table($this->_table)->where('code', '=', $code)->first();
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
        if ($this->countChildren($id) > 0) return false;
        return $this->deleteById($id);
    }
}
