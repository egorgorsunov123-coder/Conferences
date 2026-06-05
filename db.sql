--Используем английские названия чтобы запрос сработал
CREATE TABLE users (
    id int AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL,
    password  VARCHAR(255) NOT NULL,
    fio VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(150),
    role ENUM('user','admin') NOT NULL DEFAULT 'user'
);

CREATE TABLE orders (
    id int AUTO_INCREMENT PRIMARY KEY,
    user_id int NOT NULL,
    conf ENUM('Lecture', 'Coworking', 'Cinema') NOT NULL,
    date_start DATE NOT NULL,
    payment ENUM('Cash', 'Card'),
    status ENUM('New', 'Scheduled', 'Ended') NOT NULL DEFAULT 'New',
    
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE review (
    id int AUTO_INCREMENT PRIMARY KEY,
    order_id int NOT NULL,
    grade VARCHAR(1) NOT NULL,
    text VARCHAR(255) NOT NULL,

    FOREIGN KEY (order_id) REFERENCES orders(id)
);
