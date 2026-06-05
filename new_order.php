<?php
require 'db.php';
session_start();

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
    <style>
        /* ── КАРТОЧКИ ПОМЕЩЕНИЙ С ФОНОВЫМ ИЗОБРАЖЕНИЕМ ── */
        .room-card {
            position: relative;
            overflow: hidden;
            height: 160px;
            border: 2px solid #CED4DA !important;
            border-radius: 8px !important;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s, border-color .15s;
            padding: 0 !important;
        }
        .room-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(40,167,69,.2) !important;
            border-color: #28A745 !important;
        }
        .room-card.selected {
            border-color: #28A745 !important;
            box-shadow: 0 0 0 3px rgba(40,167,69,.25) !important;
        }

        /* Фоновое изображение */
        .room-card__bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            transition: transform .3s ease;
        }
        .room-card:hover .room-card__bg {
            transform: scale(1.06);
        }

        /* Тёмный градиент поверх картинки */
        .room-card__overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to top,
                rgba(0,0,0,.75) 0%,
                rgba(0,0,0,.35) 50%,
                rgba(0,0,0,.10) 100%
            );
            transition: background .2s;
        }
        .room-card.selected .room-card__overlay {
            background: linear-gradient(
                to top,
                rgba(20,80,30,.80) 0%,
                rgba(20,80,30,.40) 50%,
                rgba(20,80,30,.10) 100%
            );
        }

        /* Текст поверх */
        .room-card__body {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            padding: 14px 14px 12px;
            z-index: 2;
        }
        .room-card__name {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 2px;
            line-height: 1.2;
        }
        .room-card__desc {
            font-size: 11px;
            color: rgba(255,255,255,.75);
            margin: 0;
            line-height: 1.3;
        }

        /* Галочка выбора */
        .room-card__check {
            position: absolute;
            top: 10px; right: 10px;
            width: 24px; height: 24px;
            background: #28A745;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            opacity: 0;
            transform: scale(.7);
            transition: opacity .15s, transform .15s;
            z-index: 3;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
        }
        .room-card.selected .room-card__check {
            opacity: 1;
            transform: scale(1);
        }

        /* ── КАРТОЧКИ ОПЛАТЫ ── */
        .pay-card {
            border: 2px solid #CED4DA !important;
            border-radius: 8px;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s, border-color .15s;
            padding: 20px 16px;
            text-align: center;
            background: #fff;
        }
        .pay-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(40,167,69,.15) !important;
            border-color: #28A745 !important;
        }
        .pay-card.selected {
            border-color: #28A745 !important;
            background: #f0fff4;
            box-shadow: 0 0 0 3px rgba(40,167,69,.2) !important;
        }
        .pay-card .pay-icon { font-size: 2rem; margin-bottom: 6px; }
        .pay-card .pay-name { font-size: 14px; font-weight: 600; color: #343A40; margin: 0; }
    </style>
</head>
<body>

<nav class="navbar site-navbar shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="orders.php">КОНФЕРЕНЦИИ.РФ</a>
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

            <!-- ШАГ 1: Помещение -->
            <div class="card mb-4">
                <div class="card-header card-header-dark px-4 py-3">
                    <h3 class="mb-0" style="font-size:16px; font-weight:600;">1. Выберите помещение</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <?php
                        $rooms = [
                            'Lecture'   => [
                                'img'  => 'images/class.png',
                                'name' => 'Лекционный зал',
                                'desc' => 'Вместительный зал для лекций и семинаров',
                            ],
                            'Coworking' => [
                                'img'  => 'images/coworking.png',
                                'name' => 'Коворкинг',
                                'desc' => 'Открытое пространство для совместной работы',
                            ],
                            'Cinema'    => [
                                'img'  => 'images/cinema.png',
                                'name' => 'Кинозал',
                                'desc' => 'Уютный зал с проектором для показов',
                            ],
                        ];
                        $selConf = $_POST['conf'] ?? '';
                        foreach ($rooms as $val => $room):
                            $isSelected = $selConf === $val;
                        ?>
                        <div class="col-12 col-sm-4">
                            <label style="display:block; margin:0;">
                                <input type="radio" name="conf" value="<?= $val ?>"
                                       class="d-none room-radio"
                                       <?= $isSelected ? 'checked' : '' ?> required>
                                <div class="room-card <?= $isSelected ? 'selected' : '' ?>">
                                    <!-- Фон -->
                                    <div class="room-card__bg"
                                         style="background-image: url('<?= htmlspecialchars($room['img']) ?>');">
                                    </div>
                                    <!-- Затемнение -->
                                    <div class="room-card__overlay"></div>
                                    <!-- Галочка -->
                                    <div class="room-card__check">✓</div>
                                    <!-- Текст -->
                                    <div class="room-card__body">
                                        <div class="room-card__name"><?= htmlspecialchars($room['name']) ?></div>
                                        <p class="room-card__desc"><?= htmlspecialchars($room['desc']) ?></p>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ШАГ 2: Дата -->
            <div class="card mb-4">
                <div class="card-header card-header-dark px-4 py-3">
                    <h3 class="mb-0" style="font-size:16px; font-weight:600;">2. Дата мероприятия</h3>
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

            <!-- ШАГ 3: Оплата -->
            <div class="card mb-4">
                <div class="card-header card-header-dark px-4 py-3">
                    <h3 class="mb-0" style="font-size:16px; font-weight:600;">3. Способ оплаты</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <?php
                        $payments = [
                            'Cash' => ['icon' => '💵', 'name' => 'Наличные'],
                            'Card' => ['icon' => '💳', 'name' => 'Банковская карта'],
                        ];
                        $selPay = $_POST['payment'] ?? '';
                        foreach ($payments as $val => $p):
                        ?>
                        <div class="col-12 col-sm-4">
                            <label style="display:block; margin:0;">
                                <input type="radio" name="payment" value="<?= $val ?>"
                                       class="d-none pay-radio"
                                       <?= $selPay === $val ? 'checked' : '' ?> required>
                                <div class="pay-card <?= $selPay === $val ? 'selected' : '' ?>">
                                    <div class="pay-icon"><?= $p['icon'] ?></div>
                                    <p class="pay-name"><?= $p['name'] ?></p>
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
document.querySelectorAll('.room-radio, .pay-radio').forEach(radio => {
    radio.addEventListener('change', function () {
        const name = this.name;
        const cardClass = name === 'conf' ? '.room-card' : '.pay-card';
        document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
            r.closest('label').querySelector(cardClass).classList.remove('selected');
        });
        this.closest('label').querySelector(cardClass).classList.add('selected');
    });
});
</script>
</body>
</html>
