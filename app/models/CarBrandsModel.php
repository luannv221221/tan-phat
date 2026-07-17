<?php

use App\core\Model;

/**
 * Hãng xe — Toyota, Honda, Kia...
 * Gốc của cây danh mục xe.
 */
class CarBrandsModel extends Model {

    protected $_table   = 'car_brands';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Danh sách hãng, sắp theo thứ tự hiển thị */
    public function getLists($onlyActive = false){
        $q = $this->table($this->_table);

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

    /**
     * Xoá hãng.
     *
     * Khoá ngoại `fk_car_models_brand` để ON DELETE RESTRICT, nên MySQL sẽ
     * TỪ CHỐI nếu hãng còn model. Kiểm tra trước để báo lỗi cho người dùng
     * hiểu được, thay vì để exception SQL bắn lên.
     */
    public function remove($id){
        if ($this->countModels($id) > 0){
            return false;
        }
        return $this->deleteById($id);
    }

    /** Số model thuộc hãng này */
    public function countModels($brandId){
        $r = $this->table('car_models')
                  ->select('COUNT(*) AS total')
                  ->where('brand_id', '=', $brandId)
                  ->first();

        return (int) ($r['total'] ?? 0);
    }
}
