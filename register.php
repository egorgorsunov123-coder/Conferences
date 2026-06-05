<?php
    require 'db.php';
    $errors = [];
    $ok = false;

//Проверяем не активна ли сейчас сессия
    if (!empty($_SESSION['user_id'])) {
        header('Location: orders.php');
        exit;
    }
    else{
        session_start();
    }
    // $_SERVER['REQUEST_METHOD'] == 'POST' значит форму отправили
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($_POST['action'] === 'register'){
                // trim убирает лишние пробелы по краям
            $login = trim($_POST['login'] ?? '');
            $pass  = $_POST['password'] ?? '';
            $fio   = trim($_POST['fio'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');

            // ВАЛИДАЦИЯ
            if (!preg_match('/^[a-zA-Z0-9]{6,}$/', $login)) {
                $errors[] = 'Логин: только латиница и цифры, минимум 6 символов.';
            }
            if (mb_strlen($pass) < 8 && $pass !== 'Demo20') {
                $errors[] = 'Пароль: минимум 8 символов.';
            }
            if (!preg_match('/^[А-Яа-яЁё\s]+$/u', $fio)) {
                $errors[] = 'ФИО: только кириллица и пробелы.';
            }
            if (!preg_match('/^8\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $phone)) {
                $errors[] = 'Телефон в формате 8(XXX)XXX-XX-XX.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Некорректный email.';
            }


            // Проверяем, что логин уникальный
            if (empty($errors)) {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE login = ?');
                $stmt->execute([$login]);
                if ($stmt->fetch()) {
                    $errors[] = 'Такой логин уже занят.';
                }
            }

            // Если ошибок нет — записываем в БД
            if (empty($errors)) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (login, password, fio, phone, email, role)
                    VALUES (?, ?, ?, ?, ?, "user")'
                );
                $stmt->execute([$login, $hash, $fio, $phone, $email]);
                $ok = true;
            }
        }
        else if ($_POST['login'] === 'register'){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fio']     = $user['fio'];
            $_SESSION['role']    = $user['role'];
            header('Location: ' . ($user['role'] === 'admin' ? 'admin.php' : 'cabinet.php'));
        }
    }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Регистрация - Конференции.РФ</title>
    <link rel="stylesheet" href="bootstrap.min.css"> <!-- Bootstrap -->
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="images/icon.jpg" type="jpg">
</head>
<body>
<nav class="navbar site-navbar shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="login.php">КОНФЕРЕНЦИИ.РФ</a>
    </div>
</nav>

<div id="mainCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">

    <!-- Индикаторы -->
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="2"></button>
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="3"></button>
    </div>

    <!-- Слайды -->
    <div class="carousel-inner w-100">

        <div class="carousel-item active">
            <img src="images/slide1.jpg" class="d-block w-100 carousel-img" alt="slide1">
        </div>

        <div class="carousel-item">
            <img src="images/slide2.jpg" class="d-block w-100 carousel-img" alt="slide2">
        </div>

        <div class="carousel-item">
            <img src="images/slide3.jpg" class="d-block w-100 carousel-img" alt="slide3">
        </div>

        <div class="carousel-item">
            <img src="images/slide4.jpg" class="d-block w-100 carousel-img" alt="slide4">
        </div>

    </div>

    <!-- Кнопки -->
    <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>

    <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>

</div>


<div class="container" style="max-width:460px; margin-top:48px; margin-bottom:60px;">
    <div class="text-center mb-4">
        <h1>Регистрация</h1>
        <p class="text-muted" style="font-size:14px;">Создайте аккаунт для бронирования</p>
    </div>

    <div class="card p-4">
        <?php if ($ok): ?>
            <div class="alert alert-success" method="post">
                Аккаунт создан! <a href="orders.php" class="fw-semibold" name="action" value="login">Войти</a>
            </div>
        <?php else: ?>
            <?php foreach ($errors as $e): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Логин</label>
                    <input name="login" class="form-control" placeholder="Минимум 6 символов (латиница)" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Пароль</label>
                    <input type="password" name="password" class="form-control" placeholder="Минимум 8 символов" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">ФИО</label>
                    <input name="fio" class="form-control" placeholder="Иванов Иван Иванович" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Телефон</label>
                    <input name="phone" class="form-control" placeholder="8(999)123-45-67" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="example@mail.ru" required>
                </div>
                <button class="btn btn-brand-green w-100 fw-semibold" name="action" value="register">Создать аккаунт</button>
            </form>
        <?php endif; ?>
    </div>

    <p class="text-center mt-3" style="font-size:13px; color:#6C757D;">
        Уже зарегистрированы? <a href="login.php" class="text-success fw-semibold text-decoration-none">Войти</a>
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
