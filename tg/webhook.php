<?php
require __DIR__ . '/bootstrap.php';

$raw = file_get_contents('php://input');
$u = json_decode($raw, true);

// Helper: save video row
function save_video_row($m) {
  $pdo = db();
  $v = $m['video'];
  $stmt = $pdo->prepare("
    INSERT INTO videos (tg_chat_id, tg_message_id, file_id, file_unique_id, caption, duration, mime_type, width, height, thumb_file_id)
    VALUES (:chat, :msg, :fid, :fuid, :cap, :dur, :mime, :w, :h, :thumb)
  ");
  $stmt->execute([
    ':chat'  => $m['chat']['id'] ?? null,
    ':msg'   => $m['message_id'] ?? null,
    ':fid'   => $v['file_id'],
    ':fuid'  => $v['file_unique_id'],
    ':cap'   => $m['caption'] ?? null,
    ':dur'   => $v['duration'] ?? null,
    ':mime'  => $v['mime_type'] ?? null,
    ':w'     => $v['width'] ?? null,
    ':h'     => $v['height'] ?? null,
    ':thumb' => $v['thumbnail']['file_id'] ?? ($v['thumb']['file_id'] ?? null),
  ]);
}

if (!empty($u['message']['video']))        save_video_row($u['message']);
if (!empty($u['channel_post']['video']))   save_video_row($u['channel_post']);

http_response_code(200);
echo 'OK';
