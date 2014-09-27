<?php

class MemeGenerator {

  private $upperText;
  private $lowerText;
  private $color;
  private $font = 'impact.ttf';
  private $im;
  private $imgSize;

  public function setUpperText($txt) {
    $this->upperText = strtoupper($txt);
  }

  public function setLowerText($txt) {
    $this->lowerText = strtoupper($txt);
  }

  private function getHorizontalTextAlignment($imgWidth, $topRightPixelOfText) {
    return ceil(($imgWidth - $topRightPixelOfText) / 2);
  }

  private function CheckTextWidthExceedImage($imgWidth, $fontWidth) {
    return ($imgWidth < $fontWidth + 20);
  }

  private function GetFontPlacementCoordinates($text, $fontSize) {
    /* 		returns 
     * 		Array
     * 		(
     * 			[0] => ? // lower left X coordinate
     * 			[1] => ? // lower left Y coordinate
     * 			[2] => ? // lower right X coordinate
     * 			[3] => ? // lower right Y coordinate
     * 			[4] => ? // upper right X coordinate
     * 			[5] => ? // upper right Y coordinate
     * 			[6] => ? // upper left X coordinate
     * 			[7] => ? // upper left Y coordinate
     * 		)
     * */

    return imagettfbbox($fontSize, 0, $this->font, $text);
  }

  private function ReturnImageFromPath($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    if ($ext == 'jpg' || $ext == 'jpeg')
      return imagecreatefromjpeg($path);
    else if ($ext == 'png')
      return imagecreatefrompng($path);
    else if ($ext == 'gif')
      return imagecreatefromgif($path);

    return false;
  }

  public function __construct($path, $color = array(255, 255, 255)) {
    $this->im = $this->ReturnImageFromPath($path);
    if (!$this->im) {
      return;
    }
    $this->imgSize = getimagesize($path); //http://php.net/manual/en/function.getimagesize.php

    $this->color = imagecolorallocate($this->im, $color[0], $color[1], $color[2]);
    imagecolortransparent($this->im, $this->color);
  }

  private function WorkOnImage($text, $size, $type) {
    $TextHeight = ($type == "upper") ? $size + 35 : $this->imgSize[1] - 20;

    while (1) {
      //get coordinate for the text
      $coords = $this->GetFontPlacementCoordinates($text, $size);

      // place the text in center
      $TextX = $this->getHorizontalTextAlignment($this->imgSize[0], $coords[4]);

      //check if the text does not exceed image width if yes then repeat with size = size - 1
      if ($this->CheckTextWidthExceedImage($this->imgSize[0], $coords[2] - $coords[0])) {
        //if top text take it up as font size decreases, if bottom text take it down as font size decreases
        $TextHeight += ($type == "upper") ? - 1 : 1;

        if ($size == 10) {
          //if text size is reached to lower limit and still it is exceeding image width start breaking into lines
          if ($type == "upper") {
            $this->upperText = $this->ReturnMultipleLinesText($text, $type, 16);
            $text = $this->upperText;
            return;
          }
          else {
            $this->lowerText = $this->ReturnMultipleLinesText($text, $type, $this->imgSize[1] - 20);
            $text = $this->lowerText;
            return;
          }
        }
        else
          $size -=1;
      }
      else
        break;
    }

    //$this->PlaceTextOnImage($this->im, $size, $TextX, $TextHeight, $this->font, (($type == "upper") ? $this->upperText : $this->lowerText));
    $this->imagettfstroketext($this->im, $size, $angle = 0, $TextX, $TextHeight, $this->color, $strokecolor = 0, $this->font, (($type == "upper") ? $this->upperText : $this->lowerText), $px = $size / 15);
  }

  private function PlaceTextOnImage($img, $fontsize, $Xlocation, $Textheight, $font, $text) {
    imagettftext($this->im, $fontsize, 0, $Xlocation, $Textheight, (int) $this->color, $font, $text);
  }

