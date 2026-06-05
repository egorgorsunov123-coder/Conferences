<?php
require 'db.php';
$errors = [];
$ok = false;

// $_SERVER['REQUEST_METHOD'] == 'POST' значит форму отправили
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    if (mb_strlen($pass) < 8) {
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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Регистрация</title>
    <link rel="stylesheet" href="bootstrap.min.css"> <!-- Bootstrap -->
</head>
<body>
<div class="container" style="max-width:420px; margin-top:40px;">
    <h1 class="h3 mb-4 text-center">Регистрация</h1>

    <?php if ($ok): ?>
        <div class="alert alert-success">Готово! Теперь <a href="login.php">войдите</a>.</div>
    <?php else: ?>
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger py-1"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <form method="post">
            <div class="mb-2">
                <label class="form-label">Логин</label>
                <input name="login" class="form-control" required>
            </div>
            <div class="mb-2">
                <label class="form-label">Пароль</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-2">
                <label class="form-label">ФИО</label>
                <input name="fio" class="form-control" required>
            </div>
            <div class="mb-2">
                <label class="form-label">Телефон</label>
                <input name="phone" class="form-control" placeholder="8(999)123-45-67" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <button class="btn btn-success w-100">Создать пользователя</button>
        </form>
        <p class="mt-3 text-center">Уже зарегистрированы? <a href="login.php">Вход</a></p>
    <?php endif; ?>
</div>
</body>
</html>
