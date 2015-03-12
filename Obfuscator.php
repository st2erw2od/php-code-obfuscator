<?php
/**
 * PHP code obfuscator
 *
 * @author B. St2erw2od
 * @link https://github.com/st2erw2od/php-code-obfuscator
 * @license MIT
 *
 * replaces:
 * - variables incl. arrays
 * - session-variables
 * - interfaces
 * - constants
 * - whitespace
 * - comments
 *
 * doesn't replace:
 * - file names (no file rename because of external calls like form submit or ajax)
 * - class names (because of file names and autoload)
 * - $_GET, $_POST, $_REQUEST (because of webservice calls)
 * - $_COOKIES (because of external influences like js)
 * - magic methods (because the code would break)
 * - array index/key (might be used from json decode)
 *
 * todos:
 * - $GLOBAL
 * - exceptions
 * - source and destination path settings from the outside
 * - setting if strip whitespaces (yes/no)
 * - add custom file heading text
 * - tests with different platforms (Windows, Mac, Linux)
 * - ...
 */

require_once 'Utilities.php';

class Obfuscator
{
	private $settings = array(
		'source_dir' => './',
		'destination_dir' => 'C:/Temp/_obfuscated/',
		'illegal_items' => array(
			"\$this",
			"self",
			"parent",
			"__construct",
			"__destruct",
			"__get",
			"__set",
			"__call"
		),
		'preserve_class_names' => array( /*idea*/
			"Obfuscator"
		),
		'preserve_file_names' => array( /*idea*/
			"index.php",
			"Obfuscator.php"
		)
	);
	private $files_array = array();
	private $items_array = array();

	/**
	 * constructor
	 */
	public function __construct(){
		//do nothing
	}

	/**
	 * run the code obfuscator
	 */
	public function run(){
        if($this->checkSettings()){
    		$this->buildCopyOfProject();
    		$this->indexAllFiles($this->getSetting('destination_dir'));
    		$this->tokenizeIndexedFilesContent();
    		$this->obfuscateCode();

    		echo "Project has successfully been obfuscated.<br>Destination: ".$this->getSetting('destination_dir');
        }
        else {
            echo "Error with the settings.";
        }
	}

    /**
     * checks the settings and corrects possible errors
     * @return boolean
     */
    private function checkSettings(){
        $return = true;

        //change windows path to unix-style path
        if (DIRECTORY_SEPARATOR != '/') {
            $this->setSetting('source_dir',str_replace(DIRECTORY_SEPARATOR,'/',$this->getSetting('source_dir')));
            $this->setSetting('destination_dir',str_replace(DIRECTORY_SEPARATOR,'/',$this->getSetting('destination_dir')));
        }

        //set paths with slash at the end
        if(substr($this->getSetting('source_dir'),-1) != "/"){
            $this->setSetting('source_dir',$this->getSetting('source_dir')."/");
        }
        if(substr($this->getSetting('destination_dir'),-1) != "/"){
            $this->setSetting('destination_dir',$this->getSetting('destination_dir')."/");
        }

        //check if the source directory exists
        if(!file_exists($this->getSetting('source_dir'))){
            $return = false;
        }
        return $return;
    }

	/**
	 * build a copy of the source_dir in the destination_dir
	 */
	private function buildCopyOfProject(){
		$sourcePath = $this->getSetting('source_dir');
		$destinationPath = $this->getSetting('destination_dir');
		Utilities::recurseCopy($sourcePath,$destinationPath);
	}

