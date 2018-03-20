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
    $directory = $this->get('upload_directory');
    $s3 = $this->get('s3');
    $bucketName = $this->get('bucketName');
    
    $user_id = $request->getParam('user_id');
    $token = $request->getParam('token'); 
    $systemToken = apiToken($user_id);

    $data = $request->getParam('data');
    $filename   = $data->fileName;   
    $code       = $data->code;   
    $type       = $data->type;   
    $exhibit_id = $data->exhibit_id;   

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
    
                return $response->withJson([
                    'status' => 'success',
                    'result' => [
                        'fileName' => $filename,
                        'code' => $code,
                        'type' => $type
                    ],
                ])->withStatus(200);
            }
            
            catch(S3Exception $e){
                return $response
                ->withJson([
                    'status' => 'fail',
                    'error' => $e->getMessage()
                ])
                ->withStatus(415);
            }
        }
        else{
            return $response
                ->withJson([
                    'status' => 'fail',
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
