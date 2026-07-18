<div class="crumb"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / Giỏ hàng</div>
<h1 class="page-title">Giỏ hàng / Yêu cầu báo giá</h1>

@if (!empty($msg))
<div class="alert alert-ok">{{$msg}}</div>
@endif
{!! !empty($errors['cart']) ? '<div class="alert alert-err">'.e($errors['cart']).'</div>' : '' !!}
{!! !empty($errors['name']) ? '<div class="alert alert-err">'.e($errors['name']).'</div>' : '' !!}

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
                    <tr><td colspan="3" class="tr"><b>Tổng cộng</b></td><td class="tr"><b style="color:#c0392b;font-size:18px">{{number_format($total,0,',','.')}} ₫</b></td><td></td></tr>
                </tfoot>
            </table>
            <div class="mt"><button class="btn btn-outline" type="submit">Cập nhật giỏ</button>
                <a class="btn" href="{{_WEB_URL.'/san-pham'}}">Tiếp tục mua sắm</a></div>
        </form>
    </div>

    <aside class="sidebar" style="width:320px;flex:0 0 320px">
        <div class="card"><div class="hd">Gửi yêu cầu báo giá</div><div class="bd">
            <form method="post" action="{{_WEB_URL.'/gio-hang/gui'}}">
                <?php echo csrf_field(); ?>
                <div class="fld" style="margin-bottom:12px">
                    <label style="font-size:13px;font-weight:600">Họ tên <span style="color:#c0392b">*</span></label>
                    <input type="text" name="name" value="{{!empty($member['name'])?$member['name']:''}}" style="width:100%;padding:9px;border:1px solid #e6e6e6;border-radius:6px" required/>
                </div>
                <div class="fld" style="margin-bottom:12px">
                    <label style="font-size:13px;font-weight:600">Điện thoại</label>
                    <input type="text" name="phone" value="{{!empty($member['phone'])?$member['phone']:''}}" style="width:100%;padding:9px;border:1px solid #e6e6e6;border-radius:6px"/>
                </div>
                <div class="fld" style="margin-bottom:12px">
                    <label style="font-size:13px;font-weight:600">Ghi chú</label>
                    <textarea name="note" rows="3" style="width:100%;padding:9px;border:1px solid #e6e6e6;border-radius:6px"></textarea>
                </div>
                <button class="btn btn-brand" type="submit" style="width:100%">Gửi yêu cầu báo giá</button>
                <p class="muted" style="font-size:12px;margin-bottom:0">Nhân viên kinh doanh sẽ liên hệ báo giá cho bạn.</p>
            </form>
        </div></div>
    </aside>
</div>
@endif
