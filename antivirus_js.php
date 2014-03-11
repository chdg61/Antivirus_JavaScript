<?
/**
 *
 * ��������� !!!
 * @version 1.2
 *
*/
class AntiVirusChdg{
	private $SESSION_NAME = "JS_ANTIVIRUS_FILES";
	public $ext_files = array(".js",".html",".htm");
	private $limit_file = 40;
	private $limit_file_count = 0;
	private $patch = false;

	public function __construct($patch = false){
		set_time_limit(0);
		$this->patch = ($patch)?rtrim($_SERVER['DOCUMENT_ROOT'],"/").$patch:$_SERVER['DOCUMENT_ROOT'];
		$this->UnsetSession();
		$this->SetSession();
		$this->GetDirFiles();
	}

	private function UnsetSession(){
		if($_GET['clear_cache']=="Y"){
			$_SESSION[$this->SESSION_NAME]=array();
			exit("������ �������");
		}
	}

	private function SetSession(){
		if(!$_SESSION[$this->SESSION_NAME] || !is_array($_SESSION[$this->SESSION_NAME])){
			$_SESSION[$this->SESSION_NAME]=array();
		}
	}

	public function GetDirFiles(){

		$dir_iterator = new RecursiveDirectoryIterator($this->patch);
		$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

		foreach ($iterator as $file){
			if($this->CheckFile($file)){

				$this->CheckEnd();

				$file_string = file($file);
				$ext = ToLower(substr($file->getFilename(),-3));

				switch($ext){
					case ".js":
						//$this->JSTreat($file_string,$file);
					break;
					case ".html":
					case ".htm":
						$this->HTMLTreat($file_string,$file);
					break;
				}
			}
		}
		echo "������� ���������";
	}

	protected function CheckFile($file){
		if($file->isFile() &&
		   !in_array($file->getPathname(),$_SESSION[$this->SESSION_NAME]) &&
		   in_array(ToLower(substr($file->getFilename(),-3)),$this->ext_files)
		   ){
			return true;
		}else{
			return false;
		}
	}

	private function CheckEnd(){
		if($this->limit_file_count >= $this->limit_file){
			echo "��� ��������";
			exit();
		}
	}

	private function JSTreat($file_string,$file){
		$last_line=end($file_string);
		$last_line=trim($last_line);

		if(ToLower(substr($last_line,0,3))=="var" && strlen($last_line)>1000 && stripos($last_line,"\x")!==false){

			$this->JSVirusVar($file_string,$file,$last_line);

		}elseif(stripos($last_line,'c=3-1;i=c-2;if(window.document)if(parseInt("0"+"1"+"2"+"3")===83)try{')!==false){

			$this->JSVirusHash($file_string,$file,$last_line);

		}elseif(stripos($last_line,"Boolean().prototype")!==false || stripos($last_line,"Date().prototype")!==false){

			$this->JSsuspicionBoolean($file_string,$file,$last_line);

		}elseif(ToLower(substr($last_line,0,3))=="var"){

			$this->JSsuspicionVar($file_string,$file,$last_line);

		}elseif(strlen($last_line)>500){

			$this->JSsuspicionLongLine($file_string,$file,$last_line);

		}elseif(stripos($last_line,"\x")!==false){

			$this->JSsuspicionEndCharX($file_string,$file,$last_line);

		}else{

			$this->JSOk($file_string,$file,$last_line);

		}
	}

	private function JSVirusVar($file_string,$file,$last_line){
		$this->limit_file_count++;
		$file_data = $this->JSTreatCurrentFile($file_string,$file,$last_line);
		$this->prn_v($file_data['MESS']);
		echo $this->FormatTreat($file_string,$file,$last_line,$file_data['STATUS']);
	}

	private function JSVirusHash($file_string,$file,$last_line){
		$this->limit_file_count++;
		$file_data = $this->JSTreatCurrentFile($file_string,$file,$last_line,true);
		$this->prn_v($file_data['MESS']);
		echo $this->FormatTreat($file_string,$file,$last_line,$file_data['STATUS']);
	}

	private function JSsuspicionBoolean($file_string,$file,$last_line){
		echo $this->FormatText($file,$last_line,"��������� ������ �������� Boolean().prototype");
	}

	private function JSsuspicionVar($file_string,$file,$last_line){
		echo $this->FormatText($file,$last_line,"��������� ������ ���������� � var");
	}

	private function JSsuspicionLongLine($file_string,$file,$last_line){
		echo $this->FormatText($file,$last_line,"��������� ������ ������ 500 �������� � ������");
	}

