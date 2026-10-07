<?php
/**
 * Màn đặt hàng kho tổng — của GARA.
 *
 * Chỉ một bảng: mặt hàng kho tổng, tồn đang có bên đó, ô nhập số lượng. Không
 * dựng giỏ hàng hay nhiều bước: gara đặt hàng vài món một lần, thêm bước chỉ
 * tốn thao tác.
 */
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

@if (!empty($laKhoTong))
<div class="card card-outline card-secondary">
    <div class="card-body text-center py-5">
        <div class="h1 text-muted mb-3"><i class="fas fa-warehouse"></i></div>
        <h4 class="mb-2">Màn này dành cho gara</h4>
        <p class="text-muted mb-0">Kho tổng là nơi <b>bán ra</b>, không đặt hàng của chính mình.<br/>
           Đơn các gara đặt sẽ về <b>Kho › Phiếu xuất kho</b> ở trạng thái nháp, chờ ghi sổ.</p>
    </div>
</div>
@else

<form action="{{_WEB_URL.'/admin/'.$routeBase.'/dat'}}" method="post">
    <?php echo csrf_field(); ?>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-truck-loading mr-2"></i>{{$page_name}}</h3>
        </div>

        <div class="card-body border-bottom py-2">
            <div class="alert alert-info py-2 mb-2">
                <i class="fas fa-info-circle mr-1"></i>
                Điền số lượng vào những món cần lấy rồi bấm <b>Đặt hàng</b>. Hệ thống lập
                <b>phiếu xuất</b> bên kho tổng và <b>phiếu nhập</b> bên gara, đều ở dạng
                <b>nháp</b>. Kho tổng ghi sổ phiếu xuất là duyệt; hàng về tới nơi thì gara
                ghi sổ phiếu nhập — lúc đó tồn mới đổi.
            </div>
            <label class="mb-1 small">Ghi chú cho đơn này <span class="text-muted">(không bắt buộc)</span></label>
            <input type="text" name="ghi_chu" class="form-control form-control-sm" placeholder="VD: lấy gấp trong tuần"/>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:60px">STT</th>
                        <th style="width:120px">Mã</th>
                        <th>Mặt hàng</th>
                        <th style="width:110px" class="text-right">Giá công ty</th>
                        <th style="width:110px" class="text-right">Kho tổng còn</th>
                        <th style="width:130px" class="text-right">Số lượng đặt</th>
                    </tr>
                </thead>
                <tbody>
                @if (!empty($hang))
                    @foreach ($hang as $i => $h)
                    <?php $gia = ($h['sale_price'] !== null && $h['sale_price'] !== '') ? $h['sale_price'] : $h['price']; ?>
                    <tr class="{{(float)$h['ton'] <= 0 ? 'text-muted' : ''}}">
                        <td>{{$i + 1}}</td>
                        <td class="text-danger small">{{$h['code']}}</td>
                        <td>{{$h['name']}}<span class="text-muted small">{{!empty($h['unit_name']) ? ' · '.$h['unit_name'] : ''}}</span></td>
                        <td class="text-right">{{number_format((float)$gia, 0, ',', '.')}} ₫</td>
                        <td class="text-right">{!! (float)$h['ton'] > 0
                            ? number_format((float)$h['ton'], 0, ',', '.')
                            : '<span class="badge badge-secondary">hết</span>' !!}</td>
                        <td>
                            <?php /* Hết hàng thì khoá ô luôn: gõ số vào rồi mới bị từ chối
                                     là tốn một vòng, mà lý do thì đã thấy ngay cột bên. */ ?>
                            <input type="number" min="0" step="1" name="sl[{{$h['id']}}]"
                                   class="form-control form-control-sm text-right"
                                   {{(float)$h['ton'] <= 0 ? 'disabled' : ''}}
                                   placeholder="0"/>
                        </td>
                    </tr>
                    @endforeach
                @else
                    <tr><td colspan="6" class="text-center text-muted py-4">
                        Kho tổng chưa có mặt hàng nào để đặt.
                    </td></tr>
                @endif
                </tbody>
            </table>
        </div>

        @if (!empty($hang))
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Đặt hàng</button>
            <a href="{{_WEB_URL.'/admin/goods-receipts'}}" class="btn btn-default">Xem phiếu nhập của gara</a>
        </div>
        @endif
    </div>
</form>
@endif
