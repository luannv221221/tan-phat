<?php

use App\core\Controller;

/**
 * STOREFRONT — sitemap.xml động từ sản phẩm/tin/dự án/thư viện đã đăng.
 * Xuất XML trực tiếp (không qua layout).
 */
class Sitemap extends Controller {

    public function index(){
        $base = rtrim(_WEB_URL, '/');
        $urls = [];

        $add = function($loc) use (&$urls, $base){
            $urls[] = $base . '/' . ltrim($loc, '/');
        };

        // Trang tĩnh
        foreach (['', 'san-pham', 'tin-tuc', 'du-an', 'thu-vien'] as $p){ $add($p); }

        // Sản phẩm đang bật
        foreach ($this->model('PartsModel')->storefront([], 0, 0) as $p){
            if (!empty($p['slug'])) $add('san-pham/' . $p['slug']);
        }
        // Tin tức đã đăng
        foreach ($this->model('NewsModel')->getPublished(0, 0) as $n){
            if (!empty($n['slug'])) $add('tin-tuc/' . $n['slug']);
        }
        // Dự án đã đăng
        foreach ($this->model('ProjectsModel')->getPublished(0) as $d){
            if (!empty($d['slug'])) $add('du-an/' . $d['slug']);
        }
        // Thư viện đã đăng
        foreach ($this->model('GalleriesModel')->getPublished(0) as $g){
            if (!empty($g['slug'])) $add('thu-vien/' . $g['slug']);
        }

        header('Content-Type: application/xml; charset=utf-8');
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u){
            $xml .= '  <url><loc>' . htmlspecialchars($u, ENT_QUOTES) . '</loc></url>' . "\n";
        }
        $xml .= '</urlset>';
        echo $xml;
    }
}
