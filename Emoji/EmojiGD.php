<?php
namespace Emoji\EmojiReplace;

require_once("EmojiSets.php");
use Emoji\EmojiReplace\EmojiSets as EmojiSets;

class EmojiGD extends EmojiSets {
	
	public function __construct(){
			
	}
	
	public function getIconLink($name,$EmojiPack = null){
		if (is_null($EmojiPack)){
			return "{$this->DirAssets}Icons/{$this->PackIcon}/{$name}.png";
		}else{
			return "{$this->DirAssets}Icons/{$EmojiPack}/{$name}.png";	
		}
	}
	
	public function LoadEmojis($only = false){
		if ($only){
			$this->ListEmoji = json_decode(file_get_contents(__DIR__."/DataJson/emojiList.json"),1);
			$this->LoadEmoji = true;
			return;
		}
		$this->ListEmoji = json_decode(file_get_contents(__DIR__."/DataJson/emojiList.json"),1);
		$this->Emojis = json_decode(file_get_contents(__DIR__."/DataJson/emoji.json"),1);
		$this->LoadEmoji = true;
	}
	
	public static function ImageToBase64($img){
		$imgData = base64_encode(file_get_contents($img));
		return 'data: '.mime_content_type($img).';base64,'.$imgData;
	}
	
	public function generateEmoji(){
		$Build = false;		
		if (file_exists(__DIR__."/DataJson/emojiConfig.json")){
			$this->LastConfig = json_decode(file_get_contents(__DIR__."/DataJson/emojiConfig.json"));
			if ($this->LastConfig->Pack != $this->PackIcon){
				$Build = true;	
			}
			if ($this->LastConfig->Assets != $this->DirAssets){
				$Build = true;	
			}			
		}else{
			$Build = true;
		}
		if ($Build){
			$this->LoadEmojis(true);
			$Array = array();
			foreach ($this->ListEmoji as $Char=>$Code){
				$Array[$Char] = $this->getEmoji($Char);
			}
			$this->Emojis = $Array; 
			file_put_contents(__DIR__."/DataJson/emoji.json",json_encode($Array));
			file_put_contents(__DIR__."/DataJson/emojiConfig.json",json_encode(array(
				"Pack" => $this->PackIcon, 
				"Assets" => $this->DirAssets
			)));
		}
	}
	
	/*
		Draws emoji filled text on image
		$image		Source image
		$fontsize	Size of font used
		$x			X coordinate of the first symbol
		$y			Y coordinate of the first symbol
		$color		Color of the font
		$fontname	Path to the font file
		$text		The text string to draw in UTF8 encoding
		$EmojiPack	The name of Emoji package (compatibility with EmojiSets)
	*/
	function emojiText($image, $fontsize, $x, $y, $color, $fontname, $text, $EmojiPack=null)
	{
		$colour = imagecolorallocate($image,hexdec(substr($color, 0, 2)),hexdec(substr($color, 2, 2)),hexdec(substr($color, 4, 2)));
		$chars = $this->str_split_unicode($text);
		$start = 0;
		$length = 0;
		$emojisize = $fontsize * 2;
		for ($i = 0; $i < count($chars); $i++)
		{
			if (isset($this->emojiList[$chars[$i]]))
			{
				$emojiImage = imagecreatefromstring(file_get_contents(getIconLink($this->emojiList[$chars[$i]], $EmojiPack)));
				$coords = imagettftext($image, $fontsize, 0, $x, $y, $colour, $fontname, mb_substr($text, $start, $length));
				$length = 0;
				$start = $i+1;
				$x += $coords[2] - $coords[0]+($fontsize*0.0005);
				imagecopyresampled($image, $emojiImage, $x, $y-($fontsize+$emojisize)*0.5, 0, 0, $emojisize, $emojisize, imagesx($emojiImage), imagesy($emojiImage));
				$x+=$emojisize+$fontsize*0.0005;
			}	
			else if ($i==(count($chars)-1))
			{
				$length++;
				$coords = imagettftext($image, $fontsize, 0, $x, $y, $colour, $fontname, mb_substr($text, $start, $length));
				$x+= $coords[2] - $coords[0];
			}
			else
			{
				$length++;
			}
		}
		return $image;
	}

	/*
		Splits text into unicode symbols, as the box function doesn't support split
		$str		Input string
		$length		Length of symbol chunk, better if kept default
	*/
	private function str_split_unicode($str, $length = 1) 
	{
		$tmp = preg_split('~~u', $str, -1, PREG_SPLIT_NO_EMPTY);
		if ($length > 1) {
			$chunks = array_chunk($tmp, $length);
			foreach ($chunks as $i => $chunk) {
				$chunks[$i] = join('', (array) $chunk);
			}
			$tmp = $chunks;
		}
		return $tmp;
	}
}