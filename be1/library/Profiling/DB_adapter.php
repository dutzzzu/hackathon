<?php
class DB_adapter {
    protected $mysql_host = "prod-mysql1i.lifereimagined.org";
    protected $mysql_user = "lifereimagined";
    protected $mysql_pass = "CAow6ZZH7jWW";
    protected $mysql_db = "lr_profiling";
    protected $mysql_table = "lr_profiling";
    protected $mysql_table_session = "lr_profiling_session";
    protected $mysql_link = false;


    public function setConnectionParams($env) {
        if(strpos($env,"development_")!==false) {
            $this->mysql_host = "localhost";
            $this->mysql_user = "root";
            $this->mysql_pass = "root";
        }
        if($env=="dev") {
            $this->mysql_host = "10.102.41.67";
        }
        if(strpos($env,"qa_v2")!==false) {
            $this->mysql_host = "10.32.213.200";
            $this->mysql_pass = "FaimLijDi";
        }
        if($env=="qa") {
            $this->mysql_host = "10.88.188.165";
        }
        if($env=="staging") {
            $this->mysql_host = "10.122.206.105";
        }
    }

    public function lr_mysql_connect() {
        if($this->mysql_link) return;
        if(isset($_SERVER["APPLICATION_ENV"])) $this->setConnectionParams($_SERVER["APPLICATION_ENV"]);

        $this->mysql_link = mysql_connect($this->mysql_host, $this->mysql_user, $this->mysql_pass);
        if (!$this->mysql_link) {
            error_log('Could not connect: ' . mysql_error());
            $this->save_report_to["mysql"] = false;
            return false;
        }
        $db_selected = mysql_select_db($this->mysql_db, $this->mysql_link);
        if (!$db_selected) {
            $result3 = mysql_query("CREATE DATABASE ".$this->mysql_db);
            if(!$result3) {
                error_log('Can\'t use db : ' . mysql_error());
                $this->save_report_to["mysql"] = false;
                return false;
            } else {
                mysql_select_db($this->mysql_db, $this->mysql_link);
            }
        }

        $result = mysql_query("SHOW TABLES LIKE '".$this->mysql_table."'");
        if(!$result || !mysql_num_rows($result)) {
            $result2 = mysql_query("CREATE TABLE IF NOT EXISTS `".$this->mysql_table."` (`id` int(11) NOT NULL,`user_id` varchar(16) NOT NULL,`line` text NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
            $result22 = mysql_query("ALTER TABLE `".$this->mysql_table."` ADD INDEX(`id`);");
            $result222 = mysql_query("ALTER TABLE `".$this->mysql_table."` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;");
            if(!$result2 || !$result22 || !$result222) {
                error_log('Can\'t create table : ' . mysql_error());
                $this->save_report_to["mysql"] = false;
                return false;
            }
        }

        $result = mysql_query("SHOW TABLES LIKE '".$this->mysql_table_session."'");
        if(!$result || !mysql_num_rows($result)) {
            $result2 = mysql_query("CREATE TABLE IF NOT EXISTS `".$this->mysql_table_session."` (`id` int(11) NOT NULL,`user_id` varchar(16) NOT NULL,`line` text NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
            $result22 = mysql_query("ALTER TABLE `".$this->mysql_table_session."` ADD INDEX(`id`);");
            $result222 = mysql_query("ALTER TABLE `".$this->mysql_table_session."` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;");
            $result2222 = mysql_query("ALTER TABLE `".$this->mysql_table_session."` ADD PRIMARY KEY(`user_id`);");
            if(!$result2 || !$result22 || !$result222 || !$result2222) {
                error_log('Can\'t create table : ' . mysql_error());
                $this->save_report_to["mysql"] = false;
                return false;
            }
        }

    }

    public function getProflingReportMysql($user_id) {
        if(!$this->mysql_link) $this->lr_mysql_connect();
        $results = mysql_query("SELECT * FROM ".$this->mysql_table." WHERE user_id='$user_id' ORDER BY id DESC",$this->mysql_link);
        $output = "";
        if($results)
            while ($row = mysql_fetch_assoc($results)) {
                $output .= $row["line"]."<br />";
            }
        if(empty($output)) return "no data...";
        return $output;
    }

    public function getProfilingSession($user_id) {
        if(!$user_id) return false;
        if(!$this->mysql_link) $this->lr_mysql_connect();
        $results = mysql_query("SELECT * FROM ".$this->mysql_table_session." WHERE user_id='$user_id' ORDER BY id DESC",$this->mysql_link);
        if($results)
            while ($row = mysql_fetch_assoc($results)) {
                return $row["line"];
            }
        return false;
    }

    public function getTotalRecords() {
        if(!$this->mysql_link) $this->lr_mysql_connect();
        $results = mysql_query("SELECT count(*) as total FROM ".$this->mysql_table,$this->mysql_link);
        if($results)
            while ($row = mysql_fetch_assoc($results)) {
                return $row["total"];
            }
        return false;
    }

    public function getTotalSessions() {
        if(!$this->mysql_link) $this->lr_mysql_connect();
        $results = mysql_query("SELECT count(*) as total FROM ".$this->mysql_table_session,$this->mysql_link);
        if($results)
            while ($row = mysql_fetch_assoc($results)) {
                return $row["total"];
            }
        return false;
    }


    public function clearDB() {
        if(!$this->mysql_link) $this->lr_mysql_connect();
        $results = mysql_query("TRUNCATE TABLE ".$this->mysql_table,$this->mysql_link);
        $results = mysql_query("TRUNCATE TABLE ".$this->mysql_table_session,$this->mysql_link);
    }

    public function setProfilingSession($user_id,$data) {
        if(!$this->mysql_link) $this->lr_mysql_connect();
        $results = mysql_query("INSERT INTO ".$this->mysql_table_session." (user_id,line) VALUES ('$user_id','$data') ON DUPLICATE KEY UPDATE line='$data';",$this->mysql_link);
        if(!$results) {
            error_log('Invalid query: ' . mysql_error());
        }
        return $results;
    }

    public function removeProfilingSession($user_id) {
        if(!$user_id) return false;
        if(!$this->mysql_link) $this->lr_mysql_connect();
        $results = mysql_query("DELETE FROM ".$this->mysql_table_session." WHERE user_id='$user_id'",$this->mysql_link);
        return $results;
    }

    public function writeLine($user_id,$data) {
        if(!$this->mysql_link) $this->lr_mysql_connect();
        $result = mysql_query("INSERT INTO " . $this->mysql_table . " SET user_id='" . $user_id . "', line='" . $data . "'",$this->mysql_link);
        if (!$result) {
            error_log('Invalid query: ' . mysql_error());
        }
        return $result;
    }
}

?>
