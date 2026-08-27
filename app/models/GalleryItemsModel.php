<?php

use App\core\Model;

/** CMS — Ảnh/video trong album. */
class GalleryItemsModel extends Model {

    protected $_table   = 'gallery_items';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getByGallery($galleryId){
        return $this->table($this->_table)
            ->where('gallery_id', '=', (int) $galleryId)
            ->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get();
    }

    /**
     * ⭐ STOREFRONT — video cho khối "Video" ở trang chủ.
     *
     * Chỉ lấy video thuộc album ĐÃ ĐĂNG. Album còn nháp mà lọt lên trang chủ
     * thì thành ra đăng hộ thứ người ta chưa muốn công khai.
     *
     * Kèm luôn tên album để làm tiêu đề dự phòng: `caption` của từng video là
     * ô không bắt buộc, dữ liệu đang có 2/3 dòng bỏ trống — không có đường lui
     * thì danh sách bên phải hiện ra mấy dòng trắng.
     */
    public function getVideosPublished($limit = 8){
        $q = $this->table($this->_table)
            ->select('`gallery_items`.*, `galleries`.`name` AS gallery_name, `galleries`.`slug` AS gallery_slug')
            ->joinOn('galleries', 'gallery_items.gallery_id', 'galleries.id')
            ->where('gallery_items.media_type', '=', 'video')
            ->where('galleries.is_published', '=', 1)
            ->orderBy('gallery_items.sort_order', 'ASC')
            ->orderBy('gallery_items.id', 'DESC');

        if ($limit > 0) $q = $q->limit((int) $limit);
        return $q->get();
    }

    public function countByGallery($galleryId){
        $r = $this->table($this->_table)->select('COUNT(*) AS c')->where('gallery_id', '=', (int) $galleryId)->first();
        return (int) ($r['c'] ?? 0);
    }

    public function addImage($galleryId, $path, $caption = null){
        $this->insert('gallery_items', [
            'gallery_id' => (int) $galleryId, 'media_type' => 'image',
            'image' => $path, 'caption' => $caption, 'sort_order' => 0, 'create_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->lastId();
    }

    public function addVideo($galleryId, $url, $caption = null){
        $this->insert('gallery_items', [
            'gallery_id' => (int) $galleryId, 'media_type' => 'video',
            'video_url' => $url, 'caption' => $caption, 'sort_order' => 0, 'create_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->lastId();
    }

    public function getDetail($id){ return $this->getFirst($id); }
    public function remove($id){ return $this->deleteById($id); }
}
