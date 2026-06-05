<?php 
    //Включаем нашу базу данных и запускаем сессию
    require 'db.php';
    
    $error = '';

    //Проверяем не активна ли сейчас сессия
    if (!empty($_SESSION['user_id'])) {
        header('Location: orders.php');
        exit;
    }
    else{
        session_start();
    }

    // Проверям сервер на отправку данных
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login = trim($_POST['login'] ?? '');
        $pass  = $_POST['password'] ?? '';

        // Ищем пользователя по логину
        $stmt = $pdo->prepare('SELECT * FROM users WHERE login = ?');
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        // password_verify сравнивает введённый пароль с хэшем из БД
        if ($user && password_verify($pass, $user['password'])) {
            // Сохраняем в сессию — теперь на других страницах знаем, кто вошёл
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fio'] = $user['fio'];
            $_SESSION['role'] = $user['role'];
            header('Location: ' . ($user['role'] === 'admin' ? 'admin.php' : 'orders.php')); // перенаправляем
            exit;
    } else {
        $error = 'Неверный логин или пароль.';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход-Конференции.РФ</title>
    <link rel="stylesheet" href="bootstrap.min.css">
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

<div class="container" style="max-width:420px; margin-top:60px;">
    <div class="text-center">
        <h1>Вход</h1>
        <p class="text-muted" style="font-size:14px;">Войдите в личный кабинет</p>
    </div>

    <div class="card p-4">
        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label fw-semibold">Логин</label>
                <input name="login" class="form-control" placeholder="Введите логин" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Пароль</label>
                <input type="password" name="password" class="form-control" placeholder="Введите пароль" required>
            </div>
            <button class="btn btn-brand-green w-100 fw-semibold">Войти</button>
        </form>
    </div>

    <p class="text-center mt-3" style="font-size:13px; color:#6C757D;">
        Ещё не зарегистрированы? <a href="register.php" class="text-success fw-semibold text-decoration-none">Регистрация</a>
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
