<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
require '../src/config/db.php';


$http_origin = $_SERVER['HTTP_ORIGIN'];

if ($http_origin == "http://localhost:4200" || $http_origin == "http://localhost:8100")
{  
    header("Access-Control-Allow-Origin: $http_origin");
}

header('Access-Control-Allow-Headers: content-type');

$app = new \Slim\App;
// $app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
//     $name = $args['name'];
//     $response->getBody()->write("Hello, $name");

//     return $response;
// });

// users routes
require '../src/routes/users.php';

$app->run();