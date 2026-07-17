<?php
use App\core\Controller;
use App\core\Request;
use App\core\Session;
use App\core\Response;
use App\core\Hash;
class Auth extends Controller{

    private $__data = [];
    private $__request, $__response, $__userModel, $__loginTokenModel;

    function __construct(){
        $this->__request = new Request();
        $this->__response = new Response();
        $this->__userModel = $this->model('UsersModel');
        $this->__loginTokenModel = $this->model('LoginToken');
    }

    public function login(){
        $this->__data['sub_content'] = 'auth/login'; //gọi view

        $this->__data['page_title'] = 'Đăng nhập hệ thống';

        //Lấy dữ liệu từ flash data
        $this->__data['content']['msg'] = Session::flash('msg');
        $this->__data['content']['errors'] = Session::flash('errors');


        $this->render('layouts/auth/master_auth', $this->__data);
    }

    public function postLogin(){

        //xử lý validate
        $this->__request->rules([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $this->__request->message([
            'email.required' => 'Email không được để trống',
            'email.email' => 'Định dạng email không hợp lệ',
            'password.required' => 'Mật khẩu không được để trống'
        ]);

        if ($this->__request->validate()){

            //kiểm tra email và password để xác thực đăng nhập
            $email = $this->__request->getFields()['email'];

            // Truyền mật khẩu THÔ xuống model — model tự verify bằng bcrypt.
            // Bản cũ md5() ở đây rồi so sánh trong SQL; bcrypt có salt nên không làm vậy được.
            $password = $this->__request->getFields()['password'];

            $dataUser = $this->__userModel->checkLogin($email, $password);

            if (!empty($dataUser)){

                // Chống session fixation: cấp session id mới ngay khi đăng nhập.
                // Nếu không, kẻ tấn công ép nạn nhân dùng session id hắn biết trước,
                // đợi nạn nhân đăng nhập rồi dùng lại chính id đó để vào tài khoản.
                Session::regenerate();

                $userId = $dataUser['id'];

                // Bản cũ: md5(uniqid()) — uniqid() dựa trên thời gian nên đoán được.
                $token = Hash::randomToken();

                $dataToken = [
                    'user_id' => $userId,
                    'token' => $token,
                    'create_at' => date('Y-m-d H:i:s'),
                    'client_ip' => get_client_ip()
                ];

                $tokenId = $this->__loginTokenModel->add($dataToken);

                if (!empty($tokenId)){
                    Session::set('dataToken', $tokenId);
                }

            }else{
                Session::flash('msg', 'Email hoặc mật khẩu không chính xác');
                //$this->__response->redirect();
            }


        }else{
            $errors = $this->__request->error();
            Session::flash('errors', $errors);
            Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
            //$this->__response->redirect();
        }

        $this->__response->redirect();
    }

    public function logout(){
        $tokenId = Session::get('dataToken');
        if (!empty($tokenId)){

            //Xoá login tokem
            $this->__loginTokenModel->remove($tokenId);

            //xoá session
            Session::remove('dataToken');

            Session::remove('dataUser');
        }

        $this->__response->redirect('dang-nhap');
    }
}