<?php
/**
 * Collection of some good utility-functions that are used for the obfuscator
 */
class Utilities 
{
	/**
	 * copies and entire directory incl all files to a new destination directory
	 * source: http://php.net/manual/en/function.copy.php
	 */
	public static function recurseCopy($src,$dst){
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    self::recurseCopy($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

	/**
	 * generates a random key with a wished length
	 */
	public static function getRandomKey($keySize){
		$string = "abcdefghijklmnopqrstuvwxyz";
		$key = "";
		for($i=0;$i<$keySize;$i++){
			$rand = rand(1,strlen($string)-1);
			$key .= substr($string,$rand,1);
		}
		return $key;
	}
}
?>
