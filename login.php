<?php 
    //Включаем нашу базу данных и запускаем сессию
    require 'db.php';
    session_start();
    $error = '';

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
            header('Location: Orders.php');  // перенаправляем
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
</head>
<body>
<nav class="navbar site-navbar shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="login.php">КОНФЕРЕНЦИИ.РФ</a>
    </div>
</nav>

<div class="container" style="max-width:420px; margin-top:60px;">
    <div class="text-center mb-4">
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
</div></body>
</html>
