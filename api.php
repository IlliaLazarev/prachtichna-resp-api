<?php
header("Content-Type: application/json; charset=UTF-8");

$file = "users.json";

if (!file_exists($file)) {
    file_put_contents($file, "[]");
}

$users = json_decode(file_get_contents($file), true);

$method = $_SERVER["REQUEST_METHOD"];
$uri = trim($_SERVER["REQUEST_URI"], "/");
$parts = explode("/", $uri);

$id = $parts[1] ?? null;

function saveUsers($file, $users) {
    file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($parts[0] !== "users") {
    response(["error" => "Невірний шлях"], 404);
}

if ($method === "GET") {
    if ($id === null) {
        response($users);
    }

    foreach ($users as $user) {
        if ($user["id"] == $id) {
            response($user);
        }
    }

    response(["error" => "Користувача не знайдено"], 404);
}

if ($method === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data["name"]) || empty($data["email"])) {
        response(["error" => "Потрібно вказати name та email"], 400);
    }

    $newUser = [
        "id" => count($users) > 0 ? max(array_column($users, "id")) + 1 : 1,
        "name" => $data["name"],
        "email" => $data["email"]
    ];

    $users[] = $newUser;
    saveUsers($file, $users);

    response($newUser, 201);
}

if ($method === "PUT") {
    if ($id === null) {
        response(["error" => "Потрібно вказати ID"], 400);
    }

    $data = json_decode(file_get_contents("php://input"), true);

    foreach ($users as &$user) {
        if ($user["id"] == $id) {
            if (!empty($data["name"])) {
                $user["name"] = $data["name"];
            }

            if (!empty($data["email"])) {
                $user["email"] = $data["email"];
            }

            saveUsers($file, $users);
            response($user);
        }
    }

    response(["error" => "Користувача не знайдено"], 404);
}

if ($method === "DELETE") {
    if ($id === null) {
        response(["error" => "Потрібно вказати ID"], 400);
    }

    foreach ($users as $index => $user) {
        if ($user["id"] == $id) {
            unset($users[$index]);
            $users = array_values($users);

            saveUsers($file, $users);
            response(["message" => "Користувача видалено"]);
        }
    }

    response(["error" => "Користувача не знайдено"], 404);
}

response(["error" => "Метод не підтримується"], 405);
?>
