<?php
require_once("DB_adapter.php");

Profiling::initCheck();

class Profiling {

    const KEY = "Sdgdfgdfsgerg343434tge";
    const TOKEN_SALT = "grtgb334reerff4433";
    const TMP_DIR = "LR_PROFILING";
    const SESSION_TIME = 3600; //seconds = 60 min

    protected $profile = "default";
    protected $microtime = 0;
    protected $initial = 0;
    protected $count = 0;
    protected $file_name;
    protected $path;
    protected $line;
    protected $data = array();
    protected $break_app =      "</app_content><app_header><b>APP START</b>";
    protected $break_app_end =  "<app_header><b>APP END</b>";
    protected $break_api     =  "</api_content><api_header><b>API CALL START</b>";
    protected $break_api_end =  "<api_header>API CALL END";
    protected $break_line     = "";
    public $db;
    protected $save_report_to = array("file"=>false,
                                      "mysql"=>true);

    public function __construct() {
        $this->init();
    }

    public function __wakeup() {
        $this->init();
    }

    protected function generateFilename() {
        $this->path = DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR.self::TMP_DIR;
        if(!is_dir($this->path)) mkdir($this->path);

        $i = 0;
        do {
            $exists = false;
            $this->file_name = $this->path.DIRECTORY_SEPARATOR.date("Y-m-d_") . $this->profile . "_$i.csv";
            if(is_file($this->file_name)) {
                $i++;
                $exists = true;
            }
        } while ($exists && isset($_GET["profiling_new_file"]));
    }

    protected function init() {
        if(!$this->validateRecording()) return false;

        $this->db = new DB_adapter();

        $this->setDelimiter();

        if(!$GLOBALS["lr_api"]) {
            $this->count = 0;
            $this->initial = microtime(true);
            $this->microtime = microtime(true);
        }

        if($this->save_report_to["file"])
            $this->generateFilename();

    }

    public function setDelimiter($end=false) {
        if($GLOBALS["lr_api"]) {
            if(!$end) $this->line = PHP_EOL.$this->break_line."</api_header>".PHP_EOL;
            else $this->line = PHP_EOL.$this->break_line."</api_header><api_content>".PHP_EOL;
        } else {
            if(!$end) $this->line = PHP_EOL.$this->break_line."</app_header>".PHP_EOL;
            else $this->line = PHP_EOL.$this->break_line."</app_header><app_content>".PHP_EOL;
        }
        $this->writeLine();

        $this->line = "Request time: ".date("Y-m-d H:i:s").PHP_EOL;
        $this->writeLine();
        $this->line = "Request URL: ".'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}".PHP_EOL;
        $this->writeLine();


        if(!$end) {
            if($GLOBALS["lr_api"]) $this->line = PHP_EOL.PHP_EOL.$this->break_api.PHP_EOL;
            else $this->line = PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL.$this->break_app.PHP_EOL;
        } else {
            if($GLOBALS["lr_api"]) $this->line = PHP_EOL.PHP_EOL.$this->break_api_end.PHP_EOL;
            else $this->line = PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL.$this->break_app_end.PHP_EOL;
        }
        $this->writeLine();
    }

    public function mark() {
        if(!$this->validateRecording()) return false;

        $this->data = array();
        if(empty($this->initial)) $this->initial = microtime(true);
        if(empty($this->microtime)) $this->microtime = microtime(true);

        if(!$this->count) {
            $this->data["from_initial"] = "0.0000s";
            $this->data["micro_time"] = "0.0000s";
        }
        else {
            $this->data["from_initial"] = number_format(microtime(true)-$this->initial,4)."s";
            $this->data["micro_time"] = number_format(microtime(true)-$this->microtime,4)."s";
        }

        foreach(func_get_args() as $value) {
            $this->data[] = $value;
        }

        $this->microtime = microtime(true);
        $this->count++;
        $this->setLine();
        $this->writeLine();
    }

    protected function setLine() {
        $this->line = implode("   ,   ",$this->data).PHP_EOL;
    }

    protected function writeLine() {
        if(!$this->validateRecording()) return false;

        if($this->save_report_to["file"])
            file_put_contents($this->file_name, $this->line, FILE_APPEND | LOCK_EX);
        if($this->save_report_to["mysql"]) {
            $result = $this->db->writeLine($GLOBALS["lr_profiling_id"], $this->line);
            if (!$result) $this->save_report_to["mysql"] = false;
        }
    }

    protected function validateRecording() {
        if(isset($_COOKIE["lr_profiling_id"]) && ($user_id=$_COOKIE["lr_profiling_id"]) &&
            isset($_COOKIE["lr_profiling_token"]) && ($token=$_COOKIE["lr_profiling_token"])) {
                if($token==md5(Profiling::TOKEN_SALT.$user_id)) {
                    return true;
                }
        }
        return false;
    }

    public static function initCheck() {
        if (@$_GET["profiling_key"] == Profiling::KEY) {
            if (!isset($_COOKIE["lr_profiling_id"])) {
                $id = uniqid();
                setcookie("lr_profiling_id", $id, time() + self::SESSION_TIME, "/"); //set unique client id
                $_COOKIE["lr_profiling_id"] = $id; //makes sure the cookie is set
                $token = md5(Profiling::TOKEN_SALT.$id);
                setcookie("lr_profiling_token",$token, time() + self::SESSION_TIME, "/"); //set token for id verification
                $_COOKIE["lr_profiling_token"] = $token; //makes sure the cookie is set
                setcookie("lr_profiling_expire",time(), time() + self::SESSION_TIME, "/"); //set token expire time
                $_COOKIE["lr_profiling_expire"] = $token; //makes sure the cookie is set

                $GLOBALS["lr_profiling_id"] = $id;
            }
        }

        if (isset($_COOKIE["lr_profiling_id"])) {
            $GLOBALS["lr_profiling_id"] = $_COOKIE["lr_profiling_id"];
        }

        if (isset($_SERVER["HTTP_REFERER"])
            && (
                strpos($_SERVER["REQUEST_URI"], "/api/") !== false
                || strpos($_SERVER["REQUEST_URI"], "/profiling") !== false
            )
        ) {
            $GLOBALS["lr_api"] = true;
        } else {
            $GLOBALS["lr_api"] = false;
        }
    }

    public function __destruct() {
        if(!$this->validateRecording("destruct")) return false;
        $this->db->setProfilingSession($GLOBALS["lr_profiling_id"],serialize($this));
    }

    public static function getObject() {
        $db = new DB_adapter();
        if($serialized_session = $db->getProfilingSession(@$GLOBALS["lr_profiling_id"])) {
            return unserialize($serialized_session);
        } else {
            return new Profiling();
        }
    }
}
?>