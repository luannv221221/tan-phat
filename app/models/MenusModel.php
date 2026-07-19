<?php

use App\core\Model;

/** CMS — Menu website (cây 1 cấp submenu). */
class MenusModel extends Model {

    protected $_table   = 'menus';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Phẳng theo thứ tự cây + depth (cho danh sách admin) */
    public function getTree(){
        $all = $this->table($this->_table)->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get();
        $byParent = [];
        foreach ($all ?: [] as $r){
            $p = ($r['parent_id'] === null || $r['parent_id'] === '') ? 0 : (int) $r['parent_id'];
            $byParent[$p][] = $r;
        }
        $out = [];
        $walk = function ($pid, $depth) use (&$walk, &$out, &$byParent){
            if (empty($byParent[$pid])) return;
            foreach ($byParent[$pid] as $n){ $n['depth'] = $depth; $out[] = $n; $walk((int) $n['id'], $depth + 1); }
        };
        $walk(0, 0);
        return $out;
    }

    /** Cây menu ĐANG BẬT cho storefront: [root => [...,'children'=>[...]]] */
    public function getActiveTree(){
        $all = $this->table($this->_table)->where('status', '=', 1)
                    ->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get();
        $byParent = [];
        foreach ($all ?: [] as $r){
            $p = ($r['parent_id'] === null || $r['parent_id'] === '') ? 0 : (int) $r['parent_id'];
            $byParent[$p][] = $r;
        }
        $roots = $byParent[0] ?? [];
        foreach ($roots as &$root){
            $root['children'] = $byParent[(int) $root['id']] ?? [];
        }
        return $roots;
    }

    /** Menu gốc đang bật (cho dropdown chọn cha) */
    public function getRoots(){
        return $this->table($this->_table)->whereNull('parent_id')
                    ->orderBy('sort_order', 'ASC')->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }
    public function add($data){ $data['create_at'] = date('Y-m-d H:i:s'); $this->addNew($data); return $this->lastId(); }
    public function edit($data, $id){ $data['update_at'] = date('Y-m-d H:i:s'); return $this->updateById($data, $id); }
    public function remove($id){ return $this->deleteById($id); }
}
