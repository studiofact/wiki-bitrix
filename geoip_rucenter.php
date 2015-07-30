<?
/*
    Download and import geo ip base from RUCenter.
        v1.1
    
    Created by coldshine, Articul Media.
        bitrixdev@gmail.com
        29.03.2012

	Edited by Konstantin Lekh, Articul Media
		11.08.2013
*/
function Update_RuCenter_City_IP()
{
	global $DB;
	set_time_limit(0);

	/*CONFIG*/
	/*remote*/
	$ip_file_name = 'geo_files.tar.gz';
	$ip_file_path = 'http://ipgeobase.ru/files/db/Main';
	$ip_file_url = $ip_file_path.'/'.$ip_file_name;

	$ip_file_encode = "UTF-8"; //!important

	/*local*/
	$ipgeobase_path = $_SERVER["DOCUMENT_ROOT"].'/upload/ipgeobase';
	$tmp_path = $ipgeobase_path.'/tmp';
	global $log_path;
	$log_path = $ipgeobase_path.'/log';

	$tmp_tar_file = $tmp_path.'/'.$ip_file_name;
	$ip_tmp_file  = $tmp_path.'/cidr_optim.txt';

	$mysql_ranges_table = 'rucenter_ranges';

	/*LOGIC*/
	MyAddMessage2Log('Script started', true);
	//wget - will overwrite original file if the size or timestamp change
	MyAddMessage2Log('Trying to wget '.$ip_file_url);
	system('wget -N -P '.$tmp_path.' '.$ip_file_url.' -o '.$log_path.'/wget.log');
	$wget_log = file($log_path.'/wget.log');

	$success_download = false;
	foreach($wget_log as $row)
	{
	    if(strpos($row, "SUCCESS")!==false || strpos($row, "saved")!==false)
	    {
	        $success_download = true; 
			MyAddMessage2Log("Wget was succesefull!");
	    }
	}

	//parse & import to mysql
	if($success_download)
	{    	
		MyAddMessage2Log("Begin to untar ".$tmp_tar_file.' to '.$tmp_path);
	    system('tar -C '.$tmp_path.' -xzvf '.$tmp_tar_file.' > '.$log_path.'/tar.log'); //untar    
		if(file_exists($ip_tmp_file))
		{	        
			//parse IP's from file to array  
	        $arIP = file2array($ip_tmp_file, $ip_file_encode);
			
			if(is_array($arIP) && count($arIP))
			{		
				MyAddMessage2Log('IP range file parse to array - OK. Begin import to MySQL');
				$DB->Query("TRUNCATE TABLE ".$mysql_ranges_table); //delete old data
				foreach($arIP as $arItem)
				{   
					$query = "
						INSERT INTO ".$mysql_ranges_table."(
							IP_INT_FROM, 
							IP_INT_TO, 
							IP_RANGE, 
							COUNTRY_ID, 
							CITY_ID
						) 
						VALUES(
							'".trim($arItem[0])."', 
							'".trim($arItem[1])."', 
							'".trim($arItem[2])."', 
							'".trim($arItem[3])."', 
							'".(intval(trim($arItem[4])) > 0 ? $arItem[4] : "")."'
						)
					";
					$DB->Query($query);
				}
				MyAddMessage2Log('IP ranges MySQL import - OK.');
				unset($arIP);
				unlink($ip_tmp_file);
			} 
			else 
			{
				MyAddMessage2Log('Couldn\'t parse array from '.$ip_tmp_file);
			}
	    } 
	    else 
	    {
			MyAddMessage2Log($ip_tmp_file.' doesnt exist. Check '.$tmp_tar_file);
		}
	} 
	else 
	{
		MyAddMessage2Log('Wget failed or file didnt changed. See '.$log_path.'/wget.log for details.');
	}

	MyAddMessage2Log('Done!');
}

//parse file
function file2array($file_path, $ip_file_encode){
    if(!$file_path)
    {
		MyAddMessage2Log('Error in file2array function: $file_path is not set.');	
        return false;  
	}
    
    $arRows = file($file_path);      
	if(!is_array($arRows))
	{
		MyAddMessage2Log('Error in file2array function: couldn\'t get array from '.$file_path);	
        return false;  
	}
		
    $arOut = array();        
    foreach($arRows as $row)
    {
        if($ip_file_encode && $ip_file_encode!="UTF-8" && strpos($file_path, "cities")!==false)
            $row = iconv($ip_file_encode, "UTF-8", $row);
        
        $arOut[] = explode("\t", $row); 
    }          

    return $arOut;
}

function MyAddMessage2Log($mess, $start=false)
{
	global $log_path;
	if($start)
		$mess = "\n-----\n".date("d.m.Y h:i:s")." - ".$mess."\n";
	else
		$mess = date("d.m.Y h:i:s")." - ".$mess."\n";
	$fh = fopen($log_path."/log", "a");
	fwrite($fh, $mess);
	fclose($fh);
}
