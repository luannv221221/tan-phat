<?php
use App\core\Session;

// Dữ liệu dùng chung cho mọi trang storefront (tính ngay trong layout)
$navMenu = $this->model('MenusModel')->getActiveTree();
$memberId = Session::get('dataMember');
$memberName = '';
if (!empty($memberId)){
    $m = $this->model('MembersModel')->getDetail($memberId);
    $memberName = !empty($m['name']) ? $m['name'] : '';
}
$cart = Session::get('cart');
$cartCount = 0;
if (!empty($cart) && is_array($cart)){ foreach ($cart as $q){ $cartCount += (int) $q; } }

// Ghi log lượt xem trang khách (TASK_109-111)
$this->model('VisitsModel')->log($memberId);

// ----- SEO / cấu hình site -----
$settings = $this->model('SettingsModel')->map();
$seo = (isset($content['seo']) && is_array($content['seo'])) ? $content['seo'] : [];
$siteName = !empty($settings['site_name']) ? $settings['site_name'] : 'Tân Phát';
$metaTitle = !empty($page_title) ? ($page_title . ' - ' . $siteName) : ($siteName . ' — ' . ($settings['site_slogan'] ?? ''));
$metaDesc = !empty($seo['description']) ? $seo['description'] : (!empty($settings['meta_description']) ? $settings['meta_description'] : '');
$metaKw = !empty($settings['meta_keywords']) ? $settings['meta_keywords'] : '';
$ogImage = !empty($seo['image']) ? $seo['image'] : (!empty($settings['og_image']) ? $settings['og_image'] : '');
$ogImageUrl = $ogImage !== '' ? media_url($ogImage) : '';
$ogType = !empty($seo['type']) ? $seo['type'] : 'website';
$canonical = _WEB_URL . '/' . (isset($_GET['module']) ? trim($_GET['module'], '/') : '');

// Đường dẫn asset của theme storefront + logo
$asset = _WEB_URL . '/public/assets/storefront';
$logoUrl = !empty($settings['logo']) ? media_url($settings['logo']) : ($asset . '/images/logo.png');
$hotline = !empty($settings['hotline']) ? $settings['hotline'] : '1900 0000';
$hotlineTel = preg_replace('/[^0-9+]/', '', $hotline);
$slogan = !empty($settings['site_slogan']) ? $settings['site_slogan'] : 'Phụ tùng & thiết bị gara ô tô';

// Thanh xanh trên cùng bật/tắt ở admin > Cấu hình website. So với '0' chứ không
// so với '1' vì lý do giống bộ lọc xe bên dưới: thiếu khoá thì vẫn hiện.
$showTopbar = ($settings['show_topbar'] ?? '1') !== '0';

/**
 * Link tài khoản (Đăng ký/Đăng nhập, hoặc Tài khoản/Đăng xuất).
 *
 * Bình thường nằm ở thanh xanh. Tắt thanh xanh thì dời xuống hàng logo, vì
 * menu website không có mục đăng nhập nào — mất thanh xanh là khách hết lối
 * vào tài khoản.
 *
 * Viết thành closure chứ không tách ra partial: $this->render() dùng
 * require_once, gọi lần thứ hai (bản mobile) sẽ không in ra gì cả.
 */
$renderAccountLinks = function () use ($memberName){
    if (!empty($memberName)){
        echo '<span class="hello">Xin chào, <b>' . e($memberName) . '</b></span>';
        echo '<a href="' . _WEB_URL . '/thanh-vien">Tài khoản</a>';
        echo '<a href="' . _WEB_URL . '/thanh-vien/dang-xuat">Đăng xuất</a>';
    } else {
        echo '<a href="' . _WEB_URL . '/thanh-vien/dang-ky">Đăng ký</a>';
        echo '<a href="' . _WEB_URL . '/thanh-vien/dang-nhap">Đăng nhập</a>';
    }
};

