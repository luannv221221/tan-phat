<?php
/**
 * TÊN MIỀN CỦA MỘT GARA.
 *
 * Màn này thay cho việc gõ SQL tay vào CSDL thật — xem Garages::tenMien().
 *
 * Nói rõ BA việc mà nhìn vào bảng dữ liệu không thấy được:
 *
 *   1. Tên miền CHÍNH của gara tổng là thứ suy ra TÊN MIỀN GỐC của hệ thống,
 *      tức là địa chỉ mà mọi gara mở sau sẽ nhận. Đổi nó ở đây là đổi cả những
 *      gara chưa tồn tại. Một lần đã làm một gara thật nhận địa chỉ
 *      `<mã>.tp01.localhost` — nhìn vẫn "thành công", chỉ có điều không ai vào
 *      được.
 *
 *   2. Khai tên miền ở đây CHƯA ĐỦ. Còn hai việc ngoài hệ thống: trỏ DNS và
 *      thêm ServerAlias cho Apache. Thiếu một trong hai thì địa chỉ vẫn không
 *      mở được, mà bảng này nói rằng đã xong.
 *
 *   3. Host của máy nội bộ (`.localhost`, `.test`, tên máy trong mạng LAN) chỉ
 *      dùng để chạy thử. Đem lên máy chủ thật là vô nghĩa, mà lại chiếm mất
 *      host (cột `host` duy nhất toàn bảng).
 */
