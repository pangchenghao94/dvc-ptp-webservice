<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// //Get ind from ind_id
// $app->post('/api/report/get/{id}', function(Request $request, Response $response){
//     $db = new db();
//     $data = json_decode($request->getBody());
//     $token = $data->token;
//     $systemToken = apiToken($data->user_id);
//     $ind_id = $request->getAttribute('id');         
    
//     $s3 = $this->get('s3');
//     $bucketName = $this->get('bucketName');

//     if($token == $systemToken)
//     {
//         try{
//             //get DB object and connect
//             $db = $db->connect();
            
//             //select from ind table
//             $sql = "SELECT 
//                         `i`.`assignment_id`, `i`.`user_id`, `i`.`issue_date`, `i`.`area_inspection`, `i`.`p_cooperation`, `i`.`p_close`, `i`.`p_empty`, `i`.`p_shortAddr`, `i`.`po_name`, `i`.`po_id`, `i`.`no_familyMember`, 
//                         `i`.`no_fever`, `i`.`no_out_breeding`, `i`.`no_in_breeding`, `i`.`container_type`, `i`.`no_pot_out_breeding`, `i`.`no_pot_in_breeding`, `i`.`abating_amount`, 
//                         `i`.`abating_measure_type`, `i`.`act_destroy`, `i`.`act_education`, `i`.`act_pamphlet`, `i`.`coor_lat`, `i`.`coor_lng`, `u`.`full_name`
//                     FROM `ind` AS `i`
//                     INNER JOIN `user` AS `u` 
//                         ON `i`.`user_id` = `u`.`user_id` 
//                     WHERE `ind_id` = :ind_id";
            
//             $stmt = $db->prepare($sql);
//             $stmt->bindParam(':ind_id', $ind_id, PDO::PARAM_INT);
//             $stmt->execute();

//             $ind = $stmt->fetch(PDO::FETCH_OBJ);

//             //select from exhibit table
//             $sql = "SELECT `exhibit_id`, `issue_date`, `po_full_name`, `po_ic_no`, `acceptance`, `floor_plan_path`, `premise_location_path` 
//                     FROM `exhibit`
//                     WHERE `ind_id` = :ind_id";
            
//             $stmt1 = $db->prepare($sql);
//             $stmt1->bindParam(':ind_id', $ind_id, PDO::PARAM_INT);
//             $stmt1->execute();

//             $exhibit = $stmt1->fetch(PDO::FETCH_OBJ);

//             $exhibitItems = false;
//             if(isset($exhibit->exhibit_id)){

//                 //select from exhibitItem table
//                 $sql = "SELECT `exhibit_item_id`, `code`, `type`, `s3_path` 
//                         FROM `exhibit_item`
//                         WHERE `exhibit_id` = :exhibit_id AND `deleted_date` IS NULL";
                
//                 $stmt2 = $db->prepare($sql);
//                 $stmt2->bindParam(':exhibit_id', $exhibit->exhibit_id, PDO::PARAM_INT);
//                 $stmt2->execute();

//                 $exhibitItems = $stmt2->fetchAll(PDO::FETCH_OBJ);
//             }

//             //select from sek8 table
//             $sql = "SELECT 
//                         `sek8_id`, `checking_date`, `chkbx1`, `chkbx2`, `chkbx3`, `chkbx4`, `chkbx5`, `chkbx6`, `chkbx7`, `chkbx8`, `chkbx9`, `chkbx10`, 
//                         `chkbx11`, `chkbx12`, `chkbx13`, `remark` 
//                     FROM `sek8`
//                     WHERE `ind_id` = :ind_id";
            
//             $stmt3 = $db->prepare($sql);
//             $stmt3->bindParam(':ind_id', $ind_id, PDO::PARAM_INT);
//             $stmt3->execute();

//             $sek8 = $stmt3->fetch(PDO::FETCH_OBJ);           
            
//             //select from sek5 table
//             $sql = "SELECT `sek5_id`, `appointment_date`, `remark`, `last_modified` 
//                     FROM `sek5`
//                     WHERE `ind_id` = :ind_id";
            
//             $stmt4 = $db->prepare($sql);
//             $stmt4->bindParam(':ind_id', $ind_id, PDO::PARAM_INT);
//             $stmt4->execute();

//             $sek5 = $stmt4->fetch(PDO::FETCH_OBJ);  

//             return $response->withJson([
//                 'status' => '1',
//                 'ind_id' => $ind_id,
//                 'ind' => $ind,
//                 'exhibit' => $exhibit,
//                 'exhibitItems' => $exhibitItems,
//                 'sek8' => $sek8,
//                 'sek5' => $sek5
//             ])->withStatus(200);
//         }
//         catch(PDOException $e){
//             GenError::unexpectedError($e);
//         }
//         finally{ $db = null; }
//     }
//     else{
//         GenError::unauthorizedAccess();
//     }
// });

