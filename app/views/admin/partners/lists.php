<?php $pg = phan_trang((array) $dataList); $dataList = $pg['rows']; ?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-address-book mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            <?php /* "3 / 6" khi đang lọc — nhìn là biết danh sách đang bị thu hẹp,
                     không tưởng nhầm hệ thống chỉ có từng ấy đối tượng. */ ?>
            <span class="text-muted small mr-2">
                @if ($dangLoc)
                    {{$pg['total']}} / {{$tongTatCa}} đối tượng
                @else
                    {{$tongTatCa}} đối tượng
                @endif
            </span>
            @if (route('admin/'.$routeBase.'/add'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Thêm {{$labelOne}}</a>
            @endif
        </div>
    </div>

    <?php
    /* TAB LỌC THEO LOẠI — thay cho ô chọn "Loại" cũ.
     *
     * Hai loại này là hai việc khác hẳn nhau: khách thì bán cho, NCC thì mua
     * vào. Trộn chung một danh sách rồi bắt mở ô chọn ra lọc thì ngày nào cũng
     * phải thao tác thừa. Tab bày sẵn cả hai, bấm một cái là sang.
     *
     * Tab giữ nguyên các bộ lọc khác (tìm / nhóm / trạng thái) nhưng BỎ `page`:
     * đang ở trang 3 của khách mà sang NCC chỉ có 2 dòng thì rơi vào trang
     * trống, nhìn như mất dữ liệu.
     *
     * Loại "Cả hai" đếm vào CẢ hai tab — đối tác vừa mua vừa bán phải tìm thấy
     * ở cả hai danh sách, không rơi ra ngoài. Tab "Cả hai" chỉ hiện khi thật sự
     * có dòng nào như vậy, bày sẵn một tab luôn bằng 0 là tổ gây phân vân. */
    $tabs = [
        ''         => ['Tất cả',       'fa-address-book'],
        'customer' => ['Khách hàng',   'fa-user'],
        'supplier' => ['Nhà cung cấp', 'fa-truck'],
        'both'     => ['Cả hai',       'fa-exchange-alt'],
    ];
    // Không có đối tượng nào "cả hai" thì bỏ hẳn tab đó đi
    if (empty($demLoai['both']) && $loc['type'] !== 'both') unset($tabs['both']);
    $duongTab = function ($loai) use ($loc, $routeBase) {
        $qs = array_filter([
            'q'      => $loc['q'],
            'group'  => $loc['group'],
            'status' => $loc['status'],
            'type'   => $loai,
        ], function ($v) { return $v !== '' && $v !== null; });
        return _WEB_URL . '/admin/' . $routeBase . (!empty($qs) ? '?' . http_build_query($qs) : '');
    };
    ?>
    <ul class="nav nav-tabs px-3 pt-2" style="border-bottom:1px solid #dee2e6">
        @foreach ($tabs as $ma => $t)
        <li class="nav-item">
            <a class="nav-link {{$loc['type'] === $ma ? 'active' : ''}}" href="{{$duongTab($ma)}}">
                <i class="fas {{$t[1]}} mr-1"></i>{{$t[0]}}
                <span class="badge badge-{{$loc['type'] === $ma ? 'primary' : 'light'}} ml-1">{{(int)$demLoai[$ma]}}</span>
            </a>
        </li>
        @endforeach
    </ul>

    <?php /* BỘ LỌC. Dùng GET: dán link là người khác thấy đúng danh sách, và
             sang trang 2 không mất bộ lọc. Ô chọn tự gửi khi đổi; ô chữ thì
             bấm Lọc hoặc Enter. */ ?>
    <div class="card-body border-bottom">
        <form method="get" action="{{_WEB_URL.'/admin/'.$routeBase}}" class="form-row align-items-end">
            <?php /* Loại đã lên TAB phía trên, không còn ô chọn ở đây. Nhưng tab
                     chỉ đổi URL chứ không gửi form, nên loại đang chọn phải đi
                     kèm khi bấm Lọc — không thì gõ tìm một cái là văng về
                     "Tất cả". */ ?>
            <input type="hidden" name="type" value="{{$loc['type']}}"/>
            <div class="form-group col-md-6 mb-2">
                <label class="mb-1 small">Tìm (mã / tên / SĐT / MST)</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="VD: Bosch, 0901..., KH-0001" value="{{$loc['q']}}"/>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="mb-1 small">Nhóm khách</label>
                <select name="group" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">— Tất cả —</option>
                    @if (!empty($dsNhomKhach))
                    @foreach ($dsNhomKhach as $nk)
                    <option value="{{$nk['id']}}" {{$loc['group']===(string)$nk['id']?'selected':''}}>{{$nk['name']}}</option>
                    @endforeach
                    @endif
                    <?php /* Khách chưa xếp nhóm thì không được chiết khấu nhóm nào
                             — lọc riêng ra để còn biết mà xếp. */ ?>
                    <option value="none" {{$loc['group']==='none'?'selected':''}}>Chưa xếp nhóm</option>
                </select>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="mb-1 small">Trạng thái</label>
                <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">— Tất cả —</option>
                    <option value="1" {{$loc['status']==='1'?'selected':''}}>Đang dùng</option>
                    <option value="0" {{$loc['status']==='0'?'selected':''}}>Đã ẩn</option>
                </select>
            </div>
            <div class="form-group col-md-2 mb-2">
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button>
                @if ($dangLoc)
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-sm btn-default">Xoá lọc</a>
                @endif
            </div>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:60px" class="text-center">STT</th>
                    <th style="width:14%">Mã</th>
                    <th>Tên</th>
                    <th style="width:12%">Loại</th>
                    <th style="width:14%">Điện thoại</th>
                    <th style="width:14%">MST</th>
                    <th style="width:100px" class="text-center">Trạng thái</th>
                    <th style="width:120px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $key => $item)
                <tr>
                    <td class="text-center text-muted">{{$key+1}}</td>
                    <td><code>{{$item['code']}}</code></td>
                    <td class="font-weight-bold">{{$item['name']}}</td>
                    <td>{{ isset($types[$item['type']]) ? $types[$item['type']] : $item['type'] }}</td>
                    <td>{{!empty($item['phone'])?$item['phone']:'—'}}</td>
                    <td>{{!empty($item['tax_code'])?$item['tax_code']:'—'}}</td>
                    <td class="text-center">{!! $item['status']==1 ? '<span class="badge badge-success">Dùng</span>' : '<span class="badge badge-secondary">Ẩn</span>' !!}</td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a onclick="return confirm('Xoá đối tượng này?')" href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <?php /* Phân biệt "chưa có gì" với "không khớp bộ lọc": cùng một
                         câu "Chưa có dữ liệu" thì người dùng tưởng mất sạch đối
                         tượng, trong khi chỉ là bộ lọc đang hẹp quá. */ ?>
                <tr><td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                    @if ($dangLoc)
                        Không có đối tượng nào khớp bộ lọc.
                        <a href="{{_WEB_URL.'/admin/'.$routeBase}}">Xoá lọc</a> để xem tất cả {{$tongTatCa}} đối tượng.
                    @else
                        Chưa có dữ liệu
                    @endif
                </td></tr>
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
