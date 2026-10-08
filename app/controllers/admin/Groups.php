<?php
use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * QUẢN LÝ NHÓM NGƯỜI DÙNG — mở cho gara từ 08/10/2026 (migration 000093).
 *
 * Mỗi gara có bộ nhóm riêng (`groups.garage_id`), nên chủ gara tự quyết nhân
 * viên mình làm được gì mà không đụng tới gara khác. Nhóm hệ thống
 * (`garage_id IS NULL`, ví dụ `Admin`) là của Tân Phát với tư cách người vận
 * hành nền tảng — gara không thấy, không sửa.
 *
 * BA CHỐT CHẶN Ở ĐÂY, không ở giao diện:
 *
 *   1. SỞ HỮU — chỉ sửa / xoá / phân quyền nhóm của gara mình. Ẩn nút trên màn
 *      danh sách không ngăn được ai gõ /admin/groups/permission/<id nhóm Admin>.
 *
 *   2. KHÔNG TỰ NÂNG QUYỀN — người không toàn quyền chỉ tick được quyền mà
 *      NHÓM CỦA CHÍNH MÌNH đang có, và không sửa được nhóm của chính mình.
 *      Thiếu chốt này thì chủ gara vào nhóm Manager của mình tick thêm `garages`
 *      hoặc tick `permission` trên `users` là xong — leo thang trong một bước,
 *      bằng đúng cái màn mình được cấp.
 *
 *   3. KHÔNG ĐỂ NHÓM RỖNG, KHÔNG XOÁ NHÓM CÒN NGƯỜI — nhóm không có dòng quyền
 *      nào, và tài khoản mất nhóm (khoá ngoại SET NULL khi xoá nhóm), đều từng
 *      là "vào được mọi màn". RoleMiddleware đã vá chiều đó, nhưng vẫn chặn ở
 *      đây: để chủ gara tự khoá mình ra khỏi hệ thống bằng hai cú bấm thì sau
 *      đó phải nhờ Tân Phát vào CSDL sửa tay.
 */
class Groups extends Controller{

    private $__data = [];
    private $__groupModel, $__request, $__response, $__permissionModel, $__userModel;

    /** Nhóm của người đang đăng nhập + người đó có toàn quyền không (đọc một lần) */
    private $__toi = null;

    function __construct(){
        $this->__groupModel = $this->model('GroupsModel');
        $this->__permissionModel = $this->model('PermissionsModel');
        $this->__userModel = $this->model('UsersModel');
        $this->__request = new Request();
        $this->__response = new Response();
    }

    public function index(){
        $this->__data['sub_content'] = 'admin/groups/lists';

        $this->__data['page_title'] = 'Quản lý nhóm người dùng';
        $this->__data['content']['page_name'] = 'Danh sách nhóm';

        $dataGroups = $this->__groupModel->getLists();

        /* Kèm số người và "có sửa được không" để màn danh sách khỏi phải tự
           hỏi lại — và để nút Xoá biến mất đúng ở nhóm còn người, thay vì bấm
           vào rồi mới nhận câu từ chối. */
        $toi = $this->toi();
        foreach ($dataGroups as $k => $g){
            $dataGroups[$k]['so_nguoi'] = $this->__groupModel->soNguoi($g['id']);
            $dataGroups[$k]['sua_duoc'] = $this->__groupModel->suaQuyenDuoc($toi['nhom_id'], $g['id']);
        }

        $this->__data['content']['dataGroups'] = $dataGroups;
        $this->__data['content']['toanQuyen']  = $toi['toan_quyen'];
        $this->__data['content']['nhomCuaToi'] = $toi['nhom_id'];

        //Lấy dữ liệu từ flash data
        $this->__data['content']['msg'] = Session::flash('msg');
        $this->__data['content']['msgError'] = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = 'admin/groups/add';

        $this->__data['page_title'] = 'Thêm nhóm người dùng';
        $this->__data['content']['page_name'] = 'Thêm nhóm người dùng';

        //Lấy dữ liệu từ flash data
        $this->__data['content']['msg'] = Session::flash('msg');
        $this->__data['content']['errors'] = Session::flash('errors');
        $this->__data['content']['old'] = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $this->__request->rules([
            'name' => 'required|min:4'
        ]);

        $this->__request->message([
            'name.required' => 'Tên nhóm không được để trống',
            'name.min' => 'Tên nhóm không được nhỏ hơn 4 ký tự',
        ]);

        if ( $this->__request->validate()){

            $dataInsert = [
                'name' => $this->__request->getFields()['name'],
                'create_at' => date('Y-m-d H:i:s')
            ];
            // GroupsModel::add() tự gán `garage_id` của gara đang làm việc
            $addStatus = $this->__groupModel->add($dataInsert);
            if ($addStatus){
                Session::flash('msg', 'Thêm nhóm thành công. Nhóm mới CHƯA có quyền nào — '
                                    . 'vào Phân quyền để tick các màn hình cho nhóm này.');
                $this->__response->redirect('admin/groups');
            }

        }else{
            $errors = $this->__request->error();
            Session::flash('errors', $errors);
            Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
            Session::flash('old', $this->__request->getFields());
            $this->__response->redirect();
        }


    }

