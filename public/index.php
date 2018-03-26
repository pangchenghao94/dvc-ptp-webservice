<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Slim\Http\UploadedFile;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Credentials\CredentialProvider;

require '../vendor/autoload.php';
require '../src/config/db.php';

// $http_origin = $_SERVER['HTTP_REFERER'];

// if ($http_origin == "http://localhost:4200" || $http_origin == "http://localhost:8100")
// {  
//     header("Access-Control-Allow-Origin: $http_origin");
// }

//allow all access
header("Access-Control-Allow-Origin:*");
header('Access-Control-Allow-Headers: content-type');

$app = new \Slim\App(['settings' => ['displayErrorDetails' => true]]);

//general functions
require '../src/general/general.php';

// users routes
require '../src/routes/users.php';
require '../src/routes/assignments.php';
require '../src/routes/uploadFiles.php';
require '../src/routes/exhibits.php';
require '../src/routes/inds.php';

$app->run();