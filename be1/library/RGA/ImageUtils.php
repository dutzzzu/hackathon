<?php
class RGA_ImageUtils {
	

	public function createCallingCardImage($name, $environment, $iconpath, $isCustom=false, $isDownload=false) {
		$padding = $isCustom ? 40 : 30;
		$imgsize = $isCustom ? 190 : 240;
		$labelFontfile = realpath(APPLICATION_PATH . '/../public/assets/layout/fonts') . '/museosans_900.ttf';
		$environmentFontfile = realpath(APPLICATION_PATH . '/../public/assets/layout/fonts') . '/museo500-regular.ttf';
		$labelImg = $this->_getWrappedText($name, $labelFontfile, 20, 258, '47305d');
		$environmentImg = $this->_getWrappedText($environment, $environmentFontfile, 11, 258, '999999');
		$labelWidth = imagesx($labelImg);
		$labelHeight = imagesy($labelImg);
		$environmentWidth = imagesx($environmentImg);
		$environmentHeight = imagesy($environmentImg);
		$bgpng = imagecreatefrompng(realpath(APPLICATION_PATH . '/../public/assets/calling-cards/images/card-bg.png'));
		$bgWidth = imagesx($bgpng);
		$bgHeight = imagesy($bgpng);
		imagealphablending($bgpng, true);
    	imagesavealpha($bgpng, true);
		imagecopy($bgpng, $labelImg, ($bgWidth - $labelWidth)*.5, $bgHeight-$labelHeight-$padding-$environmentHeight-18, 0, 0, $labelWidth, $labelHeight);
		imagecopy($bgpng, $environmentImg, ($bgWidth - $environmentWidth)*.5, $bgHeight-$environmentHeight-$padding, 0, 0, $environmentWidth, $environmentHeight);
		$iconpng = imagecreatefromstring(file_get_contents($iconpath));
    	imagealphablending($iconpng, true);
    	imagesavealpha($iconpng, false);
    	$iconWidth = imagesx($iconpng);
    	$iconHeight = imagesy($iconpng);
    	//if ($isCustom) {
    		$tmp_img = imagecreatetruecolor($imgsize, $imgsize);
    		imagealphablending($tmp_img, false);
    		imagesavealpha($tmp_img, true);
    		imagecopyresized($tmp_img, $iconpng, 0, 0, 0, 0, $imgsize, $imgsize, $iconWidth, $iconHeight);
    		imagedestroy($iconpng); 
    		$iconWidth = $imgsize;
    		$iconHeight = $imgsize;
    		$iconpng = $tmp_img;
    	//}
    	imagecopy($bgpng, $iconpng, ($bgWidth-$iconWidth)*.5, $padding, 0, 0, $iconWidth, $iconHeight);
        
        if ($isDownload) {
            header('Content-Description: File Transfer');
            header('Content-type: application/force-download');
            header('Content-Transfer-Encoding: binary');
            header("Content-disposition: attachment; filename= ". $name . '-' . rand() . ".png");
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
        } else {
            header("Content-type: image/png");     
        }
		
        imagepng($bgpng);
		 
		imagedestroy($bgpng); 
		imagedestroy($labelImg); 
		imagedestroy($environmentImg); 
		imagedestroy($iconpng); 
	}

