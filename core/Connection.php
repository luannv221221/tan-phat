<?php
//Lớp Connection dùng để thực hiện kết nối với CSDL

namespace App\core;
use \PDO; //Do class Connection dùng namespace nên PDO phải khai báo use

class Connection{

    /**
     * Kết nối PDO dùng CHUNG cho toàn bộ request.
     *
     * Bản cũ viết:
     *     protected $_conn = null;
     *     if (!$this->_conn) { $this->_conn = new PDO(...); }
     * $_conn là thuộc tính instance và luôn null ở mỗi object mới,
     * nên điều kiện `if` luôn đúng => MỖI `new Model()` mở một kết nối MySQL mới.
     * Một request tạo 5 model = 5 kết nối. Với ERP nhiều model/request thì
     * đây là nguyên nhân cạn max_connections.
     */
    protected static $_shared = null;

    protected $_conn = null;

    public function __construct(){

        // Đã có kết nối rồi thì dùng lại, không mở thêm.
        if (self::$_shared instanceof PDO){
            $this->_conn = self::$_shared;
            return;
        }

        try{
            // charset đặt thẳng trong DSN — đáng tin hơn MYSQL_ATTR_INIT_COMMAND
            // port: bản cũ không có, mặc định 3306. Cần khi máy chạy nhiều instance.
            $port = defined('_PORT') ? _PORT : 3306;
            $dsn  = 'mysql:host='._HOST.';port='.$port.';dbname='._DB.';charset=utf8mb4';

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Tắt emulation => prepared statement thật ở tầng MySQL
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            self::$_shared = new PDO($dsn, _USER, _PASS, $options);
            $this->_conn   = self::$_shared;

        }catch (\PDOException $exception){
            // Bản cũ viết `catch (Exception ...)` trong namespace App\core
            // => PHP hiểu là App\core\Exception (không tồn tại) => không bao giờ bắt được,
            //    lỗi kết nối thành fatal error in stack trace kèm credential ra trình duyệt.
            error_log('Loi ket noi CSDL: '.$exception->getMessage());

            if (defined('_DEBUG') && _DEBUG === true){
                die('Lỗi kết nối máy chủ: '.$exception->getMessage());
            }

            die('Khong the ket noi CSDL. Vui long lien he quan tri vien.');
        }
    }

    /** Trả về PDO đang dùng (cho test và cho transaction) */
    public function pdo(){
        return $this->_conn;
    }
}
