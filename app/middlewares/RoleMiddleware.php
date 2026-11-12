<?php

namespace App\app\middlewares;

use App\core\Middleware;

use App\core\Request;
use App\core\Route;
use App\core\Session;

use App\core\Load;

use App\core\Response;

class RoleMiddleware extends Middleware{

    public function handle(){

        $response = new Response();

        $userId = Session::get('dataUser');

        //Truy vấn lấy group_id
        $groupModel = Load::model('GroupsModel');

        $permissionModel = Load::model('PermissionsModel');

        $moduleModel = Load::model('ModulesModel');

        $groupData = $groupModel->getGroupByUser($userId);

        $moduleLists = $moduleModel->getLists();
        $currentModuleId = 0;
        $currentLink = '';
        $currentModule = null;

        /* Đường dẫn đang xét: khi Route::is() hỏi hộ thì là đường dẫn được hỏi,
           còn request thật thì là URL hiện tại — y như Request::is() vẫn làm. */
        $duongHienTai = !empty($this->path) ? $this->path : Route::getPath();

        if (!empty($moduleLists)){
            foreach ($moduleLists as $item){
                if (!$this->khopModule($item['link'], $duongHienTai)) continue;

                /* LẤY LINK DÀI NHẤT KHỚP, không lấy cái gặp trước.
                   Xem lý do ở khopModule(). */
                if ($currentLink !== '' && strlen($item['link']) <= strlen($currentLink)) continue;

                $currentModuleId = $item['id'];
                $currentLink     = $item['link'];
                $currentModule   = $item;
            }
        }

        /*
         * ĐƯỜNG DẪN KHÔNG THUỘC MODULE NÀO THÌ KHÔNG CÓ GÌ ĐỂ KIỂM — CHO ĐI TIẾP.
         * Trả lời TƯỜNG MINH, thêm 11/11/2026 cùng lúc vá Route::post().
         *
         * Trước đây những đường dẫn này rơi ra khỏi mọi nhánh bên dưới và hàm
         * kết thúc mà không trả gì, tức trả về null. Với một request THẬT thì
         * vô hại (App::handleUrl() bỏ qua giá trị trả về, chỉ $response->redirect()
         * mới chặn được). Nhưng Route::is() — thứ mà route() và menu dùng để
         * hỏi "có quyền vào đây không" — đọc null qua
         * App::handleRouteMiddlewares(): `if (!$status) return false;` tức là
         * KHÔNG CÓ QUYỀN.
         *
         * Việc đó vô hại CHO ĐẾN KHI Route::post() bắt đầu gắn middleware cho
         * tuyến POST. Vì `admin/them-nhanh/danh-muc` là tuyến CHỈ CÓ POST và
         * không thuộc module nào: từ lúc nó có middleware, mọi câu hỏi
         * route('admin/them-nhanh/danh-muc') trả về false => nút "+ thêm nhanh"
         * tắt ở mọi màn hình. Cùng cảnh đó: `admin` (Tổng quan),
         * `admin/khong-co-quyen` và `admin/dia-gioi/*` (ô chọn tỉnh/phường).
         *
         * Chặn ở đây còn khoá cả chính trang `admin/khong-co-quyen` — tức là
         * vòng chuyển trang vô tận. AuthMiddleware đã đòi đăng nhập trước đó,
         * nên "đi tiếp" ở đây là đi tiếp với một người đã đăng nhập.
         */
        if (empty($currentModuleId)){
            return true;
        }

        /* Màn của riêng Tân Phát (website, kho tổng, đơn web...). Nhóm quyền
           dùng chung cho mọi gara, nên Manager của gara khác cũng "có quyền" —
           phải chặn theo GARA của tài khoản, TRƯỚC cả phần kiểm quyền nhóm, và
           kể cả với nhóm không có dòng quyền nào. Menu trái hỏi qua route()
           nên cũng tự ẩn các màn này. */
        if (!empty($currentModule['chi_tan_phat']) && !la_gara_tong()){
            if (empty($this->path)){
                $response->redirect('admin/khong-co-quyen');
            }
            return false;
        }

        $groupId        = !empty($groupData['group_id']) ? (int) $groupData['group_id'] : 0;
        $permissionData = $groupId > 0 ? (array) $permissionModel->getPermission($groupId) : [];

        /* KHÔNG CÓ NHÓM, HOẶC NHÓM KHÔNG CÓ DÒNG QUYỀN NÀO = KHÔNG CÓ QUYỀN.
           Vá 08/10/2026, cùng lúc mở màn Quản lý nhóm cho gara.

           Trước đó hai trường hợp này rơi ra khỏi MỌI nhánh kiểm tra bên dưới
           và hàm kết thúc mà không chặn gì — với một request thật thì không ai
           redirect, nên trang vẫn vẽ ra. Nghĩa là NHÓM RỖNG LÀ NHÓM MẠNH NHẤT:
           vào được mọi màn hình, kể cả Quản lý gara và Người dùng.

           Hai cửa vào có thật, không phải giả thiết:
             - Tạo một nhóm mới ở màn Quản lý nhóm rồi chưa tick quyền nào.
             - Xoá một nhóm: khoá ngoại `users.group_id` là ON DELETE SET NULL,
               nên mọi người trong nhóm đó mất nhóm.
           Mở màn Quản lý nhóm cho gara là mở cả hai cửa đó cho gara.

           CHỈ chặn khi URL khớp một module. Không khớp thì giữ nguyên nếp cũ
           (đi tiếp) — nhiều màn admin không phải module: Tổng quan, trang
           "không có quyền", thêm nhanh, tra địa giới. Chặn ở đây là khoá cả
           trang "không có quyền", tức vòng lặp chuyển trang vô tận. */
        if (!empty($currentModuleId) && empty($permissionData)){
            if (empty($this->path)){
                $response->redirect('admin/khong-co-quyen');
            }
            return false;
        }

        if ($groupId > 0){

            if (!empty($currentModuleId) && !empty($permissionData)){


                $permissionDataArr = [];

                foreach ($permissionData as $item){
                    if ($item['module_id']==$currentModuleId){
                        $permissionDataArr[] = $item['role'];
                    }
                }


                //Check quyền view (Cho phép vào module)
                if ((!empty($permissionDataArr) && !in_array('view', $permissionDataArr)) || empty($permissionDataArr)){

                    if (empty($this->path)){
                        $response->redirect('admin/khong-co-quyen');
                    }else{
                        return false;
                    }

                }

                //Check các action: thêm, sửa, xoá
                if (Request::is('admin/'.$currentLink.'/add', $this->path)){

                    if ((!empty($permissionDataArr) && !in_array('add', $permissionDataArr)) || empty($permissionDataArr)){
                        if (empty($this->path)){
                            $response->redirect('admin/khong-co-quyen');
                        }else{
                            return false;
                        }

                    }

                }elseif (Request::is('admin/'.$currentLink.'/edit/*', $this->path)){

                    if ((!empty($permissionDataArr) && !in_array('edit', $permissionDataArr)) || empty($permissionDataArr)){
                        if (empty($this->path)){
                            $response->redirect('admin/khong-co-quyen');
                        }else{
                            return false;
                        }
                    }

                }elseif (Request::is('admin/'.$currentLink.'/delete/*', $this->path)){

                    if ((!empty($permissionDataArr) && !in_array('delete', $permissionDataArr)) || empty($permissionDataArr)){
                        if (empty($this->path)){
                            $response->redirect('admin/khong-co-quyen');
                        }else{
                            return false;
                        }
                    }

                }elseif (Request::is('admin/'.$currentLink.'/permission/*', $this->path)){
                    if ((!empty($permissionDataArr) && !in_array('permission', $permissionDataArr)) || empty($permissionDataArr)){
                        if (empty($this->path)){
                            $response->redirect('admin/khong-co-quyen');
                        }else{
                            return false;
                        }
                    }
                }else{

                    /*
                     * MỌI HÌNH DẠNG ĐƯỜNG DẪN CÒN LẠI. Thêm 11/11/2026.
                     *
                     * Bốn nhánh trên nhận ra việc cần làm QUA TÊN ĐƯỜNG DẪN:
                     * /add, /edit/*, /delete/*, /permission/*. Đường dẫn không
                     * mang một trong bốn hình dạng đó thì trước đây chỉ phải
                     * qua cửa `view` ở trên — nghĩa là người CHỈ ĐƯỢC ĐỌC vẫn
                     * làm được.
                     *
                     * Mà tên đường dẫn không nói gì thì không có nghĩa là việc
                     * đó nhẹ. Đếm trên bảng tuyến thật ngày 11/11/2026: 17 tuyến
                     * POST nằm dưới một module mà đường dẫn không mang hình dạng
                     * nào — và KHÔNG MỘT TUYẾN NÀO trong 17 đó là tuyến đọc.
                     * Tất cả đều ghi dữ liệu: phát hành hoá đơn điện tử, import
                     * tồn đầu kỳ, sinh phiếu nhập kho, lưu cấu hình, gán tên
                     * miền cho gara, đặt hàng kho tổng, trả lời chat.
                     *
                     * Nên luật là ĐÓNG TRƯỚC: một request GHI vào màn của module
                     * này thì phải có quyền ghi trên module này. Tuyến mới thêm
                     * về sau tự động theo luật, không phải nhớ khai thêm ở đâu.
                     *
                     * CHỈ XÉT REQUEST GHI. Với GET thì giữ nguyên nếp cũ (`view`
                     * là đủ) — xiết cả GET là khoá mọi màn chỉ để xem: xem chi
                     * tiết, in, báo cáo, tải mẫu file. Đổi lại, những tuyến GET
                     * mà lại đổi dữ liệu (vd `garage-catalog/xoa-rieng/5`,
                     * `purchase-orders/chot/5`) vẫn chỉ đòi `view` — đó là một
                     * lỗ khác, cùng họ, chưa vá ở lần này.
                     */
                    if ($this->laRequestGhi() && !$this->coQuyenGhi($permissionDataArr)){
                        if (empty($this->path)){
                            $response->redirect('admin/khong-co-quyen');
                        }else{
                            return false;
                        }
                    }
                }

                return true;
            }

        }

        /* Có module nhưng không có nhóm (groupId = 0): đã bị cửa
           `empty($permissionData)` ở trên chặn. Xuống được đến đây là một
           trường hợp không lường trước — KHÔNG trả null, vì null bị đọc thành
           "bị chặn" ở Route::is() mà lại KHÔNG chặn gì ở request thật. Hai cách
           hiểu trái nhau cho cùng một giá trị là chỗ lỗi tiếp theo sẽ nằm. */
        return false;
    }

