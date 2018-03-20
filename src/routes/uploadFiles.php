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

$app->post('/api/upload', function(Request $request, Response $response) {
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

function moveUploadedFile($directory, $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
    $filename = sprintf('%s.%0.8s', $basename, $extension);
    
    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
    return $filename;
}
