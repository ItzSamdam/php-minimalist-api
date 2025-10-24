<?php

$migration = [
    'up' => "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            is_deleted TINYINT(1) DEFAULT 0,
            deleted_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        
        CREATE INDEX idx_users_deleted ON users (is_deleted);
        CREATE INDEX idx_users_email ON users (email);
    ",
    'down' => "DROP TABLE users;"
];
