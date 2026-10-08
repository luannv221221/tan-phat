<?php

use App\core\Model;

/**
 * Lớp cha cho MỌI danh mục tra cứu đơn giản có cùng cấu trúc
 * (name, slug, sort_order, status) và cùng thao tác CRUD.
 *
 * Đang dùng cho:
 *   - Danh mục xe:     kiểu dáng (dòng xe), nhiên liệu, màu xe
 *   - Danh mục phụ tùng: thương hiệu, xuất xứ, hãng sản xuất, đơn vị tính,
 *                        thông số kỹ thuật
 *
 * Lớp con chỉ cần khai báo $_table. Thêm danh mục tra cứu mới thì kế thừa lớp này,
 * đừng chép lại.
 *
 * HAI NHÓM LỚP CON KHÁC NHAU Ở CHỖ CHIA THEO GARA:
 *   - Danh mục XE không có `garage_id`. Toyota Camry 2020 là một với mọi gara,
 *     không ai cần bản riêng; Tân Phát giữ danh mục đó cho cả nền tảng.
 *   - Danh mục PHỤ TÙNG bật `$_chungVaRieng` (08/10/2026): gara thêm được
 *     thương hiệu, đơn vị tính, xuất xứ của riêng mình mà vẫn dùng được danh
 *     mục tổng.
 * Nên lọc gara phải CÓ ĐIỀU KIỆN theo cờ, không bật cứng ở lớp cha: thêm điều
 * kiện `garage_id` vào bảng không có cột đó là mọi truy vấn danh mục xe đổ.
 */
abstract class LookupModel extends Model {

    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getLists($onlyActive = false){
        $q = $this->table($this->_table);

        /* Truy vấn TỰ VIẾT nên cờ $_chungVaRieng ở lớp cha không với tới —
           getList()/getFirst() mới được lớp cha ghép điều kiện. Quên dòng này
           là màn Thương hiệu của gara Sài Gòn hiện luôn thương hiệu riêng của
           gara Đà Nẵng. */
        if ($this->_chungVaRieng) $q = $this->locChungVaRieng($q);

        if ($onlyActive){
            $q = $q->where('status', '=', 1);
        }

        return $q->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->get();
    }

    public function getDetail($id){
        return $this->getFirst($id);
    }

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

    public function remove($id){
        return $this->deleteById($id);
    }
}
