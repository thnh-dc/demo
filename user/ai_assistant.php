<?php
session_start();
require_once '../config/database.php';
require_once '../config/groq_config.php';
header('Content-Type: application/json; charset=utf-8');
$message = trim($_POST['message'] ?? '');
if ($message === '') {
    echo json_encode([
        'reply' => 'Bạn hãy nhập nội dung cần hỗ trợ nhé.'
    ]);
    exit;
}
$lower_message = mb_strtolower($message, 'UTF-8');
$is_logged_in = isset($_SESSION['user_id']);
$context = "";
$intent = "general";
/* phân loại ý định*/
if (
    str_contains($lower_message, 'đơn hàng') ||
    str_contains($lower_message, 'don hang') ||
    str_contains($lower_message, 'tra cứu đơn') ||
    str_contains($lower_message, 'trạng thái đơn')
) {
    $intent = "order";
} elseif (
    str_contains($lower_message, 'tìm') ||
    str_contains($lower_message, 'tim') ||
    str_contains($lower_message, 'sản phẩm') ||
    str_contains($lower_message, 'san pham') ||
    str_contains($lower_message, 'laptop') ||
    str_contains($lower_message, 'chuột') ||
    str_contains($lower_message, 'ban phím') ||
    str_contains($lower_message, 'bàn phím') ||
    str_contains($lower_message, 'tai nghe') ||
    str_contains($lower_message, 'màn hình') ||
    str_contains($lower_message, 'loa')
) {
    $intent = "product";
} elseif (
    str_contains($lower_message, 'bảo hành') ||
    str_contains($lower_message, 'bao hanh') ||
    str_contains($lower_message, 'thanh toán') ||
    str_contains($lower_message, 'thanh toan') ||
    str_contains($lower_message, 'đổi trả') ||
    str_contains($lower_message, 'doi tra') ||
    str_contains($lower_message, 'liên hệ') ||
    str_contains($lower_message, 'lien he')
) {
    $intent = "policy";
}
/*xử lí ý định-sản phẩm */
if ($intent === "product") {
    $max_price = null;
    $min_price = null;
    if (preg_match('/(?:dưới|duoi|nhỏ hơn|nho hon)\s*([0-9\.]+)/u', $lower_message, $matches)) {
        $max_price = (int) str_replace('.', '', $matches[1]);
    }
    if (preg_match('/(?:trên|tren|lớn hơn|lon hon)\s*([0-9\.]+)/u', $lower_message, $matches)) {
        $min_price = (int) str_replace('.', '', $matches[1]);
    }
    $keyword = $message;
        $is_cheap_search = false;
        if (
            str_contains($lower_message, 'giá rẻ') ||
            str_contains($lower_message, 'gia re') ||
            str_contains($lower_message, 'rẻ') ||
            str_contains($lower_message, 're')
        ) {
            $is_cheap_search = true;
        }
        $remove_words = [
            'tìm', 'tim',
            'sản phẩm', 'san pham',
            'dưới', 'duoi',
            'nhỏ hơn', 'nho hon',
            'trên', 'tren',
            'lớn hơn', 'lon hon',
            'giá rẻ', 'gia re',
            'giá', 'gia',
            'rẻ', 're',
            'khoảng', 'khoang'
        ];
        $keyword = str_ireplace($remove_words, '', $keyword);
        $keyword = preg_replace('/[0-9\.]+/', '', $keyword);
        $keyword = trim($keyword);
    $sql = "
        SELECT 
            p.id,
            p.name,
            p.price,
            p.stock_quantity,
            p.description,
            p.image_url,
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE 1 = 1
    ";
    $params = [];
    if ($keyword !== '') {
            $words = preg_split('/\s+/', $keyword);
            foreach ($words as $word) {
                $word = trim($word);
                if ($word === '') {
                    continue;
                }
                $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)";
                $params[] = "%$word%";
                $params[] = "%$word%";
                $params[] = "%$word%";
            }
        }
    if ($max_price !== null) {
        $sql .= " AND p.price <= ?";
        $params[] = $max_price;
    }
    if ($min_price !== null) {
        $sql .= " AND p.price >= ?";
        $params[] = $min_price;
    }
    $sql .= " ORDER BY p.price ASC LIMIT 8";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($products)) {
        $context = "Không tìm thấy sản phẩm phù hợp trong database.";
    } else {
        $context = "Kết quả sản phẩm tìm được trong database:\n";

        foreach ($products as $p) {
            $product_link = "/user/product_detail.php?id=" . $p['id'];
            $context .= "- ID: {$p['id']} | Tên: {$p['name']} | Giá: {$p['price']} | Tồn kho: {$p['stock_quantity']} | Danh mục: {$p['category_name']} | Mô tả: {$p['description']} | Link chi tiết: {$product_link}\n";
        }
    }
}
/* nếu ý định là đơn hàng */
if ($intent === "order") {
    if (!$is_logged_in) {
        $context = "Người dùng chưa đăng nhập. Không thể tra cứu đơn hàng. Hãy nhắc người dùng đăng nhập hoặc đăng ký.";
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                total_amount,
                status,
                created_at,
                shipping_address
            FROM orders
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($orders)) {
            $context = "Người dùng chưa có đơn hàng nào.";
        } else {
            $context = "Danh sách đơn hàng gần đây của người dùng:\n";
            foreach ($orders as $o) {
                $order_code = str_pad($o['id'], 6, '0', STR_PAD_LEFT);
                $context .= "- Mã đơn: #{$order_code} | Tổng tiền: {$o['total_amount']} | Trạng thái: {$o['status']} | Ngày đặt: {$o['created_at']} | Địa chỉ giao: {$o['shipping_address']}\n";
            }
        }
    }
}
/* nếu ý định liên quan chính sách */
if ($intent === "policy") {
    $context = "
Thông tin hỗ trợ của FD Tech:
- Thanh toán: Khách hàng có thể thanh toán theo phương thức website đang hỗ trợ.
- Bảo hành: Sản phẩm được hỗ trợ bảo hành theo chính sách của cửa hàng.
- Đổi trả: Khách hàng cần liên hệ cửa hàng để được kiểm tra điều kiện đổi trả.
- Liên hệ: Email fdtech@gmail.com hoặc fivedev@gmail.com.
- Địa chỉ: Trường Đại học Quy Nhơn.
";
}
/* request chung */
if ($intent === "general") {
    $context = "
Người dùng đang hỏi câu hỏi chung.
Bot chỉ nên hỗ trợ các nội dung:
- Thông tin trang web
- Tìm kiếm sản phẩm
- Tư vấn mua hàng
- Tra cứu đơn hàng
- Hướng dẫn đăng nhập, đăng ký
- Chính sách thanh toán, bảo hành, đổi trả, liên hệ
";
}
$login_status = $is_logged_in ? 'Đã đăng nhập' : 'Chưa đăng nhập';
$system_prompt = "
Bạn là Trợ lí AI của website FD Tech, tên là FD Bot. Nguyễn Thành Được là người phát triển nên bạn.
FD Tech là phần mềm ứng dụng web quản lí bán hàng thiết bị, phụ kiện công nghệ.
Vai trò:
- Hỗ trợ khách hàng tìm sản phẩm công nghệ.
- Tư vấn mua hàng.
- Tra cứu đơn hàng nếu người dùng đã đăng nhập.
- Hướng dẫn đăng nhập, đăng ký, bảo hành, thanh toán, đổi trả.

