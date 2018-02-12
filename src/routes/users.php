<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App(['settings' => ['displayErrorDetails' => true]]);

$app->post('/api/login',function(Request $request, Response $response){
    $data = json_decode($request->getBody());
    $sql = "SELECT `user_id`, `usertype`, `username` FROM `user` WHERE `username`=:username AND `password`=:password";
    $db = new db(); 
    try {
        //get DB object and connect
        $db = $db->connect();

        $userData ='';
        $stmt = $db->prepare($sql);
        $password = md5($data->password);   
    
        $stmt->bindParam("username", $data->username, PDO::PARAM_STR);
        $stmt->bindParam("password", $password, PDO::PARAM_STR);
        $stmt->execute();

        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        
        if(!empty($userData)){
            $user_id=$userData->user_id;
            $userData->token = apiToken($user_id);
        }
        
        if($userData){
            $userData = json_encode($userData);
            echo '{"userData": ' .$userData . '}';
        } 
        
        else {
            echo '{"error":{"text":"Bad request wrong username and password"}}';
        }  
    }
    catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
    finally{ $db = null; }
});

//Get all users
$app->get('/api/users', function(Request $request, Response $response){
    $db = new db();                
    try{
        //get DB object and connect
        $db = $db->connect();
        //execute statement
        $sql = "SELECT * FROM `user`";
        $stmt = $db->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode($users);
    }
    catch(PDOException $e){
        echo '{"error":{"text": '.$e->getMessage().'}}';
    }
    finally{ $db = null; }
});

//Get user list
$app->get('/api/userlist', function(Request $request, Response $response){
    $db = new db();                
    try{
        //get DB object and connect
        $db = $db->connect();
        //execute statement
        $sql = "SELECT `user_id`, `full_name`, `usertype`, `phone_no`, `state` FROM `user`";
        $stmt = $db->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode($users);
    }
    catch(PDOException $e){
        echo '{"error":{"text": '.$e->getMessage().'}}';
    }
    finally{ $db = null; }
});

//Get user by id
$app->get('/api/user/{id}', function(Request $request, Response $response){
    $db = new db();    
    try{
        //get DB object and connect
        $db = $db->connect();

        //prepare state and execute
        $id = $request->getAttribute('id');
        $sql = "SELECT * FROM `user` WHERE `user_id` = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $users = $stmt->fetch(PDO::FETCH_OBJ);
        echo json_encode($users);
    }
    catch(PDOException $e){
        echo '{"error":{"text": '.$e->getMessage().'}}';
    }
    finally{ $db = null; }
});


//check if the username is unique
$app->post('/api/checkUniqueUsername', function(Request $request, Response $response){
    $db = new db();   
    $data = json_decode($request->getBody());
     
    try{
        //get DB object and connect
        $db = $db->connect();

        //prepare state and execute
        $sql = "SELECT `username` FROM `user` WHERE `username` = :username";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':username', $data->username, PDO::PARAM_STR);
        $stmt->execute();

        $users = $stmt->fetch(PDO::FETCH_OBJ);
        echo json_encode($users);
    }
    catch(PDOException $e){
        echo '{"error":{"text": '.$e->getMessage().'}}';
    }
    finally{ $db = null; }
});

//Add user
$app->post('/api/user/add', function(Request $request, Response $response){
    $db = new db();    
    $data = json_decode($request->getBody());
    
    try{
        //get DB object and connect
        $db = $db->connect();

        //prepare state and execute     
        $sql = "INSERT INTO user 
                (`username`, `password`, `full_name`, `phone_no`, `email`, `gender`, `usertype`) 
                VALUES
                (:username, :password, :full_name, :phone_no, :email, :gender, :usertype)";

        $stmt = $db->prepare($sql);
        $pass = md5($data->password);
        $stmt->bindParam(':username', $data->username, PDO::PARAM_STR);
        $stmt->bindParam(':password', $pass, PDO::PARAM_STR);
        $stmt->bindParam(':full_name', $data->full_name, PDO::PARAM_STR);
        $stmt->bindParam(':phone_no', $data->phone_no, PDO::PARAM_STR);
        $stmt->bindParam(':email', $data->email, PDO::PARAM_STR);
        $stmt->bindParam(':gender', $data->gender, PDO::PARAM_INT);
        $stmt->bindParam(':usertype', $data->usertype, PDO::PARAM_INT);
        
        $stmt->execute();

        $sql = "SELECT LAST_INSERT_ID() AS id";
        $stmt = $db->prepare($sql);
        $stmt->execute();        

        $users = $stmt->fetch(PDO::FETCH_OBJ);
        echo json_encode($users);
    }
    catch(PDOException $e){
        echo '{ "error": {"text": '.$e->getMessage().'}}';
    }
    finally{ $db = null; }
});

