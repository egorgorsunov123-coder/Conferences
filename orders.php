<?php
require 'db.php';
session_start();

// Проверка авторизации
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';

// Обработка отправки отзыва
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $grade   = trim($_POST['grade'] ?? '');
    $text    = trim($_POST['text'] ?? '');

    // Проверяем, что заявка принадлежит пользователю и имеет статус Ended
    $chk = $pdo->prepare(
        "SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'Ended'"
    );
    $chk->execute([$orderId, $userId]);

    if (!$chk->fetch()) {
        $errorMsg = 'Оставить отзыв можно только по завершённой заявке.';
    } elseif (!in_array($grade, ['1','2','3','4','5'])) {
        $errorMsg = 'Выберите оценку от 1 до 5.';
    } elseif (mb_strlen($text) < 3) {
        $errorMsg = 'Введите текст отзыва.';
    } else {
        // Проверяем, нет ли уже отзыва
        $exists = $pdo->prepare("SELECT id FROM review WHERE order_id = ?");
        $exists->execute([$orderId]);
        if ($exists->fetch()) {
            $errorMsg = 'Вы уже оставляли отзыв на эту заявку.';
        } else {
            $ins = $pdo->prepare(
                "INSERT INTO review (order_id, grade, text) VALUES (?, ?, ?)"
            );
            $ins->execute([$orderId, $grade, $text]);
            $successMsg = 'Отзыв успешно сохранён!';
        }
    }
}

// Получаем данные пользователя
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();