  /**
   * Writes the given text with a border into the image using TrueType fonts.
   * @author John Ciacia
   * @param image An image resource
   * @param size The font size
   * @param angle The angle in degrees to rotate the text
   * @param x Upper left corner of the text
   * @param y Lower left corner of the text
   * @param textcolor This is the color of the main text
   * @param strokecolor This is the color of the text border
   * @param fontfile The path to the TrueType font you wish to use
   * @param text The text string in UTF-8 encoding
   * @param px Number of pixels the text border will be
   * @see http://us.php.net/manual/en/function.imagettftext.php
   */
  function imagettfstroketext(&$image, $size, $angle, $x, $y, &$textcolor, &$strokecolor, $fontfile, $text, $px) {

    for ($c1 = ($x - abs($px)); $c1 <= ($x + abs($px)); $c1++)
      for ($c2 = ($y - abs($px)); $c2 <= ($y + abs($px)); $c2++)
        $bg = imagettftext($image, $size, $angle, $c1, $c2, $strokecolor, $fontfile, $text);

    return imagettftext($image, $size, $angle, $x, $y, $textcolor, $fontfile, $text);
  }

  private function ReturnMultipleLinesText($text, $type, $textHeight) {
    //breaks the whole sentence into multiple lines according to the width of the image.
    //break sentence into an array of words by using the spaces as params
    $brokenText = explode(" ", $text);
    $finalOutput = "";

    if ($type != "upper")
      $textHeight = $this->imgSize[1] - ((count($brokenText) / 2) * 3);

    for ($i = 0; $i < count($brokenText); $i++) {
      $temp = $finalOutput;
      $finalOutput.= $brokenText[$i] . " ";
      // this will help us to keep the last word in hand if this word is the cause of text exceeding the image size.			
      // We will be using this to append in next line.
      //check if word is too long i.e wider than image width
      //get the sentence(appended till now) placement coordinates
      $dimensions = $this->GetFontPlacementCoordinates($finalOutput, 10);

      //check if the sentence (till now) is exceeding the image with new word appended
      if ($this->CheckTextWidthExceedImage($this->imgSize[0], $dimensions[2] - $dimensions[0])) { //yes it is then
        // append the previous sentence not with the new word  ( new word == $brokenText[$i] )
        $dimensions = $this->GetFontPlacementCoordinates($temp, 10);
        $locx = $this->getHorizontalTextAlignment($this->imgSize[0], $dimensions[4]);
        $this->PlaceTextOnImage($this->im, 10, $locx, $textHeight, $this->font, $temp);
        $finalOutput = $brokenText[$i];
        $textHeight +=13;
      }

      //if this is the last word append this also.The previous if will be true if the last word will have no room
      if ($i == count($brokenText) - 1) {
        $dimensions = $this->GetFontPlacementCoordinates($finalOutput, 10);
        $locx = $this->getHorizontalTextAlignment($this->imgSize[0], $dimensions[4]);
        $this->PlaceTextOnImage($this->im, 10, $locx, $textHeight, $this->font, $finalOutput);
      }
    }
    return $finalOutput;
  }

  public function processImg($imgOut = "abc.jpg") {
    if ($this->lowerText != "") {
      $this->WorkOnImage($this->lowerText, 30, "lower");
    }

    if ($this->upperText != "") {
      $this->WorkOnImage($this->upperText, $this->imgSize[1] / 20, "upper");
    }

    $maxWidth = 1000;
    if ($this->imgSize[0] > $maxWidth) {
      $newHeight = ($this->imgSize[1] / $this->imgSize[0]) * $maxWidth;
      $tmp = imagecreatetruecolor($maxWidth, $newHeight);
      imagecopyresampled($tmp, $this->im, 0, 0, 0, 0, $maxWidth, $newHeight, $this->imgSize[0], $this->imgSize[1]);
      imagedestroy($this->im);
      $this->im = $tmp;
    }

    imagejpeg($this->im, $imgOut);
    imagedestroy($this->im);

    echo $imgOut . "?t=" . time();
  }

}

/* EXAMPLE USAGE:
$imgIn = $_GET['img'] ? $_GET['img'] : 'testing.jpg';
$finfo = new finfo(FILEINFO_MIME);
$mime = $finfo->file(dirname(__FILE__) . DIRECTORY_SEPARATOR . $imgIn);
if (substr($mime, 0, 5) != "image") {
  exit;
}

$obj = new MemeGenerator($imgIn);
$upmsg = $_GET['upmsg'];
$downmsg = $_GET['downmsg'];

$obj->setUpperText($upmsg);
$obj->setLowerText($downmsg);
$obj->processImg();
*/