Quy tắc bắt buộc:
- Trả lời bằng tiếng Việt.
- Trả lời ngắn gọn, rõ ràng, thân thiện.
- Chỉ dùng dữ liệu được cung cấp trong phần NGỮ CẢNH.
- Không bịa sản phẩm, giá, tồn kho hoặc đơn hàng.
- Nếu có sản phẩm, không được hiển thị raw link dạng /user/product_detail.php?id=ID.
- Khi giới thiệu sản phẩm, phải viết đúng dạng: Xem chi tiết <a href=\"/user/product_detail.php?id=ID\" class=\"ai-detail-link\">tại đây</a>.
- Nếu người dùng chưa đăng nhập mà hỏi đơn hàng, hãy yêu cầu đăng nhập.
- Nếu không tìm thấy dữ liệu phù hợp, hãy nói rõ là chưa tìm thấy.
Một số nội dung đáng chú ý:
- Thông tin thành viên phát triển trang web FD Tech gồm có 5 người: Nguyễn Thành Được, Nguyễn Huỳnh Quốc Tịnh, Lê Vũ Hoài Niệm, Lê Quốc Thắng, Nguyễn Văn Khôi.
- Khi người dùng hỏi các sản phẩm không có trong danh mục sản phẩm của trang web, hãy khéo léo giới thiệu các sản phẩm hiện có trên trang Web ( không có tablet)
- Khi người dùng muốn chat với người bán hoặc liên hệ người bán, hãy đề cập khéo léo đến hotline của trang web: 19001000 hoặc địa chỉ email của trang web.
- Khi người dùng hỏi về đơn hàng, hãy khéo léo chuyển hướng người dùng sang trang đơn hàng để xem chi tiết.
- Khi chuyển hướng sang giỏ hàng phải viết đúng dạng: <a href=\"http://localhost/user/profile.php?action=orders\" class=\"ai-detail-link\">xem chi tiết đơn hàng</a>.
- Người dùng nhắc đến việc đổi mật khẩu, hãy khéo léo chuyển về trang đổi mật khẩu, viết đúng: <a href=\"http://localhost/user/profile.php?action=password\" class=\"ai-detail-link\">đổi mật khẩu ngay</a>.
- Muốn xem hoặc chỉnh sửa thông tin cá nhân : <a href=\"http://localhost/user/profile.php\" class=\"ai-detail-link\">đổi thông tin</a>.

Trạng thái người dùng: {$login_status}
Ý định đã phân loại: {$intent}
NGỮ CẢNH:
{$context}
";
$data = [
    "model" => GROQ_MODEL,
    "messages" => [
        [
            "role" => "system",
            "content" => $system_prompt
        ],
        [
            "role" => "user",
            "content" => $message
        ]
    ],
    "temperature" => 0.3,
    "max_tokens" => 700
];

$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer " . trim(GROQ_API_KEY)
    ],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_TIMEOUT => 30
]);
$response = curl_exec($ch);
if ($response === false) {
    echo json_encode([
        'reply' => 'Lỗi kết nối Groq API: ' . curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$result = json_decode($response, true);
if ($http_code < 200 || $http_code >= 300) {
    $error_message = $result['error']['message'] ?? 'Không rõ lỗi';

    echo json_encode([
        'reply' => 'Groq API đang gặp lỗi. Mã lỗi: ' . $http_code . '. Nội dung: ' . $error_message
    ]);
    exit;
}
$reply = $result['choices'][0]['message']['content'] ?? '';
if ($reply === '') {
    $reply = 'Mình chưa có phản hồi phù hợp. Bạn thử hỏi lại rõ hơn nhé.';
}
echo json_encode([
    'reply' => nl2br($reply)
]);
exit;