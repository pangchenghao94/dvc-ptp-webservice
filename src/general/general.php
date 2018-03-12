<?php
    class GenError{
        public static function unauthorizedAccess(){
            echo '{ "status"    : "0",
                    "message"   : "Unauthorized access!" }
            ';
        }

        public static function unexpectedError(PDOException $e){
            echo '{"error":{"text": '.$e->getMessage().'}}';
        }
    }
?>