//Update user by id
$app->post('/api/user/update/{id}', function(Request $request, Response $response){
    $db = new db();    
    $data = json_decode($request->getBody());

    try{
        //get DB object and connect
        $db = $db->connect();

        //prepare state and execute       .
        $id = $request->getAttribute('id');    
        $username = $request->getParam('username');
        $password = $request->getParam('password');
        $state = $request->getParam('state');
        $full_name = $request->getParam('full_name');
        $phone_no = $request->getParam('phone_no');
        $email = $request->getParam('email');
        $gender = $request->getParam('gender');
        $usertype = $request->getParam('usertype');

        $sql = "UPDATE `user` SET "
                    .($username === null? "": "`username` = :username,")
                    .($password === null? "": "`password` = :password,")
                    .($state === null? "": "`state` = :state,")
                    .($full_name === null? "": "`full_name` = :full_name,")
                    .($phone_no === null? "": "`phone_no` = :phone_no,")
                    .($email === null? "": "`email` = :email,")
                    .($gender === null? "": "`gender` = :gender,")
                    .($usertype === null? "": "`usertype` = :usertype,").                
                " WHERE `user_id` = :id";
        $pos = strrpos($sql,",");
        $sql = substr($sql, 0, $pos) . substr($sql, $pos + 1);

        $stmt = $db->prepare($sql);
        $password = $password === null? null: md5($password);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        $username   === null? : $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $password   === null? : $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        $state      === null? : $stmt->bindParam(':state', $state, PDO::PARAM_INT);
        $full_name  === null? : $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
        $phone_no   === null? : $stmt->bindParam(':phone_no', $phone_no, PDO::PARAM_STR);
        $email      === null? : $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $gender     === null? : $stmt->bindParam(':gender', $gender, PDO::PARAM_INT);
        $usertype   === null? : $stmt->bindParam(':usertype', $usertype, PDO::PARAM_INT);

        $stmt->execute();

        echo '{ "message": {"text":"User updated"}}';
    }
    catch(PDOException $e){
        echo '{ "error": {"text": '.$e->getMessage().'}}';
    }
    finally{ $db = null; }
});

//Deactivate user
$app->get('/api/user/deactivate/{id}', function(Request $request, Response $response){
    $db = new db();    

    try{
        //get DB object and connect
        $db = $db->connect();

        //prepare state and execute       .
        $id = $request->getAttribute('id');    
        $sql = "UPDATE `user` SET `state`=0 WHERE `user_id` = :id";
        $stmt = $db->prepare($sql);        
        $stmt->bindParam(':id', $id);        
        $stmt->execute();

        echo '{ "message": {"text":"User deactivated"}}';
    }
    catch(PDOException $e){
        echo '{ "error": {"text": '.$e->getMessage().'}}';
    }
    finally{ $db = null; }
});

//Deactivate user
$app->get('/api/user/activate/{id}', function(Request $request, Response $response){
    $db = new db();    

    try{
        //get DB object and connect
        $db = $db->connect();

        //prepare state and execute       .
        $id = $request->getAttribute('id');    
        $sql = "UPDATE `user` SET `state`=1 WHERE `user_id` = :id";
        $stmt = $db->prepare($sql);        
        $stmt->bindParam(':id', $id);        
        $stmt->execute();

        echo '{ "message": {"text":"User activated"}}';
    }
    catch(PDOException $e){
        echo '{ "error": {"text": '.$e->getMessage().'}}';
    }
    finally{ $db = null; }
});