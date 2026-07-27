<div class="content">
<div class="sf-page">
<div class="container">

    <div class="breadcrumb-sf"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / Giỏ hàng</div>
    <h1 class="sf-page-title mb-3">Giỏ hàng / Yêu cầu báo giá</h1>

@if (empty($rows))
    <div class="card"><div class="bd tc muted" style="padding:50px">
        Giỏ hàng trống. <a href="{{_WEB_URL.'/san-pham'}}">Xem sản phẩm →</a>
    </div></div>
@else
<div class="wrap" style="align-items:flex-start">
    <div class="content">
        <form method="post" action="{{_WEB_URL.'/gio-hang/cap-nhat'}}">
            <?php echo csrf_field(); ?>
            <table class="cart-tbl">
                <thead><tr><th>Sản phẩm</th><th style="width:120px">Đơn giá</th><th style="width:100px">SL</th><th style="width:140px" class="tr">Thành tiền</th><th style="width:60px"></th></tr></thead>
                <tbody>
                @foreach ($rows as $r)
                    <?php $p = $r['part']; ?>
                    <tr>
                        <td>
                            <a href="{{_WEB_URL.'/san-pham/'.$p['slug']}}"><b>{{$p['name']}}</b></a>
                            <div class="muted" style="font-size:12px">Mã: {{$p['code']}}</div>
                        </td>
                        <td>{{number_format($r['price'],0,',','.')}} ₫</td>
                        <td><input type="number" name="qty[{{(int)$p['id']}}]" value="{{(int)$r['qty']}}" min="1" style="width:70px;padding:6px;border:1px solid #e6e6e6;border-radius:5px"/></td>
                        <td class="tr"><b>{{number_format($r['amount'],0,',','.')}} ₫</b></td>
                        <td class="tc"><a href="{{_WEB_URL.'/gio-hang/xoa/'.(int)$p['id']}}" title="Xoá" style="color:#c0392b">✕</a></td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr><td colspan="3" class="tr"><b>Tổng cộng</b></td><td class="tr"><b style="color:#164194;font-size:18px">{{number_format($total,0,',','.')}} ₫</b></td><td></td></tr>
                </tfoot>
            </table>
            <div class="mt"><button class="btn btn-outline" type="submit">Cập nhật giỏ</button>
                <a class="btn" href="{{_WEB_URL.'/san-pham'}}">Tiếp tục mua sắm</a></div>
        </form>
    </div>

    @if (empty($rows))
        <div class="empty-box">Giỏ hàng trống. <a href="{{_WEB_URL.'/san-pham'}}">Xem sản phẩm →</a></div>
    @else
    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <form method="post" action="{{_WEB_URL.'/gio-hang/cap-nhat'}}">
                <?php echo csrf_field(); ?>
                <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr><th>Sản phẩm</th><th style="width:130px">Đơn giá</th><th style="width:100px">SL</th><th style="width:150px" class="text-end">Thành tiền</th><th style="width:50px"></th></tr>
                    </thead>
                    <tbody>
                    @foreach ($rows as $r)
                        <?php $p = $r['part']; ?>
                        <tr>
                            <td>
                                <a href="{{_WEB_URL.'/san-pham/'.$p['slug']}}" class="fw-semibold text-dark">{{$p['name']}}</a>
                                <div class="small text-muted">Mã: {{$p['code']}}</div>
                            </td>
                            <td>{{number_format($r['price'],0,',','.')}} đ</td>
                            <td><input type="number" name="qty[{{(int)$p['id']}}]" value="{{(int)$r['qty']}}" min="1" class="form-control form-control-sm" style="width:75px"/></td>
                            <td class="text-end fw-semibold">{{number_format($r['amount'],0,',','.')}} đ</td>
                            <td class="text-center"><a href="{{_WEB_URL.'/gio-hang/xoa/'.(int)$p['id']}}" title="Xoá" class="text-danger"><i class="fa fa-times"></i></a></td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3" class="text-end fw-bold">Tổng cộng</td><td class="text-end fw-bold text-accent" style="font-size:1.2rem">{{number_format($total,0,',','.')}} đ</td><td></td></tr>
                    </tfoot>
                </table>
                </div>
                <button class="btn btn-outline-primary" type="submit">Cập nhật giỏ</button>
                <a class="btn btn-outline-secondary" href="{{_WEB_URL.'/san-pham'}}">Tiếp tục mua sắm</a>
            </form>
        </div>

        <aside class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm mb-3"><div class="card-body text-center">
                <div class="text-muted mb-2">Mua ngay với giá niêm yết</div>
                <a class="btn btn-primary w-100 py-2" href="{{_WEB_URL.'/dat-hang'}}"><i class="fa fa-shopping-bag"></i> Đặt hàng ngay</a>
            </div></div>
            <div class="card border-0 shadow-sm"><div class="card-body">
                <h3 class="h6 mb-3">Hoặc gửi yêu cầu báo giá</h3>
                <form method="post" action="{{_WEB_URL.'/gio-hang/gui'}}">
                    <?php echo csrf_field(); ?>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" value="{{!empty($member['name'])?$member['name']:''}}" required/>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Điện thoại</label>
                        <input type="text" name="phone" class="form-control form-control-sm" value="{{!empty($member['phone'])?$member['phone']:''}}"/>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Ghi chú</label>
                        <textarea name="note" rows="3" class="form-control form-control-sm"></textarea>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Gửi yêu cầu báo giá</button>
                    <p class="text-muted small mt-2 mb-0">Nhân viên kinh doanh sẽ liên hệ báo giá cho bạn.</p>
                </form>
            </div></div>
        </aside>
    </div>
    @endif

</div>
</div>
</div>
