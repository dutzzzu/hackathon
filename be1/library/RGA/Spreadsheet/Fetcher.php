<?php
class RGA_Spreadsheet_Fetcher {

    public static function fetch($url, $output = null) {
        
        $header = array();
        $data = array();
        try {
            if (($handle = @fopen($url . '&t_lr=' . time(), "r")) !== FALSE) {
                if (($rowData = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $header = $rowData;
                }
                while (($rowData = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $data []= array_combine(array_values($header), array_values($rowData));
                }
                fclose($handle);
            }
            file_put_contents($output, "<?php\n /*\n* :-) File generated by scripts/spreadsheet/fetch.php \n* from spreasheet at: {$url} \n* Date: " . date('Y-m-d H:i:s'). "\n*/\n return "  . var_export($data, true) . ";\n?>\n");
            return $data;
        } catch (Exception $e) {
            return false;
        }
        
    }
}