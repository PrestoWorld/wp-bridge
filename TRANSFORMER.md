# WordPress to PrestoWorld Transformer System

Tài liệu này hướng dẫn cách sử dụng và mở rộng hệ thống **Transformer** để chuyển đổi mã nguồn WordPress Native sang cấu trúc của PrestoWorld.

---

## 1. Nguyên lý hoạt động

Hệ thống hoạt động theo cơ chế **Source-to-Source Compilation**:
1. **Detection**: `PluginDetector` nhận diện plugin qua Plugin Header.
2. **Filtering**: `TransformerRegistry` sử dụng Keyword Mapping để chỉ nạp các bộ chuyển đổi cần thiết cho file hiện tại.
3. **Transformation**: Code được sửa đổi để thay thế các pattern WP bằng PrestoWorld native.
4. **Caching**: Code đã transform được lưu vào thư mục `Cache/` để sử dụng cho các lần sau mà không cần compile lại.

## 2. Cách tạo một Transformer mới

Một Transformer là một lớp thực thi `PrestoWorld\Contracts\Sandbox\TransformerInterface`.

### Ví dụ: Chuyển đổi global $wpdb
Nếu bạn muốn thay thế việc gọi `$wpdb` bằng Service của PrestoWorld:

```php
namespace PrestoWorld\Bridge\WordPress\Sandbox\Transformers;

use PrestoWorld\Contracts\Sandbox\TransformerInterface;

class WpdbTransformer implements TransformerInterface
{
    public function getKeywords(): array
    {
        // Hệ thống sẽ chỉ chạy transformer này nếu file có chứa '$wpdb'
        return ['$wpdb'];
    }

    public function transform(string $code): string
    {
        return str_replace(
            'global $wpdb;',
            '$wpdb = \PrestoWorld\Foundation\DB::getInstance();',
            $code
        );
    }
}
```

## 3. Tối ưu hiệu suất với Keywords

Hệ thống được thiết kế để xử lý hàng nghìn Transformer. Điểm mấu chốt nằm ở phương thức `getKeywords()`:
- **Tác dụng**: Giúp `TransformerRegistry` lọc nhanh các transformer liên quan mà không cần khởi tạo class.
- **Quy tắc**: Chỉ liệt kê những từ khóa đặc trưng nhất. Ví dụ: `'wp_enqueue_script'`, `'register_post_type'`.

## 4. Bỏ qua các hàm đã được Shim

Hệ thống thông minh đủ để nhận diện các hàm đã được `wp-bridge` cung cấp sẵn (Shims). 
- Nếu một hàm đã có shim trong `wp-bridge/src/Functions/`, Transformer **không cần** can thiệp vào.
- Transformer chỉ nên can thiệp vào các cấu trúc đặc thù hoặc các thư viện global mà PrestoWorld quản lý khác đi.

## 5. Quản lý qua UI (Admin)

Bạn có thể quản lý, bật/tắt hoặc cập nhật các bộ Transformer từ Marketplace thông qua trang **Transformer Manager** trong Admin Dashboard của PrestoWorld.

---
*Dành cho cộng đồng WordPresser - Chuyển đổi mã nguồn dễ dàng, hiệu suất tuyệt vời.*
