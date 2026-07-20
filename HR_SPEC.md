# PHÂN HỆ NHÂN SỰ (HR) — lát cắt HR-1

| | |
|---|---|
| **Trạng thái** | ✅ **XONG + verify** (19/07/2026, migration `000022`). |

## Đã làm

| Màn hình | URL | Nội dung |
|---|---|---|
| Phòng ban | `admin/departments` | CRUD (code, tên, mô tả); seed 4 phòng |
| Chức vụ | `admin/positions` | CRUD; seed 4 chức vụ |
| Nhân viên | `admin/employees` | Hồ sơ: mã, tên, phòng ban, chức vụ, giới tính, ngày sinh, LH, ngày vào, lương CB; lọc theo phòng/trạng thái/từ khoá |
| Đơn nghỉ phép | `admin/leave-requests` | Lập đơn (loại: phép năm/ốm/không lương/khác) + luồng duyệt **chờ duyệt → duyệt/từ chối**; số ngày tự tính từ khoảng ngày |

## Mô hình (migration 000022)

- `departments`, `positions` (danh mục).
- `employees` (code UNIQUE, department_id/position_id FK SET NULL, salary_base, status đang làm/nghỉ).
- `leave_requests` (employee_id CASCADE, leave_type, from/to_date, days, status pending/approved/rejected).

## Hoãn (HR-2)

Chấm công (timesheet) · bảng lương (payroll từ lương CB + phép + phụ cấp) · hợp đồng lao động · liên kết employee ↔ user (tài khoản đăng nhập) · nghỉ phép trừ quỹ phép năm.
