<?php
require 'db.php';
session_start();

// Только администратор
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$successMsg = '';
$errorMsg   = '';

// Смена статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    $validStatuses = ['New', 'Scheduled', 'Ended'];

    if ($orderId > 0 && in_array($newStatus, $validStatuses)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")
            ->execute([$newStatus, $orderId]);
        $successMsg = 'Статус заявки #' . $orderId . ' обновлён.';
    } else {
        $errorMsg = 'Некорректные данные.';
    }
}

// Фильтры
$filterStatus = $_GET['status'] ?? '';
$filterConf   = $_GET['conf'] ?? '';
$search       = trim($_GET['search'] ?? '');

$where = ['1=1'];
$params = [];

if ($filterStatus) { $where[] = 'o.status = ?'; $params[] = $filterStatus; }
if ($filterConf)   { $where[] = 'o.conf = ?';   $params[] = $filterConf; }
if ($search)       {
    $where[] = '(u.fio LIKE ? OR u.login LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql = "
    SELECT o.*, u.fio, u.login, u.phone, r.grade
    FROM orders o
    JOIN users u ON u.id = o.user_id
    LEFT JOIN review r ON r.order_id = o.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY o.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Общая статистика
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status='New') AS cnt_new,
        SUM(status='Scheduled') AS cnt_sched,
        SUM(status='Ended') AS cnt_ended
    FROM orders
")->fetch();

$confLabels   = ['Lecture' => 'Лекция', 'Coworking' => 'Коворкинг', 'Cinema' => 'Кинозал'];
$payLabels    = ['Cash' => 'Наличные', 'Card' => 'Карта'];
$statusLabels = ['New' => 'Новая', 'Scheduled' => 'Запланирована', 'Ended' => 'Завершена'];
$nextStatus   = ['New' => 'Scheduled', 'Scheduled' => 'Ended'];
$nextLabel    = ['New' => 'Назначить', 'Scheduled' => 'Завершить'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Панель администратора — Конференции.РФ</title>
    <link rel="stylesheet" href="bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="images/icon.jpg" type="jpg">
</head>
<body>

<nav class="navbar site-navbar shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="admin.php">КОНФЕРЕНЦИИ.РФ <span style="font-size:11px; color:#6C757D; font-weight:400;">Admin</span></a>
        <div class="d-flex align-items-center gap-3">
            <span class="navbar-text"><?= htmlspecialchars($_SESSION['fio']) ?></span>
            <a href="logout.php" class="btn btn-sm btn-outline-secondary text-white border-secondary">Выйти</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 py-5">

    <div class="mb-4">
        <h1>Панель администратора</h1>
        <p class="text-muted" style="font-size:14px;">Управление заявками пользователей</p>
    </div>

    <!-- СТАТИСТИКА -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-sm-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-small text-muted text-uppercase mb-2" style="letter-spacing:.06em;">Всего заявок</div>
                    <div class="stat-value"><?= $stats['total'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card stat-card h-100" style="border-top-color:#1565C0!important;">
                <div class="card-body">
                    <div class="text-small text-muted text-uppercase mb-2" style="letter-spacing:.06em;">Новых</div>
                    <div class="stat-value" style="color:#1565C0;"><?= $stats['cnt_new'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card stat-card h-100" style="border-top-color:#6A1B9A!important;">
                <div class="card-body">
                    <div class="text-small text-muted text-uppercase mb-2" style="letter-spacing:.06em;">Запланировано</div>
                    <div class="stat-value" style="color:#6A1B9A;"><?= $stats['cnt_sched'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-small text-muted text-uppercase mb-2" style="letter-spacing:.06em;">Завершено</div>
                    <div class="stat-value green"><?= $stats['cnt_ended'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- АЛЕРТЫ -->
    <?php if ($successMsg): ?>
        <div class="alert alert-success alert-dismissible fade show py-2">
            <?= htmlspecialchars($successMsg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <!-- ФИЛЬТРЫ -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-12 col-sm-4 col-md-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">Поиск по пользователю</label>
                    <input name="search" class="form-control form-control-sm"
                           placeholder="ФИО или логин"
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-6 col-sm-3 col-md-2">
                    <label class="form-label fw-semibold" style="font-size:13px;">Статус</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Все</option>
                        <?php foreach ($statusLabels as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $filterStatus === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-sm-3 col-md-2">
                    <label class="form-label fw-semibold" style="font-size:13px;">Тип зала</label>
                    <select name="conf" class="form-select form-select-sm">
                        <option value="">Все</option>
                        <?php foreach ($confLabels as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $filterConf === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-auto">
                    <button class="btn btn-brand-green btn-sm fw-semibold px-3">Применить</button>
                    <a href="admin.php" class="btn btn-outline-secondary btn-sm ms-1">Сбросить</a>
                </div>
            </form>
        </div>
    </div>

    <!-- ТАБЛИЦА ЗАЯВОК -->
    <div class="card p-0 overflow-hidden">
        <?php if (empty($orders)): ?>
            <div class="text-center py-5 text-muted">
                <div style="font-size:2.5rem; opacity:.4;">🔍</div>
                <h3 class="mt-3">Заявок не найдено</h3>
                <p style="font-size:14px;">Попробуйте изменить фильтры</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Пользователь</th>
                        <th>Тип зала</th>
                        <th>Дата</th>
                        <th>Оплата</th>
                        <th>Оценка</th>
                        <th>Статус</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="text-muted" style="font-size:13px;"><?= $order['id'] ?></td>
                        <td>
                            <div class="fw-semibold" style="font-size:14px;"><?= htmlspecialchars($order['fio']) ?></div>
                            <div style="font-size:12px; color:#6C757D;"><?= htmlspecialchars($order['login']) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-25 text-dark fw-semibold" style="font-size:12px;">
                                <?= $confLabels[$order['conf']] ?? $order['conf'] ?>
                            </span>
                        </td>
                        <td style="font-size:14px;"><?= htmlspecialchars($order['date_start']) ?></td>
                        <td style="font-size:13px; color:#6C757D;"><?= $payLabels[$order['payment']] ?? '—' ?></td>
                        <td>
                            <?php if ($order['grade']): ?>
                                <span style="color:#FFC107;">
                                    <?= str_repeat('★', (int)$order['grade']) ?><span style="color:#CED4DA;"><?= str_repeat('★', 5-(int)$order['grade']) ?></span>
                                </span>
                            <?php else: ?>
                                <span style="font-size:12px; color:#CED4DA;">нет отзыва</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $s = $order['status']; ?>
                            <span class="badge <?= $s === 'New' ? 'badge-new' : ($s === 'Scheduled' ? 'badge-scheduled' : 'badge-ended') ?>" style="font-size:12px;">
                                <?= $statusLabels[$s] ?? $s ?>
                            </span>
                        </td>
                        <td>
                            <?php if (isset($nextStatus[$order['status']])): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <input type="hidden" name="new_status" value="<?= $nextStatus[$order['status']] ?>">
                                <button type="submit" name="change_status"
                                        class="btn btn-sm fw-semibold <?= $order['status'] === 'New' ? 'btn-brand-green' : 'btn-outline-secondary' ?>"
                                        style="font-size:12px;"
                                        onclick="return confirm('Изменить статус заявки #<?= $order['id'] ?>?')">
                                    <?= $nextLabel[$order['status']] ?>
                                </button>
                            </form>
                            <?php else: ?>
                                <span style="font-size:12px; color:#6C757D;">Завершена</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-4 py-2 border-top" style="font-size:12px; color:#6C757D; background:#f8f9fa;">
            Показано заявок: <strong><?= count($orders) ?></strong>
        </div>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
