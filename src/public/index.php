<?php
 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require("../../vendor/autoload.php");

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$config['db']['host']   = "localhost";
$config['db']['user']   = "root";
$config['db']['pass']   = "kopi";
$config['db']['dbname'] = "pesbuk";

$app = new \Slim\App(["settings" => $config]);

$container = $app->getContainer();

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'],
        $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

// middleware untuk atuentikasi api key
$app->add(function ($request, $response, $next) {
	
	$key = $request->getQueryParam("key");
	
	if($key == "123"){
		// $response->getBody()->write('BEFORE');
		return $response = $next($request, $response);
		// $response->getBody()->write('AFTER');
	}

	return $response->withJson(["status" => "unauthorized"], 401);

});

$app->get("/users[/]", function(Request $request, Response $response){
	$stmt = $this->db->prepare("SELECT * FROM users");
	$stmt->execute();
	$result = $stmt->fetchAll() ?: null;
	return $response->withJson(["status" => "success", "data" => $result], 200);
});

$app->get("/users/{id}", function(Request $request, Response $response, $args){
	$stmt = $this->db->prepare("SELECT * FROM users WHERE id=:id");
	$params = [":id" => $args["id"]];
	$stmt->execute($params);
	$result = $stmt->fetch() ?: null;
	return $response->withJson(["status" => "success", "data" => $result], 200);
});

$app->post("/users[/]", function(Request $request, Response $response){
	$user = $request->getParsedBody();
	$sql = "INSERT INTO users (name, username, email, password) VALUE (?, ?, ?, ?)";
	$stmt = $this->db->prepare($sql);
	$params = [
		$user["name"],
		$user["username"],
		$user["email"],
		password_hash($user["email"], PASSWORD_DEFAULT)
	];

	if($stmt->execute($params)){
		return $response->withJson(["status" => "success", "data" => "Tersimpan!"], 200);
	}
	return $response->withJson(["status" => "failed", "data" => "Terjadi masalah!"]);
});

$app->put("/users/{id}", function(Request $request, Response $response, $args){
	$input = $request->getParsedBody();
	$sql = "UPDATE users SET name=:name, email=:email WHERE id=:id";
	$stmt = $this->db->prepare($sql);
	$params = [
		":name" => $input["name"],
		":email" => $input["email"],
		":id" => $args["id"]
	];
	if($stmt->execute($params)){
		return $response->withJson(["status" => "success", "data" => "Sudah disimpan!"], 200);
	}
});

$app->delete("/users/{id}", function(Request $request, Response $response, $args){
	$stmt = $this->db->prepare("DELETE FROM users WHERE id=?");
	$params = [$args["id"]];
	if($stmt->execute($params)){
		return $response->withJson(["status" => "success", "data" => "Sudah Terhapus!"], 200);
	}
	return $response->withJson(["status" => "failed", "data" => "can not delete!"]);
});

$app->run();