    /**
     * Đường dẫn $duong có thuộc module có link $link không.
     *
     * VÌ SAO KHÔNG DÙNG Request::is('admin/<link>/*', ...) NHƯ TRƯỚC — đó là
     * một lỗi có thật, phát hiện 11/11/2026 khi nhánh quyền GHI ở trên bắt đầu
     * đọc tới đúng module.
     *
     * Request::is() ghép thẳng tham số vào biểu thức chính quy, nên `/*` trong
     * 'admin/ton-kho/*' KHÔNG phải "dấu sao đường dẫn" mà là lượng từ của biểu
     * thức chính quy: "KHÔNG hoặc NHIỀU dấu gạch chéo". Vậy
     * '~^admin/ton-kho/*~' khớp cả 'admin/ton-kho-dau-ky/import' — vì khớp
     * được đúng tiền tố 'admin/ton-kho' với không dấu gạch chéo nào.
     *
     * Hậu quả: bốn module có link là tiền tố của link khác thì BẢNG QUYỀN CỦA
     * CHÍNH NÓ CHƯA BAO GIỜ ĐƯỢC ĐỌC — vòng lặp dừng ở module ngắn gặp trước:
     *     'news'    trùm 'news-categories' và 'newsletter'
     *     'ton-kho' trùm 'ton-kho-dau-ky'  và 'ton-kho-lau'
     * Nhóm Manager có `add` trên 'ton-kho-dau-ky' nhưng dòng đó vô nghĩa; thứ
     * đang gác màn Tồn đầu kỳ là quyền của 'ton-kho'. Trước đây không ai thấy
     * vì cả hai đều có `view` nên cửa `view` mở như nhau.
     *
     * Nay khớp theo BIÊN ĐƯỜNG DẪN: sau tên module phải là '/' hoặc hết chuỗi.
     * Và lấy link DÀI NHẤT trong số khớp, để module con luôn thắng module cha
     * nếu sau này có thêm cặp tiền tố mới.
     */
    private function khopModule($link, $duong){
        return (bool) preg_match('~^admin/' . preg_quote($link, '~') . '(/|$)~i', (string) $duong);
    }