$laTong = (int) $item['is_master'] === 1;
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
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-globe mr-2"></i>{{$page_name}}</h3>
                <div class="card-tools">
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Danh sách gara
                    </a>
                </div>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tên miền</th>
                            <th style="width:110px" class="text-center">Chính</th>
                            <th style="width:120px" class="text-center">Trạng thái</th>
                            <th style="width:150px" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    @if (!empty($dsTenMien))
                        @foreach ($dsTenMien as $d)
                        <tr>
                            <td>
                                <code>{{$d['host']}}</code>
                                <?php if (la_host_noi_bo($d['host'])): ?>
                                <span class="badge badge-warning" title="Chỉ dùng để chạy thử ở máy local; trên máy chủ thật nó vô nghĩa">máy nội bộ</span>
                                <?php else: ?>
                                <a href="http://{{$d['host']}}/" target="_blank" rel="noopener"
                                   class="small ml-1" title="Mở thử trong tab mới">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                {!! $d['is_primary']==1 ? '<span class="badge badge-info">Chính</span>' : '' !!}
                            </td>
                            <td class="text-center">
                                {!! $d['status']==1 ? '<span class="badge badge-success">Đang bật</span>' : '<span class="badge badge-secondary">Tắt</span>' !!}
                            </td>
                            <td class="text-center">
                                @if ($d['is_primary']!=1 && $d['status']==1)
                                <a href="{{_WEB_URL.'/admin/'.$routeBase.'/ten-mien-chinh/'.$d['id']}}"
                                   class="btn btn-info btn-sm" title="Đặt làm tên miền chính"><i class="fas fa-star"></i></a>
                                @endif
                                @if ($d['status']==1)
                                <a onclick="return confirm('Tắt {{$d['host']}}? Địa chỉ này sẽ không vào được nữa.')"
                                   href="{{_WEB_URL.'/admin/'.$routeBase.'/ten-mien-tat/'.$d['id']}}"
                                   class="btn btn-secondary btn-sm" title="Tắt"><i class="fas fa-ban"></i></a>
                                @else
                                <a href="{{_WEB_URL.'/admin/'.$routeBase.'/ten-mien-tat/'.$d['id']}}"
                                   class="btn btn-success btn-sm" title="Bật"><i class="fas fa-check"></i></a>
                                @endif
                                <a onclick="return confirm('Xoá hẳn tên miền {{$d['host']}}?')"
                                   href="{{_WEB_URL.'/admin/'.$routeBase.'/ten-mien-xoa/'.$d['id']}}"
                                   class="btn btn-danger btn-sm" title="Xoá"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <i class="fas fa-exclamation-triangle fa-2x d-block mb-2 text-danger"></i>
                                <span class="text-danger">Gara này chưa có tên miền nào — website của nó chưa ai vào được.</span>
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Khai thêm tên miền</h3>
            </div>
            <form action="{{_WEB_URL.'/admin/'.$routeBase.'/ten-mien/'.$item['id']}}" method="post">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    <div class="form-group">
                        <label>Tên miền <span class="text-danger">*</span></label>
                        <input type="text" name="host" class="form-control"
                               value="{{!empty($old['host']) ? $old['host'] : ''}}"
                               placeholder="{{!empty($hostGoiY) ? $hostGoiY : 'gara.etek.rikkeiedu.org'}}">
                        <small class="form-text text-muted">
                            Chỉ nhập phần tên miền — dán cả <code>https://...</code> hay đường dẫn phía sau
                            thì hệ thống tự cắt. Không phân biệt chữ hoa chữ thường.
                        </small>
                    </div>
                    <?php if (!empty($hostGoiY)): ?>
                    <p class="mb-2">
                        Gợi ý theo mã gara: <code>{{$hostGoiY}}</code>
                    </p>
                    <?php endif; ?>
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="is_primary" name="is_primary" value="1">
                        <label class="form-check-label" for="is_primary">Đặt làm tên miền chính của gara này</label>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Khai tên miền</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Gara</h3></div>
            <div class="card-body">
                <p class="mb-1"><strong>{{$item['name']}}</strong>
                    {!! $laTong ? '<span class="badge badge-info">Gara tổng</span>' : '' !!}
                </p>
                <p class="mb-0 text-muted">Mã: <code>{{$item['code']}}</code></p>
            </div>
        </div>

        <?php /* TÊN MIỀN GỐC — và hệ quả của việc đổi nó. Chỉ có ý nghĩa ở gara
                 tổng, nên nói thẳng ở đây thay vì để trong tài liệu. */ ?>
        <div class="card card-outline {{$laTong ? 'card-warning' : 'card-light'}}">
            <div class="card-header"><h3 class="card-title">Tên miền gốc của hệ thống</h3></div>
            <div class="card-body">
                @if (!empty($tenMienGoc))
                <p class="mb-2"><code>{{$tenMienGoc}}</code></p>
                <p class="small text-muted mb-0">
                    Suy từ <strong>tên miền chính của gara tổng</strong>. Gara mở mới sẽ tự nhận
                    địa chỉ <code>&lt;mã gara&gt;.{{$tenMienGoc}}</code>.
                    @if ($laTong)
                    <br><strong class="text-warning">Đổi tên miền chính ở trang này là đổi địa chỉ của
                    mọi gara mở sau.</strong>
                    @endif
                </p>
                @else
                <p class="text-danger mb-0">
                    Chưa suy ra được: gara tổng chưa có tên miền nào. Gara mở mới sẽ không được
                    cấp tên miền tự động.
                </p>
                @endif
            </div>
        </div>

        <div class="card card-outline card-danger">
            <div class="card-header"><h3 class="card-title">Khai ở đây chưa đủ</h3></div>
            <div class="card-body small">
                <p>Còn hai việc <strong>ngoài hệ thống</strong>, thiếu một trong hai thì địa chỉ
                   vẫn không mở được:</p>
                <ol class="pl-3 mb-2">
                    <li class="mb-2">
                        <strong>DNS</strong> — một bản ghi wildcard là đủ cho mọi gara:
                        <br><code>*.{{!empty($tenMienGoc) ? $tenMienGoc : 'tên-miền-gốc'}} &nbsp;A &nbsp;&lt;IP máy chủ&gt;</code>
                    </li>
                    <li>
                        <strong>Apache</strong> — thêm vào <code>httpd-vhosts.conf</code>,
                        cả khối cổng 80 lẫn 443:
                        <br><code>ServerAlias *.{{!empty($tenMienGoc) ? $tenMienGoc : 'tên-miền-gốc'}}</code>
                        <br>rồi restart Apache.
                    </li>
                </ol>
                <p class="mb-0 text-muted">
                    Có hai thứ đó rồi thì mở gara mới <em>không phải</em> đụng vào DNS hay Apache nữa.
                </p>
            </div>
        </div>
    </div>
</div>