//Get ind list by user_id
$app->post('/api/dailyReport/get', function(Request $request, Response $response){
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
            $assignment_ids = implode(',', $data->assignment_ids);
            $sql = "SELECT
                        COUNT(*) AS `total`,
                        `ind_id`,
                        `assignment_id`,
                        SUM(CASE WHEN (`area_inspection` = 0 AND ((`p_cooperation` = 1 AND `p_empty` = 0 AND `p_close` = 0) OR (`p_empty` = 1))) THEN 1 ELSE 0 END ) AS `checked`,
                        SUM(CASE WHEN (`area_inspection` = 0 AND `p_close` = 1) THEN 1 ELSE 0 END) `close`,
                        SUM(CASE WHEN (`area_inspection` = 0 AND `p_empty` = 1) THEN 1 ELSE 0 END) `empty`,
                        SUM(CASE WHEN (`area_inspection` = 1) THEN 1 ELSE 0 END) `surrounding`,
                        SUM(CASE WHEN (`area_inspection` = 0) THEN 1 ELSE 0 END) `visited`,
                        SUM(CASE WHEN `area_inspection` = 0 THEN `no_in_breeding` ELSE 0 END) `premise_positive_compound`,
                        SUM(CASE WHEN `area_inspection` = 0 THEN `no_out_breeding` ELSE 0 END) `container_positive_compound`,
                        SUM(CASE WHEN `area_inspection` = 1 THEN `no_in_breeding` ELSE 0 END) `area_positive_surrounding`,
                        SUM(CASE WHEN `area_inspection` = 1 THEN `no_out_breeding` ELSE 0 END) `container_positive_surrounding`,
                        SUM(`no_out_breeding` + `no_in_breeding`) `total_breeding`,
                        SUM(`no_pot_out_breeding` + `no_pot_in_breeding`) `total_pot_breeding`,
                        SUM(CASE WHEN NOT(`abating_amount` = 0) THEN 1 ELSE 0 END) `total_abating`,                        
                        SUM(CASE WHEN (NOT(`abating_amount` = 0) AND `abating_measure_type` = 0) THEN `abating_amount` ELSE 0 END) `abating_amount_g`,
                        SUM(CASE WHEN (NOT(`abating_amount` = 0) AND `abating_measure_type` = 1) THEN `abating_amount` ELSE 0 END) `abating_amount_ml`,
                        SUM(CASE WHEN (`area_inspection` = 0) THEN `no_fever` ELSE 0 END) `ACD`,
                        SUM((SELECT EXISTS (SELECT * FROM `sek8` WHERE `ind`.`ind_id` = `sek8`.`ind_id`))) `s8`,
                        SUM((SELECT EXISTS (SELECT * FROM `sek5` WHERE `ind`.`ind_id` = `sek5`.`ind_id`))) `s5`
                    FROM `ind`
                    WHERE `assignment_id` in (";

            $comma = '';
            for($i = 0; $i < count($data->assignment_ids); $i++){
                $sql .= $comma . ':assignment_id_' . $i;
                $comma = ',';
            }

            $sql .=") GROUP BY `assignment_id`";

            $stmt = $db->prepare($sql);
            for($i = 0; $i < count($data->assignment_ids); $i++){
                $stmt->bindParam(':assignment_id_' . $i, $data->assignment_ids[$i]);
            }
            $stmt->execute();

            $report_data = $stmt->fetchAll(PDO::FETCH_OBJ);

            return $response->withJson([
                'status' => '1',
                'data' => $report_data,
            ])->withStatus(200);
        }
        catch(PDOException $e){
            GenError::unexpectedError($e);
        }
        finally{ $db = null; }
    }
    else{
        GenError::unauthorizedAccess();
    }
});

//Get ind list by user_id
$app->post('/api/dailyReport/getList', function(Request $request, Response $response){
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
            $sql = "SELECT `dr`.*, `u`.`full_name` AS `created_by`
                    FROM `daily_report` AS `dr`
                    INNER JOIN `user` AS `u`
                        ON `dr`.`user_id` = `u`.`user_id`
                    WHERE `dr`.`deleted_date` IS NULL
                    ORDER BY `dr`.`created_date` DESC";

            $stmt = $db->query($sql);
            $reports = $stmt->fetchAll(PDO::FETCH_OBJ);

            return $response->withJson([
                'status' => '1',
                'data' => $reports
            ])->withStatus(200);
        }
        catch(PDOException $e){
            GenError::unexpectedError($e);
        }
        finally{ $db = null; }
    }
    else{
        GenError::unauthorizedAccess();
    }
});
