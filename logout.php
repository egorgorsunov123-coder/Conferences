<?php 
    // ВЫХОД: уничтожаем сессию и возвращаем на вход
    session_start();
    session_destroy();
    header('Location: login.php');
    exit;
?>