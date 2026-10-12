<?php
/**
 * KHO — TỒN KHO ĐẦU KỲ.
 *
 * Màn này làm ĐÚNG MỘT LẦN lúc gara bắt đầu dùng phần mềm, nên nó phải tự giải
 * thích: người mở ra sẽ không có ai ngồi cạnh chỉ, và sai ở đây là sai toàn bộ
 * số liệu kho về sau.
 *
 * Ba thứ phải nói ngay trên màn, không giấu trong tài liệu:
 *   1. Khác gì với dòng "Tồn đầu kỳ" ở Thẻ kho / Biến động tồn (cùng tên, khác
 *      nghĩa — chỗ kia là số dư đầu KHOẢNG NGÀY đang lọc).
 *   2. Import ra phiếu NHÁP, chưa vào tồn; phải soát rồi Ghi sổ.
 *   3. Mã hàng chưa có trong danh mục thì bỏ qua, không tự tạo.
 */
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

<div class="row">
    <div class="col-lg-8">

        <?php /* ===== Kết quả lần import vừa rồi ===== */ ?>
        @if (!empty($ketQua))
        <div class="card card-outline {{empty($ketQua['phieu']) ? 'card-danger' : 'card-success'}}">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-import mr-2"></i>Kết quả import</h3>
            </div>
            <div class="card-body">
                <p>
                    Đọc <strong>{{$ketQua['tong']}}</strong> dòng —
                    nhận <strong class="text-success">{{$ketQua['nhan']}}</strong>,
                    bỏ qua <strong class="text-danger">{{count($ketQua['boQua'])}}</strong>.
                </p>

                @if (!empty($ketQua['phieu']))
                <p class="mb-2">Đã lập <strong>{{count($ketQua['phieu'])}}</strong> phiếu ở trạng thái
                   <span class="badge badge-secondary">Nháp</span> — mở ra soát lại rồi bấm
                   <strong>Ghi sổ</strong>, lúc đó tồn kho mới thay đổi:</p>
                <ul class="mb-3">
                    @foreach ($ketQua['phieu'] as $pid => $pno)
                    <li><a href="{{_WEB_URL.'/admin/goods-receipts/edit/'.$pid}}"><code>{{$pno}}</code></a></li>
                    @endforeach
                </ul>
                @endif

                @if (!empty($ketQua['boQua']))
                <p class="mb-1 font-weight-bold text-danger">Các dòng bỏ qua / cần xem lại:</p>
                <ul class="small mb-0">
                    @foreach ($ketQua['boQua'] as $l)
                    <li>{{$l}}</li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
        @endif

        <?php /* ===== Form import ===== */ ?>
        @if (route('admin/'.$routeBase.'/add'))
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-upload mr-2"></i>Nạp tồn đầu kỳ từ file</h3>
                <div class="card-tools">
                    <a href="{{_WEB_URL.'/admin/'.$routeBase.'/file-mau'}}" class="btn btn-default btn-sm">
                        <i class="fas fa-download mr-1"></i> Tải file mẫu
                    </a>
                </div>
            </div>
            <form action="{{_WEB_URL.'/admin/'.$routeBase.'/import'}}" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label>File Excel / CSV <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control-file" accept=".xlsx,.csv">
                            <small class="form-text text-muted">
                                Tám cột: Mã hàng · Tên hàng hóa · Đơn vị tính · Kho ·
                                Số lượng tồn đầu kỳ · Đơn giá vốn · Thành tiền · Ghi chú.
                                Chỉ đọc <strong>sheet đầu tiên</strong>, tối đa 5MB.
                            </small>
                        </div>
                        <div class="form-group col-md-5">
                            <label>Ngày chốt tồn đầu kỳ</label>
                            <input type="date" name="ngay" class="form-control" value="{{date('Y-m-d')}}">
                            <small class="form-text text-muted">
                                Ngày gara bắt đầu dùng phần mềm. Mọi phiếu nhập/xuất sau này
                                phải có ngày <strong>từ ngày này trở đi</strong>.
                            </small>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-import mr-1"></i> Đọc file và lập phiếu nháp
                    </button>
                </div>
            </form>
        </div>
        @endif

        <?php /* ===== Các phiếu tồn đầu kỳ đã lập ===== */ ?>
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>Phiếu tồn đầu kỳ đã lập</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Số phiếu</th>
                            <th>Kho</th>
                            <th style="width:110px">Ngày</th>
                            <th style="width:140px" class="text-right">Giá trị</th>
                            <th style="width:120px" class="text-center">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                    @if (!empty($dsPhieu))
                        @foreach ($dsPhieu as $p)
                        <tr>
                            <td><a href="{{_WEB_URL.'/admin/goods-receipts/edit/'.$p['id']}}"><code>{{$p['receipt_no']}}</code></a></td>
                            <td>{{$p['warehouse_name']}}</td>
                            <td>{{date('d/m/Y', strtotime($p['receipt_date']))}}</td>
                            <td class="text-right">{{number_format((float)$p['total_amount'], 0, ',', '.')}} ₫</td>
                            <td class="text-center">
                                {!! $p['status']==1
                                    ? '<span class="badge badge-success">Đã ghi sổ</span>'
                                    : '<span class="badge badge-secondary">Nháp</span>' !!}
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr><td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa khai tồn đầu kỳ
                        </td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-outline card-warning">
            <div class="card-header"><h3 class="card-title">Tồn kho đầu kỳ là gì</h3></div>
            <div class="card-body small">
                <p>Số lượng hàng gara <strong>đang có trong kho</strong> tại thời điểm bắt đầu
                   dùng phần mềm. Khai một lần, để mọi phiếu nhập / xuất về sau tính ra tồn đúng.</p>
                <p class="mb-0 text-muted">
                    <strong>Khác với dòng "Tồn đầu kỳ"</strong> ở Thẻ kho và Biến động tồn —
                    dòng đó là số dư tại đầu <em>khoảng ngày đang lọc</em>, phần mềm tự tính,
                    không phải khai.
                </p>
            </div>
        </div>

        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Ba điều nên biết trước</h3></div>
            <div class="card-body small">
                <ol class="pl-3 mb-0">
                    <li class="mb-2">
                        <strong>Import ra phiếu NHÁP, chưa vào tồn.</strong> Soát lại rồi bấm
                        Ghi sổ ở màn Phiếu nhập kho. Lỡ nạp hai lần thì huỷ ghi sổ và xoá
                        phiếu thừa — số không bị nhân đôi âm thầm.
                    </li>
                    <li class="mb-2">
                        <strong>Mã hàng phải có sẵn trong danh mục.</strong> Mã lạ sẽ bị bỏ qua
                        kèm lý do, không tự tạo mặt hàng mới — tránh sinh hai bản ghi cho cùng
                        một món. Khai trước ở <em>Hàng hoá › Quản lý hàng hoá</em>.
                    </li>
                    <li>
                        <strong>Chỉ khai cho mặt hàng chưa có phát sinh.</strong> Mặt hàng đã
                        từng nhập / xuất ở kho đó sẽ bị bỏ qua: chèn tồn đầu kỳ vào giữa một
                        lịch sử đang chạy là làm sai thẻ kho.
                    </li>
                </ol>
            </div>
        </div>

        @if (!empty($dsKho))
        <div class="card card-outline card-light">
            <div class="card-header"><h3 class="card-title">Kho của gara</h3></div>
            <div class="card-body small">
                <p class="text-muted mb-2">Cột <em>Kho</em> trong file phải trùng tên (hoặc mã) một trong các kho sau:</p>
                <ul class="mb-0">
                    @foreach ($dsKho as $k)
                    <li>{{$k['name']}} <span class="text-muted">— <code>{{$k['code']}}</code></span></li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>
</div>
