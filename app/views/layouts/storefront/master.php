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

<link rel="stylesheet" href="<?php echo $asset; ?>/css/bootstrap.min.css"/>
<link rel="stylesheet" href="<?php echo $asset; ?>/css/font-awesome.min.css"/>
<link rel="stylesheet" href="<?php echo $asset; ?>/css/owl.carousel.min.css"/>
<link rel="stylesheet" href="<?php echo $asset; ?>/css/owl.theme.default.min.css"/>
<link rel="stylesheet" href="<?php echo $asset; ?>/css/own-carousel.min.css"/>
<link rel="stylesheet" href="<?php echo $asset; ?>/css/style.css"/>
<style>
:root{--brand:#164194;--brand-d:#102f6b;--ink:#222;--muted:#777;--line:#e6e6e6;--bg:#f5f6f8;--ok:#27ae60}
*{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',Roboto,Arial,sans-serif;color:var(--ink);background:var(--bg);line-height:1.5}
a{color:inherit;text-decoration:none}
img{max-width:100%;display:block}
.container{max-width:1180px;margin:0 auto;padding:0 16px}
.btn{display:inline-block;padding:8px 16px;border-radius:6px;border:1px solid transparent;cursor:pointer;font-size:14px;background:#eee;color:var(--ink)}
.btn-brand{background:var(--brand);color:#fff}.btn-brand:hover{background:var(--brand-d)}
.btn-outline{background:#fff;border-color:var(--brand);color:var(--brand)}
.btn-sm{padding:5px 10px;font-size:13px}
.badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:12px;background:#eee}
.badge-promo{background:#e7edfa;color:var(--brand)}
.badge-ok{background:#e9f7ef;color:var(--ok)}
/* header */
.topbar{background:var(--brand-d);color:#fff;font-size:13px}
.topbar .container{display:flex;justify-content:space-between;padding:6px 16px}
.topbar a{opacity:.9}
header.main{background:#fff;border-bottom:1px solid var(--line)}
header.main .container{display:flex;align-items:center;gap:20px;padding:14px 16px}
.logo{display:inline-flex;align-items:center;flex-shrink:0}
.logo img{height:56px;width:auto;display:block}
.search{flex:1;display:flex}
.search input{flex:1;padding:9px 12px;border:1px solid var(--line);border-right:0;border-radius:6px 0 0 6px;font-size:14px}
.search button{border-radius:0 6px 6px 0;border:0}
.hdr-actions{display:flex;gap:16px;align-items:center;white-space:nowrap}
.hdr-actions .cart{position:relative}
.cart .count{position:absolute;top:-8px;right:-10px;background:var(--brand);color:#fff;border-radius:50%;font-size:11px;min-width:18px;height:18px;text-align:center;line-height:18px;padding:0 4px}
/* nav */
nav.cats{background:var(--ink)}
nav.cats .container{display:flex;flex-wrap:wrap;gap:2px}
nav.cats a{color:#fff;padding:11px 14px;font-size:14px;font-weight:500;display:inline-block}
nav.cats a:hover{background:rgba(255,255,255,.12)}
nav.cats .has-sub{position:relative;display:inline-block}
nav.cats .submenu{position:absolute;left:0;top:100%;background:#fff;min-width:190px;box-shadow:0 8px 20px rgba(0,0,0,.15);border-radius:0 0 6px 6px;z-index:20;display:none}
nav.cats .has-sub:hover .submenu{display:block}
nav.cats .submenu a{display:block;color:var(--ink);padding:9px 14px;font-weight:400;border-bottom:1px solid #f0f0f0}
nav.cats .submenu a:hover{background:#f5f6f8;color:var(--brand)}
/* layout */
.wrap{display:flex;gap:22px;padding:22px 0;align-items:flex-start}
.sidebar{width:250px;flex:0 0 250px}
.content{flex:1;min-width:0}
.card{background:#fff;border:1px solid var(--line);border-radius:8px}
.card .hd{padding:12px 16px;border-bottom:1px solid var(--line);font-weight:700}
.card .bd{padding:16px}
.facet{margin-bottom:14px}
.facet h4{margin:0 0 8px;font-size:14px}
.facet label{display:block;font-size:14px;padding:3px 0;color:#444;cursor:pointer}
.facet .price-row{display:flex;gap:6px}
.facet .price-row input{width:100%;padding:6px;border:1px solid var(--line);border-radius:5px;font-size:13px}
/* product grid */
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:16px}
.pcard{background:#fff;border:1px solid var(--line);border-radius:8px;overflow:hidden;display:flex;flex-direction:column;transition:.15s}
.pcard:hover{box-shadow:0 6px 18px rgba(0,0,0,.08);transform:translateY(-2px)}
.pcard .thumb{aspect-ratio:1/1;background:#fafafa;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:40px;border-bottom:1px solid var(--line)}
.pcard .thumb img{width:100%;height:100%;object-fit:cover}
.pcard .info{padding:12px;display:flex;flex-direction:column;gap:6px;flex:1}
.pcard .pname{font-size:14px;font-weight:600;color:var(--ink);min-height:38px}
.pcard .code{font-size:12px;color:var(--muted)}
.pcard .price{color:var(--brand);font-weight:700;font-size:16px}
.pcard .old{color:var(--muted);text-decoration:line-through;font-size:13px;font-weight:400;margin-left:6px}
.pcard .foot{margin-top:auto;padding-top:8px}
.page-title{font-size:22px;margin:0 0 4px}
.crumb{font-size:13px;color:var(--muted);margin-bottom:14px}
.toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px}
.toolbar select{padding:7px 10px;border:1px solid var(--line);border-radius:6px}
/* detail */
.detail{display:flex;gap:26px;flex-wrap:wrap}
.detail .gallery{flex:0 0 380px;max-width:100%}
.detail .gallery .main-img{aspect-ratio:1/1;background:#fafafa;border:1px solid var(--line);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:70px;overflow:hidden}
.detail .gallery .main-img img{width:100%;height:100%;object-fit:contain}
.detail .gallery .thumbs{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
.detail .gallery .thumbs .tn{width:64px;height:64px;object-fit:cover;border:1px solid var(--line);border-radius:6px;cursor:pointer;background:#fafafa}
.detail .gallery .thumbs .tn:hover{border-color:var(--brand)}
.detail .meta{flex:1;min-width:280px}
.detail h1{font-size:24px;margin:0 0 8px}
.detail .big-price{font-size:30px;color:var(--brand);font-weight:800}
.detail table.spec{width:100%;border-collapse:collapse;margin-top:10px}
.detail table.spec td{border:1px solid var(--line);padding:8px 12px;font-size:14px}
.detail table.spec td:first-child{background:#fafafa;width:180px;color:#555}
.alert{padding:12px 16px;border-radius:6px;margin-bottom:16px}
.alert-info{background:#eef6fb;border:1px solid #cfe6f5;color:#215e82}
.alert-ok{background:#e9f7ef;border:1px solid #cdeeda;color:#1c7a43}
.alert-err{background:#fdecea;border:1px solid #f5c6c2;color:#9b2c22}
.form-box{max-width:440px;margin:30px auto}
.form-box .fld{margin-bottom:14px}
.form-box label{display:block;font-size:14px;margin-bottom:5px;font-weight:600}
.form-box input{width:100%;padding:10px;border:1px solid var(--line);border-radius:6px;font-size:15px}
table.cart-tbl{width:100%;border-collapse:collapse;background:#fff}
table.cart-tbl th,table.cart-tbl td{border:1px solid var(--line);padding:10px;font-size:14px}
table.cart-tbl th{background:#fafafa;text-align:left}
footer.main{background:var(--ink);color:#bbb;margin-top:36px;padding:26px 0}
footer.main a{color:#ddd}
footer .cols{display:flex;flex-wrap:wrap;gap:30px;justify-content:space-between}
footer h4{color:#fff;font-size:15px;margin:0 0 10px}
.muted{color:var(--muted)}.mt{margin-top:16px}.tr{text-align:right}.tc{text-align:center}
@media(max-width:860px){.wrap{flex-direction:column}.sidebar{width:100%;flex:auto}.hdr-actions .lbl{display:none}}
/* chat widget */
#cw-btn{position:fixed;right:20px;bottom:20px;width:56px;height:56px;border-radius:50%;background:var(--brand);color:#fff;font-size:26px;border:0;cursor:pointer;box-shadow:0 6px 20px rgba(0,0,0,.25);z-index:60}
#cw-panel{position:fixed;right:20px;bottom:86px;width:330px;max-width:calc(100vw - 40px);height:440px;max-height:70vh;background:#fff;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.25);z-index:60;display:none;flex-direction:column;overflow:hidden}
#cw-panel.open{display:flex}
#cw-head{background:var(--brand);color:#fff;padding:12px 14px;font-weight:600}
#cw-head small{display:block;font-weight:400;opacity:.85;font-size:12px}
#cw-msgs{flex:1;overflow-y:auto;padding:12px;background:#f5f6f8}
.cw-m{margin-bottom:8px;display:flex}
.cw-m .b{max-width:80%;padding:7px 11px;border-radius:12px;font-size:.9rem;line-height:1.35;word-wrap:break-word}
.cw-m.customer{justify-content:flex-end}
.cw-m.customer .b{background:#2957a4;color:#fff;border-bottom-right-radius:3px}
.cw-m.staff .b{background:#eef1f6;color:#333;border-bottom-left-radius:3px}
#cw-info{display:flex;gap:6px;margin-bottom:8px}
#cw-info input{flex:1}
#cw-foot{display:flex;gap:6px}
#cw-foot input{flex:1}
</style>
</head>
<body>

<div class="topbar"><div class="container">
    <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg> Hotline: <?php echo e(!empty($settings['hotline']) ? $settings['hotline'] : '1900 0000'); ?> — <?php echo e(!empty($settings['site_slogan']) ? $settings['site_slogan'] : 'Phụ tùng & thiết bị gara ô tô'); ?></span>
    <span>
        <?php if (!empty($memberName)): ?>
            Xin chào, <b><?php echo e($memberName); ?></b> · <a href="<?php echo _WEB_URL; ?>/thanh-vien">Tài khoản</a> · <a href="<?php echo _WEB_URL; ?>/thanh-vien/dang-xuat">Đăng xuất</a>
        <?php else: ?>
            <a href="<?php echo _WEB_URL; ?>/thanh-vien/dang-nhap">Đăng nhập</a> · <a href="<?php echo _WEB_URL; ?>/thanh-vien/dang-ky">Đăng ký</a>
        <?php endif; ?>
    </span>
</div></div>

<header class="main"><div class="container">
    <a href="<?php echo _WEB_URL; ?>/" class="logo"><img src="<?php echo _WEB_URL; ?>/public/assets/img/logo.png" alt="<?php echo e($siteName); ?>"/></a>
    <form class="search" method="get" action="<?php echo _WEB_URL; ?>/san-pham">
        <input type="text" name="q" placeholder="Tìm phụ tùng, mã, OEM..." value="<?php echo e(isset($_GET['q']) ? $_GET['q'] : ''); ?>"/>
        <button class="btn btn-brand" type="submit">Tìm</button>
    </form>
    <div class="hdr-actions">
        <a href="<?php echo _WEB_URL; ?>/thanh-vien" class="lbl">👤 <?php echo e(!empty($memberName) ? 'Tài khoản' : 'Thành viên'); ?></a>
        <a href="<?php echo _WEB_URL; ?>/gio-hang" class="cart"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg> <span class="lbl">Giỏ</span><span class="count"><?php echo e((int) $cartCount); ?></span></a>
    </div>
    <div class="py-3 top-header">
        <div class="container">
            <div class="row align-items-center g-3">
                <div class="col-2 d-md-none">
                    <button type="button" class="menu-toggle"><i class="fa fa-bars"></i></button>
                </div>
                <div class="col-7 col-md-2">
                    <div class="header__logo text-center text-md-left">
                        <a href="<?php echo _WEB_URL; ?>/"><img src="<?php echo e($logoUrl); ?>" alt="<?php echo e($siteName); ?>"/></a>
                    </div>
                </div>
                <div class="col-5 px-5 d-none d-md-block">
                    <form action="<?php echo _WEB_URL; ?>/san-pham" method="get">
                        <div class="input-group header__search">
                            <input type="search" name="q" class="form-control" placeholder="Tìm phụ tùng, mã, OEM..." value="<?php echo e(isset($_GET['q']) ? $_GET['q'] : ''); ?>"/>
                            <button type="submit" class="btn"><i class="fa fa-search" aria-hidden="true"></i></button>
                        </div>
                    </form>
                </div>
                <div class="col-5 d-none d-md-block">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="header__hotline">
                            <a href="tel:<?php echo e($hotlineTel); ?>"><i class="fa fa-mobile" aria-hidden="true"></i> Hotline: <?php echo e($hotline); ?></a>
                        </div>
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
                <div class="col-12 d-md-none">
                    <form action="<?php echo _WEB_URL; ?>/san-pham" method="get">
                        <div class="input-group header__search">
                            <input type="search" name="q" class="form-control" placeholder="Tìm kiếm..." value="<?php echo e(isset($_GET['q']) ? $_GET['q'] : ''); ?>"/>
                            <button type="submit" class="btn"><i class="fa fa-search" aria-hidden="true"></i></button>
                        </div>
                    </form>
                </div>
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
                <li><a href="<?php echo _WEB_URL; ?>/">Trang chủ</a></li>
                <li><a href="<?php echo _WEB_URL; ?>/gioi-thieu">Giới thiệu</a></li>
                <?php $renderMenu($navMenu); ?>
            </ul>
        </div>
    </nav>
</header>
<!--End .header-->

<?php $this->render($sub_content, $content); ?>

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
                        <form method="post" action="<?php echo _WEB_URL; ?>/dang-ky-ban-tin" class="mb-3">
                            <?php echo csrf_field(); ?>
                            <div class="input-group">
                                <input type="email" name="email" class="form-control" placeholder="Email..." required/>
                                <button type="submit" class="btn">Gửi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
<!--End .footer-->

<!-- Chat widget (vỏ theme, nối backend /chat/send + /chat/poll) -->
<div class="chatbox">
    <h2 class="chatbox__title">Chat với chúng tôi <i class="fa fa-angle-down"></i></h2>
    <div class="chatbox__content">
        <div id="cw-msgs"></div>
        <div id="cw-info">
            <input type="text" id="cw-name" class="form-control" placeholder="Tên của bạn"/>
            <input type="text" id="cw-phone" class="form-control" placeholder="Điện thoại"/>
        </div>
        <div id="cw-foot">
            <input type="text" id="cw-input" class="form-control" placeholder="Nhập tin nhắn..." maxlength="2000"/>
            <button type="button" id="cw-send" class="btn"><i class="fa fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<!-- Back to top -->
<a id="button" href="#"></a>

<script src="<?php echo _WEB_URL; ?>/public/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?php echo $asset; ?>/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $asset; ?>/js/owl.carousel.min.js"></script>
<script src="<?php echo $asset; ?>/js/own-carousel.min.js"></script>
<script src="<?php echo $asset; ?>/js/menu.js"></script>
<script src="<?php echo $asset; ?>/js/script.js"></script>
<script>
(function(){
    var WEB = "<?php echo _WEB_URL; ?>";
    var TOKEN = "<?php echo csrf_token(); ?>";
    var chatbox = document.querySelector('.chatbox');
    var title = chatbox ? chatbox.querySelector('.chatbox__title') : null;
    var content = chatbox ? chatbox.querySelector('.chatbox__content') : null;
    if(!chatbox || !title || !content) return;

    // Ẩn/hiện khung chat (giữ hành vi trượt của theme)
    var h = content.clientHeight;
    chatbox.style.transform = 'translateY(' + h + 'px)';
    chatbox.classList.add('hide');
    chatbox.addEventListener('click', function(e){ e.stopPropagation(); });
    title.addEventListener('click', function(){
        if(chatbox.classList.contains('hide')){
            chatbox.style.transform = 'translateY(0)';
            chatbox.classList.remove('hide');
            if(!started){ started = true; poll(); timer = setInterval(poll, 4000); }
        } else {
            chatbox.style.transform = 'translateY(' + h + 'px)';
            chatbox.classList.add('hide');
        }
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
    function poll(){
        fetch(WEB + '/chat/poll?after=' + lastId, {credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){ if(d && d.messages){ d.messages.forEach(function(m){ addMsg(m); if(m.sender==='staff' && info) info.style.display='none'; }); } })
            .catch(function(){});
    }
    function doSend(){
        var body = input.value.trim(); if(!body) return;
        var fd = new FormData(); fd.append('_token', TOKEN); fd.append('body', body);
        if(nameEl.value.trim()) fd.append('name', nameEl.value.trim());
        if(phoneEl.value.trim()) fd.append('phone', phoneEl.value.trim());
        input.value = '';
        fetch(WEB + '/chat/send', {method:'POST', body:fd, credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){ if(d && d.ok){ addMsg({id:d.id, sender:'customer', body:d.body}); if(info) info.style.display='none'; } })
            .catch(function(){});
    }
    send.addEventListener('click', doSend);
    input.addEventListener('keydown', function(e){ if(e.key === 'Enter'){ e.preventDefault(); doSend(); } });
})();
</script>
</body>
</html>
