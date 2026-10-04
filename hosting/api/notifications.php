<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
$u = current_user();
if (!$u) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'auth']); exit; }

$body = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
$act  = $body['act'] ?? $_POST['act'] ?? '';
$sent = $body['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if ($sent !== csrf_token()) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'csrf']); exit; }

$id = (int)$u['id'];
switch ($act) {
    case 'read_all':
        db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$id]);
        break;
    case 'read_one':
        $nid = (int)($body['id'] ?? 0);
        db()->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?')->execute([$nid, $id]);
        break;
    case 'clear':
        db()->prepare('DELETE FROM notifications WHERE user_id=?')->execute([$id]);
        break;
    default:
        http_response_code(422); echo json_encode(['ok'=>false,'error'=>'bad act']); exit;
}
echo json_encode(['ok'=>true,'unread'=>unread_count($id)]);
