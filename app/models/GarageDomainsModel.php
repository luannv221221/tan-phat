<?php

use App\core\Model;

/**
 * TÊN MIỀN CỦA GARA — mỗi gara một (hoặc nhiều) host trỏ về.
 *
 * Đây là bảng quyết định "request này thuộc gara nào", nên nó cố ý KHÔNG bật
 * `$_theoGara`: lúc tra còn chưa biết gara là ai, bật lọc theo gara ở đây là
 * tự khoá chính mình.
 *
 * Cũng vì vậy mọi hàm ở đây chỉ ĐỌC. Thêm / sửa tên miền làm ở màn Quản lý
 * gara, nơi đã có lớp phân quyền gác.
 */
class GarageDomainsModel extends Model {

    protected $_table   = 'garage_domains';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /**
     * Gara của một host, hoặc [] nếu không khớp dòng nào.
     *
     * So khớp CHÍNH XÁC sau khi hạ chữ thường và bỏ cổng: trình duyệt gửi
     * "GaraA.Etek.vn:443" hay "garaa.etek.vn" đều phải ra một kết quả. Không
     * dùng LIKE — "gara-a.etek.vn" mà khớp nhầm sang "a.etek.vn" thì gara này
     * đọc được dữ liệu gara kia.
     *
     * Trả về cả cột của `garages` để nơi gọi khỏi tra thêm một lần.
     */
    public function theoHost($host){
        $host = self::chuanHoaHost($host);
        if ($host === '') return [];

        return $this->firstRaw(
            'SELECT `g`.*, `d`.`host`
               FROM `garage_domains` `d`
               JOIN `garages` `g` ON `g`.`id` = `d`.`garage_id`
              WHERE `d`.`host` = ? AND `d`.`status` = 1 AND `g`.`status` = 1
              LIMIT 1',
            [$host]
        ) ?: [];
    }

    /** Hệ thống đã khai tên miền nào chưa — bảng rỗng thì giữ nguyên cách cũ. */
    public function daKhaiTenMien(){
        $r = $this->firstRaw('SELECT COUNT(*) AS `c` FROM `garage_domains` WHERE `status` = 1');
        return !empty($r['c']);
    }

    /** Tên miền của một gara, chính trước, phụ sau */
    public function theoGara($garageId){
        return $this->table($this->_table)
                    ->where('garage_id', '=', (int) $garageId)
                    ->orderBy('is_primary', 'DESC')
                    ->orderBy('host', 'ASC')
                    ->get();
    }

    /**
     * Hạ chữ thường, bỏ khoảng trắng, bỏ cổng, bỏ "www." đầu.
     *
     * Bỏ cổng vì máy chủ thật chạy 80/443 nhưng proxy có thể gửi kèm cổng; giữ
     * lại là cùng một trang web mà lúc khớp lúc không.
     */
    public static function chuanHoaHost($host){
        $host = strtolower(trim((string) $host));
        if ($host === '') return '';
        // Bỏ cổng — cẩn thận với IPv6 dạng [::1]:88
        if ($host[0] === '['){
            $dong = strpos($host, ']');
            $host = $dong === false ? $host : substr($host, 0, $dong + 1);
        } elseif (($hai = strrpos($host, ':')) !== false){
            $host = substr($host, 0, $hai);
        }
        if (strpos($host, 'www.') === 0) $host = substr($host, 4);
        return $host;
    }
}
