<?php
class Setup {
    public function checkHtaccess() {
        if(empty($_SERVER["SCRIPT_FILENAME"])) return false;
        $htaccess_path = str_replace("index.php",".htaccess",$_SERVER["SCRIPT_FILENAME"]);
        $content = file_get_contents($htaccess_path);

        if(strpos($content,"#_safe")!==false) return true;

        if(strpos($content,"#_not_safe")!==false) {
            $arrContent = explode("#_not_safe",$content);
            $htpasswd_path = str_replace("index.php",".htpasswd",$_SERVER["SCRIPT_FILENAME"]);
            $arrContent[1] = str_replace("{...}",$htpasswd_path,$arrContent[1]);
            $arrContent[1] = str_replace("#","",$arrContent[1]);
            $content = implode("#_safe",$arrContent);
            $status = @file_put_contents($htaccess_path,$content);
            if($status) return "setup_ok";
            return false;
        }
    }

    public function parseOutput($data) {
        $data = preg_replace_callback(
            '/\d+\.\d+s/',
            function ($matches) {
                $offset = 60;
                $multiplier = 2;
                $time = rtrim($matches[0],"s")+0;
                $red = (($time+1)*$offset)^$multiplier; if($red>255) $red = 255;
                $blue = ((($time+1)-5)*$offset)^$multiplier; if($blue>255) $blue = 255;
                $green = 255-((($time+1)*$offset)^$multiplier); if($green<0) $green = 0;
                return "<span style='font-weight:800;color:rgb($red,$green,$blue)'>".number_format($time,4)."s</span>";
            }, $data );
        $data = preg_replace_callback(
            '/http\:\/\/(.*)/',
            function ($matches) {
                return "<a target='_blank' href='".$matches[0]."'>".$matches[0]."</a>";
            }, $data );

        $data = preg_replace_callback(
            '/\<api\_content\>(.*?)\<\/api\_content\>/s',
            function ($matches) {
                return "<div style='border:1px solid #FFDEAD;margin:5px;padding:5px;background-color:#FFFFF0'>".$matches[0]."</div>";
            }, $data );

        $data = preg_replace_callback(
            '/\<app\_content\>(.*?)\<\/app\_content\>/s',
            function ($matches) {
                return "<div style='border:1px solid #B0C4DE;margin:5px;padding:5px;background-color:#F0FFFF'>".$matches[0]."</div>";
            }, $data );

        $data = preg_replace_callback(
            '/\<api\_header\>(.*?)\<\/api\_header\>/s',
            function ($matches) {
                return "<div style='border:1px solid #FFDEAD;margin:5px;padding:5px;margin:0 25%;background-color:#FFF5EE'>".$matches[0]."</div>";
            }, $data );

        $data = preg_replace_callback(
            '/\<app\_header\>(.*?)\<\/app\_header\>/s',
            function ($matches) {
                return "<div style='border:1px solid #B0C4DE;margin:5px;padding:5px;margin:0 25%;background-color:#F0F8FF'>".$matches[0]."</div>";
            }, $data );

        return $data;
    }
}