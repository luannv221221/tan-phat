<?php $memberName = !empty($member['name']) ? $member['name'] : ''; ?>
<div class="crumb"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / <a href="{{_WEB_URL.'/gio-hang'}}">Giỏ hàng</a> / Đặt hàng</div>
<h1 class="page-title">Đặt hàng</h1>

{!! !empty($errors['name']) ? '<div class="alert alert-err">'.e($errors['name']).'</div>' : '' !!}
{!! !empty($errors['phone']) ? '<div class="alert alert-err">'.e($errors['phone']).'</div>' : '' !!}
{!! !empty($errors['address']) ? '<div class="alert alert-err">'.e($errors['address']).'</div>' : '' !!}

<form method="post" action="{{_WEB_URL.'/dat-hang'}}">
    <?php echo csrf_field(); ?>
    <div class="wrap" style="align-items:flex-start">
        <section class="content">
            <div class="card"><div class="hd">Thông tin người nhận</div><div class="bd">
                <div class="fld" style="margin-bottom:12px">
                    <label style="font-weight:600;font-size:14px">Họ tên <span style="color:#c0392b">*</span></label>
                    <input type="text" name="name" value="{{!empty($old['name'])?$old['name']:$memberName}}" style="width:100%;padding:10px;border:1px solid #e6e6e6;border-radius:6px" required/>
                </div>
                <div class="fld" style="margin-bottom:12px">
                    <label style="font-weight:600;font-size:14px">Số điện thoại <span style="color:#c0392b">*</span></label>
                    <input type="text" name="phone" value="{{!empty($old['phone'])?$old['phone']:(!empty($member['phone'])?$member['phone']:'')}}" style="width:100%;padding:10px;border:1px solid #e6e6e6;border-radius:6px" required/>
                </div>
                <div class="fld" style="margin-bottom:12px">
                    <label style="font-weight:600;font-size:14px">Email</label>
                    <input type="text" name="email" value="{{!empty($old['email'])?$old['email']:(!empty($member['email'])?$member['email']:'')}}" style="width:100%;padding:10px;border:1px solid #e6e6e6;border-radius:6px"/>
                </div>
                <div class="fld" style="margin-bottom:12px">
                    <label style="font-weight:600;font-size:14px">Địa chỉ nhận hàng <span style="color:#c0392b">*</span></label>
                    <input type="text" name="address" value="{{!empty($old['address'])?$old['address']:(!empty($member['address'])?$member['address']:'')}}" style="width:100%;padding:10px;border:1px solid #e6e6e6;border-radius:6px" required/>
                </div>
                <div class="fld" style="margin-bottom:0">
                    <label style="font-weight:600;font-size:14px">Ghi chú</label>
                    <textarea name="note" rows="2" style="width:100%;padding:10px;border:1px solid #e6e6e6;border-radius:6px"></textarea>
                </div>
            </div></div>

            <div class="card mt"><div class="hd">Hình thức thanh toán</div><div class="bd">
                @foreach ($payments as $k => $label)
                <label style="display:block;padding:6px 0;font-size:15px;cursor:pointer">
                    <input type="radio" name="payment_method" value="{{$k}}" {{$k=='bank_transfer'?'checked':''}}/> {{$label}}
                </label>
                @endforeach
            </div></div>
        </section>

        <aside class="sidebar" style="width:340px;flex:0 0 340px">
            <div class="card"><div class="hd">Đơn hàng</div><div class="bd">
                <table style="width:100%;border-collapse:collapse">
                    @foreach ($rows as $r)
                    <?php $p = $r['part']; ?>
                    <tr style="border-bottom:1px solid #eee">
                        <td style="padding:8px 0;font-size:14px">{{$p['name']}} <span class="muted">× {{(int)$r['qty']}}</span></td>
                        <td style="padding:8px 0;text-align:right;font-size:14px">{{number_format($r['amount'],0,',','.')}}</td>
                    </tr>
                    @endforeach
                    <tr><td style="padding:12px 0;font-weight:700">Tổng cộng</td><td style="padding:12px 0;text-align:right;font-weight:700;color:#c0392b;font-size:18px">{{number_format($total,0,',','.')}} ₫</td></tr>
                </table>
                <button class="btn btn-brand" type="submit" style="width:100%;margin-top:8px">Xác nhận đặt hàng</button>
                <a class="btn" href="{{_WEB_URL.'/gio-hang'}}" style="width:100%;text-align:center;margin-top:6px">← Về giỏ hàng</a>
            </div></div>
        </aside>
    </div>
</form>
