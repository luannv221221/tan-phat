<?php
$settings = isset($settings) && is_array($settings) ? $settings : [];
$siteName = !empty($settings['site_name']) ? $settings['site_name'] : 'Tân Phát';
?>
<div class="content">
<section class="page py-5">
    <div class="container">
        <div class="breadcrumb-sf"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / Giới thiệu</div>
        <h1 class="page__title text-center">{{$siteName}}</h1>
        <div class="page__content mt-4">
            @if (!empty($about))
                {!! $about !!}
            @else
                <p class="lead">{{!empty($settings['site_slogan']) ? $settings['site_slogan'] : 'Chuyên phụ tùng và thiết bị gara ô tô chính hãng.'}}</p>
                <p>{{$siteName}} là đơn vị cung cấp phụ tùng, thiết bị bảo dưỡng và sửa chữa ô tô, thiết bị tự động hóa và công nghiệp phụ trợ. Chúng tôi lựa chọn những dòng sản phẩm chất lượng, có thương hiệu, xuất xứ rõ ràng, từng bước tạo dựng lòng tin với khách hàng và đối tác.</p>
                <h2>Về chúng tôi</h2>
                <p>Với đội ngũ tư vấn kỹ thuật giàu kinh nghiệm, chúng tôi hỗ trợ tra cứu tương thích phụ tùng theo hãng — model — đời xe, đồng thời cung cấp dịch vụ bảo hành, bảo trì chuyên nghiệp cho khách hàng và gara trên toàn quốc.</p>
                <h2>Liên hệ</h2>
                <ul>
                    <?php if (!empty($settings['address'])): ?><li>Địa chỉ: {{$settings['address']}}</li><?php endif; ?>
                    <li>Hotline: {{!empty($settings['hotline']) ? $settings['hotline'] : '1900 0000'}}</li>
                    <li>Email: {{!empty($settings['email']) ? $settings['email'] : 'info@tanphat.vn'}}</li>
                </ul>
                <p><a class="btn btn-primary" href="{{_WEB_URL.'/lien-he'}}">Liên hệ với chúng tôi</a></p>
            @endif
        </div>
    </div>
</section>
</div>