// Bộ render menu đa cấp cho theme (<ul class="menu"> lồng nhau)
$renderMenu = function ($items) use (&$renderMenu){
    foreach ($items as $it){
        $children = !empty($it['children']) ? $it['children'] : [];
        $tgt = (!empty($it['target']) && $it['target'] === '_blank') ? ' target="_blank"' : '';
        echo '<li><a href="' . e(nav_url($it['url'])) . '"' . $tgt . '>' . e($it['label']) . '</a>';
        if (!empty($children)){
            echo '<ul>';
            $renderMenu($children);
            echo '</ul>';
        }
        echo '</li>';
    }
};
?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title><?php echo e($metaTitle); ?></title>
<?php if ($metaDesc !== ''): ?><meta name="description" content="<?php echo e($metaDesc); ?>"/><?php endif; ?>
<?php if ($metaKw !== ''): ?><meta name="keywords" content="<?php echo e($metaKw); ?>"/><?php endif; ?>
<link rel="canonical" href="<?php echo e($canonical); ?>"/>
<meta property="og:site_name" content="<?php echo e($siteName); ?>"/>
<meta property="og:title" content="<?php echo e($metaTitle); ?>"/>
<meta property="og:type" content="<?php echo e($ogType); ?>"/>
<meta property="og:url" content="<?php echo e($canonical); ?>"/>
<?php if ($metaDesc !== ''): ?><meta property="og:description" content="<?php echo e($metaDesc); ?>"/><?php endif; ?>
<?php if ($ogImageUrl !== ''): ?><meta property="og:image" content="<?php echo e($ogImageUrl); ?>"/><meta name="twitter:card" content="summary_large_image"/><?php endif; ?>

<link rel="stylesheet" href="<?php echo e($asset); ?>/css/bootstrap.min.css"/>
<link rel="stylesheet" href="<?php echo e($asset); ?>/css/font-awesome.min.css"/>
<link rel="stylesheet" href="<?php echo e($asset); ?>/css/owl.carousel.min.css"/>
<link rel="stylesheet" href="<?php echo e($asset); ?>/css/owl.theme.default.min.css"/>
<link rel="stylesheet" href="<?php echo e($asset); ?>/css/own-carousel.min.css"/>
<link rel="stylesheet" href="<?php echo asset("public/assets/storefront/css/style.css"); ?>"/>
<link rel="stylesheet" href="<?php echo asset("public/assets/storefront/css/car-filter.css"); ?>"/>
<?php /* Lớp thiết kế — PHẢI nạp sau style.css để ghi đè được theme mua sẵn.
         Toàn bộ phần làm lại giao diện nằm gọn trong file này, gỡ ra chỉ cần
         bỏ đúng dòng dưới. */ ?>
