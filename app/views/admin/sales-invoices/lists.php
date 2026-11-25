<?php $pg = phan_trang((array) $dataList); $dataList = $pg['rows']; ?>
<?php
/* CỘT "THANH TOÁN" (18/11/2026 — anh: "cho anh cái nút gửi link qrcode luôn
   không phải vào in"). Mã QR nhỏ + nút Gửi trên từng dòng; bấm vào là hộp thoại
   có mã to, link thanh toán cho khách và tin nhắn soạn sẵn.
   Gara chưa khai tài khoản thì cả cột chỉ một việc: chỉ đường sang Cấu hình. */
$cfGara   = isset($cfGara) && is_array($cfGara) ? $cfGara : [];
$coTkGara = !empty($cfGara['bank_bin']) && !empty($cfGara['bank_account']);
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-check-circle mr-1"></i> {{$msg}}
</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}
</div>
@endif

<div class="card card-outline card-success">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            @if (route('admin/'.$routeBase.'/add'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Lập hoá đơn</a>
            @endif
        </div>
    </div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Trạng thái</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="">— Tất cả —</option>
                    <option value="0" {{$filterStatus==='0'?'selected':''}}>Nháp</option>
                    <option value="1" {{$filterStatus==='1'?'selected':''}}>Đã ghi sổ</option>
                </select>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Từ ngày</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{$filterFrom}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Đến ngày</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{$filterTo}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button>
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-sm btn-default">Xoá</a>
            </div>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:130px">Số hoá đơn</th>
                    <th style="width:110px">Ngày</th>
                    <th>Khách hàng / Kho</th>
                    <th style="width:15%" class="text-right">Tổng thanh toán</th>
                    <th style="width:120px" class="text-center">Trạng thái</th>
                    <th style="width:150px" class="text-center">Thanh toán</th>
                    <th style="width:80px" class="text-center">Xem</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <tr>
                    <td><code>{{$item['invoice_no']}}</code></td>
                    <td>{{$item['invoice_date']}}</td>
                    <td>
                        {{!empty($item['customer_full']) ? $item['customer_full'] : (!empty($item['customer_name']) ? $item['customer_name'] : 'Khách vãng lai')}}
                        <span class="text-muted small d-block">{{$item['warehouse_name']}}</span>
                    </td>
                    <td class="text-right font-weight-bold">{{number_format((float)$item['total_amount'],0,',','.')}} ₫</td>
                    <td class="text-center">
                        {!! $item['status']==1 ? '<span class="badge badge-primary">Đã ghi sổ</span>' : '<span class="badge badge-secondary">Nháp</span>' !!}
                    </td>
                    <td class="text-center">
                        <?php $qrDong = $coTkGara ? \App\core\VietQr::choHoaDon($cfGara, $item['total_amount'], $item['invoice_no']) : null; ?>
                        <?php if (!empty($qrDong)): ?>
                        <?php $urlLink = _WEB_URL . '/admin/' . $routeBase . '/link-thanh-toan/' . (int) $item['id']; ?>
                        <div class="d-inline-flex align-items-center" style="gap:8px">
                            <button type="button" class="btn p-0 border-0 bg-transparent" title="Phóng to mã QR / gửi cho khách"
                                    data-link-thanh-toan="<?php echo (int) $item['id']; ?>" data-url="<?php echo e($urlLink); ?>">
                                <span class="vietqr vietqr-nho" data-vietqr="<?php echo e($qrDong['chuoi']); ?>"></span>
                            </button>
                            <button type="button" class="btn btn-success btn-sm" title="Gửi link thanh toán có mã QR cho khách"
                                    data-link-thanh-toan="<?php echo (int) $item['id']; ?>" data-url="<?php echo e($urlLink); ?>">
                                <i class="fas fa-paper-plane mr-1"></i>Gửi
                            </button>
                        </div>
                        <?php elseif (!$coTkGara): ?>
                            <?php if (route('admin/settings')): ?>
                            <a href="<?php echo e(_WEB_URL . '/admin/settings'); ?>" class="small" title="Chọn ngân hàng + nhập số tài khoản để hoá đơn có mã QR">Khai tài khoản</a>
                            <?php else: ?>
                            <span class="small text-muted" title="Công ty chưa khai tài khoản ngân hàng ở Cấu hình">Khai tài khoản ở Cấu hình</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-info btn-sm" title="Mở"><i class="fas fa-folder-open"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có hoá đơn nào</td></tr>
            @endif
            </tbody>
        </table>
    </div>
<?php if ($pg['total'] > 0): ?>
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem">
        {!! phan_trang_html($pg) !!}
    </div>
<?php endif; ?>
</div>

<?php if ($coTkGara): ?>
<?php /* Hộp thoại "Gửi thanh toán" — public/assets/js/vietqr.js lấy link qua
         admin/sales-invoices/link-thanh-toan/<id> rồi điền vào đây. */ ?>
<div class="modal fade" id="modalGuiTT" tabindex="-1" role="dialog" aria-labelledby="modalGuiTTTieuDe" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGuiTTTieuDe"><i class="fas fa-qrcode mr-2"></i>Gửi thanh toán <span class="tt-so"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="tt-dang-tai text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-1"></i> Đang lấy link…</div>
                <div class="tt-loi alert alert-danger d-none"></div>
                <div class="tt-noi-dung d-none">
                    <div class="d-flex flex-wrap align-items-start" style="gap:16px">
                        <div class="tt-qr vietqr vietqr-to"></div>
                        <div class="tt-thong-tin small" style="line-height:1.7"></div>
                    </div>
                    <label class="small font-weight-bold mt-3 mb-1">Link thanh toán (khách mở không cần đăng nhập)</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control tt-link" readonly/>
                        <div class="input-group-append"><button type="button" class="btn btn-outline-secondary tt-chep-link"><i class="far fa-copy mr-1"></i>Chép link</button></div>
                    </div>
                    <label class="small font-weight-bold mt-2 mb-1">Tin nhắn soạn sẵn (sửa được)</label>
                    <textarea class="form-control form-control-sm tt-tin" rows="4"></textarea>
                    <div class="mt-3 d-flex flex-wrap" style="gap:6px">
                        <button type="button" class="btn btn-primary btn-sm tt-chia-se d-none"><i class="fas fa-share-alt mr-1"></i>Chia sẻ (Zalo, Messenger…)</button>
                        <button type="button" class="btn btn-success btn-sm tt-chep-tin"><i class="far fa-copy mr-1"></i>Chép tin nhắn</button>
                        <a class="btn btn-info btn-sm tt-zalo d-none" href="#" target="_blank" rel="noopener"><i class="fas fa-comment-dots mr-1"></i>Mở Zalo khách</a>
                        <a class="btn btn-secondary btn-sm tt-sms d-none" href="#"><i class="fas fa-sms mr-1"></i>Gửi SMS</a>
                        <a class="btn btn-outline-dark btn-sm tt-mo" href="#" target="_blank" rel="noopener"><i class="fas fa-external-link-alt mr-1"></i>Xem trang khách nhận</a>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Bấm <b>Mở Zalo khách</b> thì tin nhắn đã được chép sẵn — vào khung chat rồi dán (Ctrl+V, hoặc giữ tay để dán trên điện thoại).
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.vietqr-nho { display: inline-block; width: 56px; height: 56px; background: #fff; vertical-align: middle; }
.vietqr-nho svg, .vietqr-to svg { display: block; width: 100%; height: 100%; shape-rendering: crispEdges; }
.vietqr-to { width: 180px; height: 180px; background: #fff; }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"
        integrity="sha384-mZT2gIty7ZDdOGkxfP6joZcYdMW1Jvj9dRlfpTmaJAKKXTqzygtB22k7FLe+KZC1"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="<?php echo asset('public/assets/js/vietqr.js'); ?>"></script>
<?php endif; ?>