	/**
	 * index all files in an array
	 * recursive call!
	 */
	private function indexAllFiles($src){
		$dir = opendir($src);
        while(false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->indexAllFiles($src . '/' . $file);
                }
                else {
					$this->addFileToArray($src,$file);
                }
            }
        }
        closedir($dir);
	}

	/**
	 * add php-file to files array
	 */
	private function addFileToArray($path,$file){
		$fileextension = pathinfo($path.'/'.$file, PATHINFO_EXTENSION);
		if($fileextension == "php"){
			$this->files_array[] = array('path'=>$path,'file'=>$file);
			//$this->addItemToArray('include',$file);
		}
	}

	/**
	 * add item (variable, function etc.) to an array
	 * only unique, no doubles
	 */
	private function addItemToArray($type,$nameOrig){
		$item_to_add = array('type'=>$type,'name_orig'=>$nameOrig,'name_replace'=>"");
		if(!in_array($item_to_add, $this->items_array, true)){
			array_push($this->items_array, $item_to_add);
		}
	}

	/**
	 * tokenize the content of all the indexed files
	 * all variables, functions, etc. to an array
	 */
	private function tokenizeIndexedFilesContent(){
		foreach($this->files_array as $item){
			$tokens = [];
			$file = $item['path'] . '/' . $item['file'];
			if (file_exists($file)) {
				$fileContent = file_get_contents($file);
				$tokens = token_get_all($fileContent);
			}
			for($i=0;$i<count($tokens);$i++){
				//Variable
				if($tokens[$i][0] == T_VARIABLE){
					if($tokens[$i][1] == "\$_SESSION"){
						$this->addItemToArray('session-variable',substr($tokens[$i+2][1],1,-1));//remove the ''
					}
					else {
						if(!in_array($tokens[$i][1], $this->getIllegalItems())){
							$this->addItemToArray('variable',substr($tokens[$i][1],1));//remove the $
						}
					}
				}
				//Function
				elseif($tokens[$i][0] == T_FUNCTION && $tokens[$i][1] == "function"){
					if(!in_array($tokens[$i+2][1], $this->getIllegalItems())){
						$this->addItemToArray('function',$tokens[$i+2][1]); //+1 = whitespace, +2 = string
					}
				}
				//Interface
				elseif($tokens[$i][0] == T_INTERFACE){
					$this->addItemToArray('interface',$tokens[$i+2][1]);
				}
				//Constant
				elseif($tokens[$i][0] == T_STRING){
					if($tokens[$i][1] == "define"){
						$this->addItemToArray('constant',substr($tokens[$i+2][1],1,-1)); //remove the ''
					}
				}
				elseif($tokens[$i][0] == T_CONST){//T_CONSTANT_ENCAPSED_STRING
					$this->addItemToArray('constant',$tokens[$i+2][1]);
				}
				//Class
				//elseif($tokens[$i][0] == T_CLASS){
					//$this->addItemToArray('class',$tokens[$i+2][1]);
				//}
				//File
				//elseif($tokens[$i][0] == T_REQUIRE || $tokens[$i][0] == T_REQUIRE_ONCE || $tokens[$i][0] == T_INCLUDE || $tokens[$i][0] == T_INCLUDE_ONCE){
					//$this->addItemToArray('include',substr($tokens[$i+2][1],1,-1));
				//}
			}
		}
		usort($this->items_array, array('self','sortArrayDescendingCaseInsensitive'));//dont keep the index
	}

	/**
	 * sort array case insensitive by key
	 * solve these problems:
	 * 1: $i, $item -> $abc, $abctem !! => sort array desc
	 * 2: myfunction, $myfunctionvar -> abc, $abcvar !! ==> explicit replace "function name" and "$myfunctionvar"
	 */
	private static function sortArrayDescendingCaseInsensitive($x, $y){
		if (strtolower($x['name_orig']) == strtolower($y['name_orig'])){
			return 0;
		}
		else if (strtolower($x['name_orig']) > strtolower($y['name_orig'])){
			return -1;
		}
		else {
			return 1;
		}
	}

	/**
	 * build array with unique 6 digit names, same length array like array of items
	 * when replace no problem will occur, because all are same long and unique!
	 */
	private function buildArrayWithRandomKeys($arraySize){
		$array = [];
		if($arraySize > 0 && (count($array) < $arraySize)){
			do{
				$key = Utilities::getRandomKey(6);
				if(!in_array($key, $array)){
					array_push($array, $key);
				}
			} while(count($array) < $arraySize);
		}
		return $array;
	}

	/**
	 * obfuscate the code
	 * remove whitespace and comments
	 * replace all variables, functions etc. with the unique random string per item
	 * overwrite the contents of the file
	 */
	private function obfuscateCode(){
		$unique_key_array = $this->buildArrayWithRandomKeys(count($this->getItemsArray()));
		foreach($this->files_array as $file_item){
			$infilename = $file_item['path'].'/'.$file_item['file'];
			$fileContent = php_strip_whitespace($infilename); //DEBUG: file_get_contents($infilename)
			$key_index = 0;
			$outfilename = "";
			if($fileContent != ""){
				foreach($this->items_array as $all_items_item){
					$type = $all_items_item['type'];
					$nameOrig = $all_items_item['name_orig'];
					$nameReplace = $unique_key_array[$key_index];
					switch($type){
						case "variable":
							//1x $normal
							$fileContent = str_replace("\$".$nameOrig,"\$".$nameReplace,$fileContent);
							//1x $this->normal
							$name1 = "\$this->".$nameOrig;
							$name2 = "\$this->".$nameReplace;
							$fileContent = str_replace($name1,$name2,$fileContent);
							break;
						case "session-variable":
							//1x $_SESSION['normal']
							$fileContent = str_replace("\$_SESSION['".$nameOrig."']","\$_SESSION['".$nameReplace."']",$fileContent);
							break;
						case "function":
							//1x explicit "function name"
							$name1 = "function ".$nameOrig;
							$name2 = "function ".$nameReplace;
							$fileContent = str_replace($name1,$name2,$fileContent);
							//1x normal
							$fileContent = str_replace($nameOrig,$nameReplace,$fileContent);
							break;
						case "interface":
							//1x normal
							$fileContent = str_replace($nameOrig,$nameReplace,$fileContent);
							break;
						case "constant":
							//1x normal
							$fileContent = str_replace($nameOrig,strtoupper($nameReplace),$fileContent);
							break;
						//case "class":
							//1x normal
							//$fileContent = str_replace($nameOrig,$nameReplace,$fileContent);
							//break;
						//case "include":
							//break;
					}
					$key_index++;
				}
			}
			//overwrite existing content of the copied file with the obfuscated code
			$filehandler = fopen($infilename, "w") or die("can't open file");
			fwrite($filehandler, $fileContent);
			fclose($filehandler);
		}
	}

    /**
	 * sets a settings-value based on a given key
	 */
	public function setSetting($key,$value){
        if(array_key_exists($key, $this->settings)){
            $this->settings[$key] = $value;
        }
	}

	/**
	 * returns a settings-value based on a given key
	 */
	public function getSetting($key){
        $value = null;
        if(array_key_exists($key, $this->settings)){
            $value = $this->settings[$key];
        }
        return $value;
	}

    /**
	 * returns the illegal item array (more readable than getSessing($key))
	 */
	private function getIllegalItems(){
		return $this->settings['illegal_items'];
	}

    /**
	 * returns the array with all the files
	 */
	private function getFilesArray(){
		return $this->files_array;
	}

	 /**
	 * returns the array with all the items
	 */
	private function getItemsArray(){
		return $this->items_array;
	}
}
?>
