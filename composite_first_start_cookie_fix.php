<?
// cookies settings
$domain_raw = str_replace("www.", "", $_SERVER['SERVER_NAME']);
define("COOKIE_DOMAIN", ".".$domain_raw);
$cookie_life = time()+3600*24*30;   // one month

//unset($_COOKIE['BITRIX_SM_PK']);
//setcookie("BITRIX_SM_PK", "", -1, "/");
setcookie("BITRIX_SM_PK", "", -1, "/");
if(empty($_COOKIE["BITRIX_SM_PK"])){
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/dbconn.php");
	define("RUCENTER_GEOIP_INSTALLED", true);
	error_reporting(E_ERROR);

	// get user ip
	$userIp = $_SERVER['REMOTE_ADDR'];
	if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
		$userIp = $_SERVER["HTTP_X_FORWARDED_FOR"];
	else if (isset($_SERVER["HTTP_X_REAL_IP"]))
		$userIp = $_SERVER["HTTP_X_REAL_IP"];

	if ($_SERVER['HTTP_NS_CLIENT_IP'] != '')
		$userIp = $_SERVER['HTTP_NS_CLIENT_IP'];

	$savedCityID = $_SESSION['CITY_ID'];

	// если город указан явно и ранее не обрабатывался

	$QUERY = "
		SELECT * from rucenter_ranges AS RR
		WHERE
			RR.IP_INT_FROM<=inet_aton('".$userIp."') AND RR.IP_INT_TO>=inet_aton('".$userIp."') AND RR.COUNTRY_ID='RU'
	";

	$mysqli = new mysqli($DBHost, $DBLogin, $DBPassword, $DBName);
	if ($mysqli->connect_errno) {
		echo "Не удалось подключиться к MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	}
	$res = mysqli_query($mysqli, $QUERY);
	$arIpInfo = mysqli_fetch_assoc($res);

	// Достаем id элемента инфоблока из справочника городов
	$QUERY_ELEMENT = "
		SELECT * from b_iblock_element AS ELEM
		WHERE
			ELEM.IBLOCK_ID = 20 AND ELEM.XML_ID = ".$arIpInfo["CITY_ID"]."
	";
	$res = mysqli_query($mysqli, $QUERY_ELEMENT);
	$arCityInfo = mysqli_fetch_assoc($res);

	//echo "<pre style=\"display:block;\">"; print_r($arCityInfo); echo "</pre>";

	$_COOKIE["BITRIX_SM_PK"] = "page_region_".$arCityInfo["ID"];
	setcookie("BITRIX_SM_PK", "page_region_".$arCityInfo["ID"], 0, "/");

}?>
