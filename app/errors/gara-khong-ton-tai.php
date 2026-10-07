<?php
/**
 * Host không thuộc gara nào. Xem TenMienMiddleware để biết khi nào trang này
 * hiện ra.
 *
 * Nói rõ ĐỊA CHỈ ĐANG GÕ là gì: gần như lần nào gặp trang này cũng là do gõ
 * nhầm tên miền phụ hoặc DNS chưa trỏ xong, mà hai cái đó chỉ nhìn địa chỉ mới
 * biết. KHÔNG liệt kê các gara đang có — địa chỉ của gara khác không phải thứ
 * đem khoe cho người lạ.
 *
 * $host do middleware truyền vào.
 */
$host = isset($host) ? (string) $host : '';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Không tìm thấy gara</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            background: #f4f6f9; color: #1f2d3d; padding: 24px;
        }
        .hop {
            background: #fff; border-radius: 12px; padding: 32px; max-width: 520px; width: 100%;
            box-shadow: 0 10px 30px rgba(0,0,0,.08); text-align: center;
        }
        h1 { font-size: 20px; margin: 0 0 12px; }
        p  { margin: 0 0 10px; line-height: 1.6; color: #52606d; }
        code {
            display: inline-block; margin-top: 4px; padding: 4px 10px; border-radius: 6px;
            background: #eef1f5; color: #1f2d3d; font-size: 14px; word-break: break-all;
        }
        @media (prefers-color-scheme: dark) {
            body { background: #11161c; color: #e6edf3; }
            .hop { background: #1b222b; box-shadow: none; }
            p    { color: #9fb0c0; }
            code { background: #263040; color: #e6edf3; }
        }
    </style>
</head>
<body>
    <div class="hop">
        <h1>Không tìm thấy gara cho địa chỉ này</h1>
        <?php if ($host !== ''): ?>
        <p>Địa chỉ đang mở:<br/><code><?php echo htmlspecialchars($host, ENT_QUOTES, 'UTF-8'); ?></code></p>
        <?php endif; ?>
        <p>Kiểm tra lại địa chỉ, hoặc liên hệ quản trị hệ thống nếu gara vừa được mở.</p>
    </div>
</body>
</html>
