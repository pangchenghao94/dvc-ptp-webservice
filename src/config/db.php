<?php 
    class db{
        // // database properties - local
        // private $dbhost = 'localhost';
        // private $dbuser = 'root';
        // private $dbpass = '';
        // private $dbname = 'dvc';

        // database properties - cloud
        private $dbhost = 'dvc-ptp-instance.cyqqwtd2znsg.ap-southeast-1.rds.amazonaws.com';
        private $dbuser = 'dvcMaster';
        private $dbpass = 'dvcMaster123';
        private $dbname = 'dvc';

        // Connect
        public function connect(){
            $mysql_connect_str = "mysql:host=$this->dbhost;dbname=$this->dbname";
            $dbConnection = new PDO($mysql_connect_str, $this->dbuser, $this->dbpass);
            $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $dbConnection;
        }
    }

    define("SITE_KEY",'dvc-ptp');
    function apiToken($session_uid){
        $key = md5(SITE_KEY.$session_uid);
        return hash('sha256',$key);
    }