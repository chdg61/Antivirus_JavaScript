<?
/**
 * Version 1.1
 *
 *
*/
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?><?
set_time_limit(0);

if($_GET['clear_cache']=="Y"){
    $_SESSION['JS_ANTIVIRUS_FILES']=array();
    exit("������ �������");
}

if(!$_SESSION['JS_ANTIVIRUS_FILES'] || !is_array($_SESSION['JS_ANTIVIRUS_FILES'])){
    $_SESSION['JS_ANTIVIRUS_FILES']=array();
}

function prn_v($obj){
    $dump="<div style='font-size: 11px; font-family: tahoma;'>".print_r($obj, true)."</div>";
    $files = $_SERVER["DOCUMENT_ROOT"]."/log_js_v.html";
    $fp = fopen( $files, "a+" ) or die("�� ���� �������");
    if (fwrite( $fp, $dump) === FALSE) {
        //AddMessage2Log("�� ���� ���������� ������ � ���� ($filename)");
    }
    fclose( $fp );
}

function backup_js($file,$path,$dir="/bb/"){
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

function GetDirFilesR(){
    $limit_file=40;
    $limit_file_count=0;
    $dir_iterator = new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT']);
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $file){
        if($file->isFile() && !in_array($file->getPathname(),$_SESSION['JS_ANTIVIRUS_FILES']) && ToLower(substr($file->getFilename(),-3)==".js")){

            if($limit_file_count>=$limit_file){
                echo "��� ��������";
                exit();
            }
            
            $file_=file($file);
            
            $last_line=end($file_);
            $last_line=trim($last_line);
            
            if(ToLower(substr($last_line,0,3))=="var" && strlen($last_line)>1000 && stripos($last_line,"\x")!==false){
                
                $limit_file_count++;
                
                echo "<div style='color:red;'>------------------------------------------------------------------------------------<br>";
                echo "����: ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file). "<br>";
                echo "Line [".count($file_)."]: <div style='background:#F0F0F0;padding:10px;color:black;'>".substr($last_line,0,400). "</div><br>";
                array_pop($file_);
                if(backup_js($file->getFilename(),$file->getPathname())){
                    $_SESSION['JS_ANTIVIRUS_FILES'][]=$file->getPathname();
                    if(file_put_contents($file,$file_)){
                        $_SESSION['JS_ANTIVIRUS_FILES'][]=$file->getPathname();
                        prn_v("<div style='color:green;'>����: ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)." �������.<br><div style='background:#F0F0F0;padding:10px;color:black;'>".$last_line."</div></div>");
                        echo "�������! <br>";
                    }else{
                        prn_v("<div style='color:red;'>����: ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)." �� �������.�� ������ �������<br><div style='background:#F0F0F0;padding:10px;color:black;'>".$last_line."</div></div>");
                        echo "����� ������, ���� �� �������<br>";
                    }
                }else{
                    prn_v("<div style='color:red;'>����: ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)." �� �������.� ������ �� �������<br><div style='background:#F0F0F0;padding:10px;color:black;'>".$last_line."</div></div>");
                    echo "����� �� ������, ���� �� �������<br>";
                }
                echo "</div>";
            }elseif(stripos($last_line,'c=3-1;i=c-2;if(window.document)if(parseInt("0"+"1"+"2"+"3")===83)try{')!==false){
                $limit_file_count++;
                
                echo "<div style='color:red;'>------------------------------------------------------------------------------------<br>";
                echo "����: ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file). "<br>";
                echo "Line [".count($file_)."]: <div style='background:#F0F0F0;padding:10px;color:black;'>".substr($last_line,0,400). "</div><br>";
                array_pop($file_);
                if(backup_js($file->getFilename(),$file->getPathname())){
                    $_SESSION['JS_ANTIVIRUS_FILES'][]=$file->getPathname();
                    if(file_put_contents($file,$file_)){
                        $_SESSION['JS_ANTIVIRUS_FILES'][]=$file->getPathname();
                        prn_v("<div style='color:green;'>����: ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)." �������.<br><div style='background:#F0F0F0;padding:10px;color:black;'>".$last_line."</div></div>");
                        echo "�������! (������ ������ ������!)<br>";
                    }else{
                        prn_v("<div style='color:red;'>����: ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)." �� �������.�� ������ �������<br><div style='background:#F0F0F0;padding:10px;color:black;'>".$last_line."</div></div>");
                        echo "����� ������, ���� �� �������<br>";
                    }
                }else{
                    prn_v("<div style='color:red;'>����: ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)." �� �������.� ������ �� �������<br><div style='background:#F0F0F0;padding:10px;color:black;'>".$last_line."</div></div>");
                    echo "����� �� ������, ���� �� �������<br>";
                }
                echo "</div>";
                
            }elseif(stripos($last_line,"Boolean().prototype")!==false || stripos($last_line,"Date().prototype")!==false){
                echo "<div style='color:#AF8D43;'>------------------------------------------------------------------------------------<br>";
                echo "�������������� ���� (��������� ������ �������� Boolean().prototype): ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)."<br>";
                echo "<div style='background:#F0F0F0;padding:10px;color:black;'>".$last_line."</div><br>";
                echo "</div>";    
            }elseif(ToLower(substr($last_line,0,3))=="var"){
                echo "<div style='color:#AF8D43;'>------------------------------------------------------------------------------------<br>";
                echo "�������������� ���� (��������� ������ ���������� � var): ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)."<br>";
                echo "<div style='background:#F0F0F0;padding:10px;color:black;'>".$last_line."</div><br>";
                echo "</div>";
            }elseif(strlen($last_line)>500){
                echo "<div style='color:#AF8D43;'>------------------------------------------------------------------------------------<br>";
                echo "�������������� ���� (��������� ������ ������ 500 �������� � ������): ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)."<br>";
                echo "<div style='background:#F0F0F0;padding:10px;color:black;'>".$last_line."</div><br>";
                echo "</div>";
            }elseif(stripos($last_line,"\x")!==false){
                echo "<div style='color:#AF8D43;'>------------------------------------------------------------------------------------<br>";
                echo "�������������� ���� (��������� ������ �������� ������� '\x'): ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)."<br>";
                echo "<div style='background:#F0F0F0;padding:10px;color:black;'>".$last_line."</div><br>";
                echo "</div>";
            }else{
                prn_v("<div style='color:green;'>����: ".str_replace($_SERVER['DOCUMENT_ROOT'],"",$file)." �� �������� �����.</div>");
                $_SESSION['JS_ANTIVIRUS_FILES'][]=$file->getPathname();
            }
        }
    }
    
    echo "������� ���������";
}

GetDirFilesR();
//var_dump($_SERVER['DOCUMENT_ROOT']);

?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>