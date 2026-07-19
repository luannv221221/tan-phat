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
<style>
:root{--brand:#c0392b;--brand-d:#96271b;--ink:#222;--muted:#777;--line:#e6e6e6;--bg:#f5f6f8;--ok:#27ae60}
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
.badge-promo{background:#fdecea;color:var(--brand)}
.badge-ok{background:#e9f7ef;color:var(--ok)}
/* header */
.topbar{background:var(--brand-d);color:#fff;font-size:13px}
.topbar .container{display:flex;justify-content:space-between;padding:6px 16px}
.topbar a{opacity:.9}
header.main{background:#fff;border-bottom:1px solid var(--line)}
header.main .container{display:flex;align-items:center;gap:20px;padding:14px 16px}
.logo{font-size:24px;font-weight:800;color:var(--brand);white-space:nowrap}
.logo span{color:var(--ink)}
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
.cw-m .b{max-width:78%;padding:8px 11px;border-radius:12px;font-size:14px;line-height:1.35;word-wrap:break-word}
.cw-m.customer{justify-content:flex-end}
.cw-m.customer .b{background:var(--brand);color:#fff;border-bottom-right-radius:3px}
.cw-m.staff .b{background:#fff;border:1px solid #e6e6e6;border-bottom-left-radius:3px}
#cw-foot{border-top:1px solid #eee;padding:8px;display:flex;gap:6px}
#cw-foot input{flex:1;padding:9px;border:1px solid #e6e6e6;border-radius:20px;font-size:14px}
#cw-foot button{border:0;background:var(--brand);color:#fff;border-radius:50%;width:38px;height:38px;cursor:pointer}
#cw-info{padding:8px;display:flex;gap:6px;border-top:1px solid #eee}
#cw-info input{flex:1;padding:7px;border:1px solid #e6e6e6;border-radius:6px;font-size:13px}
</style>
</head>
<body>

<div class="topbar"><div class="container">
    <span><i>☎</i> Hotline: <?php echo e(!empty($settings['hotline']) ? $settings['hotline'] : '1900 0000'); ?> — <?php echo e(!empty($settings['site_slogan']) ? $settings['site_slogan'] : 'Phụ tùng & thiết bị gara ô tô'); ?></span>
    <span>
        <?php if (!empty($memberName)): ?>
            Xin chào, <b><?php echo e($memberName); ?></b> · <a href="<?php echo _WEB_URL; ?>/thanh-vien">Tài khoản</a> · <a href="<?php echo _WEB_URL; ?>/thanh-vien/dang-xuat">Đăng xuất</a>
        <?php else: ?>
            <a href="<?php echo _WEB_URL; ?>/thanh-vien/dang-nhap">Đăng nhập</a> · <a href="<?php echo _WEB_URL; ?>/thanh-vien/dang-ky">Đăng ký</a>
        <?php endif; ?>
    </span>
</div></div>

<header class="main"><div class="container">
    <a href="<?php echo _WEB_URL; ?>/" class="logo">TÂN <span>PHÁT</span></a>
    <form class="search" method="get" action="<?php echo _WEB_URL; ?>/san-pham">
        <input type="text" name="q" placeholder="Tìm phụ tùng, mã, OEM..." value="<?php echo e(isset($_GET['q']) ? $_GET['q'] : ''); ?>"/>
        <button class="btn btn-brand" type="submit">Tìm</button>
    </form>
    <div class="hdr-actions">
        <a href="<?php echo _WEB_URL; ?>/thanh-vien" class="lbl">👤 <?php echo e(!empty($memberName) ? 'Tài khoản' : 'Thành viên'); ?></a>
        <a href="<?php echo _WEB_URL; ?>/gio-hang" class="cart">🛒 <span class="lbl">Giỏ</span><span class="count"><?php echo e((int) $cartCount); ?></span></a>
    </div>
</div></header>

<nav class="cats"><div class="container">
    <?php
    foreach ($navMenu as $m){
        $children = !empty($m['children']) ? $m['children'] : [];
        $tgt = ($m['target'] === '_blank') ? ' target="_blank"' : '';
        if (empty($children)){
            echo '<a href="'.e(nav_url($m['url'])).'"'.$tgt.'>'.e($m['label']).'</a>';
        } else {
            echo '<div class="has-sub"><a href="'.e(nav_url($m['url'])).'"'.$tgt.'>'.e($m['label']).' ▾</a><div class="submenu">';
            foreach ($children as $ch){
                $ctgt = ($ch['target'] === '_blank') ? ' target="_blank"' : '';
                echo '<a href="'.e(nav_url($ch['url'])).'"'.$ctgt.'>'.e($ch['label']).'</a>';
            }
            echo '</div></div>';
        }
    }
    ?>
</div></nav>

<main class="container">
    <?php $this->render($sub_content, $content); ?>
</main>

<footer class="main"><div class="container cols">
    <div>
        <h4>TÂN PHÁT</h4>
        <div class="muted" style="max-width:320px">Chuyên phụ tùng và thiết bị gara ô tô chính hãng. Tư vấn tương thích theo hãng — model — đời xe.</div>
    </div>
    <div>
        <h4>Hỗ trợ</h4>
        <div><a href="<?php echo _WEB_URL; ?>/san-pham">Sản phẩm</a></div>
        <div><a href="<?php echo _WEB_URL; ?>/gio-hang">Giỏ hàng / Yêu cầu báo giá</a></div>
        <div><a href="<?php echo _WEB_URL; ?>/thanh-vien">Tài khoản thành viên</a></div>
        <div><a href="<?php echo _WEB_URL; ?>/lien-he">Liên hệ</a></div>
    </div>
    <div>
        <h4>Liên hệ</h4>
        <div class="muted">Hotline: <?php echo e(!empty($settings['hotline']) ? $settings['hotline'] : '1900 0000'); ?></div>
        <div class="muted">Email: <?php echo e(!empty($settings['email']) ? $settings['email'] : 'info@tanphat.vn'); ?></div>
        <?php if (!empty($settings['address'])): ?><div class="muted"><?php echo e($settings['address']); ?></div><?php endif; ?>
    </div>
    <div>
        <h4>Nhận bản tin</h4>
        <div class="muted" style="margin-bottom:8px;max-width:240px">Đăng ký nhận thông tin sản phẩm & khuyến mãi.</div>
        <form method="post" action="<?php echo _WEB_URL; ?>/dang-ky-ban-tin" style="display:flex;gap:6px;max-width:260px">
            <?php echo csrf_field(); ?>
            <input type="email" name="email" placeholder="Email của bạn" required style="flex:1;padding:7px 10px;border-radius:6px;border:1px solid #555;background:#2a2a2a;color:#eee"/>
            <button type="submit" class="btn btn-brand btn-sm">Đăng ký</button>
        </form>
    </div>
</div></footer>

<button id="cw-btn" title="Chat hỗ trợ">💬</button>
<div id="cw-panel">
    <div id="cw-head">Hỗ trợ trực tuyến<small>Tân Phát thường trả lời trong ít phút</small></div>
    <div id="cw-msgs"></div>
    <div id="cw-info">
        <input type="text" id="cw-name" placeholder="Tên của bạn"/>
        <input type="text" id="cw-phone" placeholder="SĐT"/>
    </div>
    <div id="cw-foot">
        <input type="text" id="cw-input" placeholder="Nhập tin nhắn..." maxlength="2000"/>
        <button id="cw-send" title="Gửi">➤</button>
    </div>
</div>
<script>
(function(){
    var WEB = "<?php echo _WEB_URL; ?>";
    var TOKEN = "<?php echo csrf_token(); ?>";
    var btn=document.getElementById('cw-btn'), panel=document.getElementById('cw-panel');
    var msgs=document.getElementById('cw-msgs'), input=document.getElementById('cw-input'), send=document.getElementById('cw-send');
    var nameEl=document.getElementById('cw-name'), phoneEl=document.getElementById('cw-phone'), info=document.getElementById('cw-info');
    var lastId=0, timer=null, started=false;

    function addMsg(m){
        var row=document.createElement('div'); row.className='cw-m '+(m.sender==='staff'?'staff':'customer');
        var b=document.createElement('div'); b.className='b'; b.textContent=m.body; row.appendChild(b);
        msgs.appendChild(row); msgs.scrollTop=msgs.scrollHeight;
        if(m.id && m.id>lastId) lastId=m.id;
    }
    function poll(){
        fetch(WEB+'/chat/poll?after='+lastId,{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(d){
            if(d && d.messages){ d.messages.forEach(function(m){ addMsg(m); if(m.sender==='staff') info.style.display='none'; }); }
        }).catch(function(){});
    }
    function open(){
        panel.classList.add('open');
        if(!started){ started=true; poll(); timer=setInterval(poll,4000); }
    }
    btn.addEventListener('click',function(){ panel.classList.contains('open')?panel.classList.remove('open'):open(); });

    function doSend(){
        var body=input.value.trim(); if(!body) return;
        var fd=new FormData(); fd.append('_token',TOKEN); fd.append('body',body);
        if(nameEl.value.trim()) fd.append('name',nameEl.value.trim());
        if(phoneEl.value.trim()) fd.append('phone',phoneEl.value.trim());
        input.value='';
        fetch(WEB+'/chat/send',{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(d){
            if(d && d.ok){ addMsg({id:d.id,sender:'customer',body:d.body}); info.style.display='none'; }
        }).catch(function(){});
    }
    send.addEventListener('click',doSend);
    input.addEventListener('keydown',function(e){ if(e.key==='Enter'){ e.preventDefault(); doSend(); } });
})();
</script>

</body>
</html>
