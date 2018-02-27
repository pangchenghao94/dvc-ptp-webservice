<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//Add user
// 1 = sucess
// 3 = unexpected error
$app->post('/api/assignment/add', function(Request $request, Response $response){
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
            $sql = "INSERT INTO `assignment` 
                    (`user_id`, `team`, `address`, `remark`, `date`, `postcode`) 
                    VALUES
                    (:user_id, :team, :address, :remark, :date, :postcode)";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $data->user_id, PDO::PARAM_INT);
            $stmt->bindParam(':team', $data->data->team, PDO::PARAM_INT);            
            $stmt->bindParam(':address', $data->data->address, PDO::PARAM_STR);
            $stmt->bindParam(':remark', $data->data->remark, PDO::PARAM_STR);
            $stmt->bindParam(':date', $data->data->date, PDO::PARAM_STR);
            $stmt->bindParam(':postcode', $data->data->postcode, PDO::PARAM_STR);
            
            $stmt->execute();

            $sql = "SELECT LAST_INSERT_ID() AS id";
            $stmt = $db->prepare($sql);
            $stmt->execute();        

            $assignment_id = $stmt->fetch(PDO::FETCH_OBJ);


            foreach($data->data2 as $row){
                $sql = "INSERT INTO `assignment_admin` (`user_id`, `assignment_id`) VALUES (:user_id, :assignment_id)";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':user_id', $row, PDO::PARAM_INT);
                $stmt->bindParam(':assignment_id', $assignment_id->id, PDO::PARAM_INT);
                $stmt->execute();   
            }

            echo '{ "status": "1",
                    "data"  : ' . json_encode($assignment_id) . ' }
            ';
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

//Get assignment list
$app->post('/api/assignment/assignmentList', function(Request $request, Response $response){
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
            $sql = "SELECT `assignment_id`, `address`, `team`, `postcode`, `date` FROM `assignment`";
            $stmt = $db->query($sql);
            $users = $stmt->fetchAll(PDO::FETCH_OBJ);
            echo json_encode($users);
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

//Get assignment from assignment_id
$app->post('/api/assignment/get/{id}', function(Request $request, Response $response){
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
            $sql = "SELECT * FROM `assignment` WHERE `assignment_id` = :assignment_id";
            
            $stmt = $db->prepare($sql);
            $assignment_id = $request->getAttribute('id');            
            $stmt->bindParam(':assignment_id', $assignment_id, PDO::PARAM_STR);
            $stmt->execute();

            $assignment = $stmt->fetch(PDO::FETCH_OBJ);
            echo '{ "status": "1",
                    "data"  : ' .json_encode($assignment). ' }
            ';
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