<link rel="stylesheet" href="<?php echo asset("public/assets/storefront/css/theme.css"); ?>"/>
<style>
/* ---- Bổ sung nhỏ cho storefront (không có trong theme) ---- */
:root{--sf-accent:#2957a4;--sf-accent-d:#1d418d}
.btn-primary{background:var(--sf-accent);border-color:var(--sf-accent)}
.btn-primary:hover,.btn-primary:focus{background:var(--sf-accent-d);border-color:var(--sf-accent-d)}
.btn-outline-primary{color:var(--sf-accent);border-color:var(--sf-accent)}
.btn-outline-primary:hover{background:var(--sf-accent);border-color:var(--sf-accent);color:#fff}
.text-accent{color:var(--sf-accent)!important}
/* Link tài khoản: ở thanh xanh thì chữ trắng, dời xuống hàng logo thì theo màu accent */
.topbar__right .hello{color:#fff}
.header__account{display:flex;align-items:center;gap:14px;white-space:nowrap;font-size:.95rem}
.header__account a{color:var(--sf-accent)}
.header__account a:hover{text-decoration:underline}
.header__account .hello{color:#555}
.price-now{color:var(--sf-accent);font-weight:700}
.price-old-sm{color:#999;text-decoration:line-through;font-size:.9rem;margin-left:6px}
.sf-section{margin:34px 0}
.sf-page{margin:24px 0}
.sf-page-title{font-size:1.8rem;font-weight:600;margin-bottom:6px}
.sf-flash{padding:12px 16px;border-radius:6px;margin:16px 0;font-size:.95rem}
.sf-flash-ok{background:#e9f7ef;border:1px solid #cdeeda;color:#1c7a43}
.sf-flash-err{background:#fdecea;border:1px solid #f5c6c2;color:#9b2c22}
.sf-flash-info{background:#eef6fb;border:1px solid #cfe6f5;color:#215e82}
.breadcrumb-sf{font-size:.9rem;color:#777;padding:14px 0}
.breadcrumb-sf a{color:#2957a4}
.pagination-sf{display:flex;gap:6px;flex-wrap:wrap;justify-content:center;padding:24px 0}
.pagination-sf a,.pagination-sf span{display:inline-block;padding:7px 13px;border:1px solid #ddd;border-radius:5px;color:#333;font-size:.9rem}
.pagination-sf .active{background:#2957a4;border-color:#2957a4;color:#fff}
.empty-box{text-align:center;color:#888;padding:50px 20px}
.products--item .item__image img{width:100%;height:190px;object-fit:cover}
.categories .categories__item img{width:100%;height:180px;object-fit:cover}
/* Chat widget nối backend */
#cw-msgs{height:230px;overflow-y:auto;background:#fff;border:1px solid #e6e6e6;border-radius:6px;padding:10px;margin-bottom:10px}
.cw-m{margin-bottom:8px;display:flex}
.cw-m .b{max-width:80%;padding:7px 11px;border-radius:12px;font-size:.9rem;line-height:1.35;word-wrap:break-word}
.cw-m.customer{justify-content:flex-end}
.cw-m.customer .b{background:#2957a4;color:#fff;border-bottom-right-radius:3px}
.cw-m.staff .b{background:#eef1f6;color:#333;border-bottom-left-radius:3px}
#cw-info{display:flex;gap:6px;margin-bottom:8px}
#cw-info input{flex:1}
#cw-foot{display:flex;gap:6px}
#cw-foot input{flex:1}
/* Nhãn khuyến mãi: bo tròn, đậm, màu đỏ cho bật khỏi ảnh.
   Bản theme là ô vuông xanh trùng màu nút nên nhìn như chữ dán vào ảnh. */
.item--sales{background:#d93025;border-radius:4px;padding:3px 8px;font-size:.8rem;font-weight:700;line-height:1.3;box-shadow:0 1px 3px rgba(0,0,0,.25)}
/* Thu gọn khung chat bằng max-height thay vì translateY đo bằng JS.
   Thanh tiêu đề luôn nằm nguyên chỗ nên lúc nào cũng bấm được, và
   trạng thái đóng có sẵn từ HTML nên không nháy khi chuyển trang. */
.chatbox .chatbox__content{max-height:80vh;overflow:hidden;transition:max-height .3s ease,padding .3s ease}
.chatbox.hide .chatbox__content{max-height:0;padding-top:0;padding-bottom:0}
</style>
</head>
<body>

<header class="header">
    <?php if ($showTopbar): ?>
    <div class="topbar">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-md-8">
                    <ul class="topbar__left">
                        <li><a href="<?php echo _WEB_URL; ?>/gio-hang">Xây dựng báo giá</a></li>
                        <li><a href="<?php echo _WEB_URL; ?>/lien-he">Hệ thống chi nhánh</a></li>
                    </ul>
                </div>
                <div class="col-12 col-md-4">
                    <div class="topbar__right"><?php $renderAccountLinks(); ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="py-3 top-header">
        <div class="container">
            <div class="row align-items-center g-3">
                <div class="col-2 d-md-none">
                    <button type="button" class="menu-toggle"><i class="fa fa-bars"></i></button>
                </div>
                <div class="col-7 col-md-4">
                    <?php /* text-md-start chứ không phải text-md-left: bản đang dùng là
                             Bootstrap 5, tên .text-md-left của bản 4 không còn tồn tại nên
                             .text-center thắng và logo bị đẩy vào giữa cột. */ ?>
                    <div class="header__logo text-center text-md-start">
                        <a href="<?php echo _WEB_URL; ?>/"><img src="<?php echo e($logoUrl); ?>" alt="<?php echo e($siteName); ?>"/></a>
                    </div>
                </div>
                <div class="col-md-8 d-none d-md-block">
                    <?php /* Gom hotline + tài khoản + giỏ hàng thành MỘT cụm bên phải.
                             Trước đây hotline bị justify-content-between đẩy ra giữa, cách
                             giỏ hàng hơn 600px toàn khoảng trống. */ ?>
                    <div class="d-flex align-items-center justify-content-end" style="gap:26px">
                        <div class="header__hotline">
                            <a href="tel:<?php echo e($hotlineTel); ?>">
                                <i class="fa fa-phone" aria-hidden="true"></i>
                                <span>
                                    <span class="nhan">Hotline tư vấn</span>
                                    <span class="so"><?php echo e($hotline); ?></span>
                                </span>
                            </a>
                        </div>
                        <div class="d-flex align-items-center" style="gap:22px">
                            <?php if (!$showTopbar): ?>
                            <div class="header__account"><?php $renderAccountLinks(); ?></div>
                            <?php endif; ?>
                            <div class="header__cart">
                                <a href="<?php echo _WEB_URL; ?>/gio-hang">
                                    <span class="header__cart--item"> Giỏ hàng </span>
                                    <span class="header__cart--item">
                                        <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                                        <span class="count"><?php echo (int) $cartCount; ?></span>
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-3 d-md-none">
                    <div class="d-flex gap-2 justify-content-end">
                        <div class="header__cart">
                            <a href="<?php echo _WEB_URL; ?>/gio-hang">
                                <span class="header__cart--item">
                                    <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                                    <span class="count"><?php echo (int) $cartCount; ?></span>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
                <?php if (!$showTopbar): ?>
                <!-- Trên điện thoại không đủ chỗ nhét cạnh logo, cho xuống hàng riêng -->
                <div class="col-12 d-md-none">
                    <div class="header__account justify-content-end"><?php $renderAccountLinks(); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <nav class="header__primary-menu">
        <div class="d-md-none py-2 px-3 text-center">
            <a href="<?php echo _WEB_URL; ?>/"><img src="<?php echo e($logoUrl); ?>" alt=""/></a>
            <button type="button" class="close">&times;</button>
        </div>
        <div class="container">
            <ul class="menu">
                <?php
                // Toàn bộ menu lấy từ bảng `menus` (quản trị ở admin > Menu website).
                // Trước đây "Trang chủ" và "Giới thiệu" được viết cứng ở đây, trong khi
                // bảng `menus` cũng đã có "Trang chủ" -> nav hiện 2 lần. Nay bỏ phần
                // viết cứng; migration 000042 thêm "Giới thiệu" vào bảng cho đủ mục.
                $renderMenu($navMenu);
                ?>
            </ul>
        </div>
    </nav>
</header>
<!--End .header-->

<?php
/* ---------------------------------------------------------------------------
   THANH LỌC XE — nay nằm NGOÀI <header>, ngay dưới dải menu xanh.

   Trước đây nó kẹp giữa hàng logo và menu. Hai cái phiền:
     - Mở ô chọn ra là danh sách đổ xuống đè lên chính dải menu.
     - Đầu trang phình lên 225px, ghim khi cuộn thì mất 1/4 màn hình.

   Chỗ đặt: đầu vùng nội dung. Trang chủ có băng-rôn nên thứ tự thành
   menu -> băng-rôn -> thanh lọc; các trang khác không có băng-rôn nên
   thành menu -> thanh lọc -> nội dung. Một luật duy nhất.

   Vì sao phải dựng vào biến rồi mới in: view con (home.php...) chạy qua
   Template::run() bằng eval, ở trong đó $this là đối tượng Template chứ
   KHÔNG phải Controller — nên tự nó không gọi được $this->render(). Master
   dựng sẵn chuỗi HTML rồi truyền xuống, view con chỉ việc in ra.

   Bật/tắt tại admin > Cấu hình website. So với '0' chứ không so với '1':
   chỉ ẩn khi khách CHỦ ĐỘNG tắt, còn thiếu khoá (chưa chạy migration 000050)
   thì vẫn hiện — đẩy code lên trước migration không làm mất thanh lọc.
   --------------------------------------------------------------------------- */
$thanhLoc = '';
if (($settings['show_car_filter'] ?? '1') !== '0'){
    ob_start();
    $this->render('layouts/storefront/partials/car-filter');
    $thanhLoc = ob_get_clean();
}

// Trang chủ tự in sau băng-rôn; các trang còn lại để master in ngay đây.
$laTrangChu = (isset($sub_content) && $sub_content === 'storefront/home');
if (!$laTrangChu) echo $thanhLoc;

$content['thanhLoc'] = $laTrangChu ? $thanhLoc : '';
$this->render($sub_content, $content);
?>

<footer class="footer py-4">
    <div class="container">
        <div class="footer__logo">
            <p><a href="<?php echo _WEB_URL; ?>/"><img src="<?php echo e($logoUrl); ?>" alt="" style="max-height:70px;width:auto"/></a></p>
            <h2><?php echo e($siteName); ?></h2>
        </div>
        <hr/>
        <div class="footer__address">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="footer__address--item">
                        <h4><i class="fa fa-map-marker" aria-hidden="true"></i> Địa chỉ</h4>
                        <p><?php echo e(!empty($settings['address']) ? $settings['address'] : 'Đang cập nhật'); ?></p>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="footer__address--item">
                        <h4><i class="fa fa-phone" aria-hidden="true"></i> Liên hệ</h4>
                        <p>Hotline: <?php echo e($hotline); ?></p>
                        <p>Email: <?php echo e(!empty($settings['email']) ? $settings['email'] : 'info@tanphat.vn'); ?></p>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="footer__address--item">
                        <h4><i class="fa fa-info-circle" aria-hidden="true"></i> Giới thiệu</h4>
                        <p><?php echo e($slogan); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer__inner pt-4">
            <div class="row">
                <div class="col-12 col-md-9">
                    <div class="footer__inner--menu">
                        <ul>
                            <li><a href="<?php echo _WEB_URL; ?>/san-pham">Sản phẩm</a></li>
                            <li><a href="<?php echo _WEB_URL; ?>/tin-tuc">Tin tức</a></li>
                            <li><a href="<?php echo _WEB_URL; ?>/du-an">Dự án</a></li>
                            <li><a href="<?php echo _WEB_URL; ?>/thu-vien">Thư viện</a></li>
                            <li><a href="<?php echo _WEB_URL; ?>/lien-he">Liên hệ</a></li>
                        </ul>
                        <ul>
                            <li><a href="<?php echo _WEB_URL; ?>/gio-hang">Giỏ hàng / Báo giá</a></li>
                            <li><a href="<?php echo _WEB_URL; ?>/thanh-vien">Tài khoản thành viên</a></li>
                            <li><a href="<?php echo _WEB_URL; ?>/thanh-vien/dang-ky">Đăng ký thành viên</a></li>
                        </ul>
                        <ul>
                            <?php foreach (array_slice($navMenu, 0, 6) as $mItem): ?>
                                <li><a href="<?php echo e(nav_url($mItem['url'])); ?>"><?php echo e($mItem['label']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="footer__inner--subscribe">
                        <p>Đăng ký nhận bản tin</p>
                        <form method="post" action="<?php echo _WEB_URL; ?>/dang-ky-ban-tin" class="mb-2">
                            <?php echo csrf_field(); ?>
                            <div class="input-group">
                                <input type="email" name="email" class="form-control" placeholder="Email..." required/>
                                <button type="submit" class="btn">Gửi</button>
                            </div>
                        </form>
                        <?php
                        // Kết quả đăng ký hiện ngay dưới form, ở đúng trang vừa bấm.
                        // Dùng khoá riêng `newsletter_flash` để không giành mất
                        // `store_flash` của form liên hệ.
                        $__nlFlash = \App\core\Session::flash('newsletter_flash');
                        if (!empty($__nlFlash) && strpos($__nlFlash, '|') !== false):
                            list($__nlType, $__nlMsg) = explode('|', $__nlFlash, 2);
                        ?>
                            <div class="alert <?php echo $__nlType === 'ok' ? 'alert-success' : 'alert-danger'; ?> py-2 px-3 mb-3" style="font-size:14px">
                                <?php echo e($__nlMsg); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
<!--End .footer-->

<!-- Chat widget (vỏ theme, nối backend /chat/send + /chat/poll) -->
<div class="chatbox hide">
    <h2 class="chatbox__title">Chat với chúng tôi <i class="fa fa-angle-down"></i></h2>
    <div class="chatbox__content">
        <div id="cw-msgs"></div>
        <div id="cw-info">
            <input type="text" id="cw-name" class="form-control" placeholder="Tên của bạn"/>
            <input type="tel" inputmode="numeric" maxlength="11" pattern="0[0-9]{9,10}" id="cw-phone" class="form-control" placeholder="Điện thoại"/>
        </div>
        <div id="cw-err" class="text-danger small px-2" style="display:none"></div>
        <div id="cw-foot">
            <input type="text" id="cw-input" class="form-control" placeholder="Nhập tin nhắn..." maxlength="2000"/>
            <button type="button" id="cw-send" class="btn"><i class="fa fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<!-- Back to top -->
<a id="button" href="#"></a>

<script src="<?php echo _WEB_URL; ?>/public/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?php echo e($asset); ?>/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo asset("public/assets/storefront/js/owl.carousel.min.js"); ?>"></script>
<script src="<?php echo asset("public/assets/storefront/js/own-carousel.min.js"); ?>"></script>
<script src="<?php echo asset("public/assets/storefront/js/menu.js"); ?>"></script>
<script src="<?php echo asset("public/assets/storefront/js/script.js"); ?>"></script>
<script>
(function(){
    var WEB = "<?php echo _WEB_URL; ?>";
    var TOKEN = "<?php echo csrf_token(); ?>";
    var chatbox = document.querySelector('.chatbox');
    var title = chatbox ? chatbox.querySelector('.chatbox__title') : null;
    var content = chatbox ? chatbox.querySelector('.chatbox__content') : null;
    if(!chatbox || !title || !content) return;

    // Đóng/mở khung chat.
    //
    // Bản cũ đo content.clientHeight MỘT LẦN lúc tải trang rồi
    // translateY(h) để đẩy khung xuống. Hai hệ quả:
    //   - Chiều cao thay đổi sau đó (ẩn ô tên/SĐT, hiện dòng lỗi) nhưng vẫn
    //     đẩy theo h cũ => đẩy quá tay, thanh tiêu đề tụt gần hết khỏi màn
    //     hình, rất khó bấm lại.
    //   - Lúc tải trang khung hiện đủ rồi mới bị JS đẩy xuống => nháy/giật
    //     ở mỗi lần chuyển trang.
    //
    // Nay chỉ bật/tắt class `hide`, còn việc thu gọn do CSS lo bằng
    // max-height — không cần đo gì, và trạng thái đóng đã có sẵn trong
    // HTML nên không còn nháy.
    chatbox.addEventListener('click', function(e){ e.stopPropagation(); });
    title.addEventListener('click', function(){
        var opening = chatbox.classList.contains('hide');
        chatbox.classList.toggle('hide', !opening);
        if(opening && !started){ started = true; poll(); timer = setInterval(poll, 4000); }
    });

    var msgs = document.getElementById('cw-msgs');
    var input = document.getElementById('cw-input');
    var send = document.getElementById('cw-send');
    var nameEl = document.getElementById('cw-name');
    var phoneEl = document.getElementById('cw-phone');
    var info = document.getElementById('cw-info');
    var lastId = 0, timer = null, started = false;

    function addMsg(m){
        var row = document.createElement('div');
        row.className = 'cw-m ' + (m.sender === 'staff' ? 'staff' : 'customer');
        var b = document.createElement('div'); b.className = 'b'; b.textContent = m.body;
        row.appendChild(b); msgs.appendChild(row); msgs.scrollTop = msgs.scrollHeight;
        if(m.id && m.id > lastId) lastId = m.id;
    }
    function hideInfo(){ if(info) info.style.display = 'none'; }

    function poll(){
        fetch(WEB + '/chat/poll?after=' + lastId, {credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if(!d) return;
                // Hội thoại đã có tên/SĐT thì không hỏi lại nữa.
                // Bản cũ chỉ ẩn khi NHÂN VIÊN trả lời, nên tải lại trang là
                // 2 ô trắng hiện lại dù khách đã khai từ trước.
                if(d.hasInfo) hideInfo();
                if(d.messages){ d.messages.forEach(addMsg); }
            })
            .catch(function(){});
    }
    var errEl = document.getElementById('cw-err');
    function showErr(msg){
        if(!errEl) return;
        errEl.textContent = msg || '';
        errEl.style.display = msg ? 'block' : 'none';
    }

    function doSend(){
        var body = input.value.trim(); if(!body) return;
        var fd = new FormData(); fd.append('_token', TOKEN); fd.append('body', body);
        if(nameEl.value.trim()) fd.append('name', nameEl.value.trim());
        if(phoneEl.value.trim()) fd.append('phone', phoneEl.value.trim());
        showErr('');

        // KHÔNG xoá ô nhập trước khi biết kết quả: server có thể từ chối
        // (vd SĐT sai) và bản cũ xoá trắng luôn => khách mất tin vừa gõ mà
        // không hiểu vì sao không gửi được.
        fetch(WEB + '/chat/send', {method:'POST', body:fd, credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if(d && d.ok){
                    input.value = '';
                    addMsg({id:d.id, sender:'customer', body:d.body});
                    hideInfo();
                } else {
                    showErr((d && d.message) ? d.message : 'Không gửi được, vui lòng thử lại.');
                }
            })
            .catch(function(){ showErr('Mất kết nối, vui lòng thử lại.'); });
    }
    send.addEventListener('click', doSend);
    input.addEventListener('keydown', function(e){ if(e.key === 'Enter'){ e.preventDefault(); doSend(); } });
})();
</script>
</body>
</html>
