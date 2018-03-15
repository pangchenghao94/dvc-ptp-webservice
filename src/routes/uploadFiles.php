<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$container = $app->getContainer();
$container['upload_directory'] = __DIR__ . '\uploads';

$app->post('/api/upload', function(Request $request, Response $response) {
    $directory = $this->get('upload_directory');
    
    $data = $request->getParam('fileName');
    $files = $request->getUploadedFiles();

    if (empty($files['file'])) {
        throw new \RuntimeException('Expected a newfile');
    }

    $file = $files['file'];

    if ($file->getError() === UPLOAD_ERR_OK) {
        $filename = moveUploadedFile($directory, $file);
        
        return $response->withJson([
            'status' => 'success',
            'result' => [
                'fileName' => $filename
            ],
        ])->withStatus(200);
    }
    else{
        return $response
            ->withJson([
                'status' => 'fail',
                'error' => 'Nothing was uploaded'
            ])
            ->withStatus(415);
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