    public function edit($id=0){

        $groupDetail = $this->layNhom($id, true);
        if (empty($groupDetail)) return;

        $this->__data['sub_content'] = 'admin/groups/edit';

        $this->__data['page_title'] = 'Cập nhật nhóm người dùng';
        $this->__data['content']['page_name'] = 'Cập nhât nhóm người dùng';

        //Lấy dữ liệu từ flash data
        $this->__data['content']['msg'] = Session::flash('msg');
        $this->__data['content']['errors'] = Session::flash('errors');

        $oldFlash = Session::flash('old');
        if (empty($oldFlash)){;
            $this->__data['content']['old'] = $groupDetail;
        }else{
            $this->__data['content']['old'] = $oldFlash;
        }

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){

        if (empty($this->layNhom($id, true))) return;

        $this->__request->rules([
            'name' => 'required|min:4'
        ]);

        $this->__request->message([
            'name.required' => 'Tên nhóm không được để trống',
            'name.min' => 'Tên nhóm không được nhỏ hơn 4 ký tự',
        ]);

        if ( $this->__request->validate()){

            $dataUpdate = [
                'name' => $this->__request->getFields()['name'],
                'update_at' => date('Y-m-d H:i:s')
            ];
            $updateStatus = $this->__groupModel->edit($dataUpdate, $id);
            if ($updateStatus){
                Session::flash('msg', 'Cập nhật nhóm thành công');
            }

        }else{

            $errors = $this->__request->error();
            Session::flash('errors', $errors);
            Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
            Session::flash('old', $this->__request->getFields());
        }

        $this->__response->redirect();

    }

    public function delete($id=0){
        $groupDetail = $this->layNhom($id, true);
        if (empty($groupDetail)) return;

        /* NHÓM CÒN NGƯỜI THÌ KHÔNG XOÁ. Khoá ngoại `users.group_id` là
           ON DELETE SET NULL, nên xoá nhóm là mọi người trong đó mất nhóm —
           không còn dòng quyền nào để gác, và chính người vừa bấm có thể nằm
           trong số đó. Nói rõ còn bao nhiêu người để biết phải chuyển ai. */
        $soNguoi = $this->__groupModel->soNguoi($id);
        if ($soNguoi > 0){
            Session::flash('msgError', 'Không xoá được nhóm "' . $groupDetail['name'] . '": còn '
                . $soNguoi . ' tài khoản thuộc nhóm này. Chuyển họ sang nhóm khác ở màn '
                . 'Người dùng rồi xoá lại.');
            $this->__response->redirect('admin/groups');
            return;
        }

        $delete = $this->__groupModel->remove($id);

        if ($delete){
            Session::flash('msg', 'Xoá nhóm thành công');
        }
        $this->__response->redirect('admin/groups');
    }

