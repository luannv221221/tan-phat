@if (!empty($msg))
<div class="alert alert-info alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <i class="fas fa-info-circle mr-1"></i> {{$msg}}
</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}
</div>
@endif

@if (!empty($result))
<div class="card card-outline card-success">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>Kết quả import</h3></div>
    <div class="card-body">
        <span class="badge badge-success p-2 mr-2">Thêm mới: {{$result['created']}}</span>
        <span class="badge badge-primary p-2 mr-2">Cập nhật: {{$result['updated']}}</span>
        <span class="badge badge-danger p-2">Lỗi/bỏ qua: {{count($result['errors'])}}</span>

        @if (!empty($result['errors']))
        <ul class="mt-3 mb-0 text-danger small">
            @foreach ($result['errors'] as $err)
            <li>{{$err}}</li>
            @endforeach
        </ul>
        @endif
    </div>
</div>
@endif

<div class="row">
    <div class="col-lg-6">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-file-upload mr-2"></i>{{$page_name}}</h3></div>
            <form action="{{_WEB_URL.'/admin/'.$routeBase.'/import'}}" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    <div class="form-group">
                        <label>Chọn file <b>.xlsx</b> hoặc <b>.csv</b></label>
                        <div class="custom-file">
                            <input type="file" name="file" id="impfile" class="custom-file-input" accept=".xlsx,.csv"/>
                            <label class="custom-file-label" for="impfile">Chọn file...</label>
                        </div>
                        <small class="form-text text-muted">Tối đa 5MB. Khớp theo cột <code>code</code> (mã) — có sẵn thì cập nhật, chưa có thì thêm mới.</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload mr-1"></i> Import</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase.'/import-template'}}" class="btn btn-success"><i class="fas fa-download mr-1"></i> Tải file mẫu (CSV)</a>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Về danh sách</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-list-ul mr-2"></i>Định dạng cột</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Cột</th><th>Bắt buộc</th><th>Ghi chú</th></tr></thead>
                    <tbody>
                        <tr><td><code>code</code></td><td class="text-danger">Có</td><td>Mã phụ tùng (khoá để cập nhật)</td></tr>
                        <tr><td><code>name</code></td><td class="text-danger">Có</td><td>Tên phụ tùng</td></tr>
                        <tr><td><code>price</code></td><td>—</td><td>Giá VND (số). VD 350000</td></tr>
                        <tr><td><code>oem_code</code></td><td>—</td><td>Mã OEM</td></tr>
                        <tr><td><code>sale_price</code></td><td>—</td><td>Giá khuyến mãi</td></tr>
                        <tr><td><code>warranty_month</code></td><td>—</td><td>Số tháng bảo hành</td></tr>
                        <tr><td><code>status</code></td><td>—</td><td>1 = hiện, 0 = ẩn (mặc định 1)</td></tr>
                        <tr><td><code>category_slug</code></td><td>—</td><td>Slug danh mục, VD <code>loc-gio</code></td></tr>
                        <tr><td><code>brand_slug</code></td><td>—</td><td>Slug thương hiệu, VD <code>bosch</code></td></tr>
                        <tr><td><code>manufacturer_slug</code></td><td>—</td><td>Slug hãng sản xuất</td></tr>
                        <tr><td><code>origin_slug</code></td><td>—</td><td>Slug xuất xứ, VD <code>nhat-ban</code></td></tr>
                        <tr><td><code>unit_slug</code></td><td>—</td><td>Slug đơn vị, VD <code>cai</code></td></tr>
                        <tr><td><code>description</code></td><td>—</td><td>Mô tả</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <p class="text-muted small">Dòng đầu tiên phải là tiêu đề cột. Cột không khớp sẽ bị bỏ qua. Slug tham chiếu (danh mục, thương hiệu...) không tìm thấy sẽ để trống.</p>
    </div>
</div>

<script>
document.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'impfile') {
        var lbl = e.target.parentNode.querySelector('.custom-file-label');
        if (lbl) lbl.textContent = e.target.files.length ? e.target.files[0].name : 'Chọn file...';
    }
});
</script>
