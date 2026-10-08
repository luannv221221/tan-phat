<?php

use App\core\Model;

/**
 * TÊN MIỀN CỦA GARA — mỗi gara một (hoặc nhiều) host trỏ về.
 *
 * Đây là bảng quyết định "request này thuộc gara nào", nên nó cố ý KHÔNG bật
 * `$_theoGara`: lúc tra còn chưa biết gara là ai, bật lọc theo gara ở đây là
 * tự khoá chính mình.
 *
 * PHẦN GHI (08/10/2026) nằm ở cuối file, và CHỈ màn Hệ thống › Quản lý gara gọi
 * tới — màn đó mang cờ `chi_tan_phat` nên chỉ Tân Phát vào được. Khai tên miền
 * là việc của người vận hành nền tảng, không phải của một gara: gara tự khai
 * được host là nó tự nhận request của gara khác.
 *
 * Trước đó không có màn nào cả — phải gõ SQL tay vào CSDL thật. Đã suýt hỏng
 * một lần vì thứ tự hai câu lệnh (DELETE trước UPDATE nên UPDATE không khớp
 * dòng nào), nên dựng màn hình là để không phải làm thế nữa.
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

    // ===================== PHẦN GHI =====================

    /** Một dòng tên miền theo id, kèm mã + tên gara */
    public function theoId($id){
        return $this->firstRaw(
            'SELECT `d`.*, `g`.`code` AS `garage_code`, `g`.`name` AS `garage_name`
               FROM `garage_domains` `d` JOIN `garages` `g` ON `g`.`id` = `d`.`garage_id`
              WHERE `d`.`id` = ? LIMIT 1', [(int) $id]) ?: [];
    }

    /** Dòng đang giữ host này (bất kể trạng thái), kèm gara — để báo trùng cho tử tế */
    public function aiGiuHost($host){
        $host = self::chuanHoaHost($host);
        if ($host === '') return [];
        return $this->firstRaw(
            'SELECT `d`.*, `g`.`code` AS `garage_code`, `g`.`name` AS `garage_name`
               FROM `garage_domains` `d` JOIN `garages` `g` ON `g`.`id` = `d`.`garage_id`
              WHERE `d`.`host` = ? LIMIT 1', [$host]) ?: [];
    }

    /** Số tên miền ĐANG BẬT của một gara */
    public function demDangBat($garageId){
        $r = $this->firstRaw('SELECT COUNT(*) AS `c` FROM `garage_domains`
                               WHERE `garage_id` = ? AND `status` = 1', [(int) $garageId]);
        return (int) (isset($r['c']) ? $r['c'] : 0);
    }

    /**
     * KIỂM MỘT HOST NGƯỜI DÙNG GÕ VÀO. Trả về [host đã chuẩn hoá, câu lỗi].
     * Lỗi rỗng = dùng được.
     *
     * Kiểm ở đây chứ không để MySQL chặn, vì MySQL chỉ chặn được đúng một thứ
     * (trùng). Những cái còn lại thì nó nhận hết: một host có dấu cách, có
     * "https://", có đường dẫn phía sau — lưu vào trông như bình thường, tới
     * lúc mở tên miền mới ra "Không tìm thấy gara" mà nhìn vào đâu cũng không
     * ra lý do. Đã mắc đúng kiểu đó với mã gara "Long Biên".
     */
    public static function kiemHost($host){
        $tho = trim((string) $host);
        if ($tho === '') return ['', 'Chưa nhập tên miền.'];

        /* Người dùng hay dán cả địa chỉ từ thanh trình duyệt. Cắt phần giao
           thức và đường dẫn thay vì từ chối — ý họ rõ ràng. */
        $tho = preg_replace('~^[a-z][a-z0-9+.-]*://~i', '', $tho);
        $tho = preg_replace('~[/?#].*$~', '', $tho);

        $h = self::chuanHoaHost($tho);
        if ($h === '') return ['', 'Tên miền không hợp lệ.'];
        if (strlen($h) > 190) return ['', 'Tên miền dài quá 190 ký tự.'];

        if (strpos($h, '@') !== false || strpos($h, ' ') !== false){
            return ['', 'Tên miền không được chứa dấu cách hay ký tự @ — nhập dạng gara.etek.rikkeiedu.org.'];
        }

        /* Mỗi nhãn: chữ thường / số / gạch nối, không mở đầu hay kết thúc bằng
           gạch nối, tối đa 63 ký tự. KHÔNG đòi phải có dấu chấm: `localhost` và
           tên máy trong mạng nội bộ đều không có, mà vẫn dùng để chạy thử. */
        foreach (explode('.', $h) as $nhan){
            if ($nhan === '')      return ['', 'Tên miền có hai dấu chấm liền nhau hoặc chấm ở đầu / cuối.'];
            if (strlen($nhan) > 63) return ['', 'Một đoạn của tên miền dài quá 63 ký tự.'];
            if (!preg_match('~^[a-z0-9]([a-z0-9-]*[a-z0-9])?$~', $nhan)){
                return ['', 'Đoạn "' . $nhan . '" không hợp lệ: chỉ dùng chữ không dấu, số và gạch nối,'
                          . ' không mở đầu hay kết thúc bằng gạch nối.'];
            }
        }

        return [$h, ''];
    }

    /**
     * Thêm một tên miền cho gara. Trả về [host đã lưu, câu lỗi].
     *
     * TRẢ VỀ CẢ HOST, không chỉ câu lỗi: host lưu vào khác chữ người dùng gõ
     * (hạ chữ thường, cắt giao thức, cắt cổng và đường dẫn). Nơi gọi mà tự
     * chuẩn hoá lại bằng chuanHoaHost() thì sai — hàm đó KHÔNG cắt giao thức,
     * nên "https://a.etek.vn" ra "https", và câu thông báo vừa đọc sai host vừa
     * kết luận sai là "máy nội bộ" (vì "https" không có dấu chấm).
     *
     * @param bool $laChinh đặt làm tên miền CHÍNH của gara đó
     */
    public function them($garageId, $host, $laChinh = false){
        $garageId = (int) $garageId;
        list($h, $loi) = self::kiemHost($host);
        if ($loi !== '') return ['', $loi];

        $co = $this->aiGiuHost($h);
        if (!empty($co)){
            return [$h, (int) $co['garage_id'] === $garageId
                ? 'Gara này đã khai tên miền ' . $h . '.'
                : 'Tên miền ' . $h . ' đang thuộc gara ' . $co['garage_name']
                  . ' (' . $co['garage_code'] . ').'];
        }

        /* Tên miền ĐẦU TIÊN của một gara tự thành tên miền chính — không thì
           gara có host mà theoGara() không biết đâu là chính, và
           MoGaraModel::tenMienGoc() suy tên miền gốc từ host chính. */
        if ($this->demTatCa($garageId) === 0) $laChinh = true;

        $this->transaction(function($db) use ($garageId, $h, $laChinh){
            if ($laChinh){
                $db->update('garage_domains', ['is_primary' => 0], '`garage_id` = ?', [$garageId]);
            }
            $db->insert('garage_domains', [
                'garage_id'  => $garageId,
                'host'       => $h,
                'is_primary' => $laChinh ? 1 : 0,
                'status'     => 1,
                'create_at'  => date('Y-m-d H:i:s'),
            ]);
            return true;
        });
        return [$h, ''];
    }

    /** Số tên miền của một gara, kể cả đang tắt */
    public function demTatCa($garageId){
        $r = $this->firstRaw('SELECT COUNT(*) AS `c` FROM `garage_domains` WHERE `garage_id` = ?',
                             [(int) $garageId]);
        return (int) (isset($r['c']) ? $r['c'] : 0);
    }

    /**
     * Đặt một tên miền làm CHÍNH. Gỡ cờ ở các host khác của cùng gara, trong
     * một giao dịch — hai host cùng mang cờ chính thì theoGara() sắp xếp xong
     * trả về cái nào là tuỳ thứ tự truy vấn, và tenMienGoc() suy ra tên miền
     * gốc khác nhau giữa hai lần gọi.
     */
    public function datLamChinh($id){
        $d = $this->theoId($id);
        if (empty($d)) return 'Không tìm thấy tên miền.';
        if ((int) $d['status'] !== 1){
            return 'Tên miền đang tắt thì không đặt làm chính được — bật nó lên trước đã.';
        }
        $gid = (int) $d['garage_id'];
        $this->transaction(function($db) use ($gid, $id){
            $db->update('garage_domains', ['is_primary' => 0], '`garage_id` = ?', [$gid]);
            $db->update('garage_domains', ['is_primary' => 1, 'update_at' => date('Y-m-d H:i:s')],
                        '`id` = ?', [(int) $id]);
            return true;
        });
        return '';
    }

    /** Bật / tắt một tên miền. Trả về [trạng thái mới, câu lỗi]. */
    public function doiTrangThai($id){
        $d = $this->theoId($id);
        if (empty($d)) return [null, 'Không tìm thấy tên miền.'];

        $moi = (int) $d['status'] === 1 ? 0 : 1;

        /* KHÔNG TẮT TÊN MIỀN BẬT CUỐI CÙNG của một gara: gara không còn host
           nào đang bật thì website của nó không ai vào được, mà màn hình này
           chẳng nói gì — chỉ thấy một dòng đổi màu. */
        if ($moi === 0 && $this->demDangBat((int) $d['garage_id']) <= 1){
            return [null, 'Đây là tên miền đang bật DUY NHẤT của ' . $d['garage_name']
                        . '. Tắt nó là website gara đó không ai vào được. Khai thêm một tên miền'
                        . ' khác trước, hoặc khoá cả gara ở màn Quản lý gara.'];
        }

        $this->update($this->_table, ['status' => $moi, 'update_at' => date('Y-m-d H:i:s')],
                      '`id` = ?', [(int) $id]);
        return [$moi, ''];
    }

    /** Xoá một tên miền. Trả về câu lỗi, hoặc '' nếu xong. */
    public function xoa($id){
        $d = $this->theoId($id);
        if (empty($d)) return 'Không tìm thấy tên miền.';

        if ($this->demTatCa((int) $d['garage_id']) <= 1){
            return 'Đây là tên miền DUY NHẤT của ' . $d['garage_name']
                 . '. Xoá nó là gara đó không còn website. Khai tên miền mới trước đã.';
        }

        /* Xoá host CHÍNH thì phải chuyển cờ sang host khác, không để gara mất
           tên miền chính: tenMienGoc() lấy host chính của gara tổng để suy ra
           tên miền gốc cho các gara mở sau. Không có host chính thì theoGara()
           trả về host đầu theo thứ tự chữ cái — một hôm đẹp trời gara mới mở ra
           nhận tên miền dưới một host chạy thử. Đã mắc đúng lỗi đó.  */
        $gid = (int) $d['garage_id'];
        $laChinh = (int) $d['is_primary'] === 1;

        $this->transaction(function($db) use ($id, $gid, $laChinh){
            $db->delete('garage_domains', '`id` = ?', [(int) $id]);
            if ($laChinh){
                $ke = $db->firstRaw('SELECT `id` FROM `garage_domains`
                                      WHERE `garage_id` = ? ORDER BY `status` DESC, `id` ASC LIMIT 1',
                                    [$gid]);
                if (!empty($ke['id'])){
                    $db->update('garage_domains', ['is_primary' => 1], '`id` = ?', [(int) $ke['id']]);
                }
            }
            return true;
        });
        return '';
    }
}