    public function permission($id=0){

        $groupDetail = $this->layNhom($id, true);
        if (empty($groupDetail)) return;

        $permissionData = $this->__permissionModel->getPermission($id);
        $this->__data['content']['permissionData'] = $permissionData;

        /* Người không toàn quyền chỉ được tick trong phạm vi quyền của NHÓM
           MÌNH. Gửi xuống view để các ô ngoài phạm vi hiện ra dạng khoá, thay
           vì cho tick rồi lặng lẽ bỏ lúc lưu. */
        $toi = $this->toi();
        $this->__data['content']['toanQuyen']  = $toi['toan_quyen'];
        $this->__data['content']['quyenCuaToi'] = $toi['toan_quyen'] ? null : $toi['quyen'];
        $this->__data['content']['nhom']        = $groupDetail;
        $this->__data['content']['dsModule']    = $this->dsModule($toi['toan_quyen']);

        $this->__data['sub_content'] = 'admin/groups/permission';

        $this->__data['page_title'] = 'Phân quyền nhóm';
        $this->__data['content']['page_name'] = 'Phân quyền nhóm: '.$groupDetail['name']
            . (!empty($groupDetail['garage_name']) ? ' — ' . $groupDetail['garage_name'] : ' — hệ thống');

        //Lấy dữ liệu từ flash data
        $this->__data['content']['msg'] = Session::flash('msg');
        $this->__data['content']['msgError'] = Session::flash('msgError');
        $this->__data['content']['errors'] = Session::flash('errors');
        $this->__data['content']['old'] = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postPermission($id){
        $groupDetail = $this->layNhom($id, true);
        if (empty($groupDetail)) return;

        $f = $this->__request->getFields();
        $permissionData = !empty($f['permission']) && is_array($f['permission']) ? $f['permission'] : [];

        /* Gom thành danh sách phẳng "module:role" TRƯỚC khi xoá gì cả. Bản cũ
           xoá sạch quyền rồi mới chèn lại trong vòng lặp, nên một POST dạng
           permission[5]=[] (mảng có khoá, nhưng không role nào) xoá hết và chèn
           lại số không — nhóm thành rỗng, mà nhóm rỗng xưa nay vào được mọi
           màn. Tính xong rồi mới ghi thì không có trạng thái giữa đường. */
        $toi      = $this->toi();
        $duocTick = $toi['toan_quyen'] ? null : $toi['quyen'];

        /* Màn hình được phép xuất hiện trong POST này. Dựng lại từ CSDL chứ
           không tin những gì form gửi lên: form chỉ vẽ ra các màn hợp lệ, còn
           POST thì ai cũng tự soạn được. */
        $choPhep = [];
        foreach ($this->dsModule($toi['toan_quyen']) as $m) $choPhep[(int) $m['id']] = $m;

        $moi = [];
        $boQua = 0;
        foreach ($permissionData as $moduleId => $roleArr){
            $moduleId = (int) $moduleId;
            if ($moduleId <= 0 || !is_array($roleArr) || !isset($choPhep[$moduleId])) continue;
            foreach ($roleArr as $role){
                $role = (string) $role;
                if (!in_array($role, PermissionsModel::ROLES, true)) continue;
                /* `permission` chỉ có nghĩa trên màn Quản lý nhóm — RoleMiddleware
                   không gác role này ở màn nào khác. Để nó lọt vào 57 màn còn
                   lại là làm bảng phân quyền có những dòng không gác gì. */
                if ($role === 'permission' && $choPhep[$moduleId]['link'] !== 'groups') continue;
                /* CHỈ TICK ĐƯỢC QUYỀN MÌNH ĐANG CÓ. Không chặn thì chủ gara vào
                   nhóm của mình tick thêm Quản lý gara / Quản lý module là tự
                   nâng quyền, bằng đúng màn vừa được cấp. */
                if ($duocTick !== null && !in_array($moduleId . ':' . $role, $duocTick, true)){
                    $boQua++;
                    continue;
                }
                $moi[$moduleId . ':' . $role] = [$moduleId, $role];
            }
        }

        if (empty($moi)){
            Session::flash('msgError', 'Nhóm phải có ít nhất một quyền. Nhóm không quyền nào là '
                . 'nhóm không vào được màn hình nào — muốn chặn hẳn thì tắt trạng thái từng tài khoản.');
            $this->__response->redirect();
            return;
        }

        $this->__permissionModel->luuChoNhom((int) $id, array_values($moi));

        $msg = 'Phân quyền thành công';
        if ($boQua > 0){
            $msg .= '. Đã bỏ qua ' . $boQua . ' quyền ngoài phạm vi của bạn — '
                 . 'bạn chỉ cấp được những quyền nhóm của chính bạn đang có.';
        }
        Session::flash('msg', $msg);
        $this->__response->redirect();
    }

    // ===== Helper =====

    /**
     * Màn hình được đưa ra bảng phân quyền, xếp theo tên.
     *
     * Người không toàn quyền KHÔNG thấy màn mang cờ `chi_tan_phat` (Quản lý
     * gara, Quản lý module, danh mục xe...): RoleMiddleware chặn các màn đó
     * theo gara TRƯỚC cả phần kiểm quyền nhóm, nên tick vào cũng không có tác
     * dụng. Bày ra một ô tick không tác dụng là nói dối người dùng — họ tick,
     * thấy lưu thành công, rồi nhân viên vẫn không vào được và không hiểu vì sao.
     */
    private function dsModule($toanQuyen){
        $ds = (array) $this->model('ModulesModel')->getLists();
        if (!$toanQuyen){
            $ds = array_values(array_filter($ds, function($m){ return empty($m['chi_tan_phat']); }));
        }
        usort($ds, function($a, $b){ return strcmp(mb_strtolower($a['name']), mb_strtolower($b['name'])); });
        return $ds;
    }

    /**
     * Nhóm của người đang đăng nhập: ['nhom_id', 'toan_quyen', 'quyen'].
     * `quyen` dạng ["<module_id>:<role>", ...] — phạm vi tối đa họ cấp được.
     */
    private function toi(){
        if ($this->__toi !== null) return $this->__toi;

        $u   = $this->__userModel->getDetail(Session::get('dataUser'));
        $gid = !empty($u['group_id']) ? (int) $u['group_id'] : 0;

        return $this->__toi = [
            'nhom_id'    => $gid,
            'toan_quyen' => $gid > 0 && $this->__groupModel->laToanQuyen($gid),
            'quyen'      => $gid > 0 ? $this->__groupModel->quyenCua($gid) : [],
        ];
    }

    /**
     * Lấy nhóm $id, hoặc null KÈM redirect sẵn khi không được phép.
     *
     * @param bool $phaiSoHuu true = việc này cần quyền SỬA (sửa, xoá, phân quyền)
     *
     * Nơi gọi chỉ cần `if (empty(...)) return;`.
     */
    private function layNhom($id, $phaiSoHuu = false){
        $id = (int) $id;
        $g  = $id > 0 ? $this->__groupModel->getDetail($id) : null;

        if (empty($g)){
            /* Nhóm của gara khác cũng trả về câu này, không phải "bạn không có
               quyền": nói "nhóm tồn tại nhưng không phải của bạn" là tiết lộ
               hệ thống có những nhóm nào. */
            Session::flash('msgError', 'Nhóm này không tồn tại');
            $this->__response->redirect('admin/groups');
            return null;
        }

        if (!$phaiSoHuu) return $g;

        if (!$this->__groupModel->soHuu($id)){
            Session::flash('msgError', 'Nhóm "' . $g['name'] . '" thuộc '
                . (!empty($g['garage_name']) ? $g['garage_name'] : 'hệ thống')
                . ', bạn chỉ sửa được nhóm của gara mình.');
            $this->__response->redirect('admin/groups');
            return null;
        }

        /* CHỈ ĐỤNG ĐƯỢC NHÓM YẾU HƠN MÌNH — xem GroupsModel::suaQuyenDuoc.
           Chốt này gồm cả "không tự sửa nhóm của chính mình", và chặn luôn
           đường vòng: chủ gara cấp `permission` cho nhân viên, nhân viên mở
           nhóm Manager ra bỏ tick cho tới khi chủ gara mất quyền. */
        $toi = $this->toi();
        if (!$this->__groupModel->suaQuyenDuoc($toi['nhom_id'], $id)){
            Session::flash('msgError', $id === $toi['nhom_id']
                ? 'Bạn không sửa được nhóm của chính mình. Nhờ Tân Phát nếu cần đổi quyền của nhóm bạn.'
                : 'Nhóm "' . $g['name'] . '" có quyền ngang hoặc cao hơn nhóm của bạn — '
                . 'bạn chỉ sửa được nhóm thấp hơn mình.');
            $this->__response->redirect('admin/groups');
            return null;
        }

        return $g;
    }
}
