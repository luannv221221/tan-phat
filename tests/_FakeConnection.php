<?php
/**
 * Connection giả dùng cho test — SQLite in-memory, không cần MySQL server.
 *
 * File này PHẢI được require TRƯỚC core/Database.php.
 * Khi đó class App\core\Connection đã tồn tại, nên core/Connection.php thật
 * (vốn đọc hằng số _DB/_HOST/_USER/_PASS và kết nối MySQL) không được nạp.
 */

namespace App\core;

class Connection {
    protected $_conn = null;

    /**
     * Dung CHUNG mot PDO cho moi instance.
     * 'sqlite::memory:' tao DB rieng cho tung ket noi, nen neu moi model mo mot
     * ket noi moi thi model se nhin vao DB rong.
     */
    protected static $shared = null;

    public function __construct(){
        if (self::$shared === null){
            self::$shared = new \PDO('sqlite::memory:');
            self::$shared->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            self::$shared->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        }
        $this->_conn = self::$shared;
    }

    /** Cho test truy cập PDO để dựng schema */
    public function pdo(){ return $this->_conn; }
}
