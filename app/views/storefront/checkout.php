<?php $memberName = !empty($member['name']) ? $member['name'] : ''; ?>
<div class="content">
<div class="sf-page">
<div class="container">

    <div class="breadcrumb-sf"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / <a href="{{_WEB_URL.'/gio-hang'}}">Giỏ hàng</a> / Đặt hàng</div>
    <h1 class="sf-page-title mb-3">Đặt hàng</h1>

    {!! !empty($errors['name']) ? '<div class="alert alert-danger">'.e($errors['name']).'</div>' : '' !!}
    {!! !empty($errors['phone']) ? '<div class="alert alert-danger">'.e($errors['phone']).'</div>' : '' !!}
    {!! !empty($errors['address']) ? '<div class="alert alert-danger">'.e($errors['address']).'</div>' : '' !!}
    {!! !empty($errors['email']) ? '<div class="alert alert-danger">'.e($errors['email']).'</div>' : '' !!}

    <form method="post" action="{{_WEB_URL.'/dat-hang'}}">
        <?php echo csrf_field(); ?>
        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm"><div class="card-header bg-white fw-semibold">Thông tin người nhận</div><div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{!empty($old['name'])?$old['name']:$memberName}}" required/>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="tel" inputmode="numeric" maxlength="11" pattern="0[0-9]{9,10}" title="Di dong 10 so (0912345678) hoac co dinh 11 so (02438765432)" name="phone" class="form-control" value="{{!empty($old['phone'])?$old['phone']:(!empty($member['phone'])?$member['phone']:'')}}" required/>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" value="{{!empty($old['email'])?$old['email']:(!empty($member['email'])?$member['email']:'')}}"/>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Địa chỉ nhận hàng <span class="text-danger">*</span></label>
                        <input type="text" name="address" class="form-control" value="{{!empty($old['address'])?$old['address']:(!empty($member['address'])?$member['address']:'')}}" required/>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Ghi chú</label>
                        <textarea name="note" rows="2" class="form-control"></textarea>
                    </div>
                </div></div>

                <div class="card border-0 shadow-sm mt-3"><div class="card-header bg-white fw-semibold">Hình thức thanh toán</div><div class="card-body">
                    @foreach ($payments as $k => $label)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" id="pay-{{$k}}" value="{{$k}}" {{$k=='bank_transfer'?'checked':''}}/>
                        <label class="form-check-label" for="pay-{{$k}}">{{$label}}</label>
                    </div>
                    @endforeach
                </div></div>
            </div>

            <aside class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm"><div class="card-header bg-white fw-semibold">Đơn hàng</div><div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                        @foreach ($rows as $r)
                        <?php $p = $r['part']; ?>
                        <tr>
                            <td class="small">{{$p['name']}} <span class="text-muted">× {{(int)$r['qty']}}</span></td>
                            <td class="text-end small">{{number_format($r['amount'],0,',','.')}}</td>
                        </tr>
                        @endforeach
                        <tr><td class="fw-bold">Tổng cộng</td><td class="text-end fw-bold text-accent" style="font-size:1.15rem">{{number_format($total,0,',','.')}} đ</td></tr>
                        </tbody>
                    </table>
                    <button class="btn btn-primary w-100" type="submit">Xác nhận đặt hàng</button>
                    <a class="btn btn-outline-secondary w-100 mt-2" href="{{_WEB_URL.'/gio-hang'}}"><i class="fa fa-angle-left"></i> Về giỏ hàng</a>
                </div></div>
            </aside>
        </div>
    </form>

</div>
</div>
</div>
