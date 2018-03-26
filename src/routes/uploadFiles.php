<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Aws\S3\Exception\S3Exception;
use Slim\Http\UploadedFile;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Credentials\CredentialProvider;

//note to upload to EC2 instance
//1. do not forget to change \uploads to /uploads in $container['upload_directory'] = dirname( __DIR__, 2 ) . '\uploads'; 
//2. remove $provider for aws due to EC2 instance app uses IAM

$container = $app->getContainer();
$container['upload_directory'] = dirname( __DIR__, 2 ) . '\uploads'; 

$container['bucketName'] = 'ptp-dvc';
$provider = CredentialProvider::ini();
// Cache the results in a memoize function to avoid loading and parsing
// the ini file on every API operation.
$provider = CredentialProvider::memoize($provider);
$container['s3'] = new S3Client([
    'version' => 'latest',
    'region'  => 'ap-southeast-1',
    'credentials' => $provider
]);

function moveUploadedFile($directory, $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
    $filename = sprintf('%s.%0.8s', $basename, $extension);
    
    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
    return $filename;
}

function removeTemporaryFiles($directory){
    try{
        $files = glob($directory .DIRECTORY_SEPARATOR. "*"); // get all file names
        
        foreach($files as $file){ // iterate files
            if(is_file($file)){
                unlink($file); // delete file
            }
        }
    }
    catch(Exception $e){
        echo json_encode($e);
    }
}

$app->post('/api/upload/exhibit_item', function(Request $request, Response $response) {
    $db = new db();
    $directory = $this->get('upload_directory');
    $s3 = $this->get('s3');
    $bucketName = $this->get('bucketName');

    //extend execution time.
    ini_set('max_execution_time', 400);
    
    $user_id    = $request->getParam('user_id');
    $token      = $request->getParam('token'); 
    $exhibit_id =  $request->getParam('exhibit_id');    
    $systemToken= apiToken($user_id);
    
    if($token == $systemToken){
        $files = $request->getUploadedFiles();

        if (empty($files['file'])) {
            throw new \RuntimeException('Expected a newfile');
        }
    
        $file = $files['file'];
    
        if ($file->getError() === UPLOAD_ERR_OK) {
            $filename = moveUploadedFile($directory, $file);
            
            try{
                $s3->putObject([
                    'Bucket' => $bucketName,
                    'Key' => "exhibit/{$exhibit_id}/{$filename}",
                    'SourceFile' => $directory . DIRECTORY_SEPARATOR . $filename
                ]);

                try{
                    $code       =  $request->getParam('code');
                    $type       =  $request->getParam('type');
                    $s3_path    =  $filename;
                    
                    //get DB object and connect
                    $db = $db->connect();

                    //prepare state and execute     
                    $sql = "INSERT INTO `exhibit_item` 
                            (`exhibit_id`, `code`, `type`, `s3_path`) 
                            VALUES
                            (:exhibit_id, :code, :type, :s3_path)";

                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':exhibit_id', $exhibit_id, PDO::PARAM_INT);
                    $stmt->bindParam(':code', $code, PDO::PARAM_STR);            
                    $stmt->bindParam(':type', $type, PDO::PARAM_STR);
                    $stmt->bindParam(':s3_path', $s3_path, PDO::PARAM_STR);

                    $stmt->execute();      

                    return $response->withJson([
                        'status' => '1'
                    ])->withStatus(200);
                }
                catch(PDOException $e){
                    GenError::unexpectedError($e);
                }
                finally{ $db = null; }
            }
            
            catch(S3Exception $e){
                return $response
                ->withJson([
                    'status' => '0',
                    'error' => $e->getMessage()
                ])
                ->withStatus(415);
            }
        }
        else{
            return $response
                ->withJson([
                    'status' => '0',
                    'error' => 'Nothing was uploaded'
                ])
                ->withStatus(415);
        }
    }
    else{
        GenError::unauthorizedAccess();
    }
});

