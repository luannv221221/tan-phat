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
