<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Biên bản giao nhận {{$h['handover_no']}}</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: "Times New Roman", Times, serif; color:#000; font-size:14px; line-height:1.5; margin:0; background:#f2f2f2; }
    .sheet { background:#fff; width:210mm; min-height:297mm; margin:12px auto; padding:18mm 16mm; }
    .head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid #000; padding-bottom:8px; }
    .company { font-weight:bold; text-transform:uppercase; font-size:15px; }
    .muted { color:#333; font-size:13px; }
    h1 { text-align:center; font-size:20px; margin:22px 0 2px; text-transform:uppercase; }
    .sub { text-align:center; font-style:italic; margin-bottom:18px; }
    table.info { width:100%; border-collapse:collapse; margin:6px 0; }
    table.info td { padding:5px 4px; vertical-align:top; }
    table.info td.k { width:170px; font-weight:bold; }
    .box { border:1px solid #000; padding:8px 10px; margin:8px 0; min-height:44px; }
    .box .lbl { font-weight:bold; display:block; margin-bottom:3px; }
    .signs { display:flex; justify-content:space-between; margin-top:34px; text-align:center; }
    .signs .col { width:45%; }
    .signs .role { font-weight:bold; }
    .signs .hint { font-style:italic; font-size:13px; color:#333; }
    .signs .space { height:80px; }
    .toolbar { text-align:center; margin:10px; }
    .toolbar button, .toolbar a { font-family:Arial, sans-serif; font-size:13px; padding:8px 16px; border-radius:4px; border:1px solid #888; background:#fff; cursor:pointer; text-decoration:none; color:#333; }
    .toolbar button { background:#c0392b; color:#fff; border-color:#c0392b; }
    @media print { body { background:#fff; } .sheet { margin:0; width:auto; min-height:auto; padding:10mm; } .toolbar { display:none; } }
</style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">🖨 In biên bản</button>
        <a href="{{_WEB_URL.'/admin/warranty/edit/'.$item['id']}}">← Về phiếu bảo hành</a>
    </div>

    <div class="sheet">
        <div class="head">
            <div>
                <div class="company">{{$company}}</div>
                {{$address!==''?$address:''}}
                <div class="muted">{{$phone!==''?'ĐT: '.$phone:''}}</div>
            </div>
            <div class="muted" style="text-align:right">
                Số: <b>{{$h['handover_no']}}</b><br/>
                Ngày: {{date('d/m/Y', strtotime($h['handover_date']))}}
            </div>
        </div>

        <h1>Biên bản giao nhận thiết bị</h1>
        <div class="sub">({{$typeLabel}} — theo phiếu bảo hành {{$item['request_no']}})</div>

        <table class="info">
            <tr><td class="k">Khách hàng:</td><td>{{$ctx['customer']!==''?$ctx['customer']:'…………………………'}}</td></tr>
            <tr><td class="k">Điện thoại:</td><td>{{$ctx['phone']!==''?$ctx['phone']:'…………………………'}}</td></tr>
            <tr><td class="k">Thiết bị:</td><td>{{$ctx['product']!==''?$ctx['product']:'…………………………'}}</td></tr>
            <tr><td class="k">Số serial:</td><td>{{!empty($item['serial_no'])?$item['serial_no']:'…………………………'}}</td></tr>
            <tr><td class="k">Bên giao:</td><td>{{!empty($h['deliverer'])?$h['deliverer']:'…………………………'}}</td></tr>
            <tr><td class="k">Bên nhận:</td><td>{{!empty($h['receiver'])?$h['receiver']:'…………………………'}}</td></tr>
        </table>

        <div class="box">
            <span class="lbl">Phụ kiện đi kèm:</span>
            {{!empty($h['accessories'])?$h['accessories']:'…………………………………………………………………………………'}}
        </div>
        <div class="box">
            <span class="lbl">Tình trạng thiết bị khi giao nhận:</span>
            {{!empty($h['condition_note'])?$h['condition_note']:'…………………………………………………………………………………'}}
        </div>
        @if (!empty($h['note']))
        <div class="box">
            <span class="lbl">Ghi chú:</span>
            {{$h['note']}}
        </div>
        @endif

        <p style="margin-top:14px">Hai bên đã kiểm tra và thống nhất nội dung giao nhận nêu trên. Biên bản được lập thành 02 bản, mỗi bên giữ 01 bản có giá trị như nhau.</p>

        <div class="signs">
            <div class="col">
                <div class="role">BÊN GIAO</div>
                <div class="hint">(Ký, ghi rõ họ tên)</div>
                <div class="space"></div>
                <div>{{!empty($h['deliverer'])?$h['deliverer']:''}}</div>
            </div>
            <div class="col">
                <div class="role">BÊN NHẬN</div>
                <div class="hint">(Ký, ghi rõ họ tên)</div>
                <div class="space"></div>
                <div>{{!empty($h['receiver'])?$h['receiver']:''}}</div>
            </div>
        </div>
    </div>
</body>
</html>
