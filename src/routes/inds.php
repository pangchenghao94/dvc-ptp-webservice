<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->post('/api/ind/add', function(Request $request, Response $response){
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
            $sql = "INSERT INTO `ind` 
                        (`assignment_id`, `user_id`, `p_cooperation`, `p_close`, `p_empty`, `p_shortAddr`, `po_name`, `po_id`, `no_familyMember`, `no_fever`, `no_out_breeding`, 
                        `no_in_breeding`, `container_type`, `no_pot_out_breeding`, `no_pot_in_breeding`, `act_abating`, `act_destroy`, `act_education`, `act_pamphlet`, `issue_date`) 
                    VALUES 
                        (:assignment_id, :user_id, :p_cooperation, :p_close, :p_empty, :p_shortAddr, :po_name, :po_id, :no_familyMember, :no_fever, :no_out_breeding, 
                        :no_in_breeding, :container_type, :no_pot_out_breeding, :no_pot_in_breeding, :act_abating, :act_destroy, :act_education, :act_pamphlet, CURDATE())";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':assignment_id', $data->data->assignment_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $data->user_id, PDO::PARAM_INT);            
            $stmt->bindParam(':p_cooperation', $data->data->p_cooperation, PDO::PARAM_INT);
            $stmt->bindParam(':p_close', $data->data->p_close, PDO::PARAM_INT);
            $stmt->bindParam(':p_empty', $data->data->p_empty, PDO::PARAM_INT);
            $stmt->bindParam(':p_shortAddr', $data->data->p_shortAddr, PDO::PARAM_STR);
            $stmt->bindParam(':po_name', $data->data->po_name, PDO::PARAM_STR);
            $stmt->bindParam(':po_id', $data->data->po_id, PDO::PARAM_STR);
            $stmt->bindParam(':no_familyMember', $data->data->no_familyMember, PDO::PARAM_INT);
            $stmt->bindParam(':no_fever', $data->data->no_fever, PDO::PARAM_INT);
            $stmt->bindParam(':no_out_breeding', $data->data->no_out_breeding, PDO::PARAM_INT);
            $stmt->bindParam(':no_in_breeding', $data->data->no_in_breeding, PDO::PARAM_INT);
            $stmt->bindParam(':container_type', $data->data->container_type, PDO::PARAM_STR);
            $stmt->bindParam(':no_pot_out_breeding', $data->data->no_pot_out_breeding, PDO::PARAM_INT);
            $stmt->bindParam(':no_pot_in_breeding', $data->data->no_pot_in_breeding, PDO::PARAM_INT);
            $stmt->bindParam(':act_abating', $data->data->act_abating, PDO::PARAM_INT);
            $stmt->bindParam(':act_destroy', $data->data->act_destroy, PDO::PARAM_INT);
            $stmt->bindParam(':act_education', $data->data->act_education, PDO::PARAM_INT);
            $stmt->bindParam(':act_pamphlet', $data->data->act_pamphlet, PDO::PARAM_INT);
            
            $stmt->execute();

            $sql = "SELECT LAST_INSERT_ID() AS id";
            $stmt = $db->prepare($sql);
            $stmt->execute();        

            $ind_id = $stmt->fetch(PDO::FETCH_OBJ);

            // //prepare state and execute     
            // $sql = "INSERT INTO `exhibit` 
            //             (`po_full_name`, `po_ic_no`, `acceptance`) 
            //         VALUES
            //             (:po_full_name, :po_ic_no, :acceptance)";

            // $stmt = $db->prepare($sql);
            // $stmt->bindParam(':po_full_name', $data->data->po_full_name, PDO::PARAM_STR);
            // $stmt->bindParam(':po_ic_no', $data->data->po_ic_no, PDO::PARAM_STR);            
            // $stmt->bindParam(':acceptance', $data->data->acceptance, PDO::PARAM_STR);

            // $stmt->execute();

            // $sql = "SELECT LAST_INSERT_ID() AS id";
            // $stmt = $db->prepare($sql);
            // $stmt->execute();        

            // $exhibit_id = $stmt->fetch(PDO::FETCH_OBJ);

            $sek5_id = "";
            if(isset($data->sek5Data)){
                //Insert Seksyen 5  
                $sql = "INSERT INTO `sek5` 
                        (`ind_id`, `appointment_date`, `remark`, `issue_date`) 
                        VALUES
                        (:ind_id, :appointment_date, :remark, CURDATE())";

                $stmt = $db->prepare($sql);
                $stmt->bindParam(':ind_id', $ind_id->id, PDO::PARAM_INT);
                $stmt->bindParam(':appointment_date', $data->sek5Data->appointment_date, PDO::PARAM_STR);            
                $stmt->bindParam(':remark', $data->sek5Data->remark, PDO::PARAM_STR);

                $stmt->execute();

                $sql = "SELECT LAST_INSERT_ID() AS id";
                $stmt = $db->prepare($sql);
                $stmt->execute();        

                $sek5_id = $stmt->fetch(PDO::FETCH_OBJ);
            }
            
            $sek8_id = "";
            if(isset($data->sek8Data)){
                //Insert Seksyen 8
                $sql = "INSERT INTO `sek8` 
                (`ind_id`, `issue_date`, `checking_date`, `remark`,
                `chkbx1`, `chkbx2`, `chkbx3`, `chkbx4`, `chkbx5`, `chkbx6`, `chkbx7`, `chkbx8`, `chkbx9`, `chkbx10`, `chkbx11`, `chkbx12`, `chkbx13`) 
                VALUES
                (:ind_id, CURDATE(), :checking_date, :remark,
                :chkbx1, :chkbx2, :chkbx3, :chkbx4, :chkbx5, :chkbx6, :chkbx7, :chkbx8, :chkbx9, :chkbx10, :chkbx11, :chkbx12, :chkbx13)";

                $stmt = $db->prepare($sql);
                $stmt->bindParam(':ind_id', $ind_id->id, PDO::PARAM_INT);
                $stmt->bindParam(':checking_date', $data->sek8Data->checking_date, PDO::PARAM_STR);            
                $stmt->bindParam(':remark', $data->sek8Data->remark, PDO::PARAM_STR);
                $stmt->bindParam(':chkbx1', $data->sek8Data->chkbx1, PDO::PARAM_INT);
                $stmt->bindParam(':chkbx2', $data->sek8Data->chkbx2, PDO::PARAM_INT);
                $stmt->bindParam(':chkbx3', $data->sek8Data->chkbx3, PDO::PARAM_INT);
                $stmt->bindParam(':chkbx4', $data->sek8Data->chkbx4, PDO::PARAM_INT);
                $stmt->bindParam(':chkbx5', $data->sek8Data->chkbx5, PDO::PARAM_INT);
                $stmt->bindParam(':chkbx6', $data->sek8Data->chkbx6, PDO::PARAM_INT);
                $stmt->bindParam(':chkbx7', $data->sek8Data->chkbx7, PDO::PARAM_INT);
                $stmt->bindParam(':chkbx8', $data->sek8Data->chkbx8, PDO::PARAM_INT);
                $stmt->bindParam(':chkbx9', $data->sek8Data->chkbx9, PDO::PARAM_INT);
                $stmt->bindParam(':chkbx10', $data->sek8Data->chkbx10, PDO::PARAM_INT);
                $stmt->bindParam(':chkbx11', $data->sek8Data->chkbx11, PDO::PARAM_INT);
                $stmt->bindParam(':chkbx12', $data->sek8Data->chkbx12, PDO::PARAM_INT);
                $stmt->bindParam(':chkbx13', $data->sek8Data->chkbx13, PDO::PARAM_INT);

                $stmt->execute();

                $sql = "SELECT LAST_INSERT_ID() AS id";
                $stmt = $db->prepare($sql);
                $stmt->execute();        

                $sek8_id = $stmt->fetch(PDO::FETCH_OBJ);
            }

            $exhibit_id = "";
            if(isset($data->exhibitData)){
                //Insert Exhibit
                $sql = "INSERT INTO `exhibit` 
                (`po_full_name`, `po_ic_no`, `acceptance`, `issue_date`) 
                VALUES
                (:po_full_name, :po_ic_no, :acceptance, CURDATE())";

                $stmt = $db->prepare($sql);
                $stmt->bindParam(':po_full_name', $data->exhibitData->po_full_name, PDO::PARAM_STR);
                $stmt->bindParam(':po_ic_no', $data->exhibitData->po_ic_no, PDO::PARAM_STR);            
                $stmt->bindParam(':acceptance', $data->exhibitData->acceptance, PDO::PARAM_STR);

                $stmt->execute();

                $sql = "SELECT LAST_INSERT_ID() AS id";
                $stmt = $db->prepare($sql);
                $stmt->execute();        

                $exhibit_id = $stmt->fetch(PDO::FETCH_OBJ);
            }

            echo '{ "status"    : "1",
                    "ind"       : ' .json_encode($ind_id). ',
                    "sek5"      : ' .json_encode($sek5_id). ',
                    "sek8"      : ' .json_encode($sek8_id). ',
                    "exhibit"   : ' .json_encode($exhibit_id). '
            }';
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