    /**
     * Request này có ghi dữ liệu không?
     *
     * Dùng đúng danh sách phương thức mà CsrfMiddleware đang dùng — hai nơi
     * lệch nhau thì sẽ có phương thức được CSRF bảo vệ mà không bị phân quyền,
     * hoặc ngược lại.
     */
    private function laRequestGhi(){
        $method = isset($_SERVER['REQUEST_METHOD'])
                ? strtoupper($_SERVER['REQUEST_METHOD'])
                : 'GET';

        return in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    /**
     * Có quyền GHI nào trên module này không (thêm, sửa, hoặc xoá).
     *
     * VÌ SAO "QUYỀN NÀO CŨNG ĐƯỢC" chứ không đòi đúng `edit`: đòi `edit` là
     * khoá luôn người dùng thật. Trong CSDL đang chạy, module `ton-kho-dau-ky`
     * và `dat-hang-kho-tong` CHỈ CÓ hai quyền `add` + `view` — kể cả nhóm Admin
     * cũng không có dòng `edit` nào. Đòi `edit` ở `ton-kho-dau-ky/import` thì
     * chính Admin cũng không import được.
     *
     * Đây là cửa THỨ HAI, không phải cửa duy nhất: cửa `view` ở trên đã lọc
     * xong người ngoài module và người của gara khác. Việc còn lại của cửa này
     * chỉ là tách người ĐỌC khỏi người GHI trong cùng một module.
     */
    private function coQuyenGhi($quyenCuaModule){
        foreach (['add', 'edit', 'delete'] as $quyen){
            if (in_array($quyen, $quyenCuaModule, true)) return true;
        }

        return false;
    }
}