// Получаем заявки пользователя с отзывами 
$stmtOrders = $pdo->prepare("
    SELECT o.*, r.id AS review_id, r.grade, r.text AS review_text
    FROM orders o
    LEFT JOIN review r ON r.order_id = o.id
    WHERE o.user_id = ?
    ORDER BY o.date_start DESC
");
$stmtOrders->execute([$userId]);
$orders = $stmtOrders->fetchAll();

// Подсчёт статистики
$total = count($orders);
$ended = 0; $avgGrade = 0; $gradeCount = 0;
foreach ($orders as $o) {
    if ($o['status'] === 'Ended') $ended++;
    if ($o['grade']) { $avgGrade += $o['grade']; $gradeCount++; }
}
$avgGrade = $gradeCount ? round($avgGrade / $gradeCount, 1) : null;

$confLabels = ['Lecture' => 'Лекция', 'Coworking' => 'Коворкинг', 'Cinema' => 'Кинозал'];
$payLabels  = ['Cash' => 'Наличные', 'Card' => 'Карта'];
$statusLabels = ['New' => 'Новая', 'Scheduled' => 'Запланирована', 'Ended' => 'Завершена'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Личный кабинет - Конференции.РФ</title>
    <link rel="stylesheet" href="bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="images/icon.jpg" type="jpg">
</head>
<body>

<nav class="navbar site-navbar shadow-sm">
    <div class="container-fluid px-4">
        <div class="">
            <img src="images/icon3.png" class="iconImg">
            <a class="navbar-brand" href="orders.php">КОНФЕРЕНЦИИ.РФ</a>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="navbar-text"><?= htmlspecialchars($user['fio']) ?></span>
            <a href="new_order.php" class="btn btn-brand-green btn-sm fw-semibold">+ Новая заявка</a>
            <a href="logout.php" class="btn btn-sm btn-outline-secondary text-white border-secondary">Выйти</a>
        </div>
    </div>
</nav>

<div class="container py-5">

    <!-- ЗАГОЛОВОК -->
    <div class="mb-4">
        <h1>Личный кабинет</h1>
        <p class="text-muted" style="font-size:14px;">Управляйте заявками и оставляйте отзывы</p>
    </div>

    <!-- ПРОФИЛЬ -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <div class="profile-avatar"><?= mb_strtoupper(mb_substr($user['fio'], 0, 1)) ?></div>
                <div>
                    <h3 class="mb-1"><?= htmlspecialchars($user['fio']) ?></h3>
                    <div class="d-flex flex-wrap gap-3" style="font-size:13px; color:#6C757D;">
                        <span>Логин: <strong class="text-dark"><?= htmlspecialchars($user['login']) ?></strong></span>
                        <?php if ($user['phone']): ?>
                        <span>Телефон: <strong class="text-dark"><?= htmlspecialchars($user['phone']) ?></strong></span>
                        <?php endif; ?>
                        <?php if ($user['email']): ?>
                        <span>Email: <strong class="text-dark"><?= htmlspecialchars($user['email']) ?></strong></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- СТАТИСТИКА -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-small text-muted text-uppercase mb-2" style="letter-spacing:.06em;">Всего заявок</div>
                    <div class="stat-value"><?= $total ?></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-small text-muted text-uppercase mb-2" style="letter-spacing:.06em;">Завершённых</div>
                    <div class="stat-value green"><?= $ended ?></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-small text-muted text-uppercase mb-2" style="letter-spacing:.06em;">Средняя оценка</div>
                    <div class="stat-value"><?= $avgGrade !== null ? $avgGrade : '—' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- АЛЕРТЫ -->
    <?php if ($successMsg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <!-- ЗАЯВКИ -->
    <h2 class="mb-3">История заявок</h2>

    <?php if (empty($orders)): ?>
        <div class="card text-center py-5">
            <div class="card-body">
                <div style="font-size:3rem; opacity:.4;">📋</div>
                <h3 class="mt-3">Заявок пока нет</h3>
                <p class="text-muted" style="font-size:14px;">Создайте первую заявку, чтобы забронировать помещение</p>
                <a href="new_order.php" class="btn btn-brand-green mt-2 fw-semibold">Создать заявку</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Тип зала</th>
                            <th>Дата</th>
                            <th>Оплата</th>
                            <th>Статус</th>
                            <th style="min-width:230px;">Отзыв</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="text-muted" style="font-size:13px;"><?= $order['id'] ?></td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-25 text-dark fw-semibold" style="font-size:12px;">
                                    <?= $confLabels[$order['conf']] ?? $order['conf'] ?>
                                </span>
                            </td>
                            <td style="font-size:14px;"><?= htmlspecialchars($order['date_start']) ?></td>
                            <td style="font-size:14px; color:#6C757D;"><?= $payLabels[$order['payment']] ?? '—' ?></td>
                            <td>
                                <?php $s = $order['status']; ?>
                                <span class="badge <?= $s === 'New' ? 'badge-new' : ($s === 'Scheduled' ? 'badge-scheduled' : 'badge-ended') ?>" style="font-size:12px;">
                                    <?= $statusLabels[$s] ?? $s ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($order['review_id']): ?>
                                    <div class="review-box">
                                        <div class="review-stars">
                                            <?= str_repeat('★', (int)$order['grade']) . str_repeat('☆', 5 - (int)$order['grade']) ?>
                                        </div>
                                        <div style="font-size:12px; color:#6C757D; margin-top:3px;">
                                            <?= htmlspecialchars(mb_substr($order['review_text'], 0, 80)) ?><?= mb_strlen($order['review_text']) > 80 ? '…' : '' ?>
                                        </div>
                                    </div>
                                <?php elseif ($order['status'] === 'Ended'): ?>
                                    <form method="post" class="p-2 rounded" style="background:#f0fff4; border:1px solid #C3E6CB;">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <div style="font-size:12px; color:#6C757D; margin-bottom:6px;">Оцените посещение</div>
                                        <div class="star-picker mb-2">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" name="grade" id="s<?= $order['id'] ?>_<?= $i ?>" value="<?= $i ?>">
                                            <label for="s<?= $order['id'] ?>_<?= $i ?>">★</label>
                                            <?php endfor; ?>
                                        </div>
                                        <textarea name="text" class="form-control form-control-sm mb-2" rows="2"
                                                  placeholder="Напишите отзыв…" required></textarea>
                                        <button type="submit" name="submit_review"
                                                class="btn btn-brand-green btn-sm fw-semibold">Отправить</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size:12px; color:#6C757D;">Доступно после завершения</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
