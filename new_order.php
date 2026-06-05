<?php
// подключаем бд
    require 'db.php';
    session_start();

    // Проверяем вошел ли пользователь в систему
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $userId = (int)$_SESSION['user_id'];
    $errors = [];
    $ok = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conf    = $_POST['conf'] ?? '';
        $date    = $_POST['date_start'] ?? '';
        $payment = $_POST['payment'] ?? '';

        $validConf    = ['Lecture', 'Coworking', 'Cinema'];
        $validPayment = ['Cash', 'Card'];

        if (!in_array($conf, $validConf))
            $errors[] = 'Выберите тип помещения.';
        if (empty($date) || strtotime($date) < strtotime('today'))
            $errors[] = 'Укажите корректную дату (не ранее сегодня).';
        if (!in_array($payment, $validPayment))
            $errors[] = 'Выберите способ оплаты.';

        if (empty($errors)) {
            $pdo->prepare('INSERT INTO orders (user_id, conf, date_start, payment, status) VALUES (?, ?, ?, ?, "New")')
                ->execute([$userId, $conf, $date, $payment]);
            $ok = true;
        }
    }

    $minDate = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Новая заявка — Конференции.РФ</title>
    <link rel="stylesheet" href="bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar site-navbar shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="cabinet.php">КОНФЕРЕНЦИИ.РФ</a>
        <div class="d-flex align-items-center gap-3">
            <span class="navbar-text"><?= htmlspecialchars($_SESSION['fio']) ?></span>
            <a href="orders.php" class="btn btn-sm btn-outline-secondary text-white border-secondary">Кабинет</a>
            <a href="logout.php" class="btn btn-sm btn-outline-secondary text-white border-secondary">Выйти</a>
        </div>
    </div>
</nav>

<div class="container" style="max-width:680px; margin-top:48px; margin-bottom:60px;">

    <div class="mb-4">
        <h1>Новая заявка</h1>
        <p class="text-muted" style="font-size:14px;">Выберите помещение, дату и способ оплаты</p>
    </div>

    <?php if ($ok): ?>
        <div class="alert alert-success d-flex align-items-center gap-2">
            <span style="font-size:1.3rem;">✅</span>
            <div>
                <strong>Заявка отправлена!</strong> Администратор рассмотрит её в ближайшее время.
                <div class="mt-1"><a href="orders.php" class="fw-semibold text-success">← Вернуться в кабинет</a></div>
            </div>
        </div>
    <?php else: ?>

        <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <form method="post">

            <!-- Помещение -->
            <div class="card mb-4">
                <div class="card-header card-header-dark px-4 py-3">
                    <h3 class="mb-0" style="font-size:16px;font-weight:600;">1. Выберите помещение</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <?php
                        $rooms = [
                            'Lecture'   => ['icon' => '🎓', 'name' => 'Лекционный зал',  'desc' => 'Вместительный зал для лекций и семинаров'],
                            'Coworking' => ['icon' => '💼', 'name' => 'Коворкинг',        'desc' => 'Открытое пространство для совместной работы'],
                            'Cinema'    => ['icon' => '🎬', 'name' => 'Кинозал',          'desc' => 'Уютный зал с проектором для показов'],
                        ];
                        $selConf = $_POST['conf'] ?? '';
                        foreach ($rooms as $val => $room):
                            $checked = $selConf === $val ? 'selected' : '';
                        ?>
                        <div class="col-12 col-sm-4">
                            <label style="cursor:pointer;display:block;">
                                <input type="radio" name="conf" value="<?= $val ?>" class="d-none room-radio" <?= $checked ? 'checked' : '' ?> required>
                                <div class="card room-card text-center p-3 h-100 <?= $checked ?>">
                                    <div class="room-icon mb-2"><?= $room['icon'] ?></div>
                                    <h3 class="mb-1" style="font-size:15px;"><?= $room['name'] ?></h3>
                                    <p class="text-muted mb-0" style="font-size:12px;"><?= $room['desc'] ?></p>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Дата -->
            <div class="card mb-4">
                <div class="card-header card-header-dark px-4 py-3">
                    <h3 class="mb-0" style="font-size:16px;font-weight:600;">2. Дата мероприятия</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold">Дата начала</label>
                            <input type="date" name="date_start" class="form-control"
                                   min="<?= $minDate ?>"
                                   value="<?= htmlspecialchars($_POST['date_start'] ?? '') ?>"
                                   required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Оплата -->
            <div class="card mb-4">
                <div class="card-header card-header-dark px-4 py-3">
                    <h3 class="mb-0" style="font-size:16px;font-weight:600;">3. Способ оплаты</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <?php
                        $payments = ['Cash' => ['icon' => '💵', 'name' => 'Наличные'], 'Card' => ['icon' => '💳', 'name' => 'Банковская карта']];
                        $selPay = $_POST['payment'] ?? '';
                        foreach ($payments as $val => $p):
                        ?>
                        <div class="col-sm-12 col-sm-4">
                            <label style="cursor:pointer;display:block;">
                                <input type="radio" name="payment" value="<?= $val ?>" class="d-none pay-radio" <?= $selPay === $val ? 'checked' : '' ?> required>
                                <div class="card room-card text-center p-3 <?= $selPay === $val ? 'selected' : '' ?>">
                                    <div class="room-icon mb-2"><?= $p['icon'] ?></div>
                                    <h3 style="font-size:15px;"><?= $p['name'] ?></h3>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <button class="btn btn-brand-green w-100 fw-semibold py-2" style="font-size:16px;">
                Отправить заявку на согласование
            </button>
        </form>

    <?php endif; ?>
</div>

<script>
// Подсветка выбранных карточек
document.querySelectorAll('.room-radio, .pay-radio').forEach(radio => {
    radio.addEventListener('change', function() {
        const name = this.name;
        document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
            r.closest('label').querySelector('.room-card').classList.remove('selected');
        });
        this.closest('label').querySelector('.room-card').classList.add('selected');
    });
});
</script>
</body>
</html>