$app->post('/api/upload/premise_location_drawing', function(Request $request, Response $response) {
    $db = new db();
    $directory = $this->get('upload_directory');
    $s3 = $this->get('s3');
    $bucketName = $this->get('bucketName');
    
    $user_id    = $request->getParam('user_id');
    $token      = $request->getParam('token'); 
    $exhibit_id =  $request->getParam('exhibit_id');    
    $systemToken= apiToken($user_id);
    
    if($token == $systemToken){
        $files = $request->getUploadedFiles();

        if (empty($files['file'])) {
            throw new \RuntimeException('Expected a newfile');
        }
    
        $file = $files['file'];
    
        if ($file->getError() === UPLOAD_ERR_OK) {
            $filename = moveUploadedFile($directory, $file);
            
            try{
                $s3->putObject([
                    'Bucket' => $bucketName,
                    'Key' => "exhibit/{$exhibit_id}/{$filename}",
                    'SourceFile' => $directory . DIRECTORY_SEPARATOR . $filename
                ]);

                try{
                    $premise_location_path = $filename;
                    
                    //get DB object and connect
                    $db = $db->connect();

                    //prepare state and execute 
                    $sql = "UPDATE `exhibit` 
                            SET `premise_location_path` = :premise_location_path
                            WHERE `exhibit_id` = :exhibit_id";

                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':exhibit_id', $exhibit_id, PDO::PARAM_INT);
                    $stmt->bindParam(':premise_location_path', $premise_location_path, PDO::PARAM_STR);

                    $stmt->execute();      

                    return $response->withJson([
                        'status' => '1'
                    ])->withStatus(200);
                }
                catch(PDOException $e){
                    GenError::unexpectedError($e);
                }
                finally{ $db = null; }
            }
            
            catch(S3Exception $e){
                return $response
                ->withJson([
                    'status' => '0',
                    'error' => $e->getMessage()
                ])
                ->withStatus(415);
            }
        }
        else{
            return $response
                ->withJson([
                    'status' => '0',
                    'error' => 'Nothing was uploaded'
                ])
                ->withStatus(415);
        }
    }
    else{
        GenError::unauthorizedAccess();
    }
});

$app->post('/api/upload/floor_plan_drawing', function(Request $request, Response $response) {
    $db = new db();
    $directory = $this->get('upload_directory');
    $s3 = $this->get('s3');
    $bucketName = $this->get('bucketName');
    
    $user_id    = $request->getParam('user_id');
    $token      = $request->getParam('token'); 
    $exhibit_id =  $request->getParam('exhibit_id');    
    $systemToken= apiToken($user_id);
    
    if($token == $systemToken){
        $files = $request->getUploadedFiles();

        if (empty($files['file'])) {
            throw new \RuntimeException('Expected a newfile');
        }
    
        $file = $files['file'];
    
        if ($file->getError() === UPLOAD_ERR_OK) {
            $filename = moveUploadedFile($directory, $file);
            
            try{
                $s3->putObject([
                    'Bucket' => $bucketName,
                    'Key' => "exhibit/{$exhibit_id}/{$filename}",
                    'SourceFile' => $directory . DIRECTORY_SEPARATOR . $filename
                ]);

                try{
                    $floor_plan_path = $filename;
                    
                    //get DB object and connect
                    $db = $db->connect();

                    //prepare state and execute 
                    $sql = "UPDATE `exhibit` 
                            SET `floor_plan_path` = :floor_plan_path
                            WHERE `exhibit_id` = :exhibit_id";

                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':exhibit_id', $exhibit_id, PDO::PARAM_INT);
                    $stmt->bindParam(':floor_plan_path', $floor_plan_path, PDO::PARAM_STR);

                    $stmt->execute();      

                    return $response->withJson([
                        'status' => '1'
                    ])->withStatus(200);
                }
                catch(PDOException $e){
                    GenError::unexpectedError($e);
                }
                finally{ $db = null; }
            }
            
            catch(S3Exception $e){
                return $response
                ->withJson([
                    'status' => '0',
                    'error' => $e->getMessage()
                ])
                ->withStatus(415);
            }
        }
        else{
            return $response
                ->withJson([
                    'status' => '0',
                    'error' => 'Nothing was uploaded'
                ])
                ->withStatus(415);
        }
    }
    else{
        GenError::unauthorizedAccess();
    }
});