    public function createCallingCardSharingImage($name, $environment, $iconpath, $isCustom=false, $isDownload=false) {
        $padding = $isCustom ? 30 : 30;
        $imgsize = $isCustom ? 120 : 150;
        $labelFontfile = realpath(APPLICATION_PATH . '/../public/assets/layout/fonts') . '/museosans_900.ttf';
        $environmentFontfile = realpath(APPLICATION_PATH . '/../public/assets/layout/fonts') . '/museo500-regular.ttf';
        $labelImg = $this->_getWrappedText($name, $labelFontfile, 16, 150, '47305d');
        $environmentImg = $this->_getWrappedText($environment, $environmentFontfile, 11, 258, '999999');
        $labelWidth = imagesx($labelImg);
        $labelHeight = imagesy($labelImg);
        $environmentWidth = imagesx($environmentImg);
        $environmentHeight = imagesy($environmentImg);
        $bgpng = imagecreatefrompng(realpath(APPLICATION_PATH . '/../public/assets/email-templates/images/shuffled-cards-bg.png'));
        $bgWidth = imagesx($bgpng);
        $bgHeight = imagesy($bgpng);
        imagealphablending($bgpng, true);
        imagesavealpha($bgpng, true);
        imagecopy($bgpng, $labelImg, ($bgWidth - $labelWidth)*.5, $bgHeight-$labelHeight-$padding-$environmentHeight-5, 0, 0, $labelWidth, $labelHeight);
        imagecopy($bgpng, $environmentImg, ($bgWidth - $environmentWidth)*.5, $bgHeight-$environmentHeight-$padding, 0, 0, $environmentWidth, $environmentHeight);
        $iconpng = imagecreatefromstring(file_get_contents($iconpath));
        imagealphablending($iconpng, true);
        imagesavealpha($iconpng, false);
        $iconWidth = imagesx($iconpng);
        $iconHeight = imagesy($iconpng);
        //if ($isCustom) {
            $tmp_img = imagecreatetruecolor($imgsize, $imgsize);
            imagealphablending($tmp_img, false);
            imagesavealpha($tmp_img, true);
            imagecopyresized($tmp_img, $iconpng, 0, 0, 0, 0, $imgsize, $imgsize, $iconWidth, $iconHeight);
            imagedestroy($iconpng); 
            $iconWidth = $imgsize;
            $iconHeight = $imgsize;
            $iconpng = $tmp_img;
        //}
        imagecopy($bgpng, $iconpng, ($bgWidth-$iconWidth)*.5, $padding, 0, 0, $iconWidth, $iconHeight);
        if ($isDownload) {
            header('Content-Description: File Transfer');
            header("Content-disposition: attachment; filename= ". $name . '-' . rand() . ".png");
        } else {
            header("Content-type: image/png");     
        }
        
        imagepng($bgpng);
         
        imagedestroy($bgpng); 
        imagedestroy($labelImg); 
        imagedestroy($environmentImg); 
        imagedestroy($iconpng); 
    }

	protected function _getWrappedText($txt, $fontfile, $fontsize, $boxwidth, $textcolor) {


		$wrappedText = $this->_wordWrap($fontsize, 0, $fontfile, $txt, $boxwidth);

        $parts = explode ( "\n", $wrappedText);
        $bounds = imagettfbbox ( intval($fontsize), 0, $fontfile, $wrappedText);
        $width = $bounds[2] - $bounds[0];
        $height = ceil(abs($bounds[3]) + abs($bounds[5])) * 1.2;
        $line_height = floor($height / count ( $parts ));

        foreach ( $parts as $index => $part ) {
            $bounds = imagettfbbox ( intval($fontsize), 0, $fontfile, $part );
            $new_width = $bounds[2] - $bounds[0];
            $diff = ( $width - $new_width ) / 2;
            $new_left = $diff;

            $new_string = array();
            $new_string['left'] = $new_left;
            $new_string['top'] = ($index * $line_height) + $fontsize;
            $new_string['string'] = $part;
            $new_strings[] = $new_string;
        }
        
        $png = imagecreatetruecolor($width, $height);
		imagesavealpha($png, true);
		imagealphablending($png, false);

		$transparentColor = imagecolorallocatealpha($png, 200, 200, 200, 127);
		imagefill($png, 0, 0, $transparentColor);

		
		$color = str_replace("#","",$textcolor);
		$red = hexdec(substr($textcolor,0,2));
		$green = hexdec(substr($textcolor,2,2));
		$blue = hexdec(substr($textcolor,4,2));
		$tx = imagecolorallocate($png, $red, $green, $blue);


		foreach ($new_strings as $row) {
			imagettftext($png, $fontsize, 0, $row['left'], $row['top'], $tx, $fontfile, $row['string']);	
		}
		return $png;
	}



	protected function _wordWrap($fontSize, $angle, $fontFace, $string, $width){

    $ret = "";

    $arr = explode(' ', $string);

    foreach ( $arr as $word ){

        $teststring = $ret.' '.$word;
        $testbox = imagettfbbox($fontSize, $angle, $fontFace, $teststring);
        if ( $testbox[2] > $width ){
            $ret.=($ret==""?"":"\n").$word;
        } else {
            $ret.=($ret==""?"":' ').$word;
        }
    }

    return $ret;
}

}