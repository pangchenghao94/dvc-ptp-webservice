<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// 1 = sucess
// 2 = password mismatch
// 3 = unexpected error
$app->post('/api/login',function(Request $request, Response $response){
    $data = json_decode($request->getBody());
    $sql = "SELECT `user_id`, `usertype`, `username`, `state` FROM `user` WHERE `username`=:username AND `password`=:password";
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
            echo '{ "status": "1",
                    "data"  : ' .$userData . ' }
                ';
        } 
        
        else {
            echo '{ "status"    : "2",
                    "message"   : "Bad request wrong username and password" }
                ';
        }  
    }
    catch(PDOException $e) {
        echo '{ "status"    : "3",
                "message"   : '. $e->getMessage() .' }
        ';
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
$app->post('/api/userlist', function(Request $request, Response $response){
    $db = new db();
    $data = json_decode($request->getBody());
    $token = $data->token;
    $systemToken = apiToken($data->user_id);

    if($token == $systemToken)
    {
        try{
            //get DB object and connect
            $db = $db->connect();
            
            //execute statement
            $sql = "SELECT `user_id`, `full_name`, `usertype`, `phone_no`, `state` FROM `user` WHERE `user_id` != :user_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $data->user_id, PDO::PARAM_INT);            
            $stmt->execute();

            $users = $stmt->fetchAll(PDO::FETCH_OBJ);
            echo '{ "status": "1",
                    "data"  : ' .json_encode($users). ' }
        ';
        }
        catch(PDOException $e){
            echo '{"error" : { "text": '.$e->getMessage().' }}';
        }
        finally{ $db = null; }
    }
    else{
        echo '{ "status"    : "0",
                "message"   : "Unauthorized access!" }
        ';
    }
});

//Get user by id
$app->post('/api/user/get/{id}', function(Request $request, Response $response){
    $db = new db();
    $data = json_decode($request->getBody());
    $token = $data->token;
    $systemToken = apiToken($data->user_id);

    if($token == $systemToken)
    {
        try{
            //get DB object and connect
            $db = $db->connect();

            //prepare state and execute
            $id = $request->getAttribute('id');
            $sql = "SELECT `username`, `state`, `full_name`, `phone_no`, `email`, `gender`, `usertype` FROM `user` WHERE `user_id` = :id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_OBJ);
            echo '{ "status": "1",
                    "data"  : ' .json_encode($user). ' }
            ';
        }
        catch(PDOException $e){
            echo '{"error" : { "text": '.$e->getMessage().' }}';
        }
        finally{ $db = null; }
    }
    else{
        echo '{ "status"    : "0",
                "message"   : "Unauthorized access!" }
        ';
    }
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
// 1 = sucess
// 2 = password mismatch
// 3 = unexpected error
$app->post('/api/user/add', function(Request $request, Response $response){
    $db = new db();    
    $data = json_decode($request->getBody());
    $token = $data->token;
    $systemToken = apiToken($data->user_id);
    
    if($token == $systemToken)
    {
        try{
            //get DB object and connect
            $db = $db->connect();

            //prepare state and execute     
            $sql = "INSERT INTO user 
                    (`username`, `password`, `full_name`, `phone_no`, `email`, `gender`, `usertype`) 
                    VALUES
                    (:username, :password, :full_name, :phone_no, :email, :gender, :usertype)";

            $stmt = $db->prepare($sql);
            $pass = md5($data->data->password);
            $stmt->bindParam(':username', $data->data->username, PDO::PARAM_STR);
            $stmt->bindParam(':password', $pass, PDO::PARAM_STR);
            $stmt->bindParam(':full_name', $data->data->full_name, PDO::PARAM_STR);
            $stmt->bindParam(':phone_no', $data->data->phone_no, PDO::PARAM_STR);
            $stmt->bindParam(':email', $data->data->email, PDO::PARAM_STR);
            $stmt->bindParam(':gender', $data->data->gender, PDO::PARAM_INT);
            $stmt->bindParam(':usertype', $data->data->usertype, PDO::PARAM_INT);
            
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
    }
    else{
        echo '{ "status"    : "0",
                "message"   : "Unauthorized access!" }
        ';
    }
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

//Get list of active user_id and fullName
//module: Manage PDK
//status => 1=success, 0=fail
$app->get('/api/fullNameList', function(Request $request, Response $response){
    $db = new db();                
    try{
        //get DB object and connect
        $db = $db->connect();
        //execute statement
        $sql = "SELECT `user_id`, `full_name` FROM `user` WHERE (`state`=1) AND (`usertype`=0 OR `usertype`=4 OR `usertype`=1)";
        $stmt = $db->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_OBJ);
        $users = json_encode($users);

        echo '{ "status": "1",
                "data"  : ' .$users . ' }
        ';
    }
    catch(PDOException $e){
        echo '{ "status"    : "0",
                "message"   : '. $e->getMessage() .' }
        ';
    }
    finally{ $db = null; }
});

//Get user list
$app->post('/api/user/changePassword', function(Request $request, Response $response){
    $db = new db();
    $data = json_decode($request->getBody());
    $token = $data->token;
    $systemToken = apiToken($data->user_id);

    if($token == $systemToken)
    {
        try{
            //get DB object and connect
            $db = $db->connect();
            //execute statement
            $sql = "SELECT `password` FROM `user` WHERE `user_id` = :user_id";
            $stmt = $db->prepare($sql);

            $password = md5($data->data->oldPass); 
            $stmt->bindParam(':user_id', $data->user_id, PDO::PARAM_STR);
            $stmt->execute();
            $db_pass = $stmt->fetch(PDO::FETCH_OBJ);
            
            if(!empty($db_pass)){
                if($db_pass->password == $password){
                    $sql = "UPDATE `user` SET `password` = :password WHERE `user_id` = :user_id";
                    $stmt = $db->prepare($sql);   
                    $newPass = md5($data->data->newPassRepeat);
                    $stmt->bindParam(':user_id', $data->user_id, PDO::PARAM_STR);
                    $stmt->bindParam(':password', $newPass, PDO::PARAM_STR);                                    
                    $stmt->execute();

                    echo '{ "status"    : "1",
                            "message"   : "Update Successfully" }
                    ';
                }
                else{
                    echo '{ "status"    : "2",
                            "message"   : "Bad request, you have entered the wrong existing password" }
                    ';
                }
            }
        }
        catch(PDOException $e){
            echo '{"error":{"text": '.$e->getMessage().'}}';
        }
        finally{ $db = null; }
    }
    else{
        echo '{ "status"    : "0",
                "message"   : "Unauthorized access!" }
        ';
    }
});