	private function JSsuspicionEndCharX($file_string,$file,$last_line){
		echo $this->FormatText($file,$last_line,"��������� ������ �������� ������� '\x'");
	}

	private function JSOk($file_string,$file,$last_line){
		$this->prn_v("<div style='color:green;'>����: ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)." �� �������� �����.</div>");
		$_SESSION[$this->SESSION_NAME][]=$file->getPathname();
	}

	private function JSTreatCurrentFile($file_string,$file,$last_line,$two_virus = false){
		array_pop($file_string);
		if($this->backup_js($file->getFilename(),$file->getPathname())){
			$_SESSION[$this->SESSION_NAME][]=$file->getPathname();
			if(file_put_contents($file,$file_string)){
				$_SESSION[$this->SESSION_NAME][]=$file->getPathname();
				return $this->FormatGoodHelpFileAndGoodBackup($file,$last_line,$two_virus);
			}else{
				return $this->FormatBadHelpFileAndGoodBackup($file,$last_line);
			}
		}else{
			return $this->FormatBadBackup($file,$last_line);
		}
	}

	private function HTMLTreat($file_string,$file){

		echo "<pre>";print_r($file);echo "</pre>";
		if(preg_match('/<iframe(?:.*?)src="(?:.*?)"(?:.*?)><\/iframe>/',$file_string,$matches)){
			echo "<pre>";print_r($matches);echo "</pre>";

		}else{

			//$this->JSOk($file_string,$file,$last_line);

		}
	}

	private function FormatTreat($file_string,$file,$last_line,$status){
		$file_name = str_replace($_SERVER['DOCUMENT_ROOT'],"",$file);
		$count = count($file_string);
		$last_line_short = substr($last_line,0,400);

$return = <<<TEXT
<div style='color:red;'>
	------------------------------------------------------------------------------------
	<br>
	����: $file_name<br>
	Line [$count]:
		<div style='background:#F0F0F0;padding:10px;color:black;'>$last_line_short</div><br>
		$status<br>
</div>
TEXT;

		return $return;
	}

	private function FormatText($file,$last_line,$message){
		return "<div style='color:#AF8D43;'>
			------------------------------------------------------------------------------------<br>
			�������������� ���� (".$message."): ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)."<br>
			<div style='background:#F0F0F0;padding:10px;color:black;'>".$last_line."</div><br>
		</div>";
	}

	private function FormatGoodHelpFileAndGoodBackup($file,$last_line,$two_virus = false){
		return array(
			"MESS" => "<div style='color:green;'>����: ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)." �������.<br><div style='background:#F0F0F0;padding:10px;color:black;'>".$last_line."</div></div>",
			"STATUS" => ($two_virus)?"�������! (������ ������ ������!)<br>":"�������! <br>"
		);
	}

	private function FormatBadHelpFileAndGoodBackup($file,$last_line){
		return array(
			"MESS" => "<div style='color:red;'>����: ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)." �� �������.�� ������ �������<br><div style='background:#F0F0F0;padding:10px;color:black;'>".$last_line."</div></div>",
			"STATUS" => "����� ������, ���� �� �������<br>"
		);
	}

	private function FormatBadBackup($file,$last_line){
		return array(
			"MESS" => "<div style='color:red;'>����: ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)." �� �������.� ������ �� �������<br><div style='background:#F0F0F0;padding:10px;color:black;'>".$last_line."</div></div>",
			"STATUS" => "����� �� ������, ���� �� �������<br>"
		);
	}

	private function prn_v($obj){
		$dump="<div style='font-size: 11px; font-family: tahoma;'>".print_r($obj, true)."</div>";
		$files = $_SERVER["DOCUMENT_ROOT"]."/log_js_v.html";
		$fp = fopen( $files, "a+" ) or die("�� ���� �������");
		if (fwrite( $fp, $dump) === FALSE) {
			//AddMessage2Log("�� ���� ���������� ������ � ���� ($filename)");
		}
		fclose( $fp );
	}

	private function backup_js($file,$path,$dir="/bb/"){
		$dir=$_SERVER['DOCUMENT_ROOT'].$dir;
		if(is_dir($dir)){
			$new_file_name=$file."_".time();
			if(copy($path,$dir.$new_file_name)){
				$dump="<div>".$new_file_name." :: ".$path."</div>";
				$log = $dir."/log.html";
				$fp = fopen( $log, "a+" ) or die("�� ���� �������");
				@fwrite( $fp, $dump);
				fclose( $fp );
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
}
?>
<?
//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");


$av = new AntiVirusChdg();
$av->GetDirFiles();


?><?//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>