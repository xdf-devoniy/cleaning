<?php

function db_path(): string {
    $path = __DIR__ . '/../storage/database.sqlite';
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }
    return $path;
}

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . db_path());
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}

function query(string $sql, array $params = []): PDOStatement {
    $stmt = get_db()->prepare($sql);
    foreach ($params as $key => $value) {
        $paramKey = is_int($key) ? $key + 1 : $key;
        $stmt->bindValue($paramKey, $value);
    }
    $stmt->execute();
    return $stmt;
}

function fetch_all(string $sql, array $params = []): array {
    return query($sql, $params)->fetchAll();
}

function fetch_one(string $sql, array $params = []): ?array {
    $stmt = query($sql, $params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function execute(string $sql, array $params = []): bool {
    return query($sql, $params)->rowCount() >= 0;
}

