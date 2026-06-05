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
    <title>Вход</title>
    <link rel="stylesheet" href="bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container" style="max-width:380px; margin-top:60px;">
    <h1 class="h3 mb-4 text-center">Вход</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger py-1"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-2">
            <label class="form-label">Логин</label>
            <input name="login" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Пароль</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-success w-100">Войти</button>
    </form>

    <p class="mt-3 text-center">Еще не зарегистрированы? <a href="register.php">Регистрация</a></p>
</div>
</body>
</html>
