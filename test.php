#!/usr/bin/php
<?php

/*

For all Ubus Calls, refer to:
https://openwrt.org/docs/techref/ubus

*/

// Fill this 
$cfg['host']="OpenWRT IP ADDRESS";
$cfg['user']="root";
$cfg['pass']="PASSWORD";

$debug=false;



// --------------------------------------------------------------------------

require_once(dirname(__FILE__)."/src/owa.php");

$owa=new OpenWrtApi("http://".$cfg['host'] , $debug);
$owa->UbusLogin($cfg['user'], $cfg['pass']);


echo "<pre>";

TestUbusCall('system','board');
TestUbusCall('system','info');

// grab all interfaces
if($devices=TestUbusCall('luci-rpc','getWirelessDevices')){
	foreach($devices as $dev => $info){
		foreach($info['interfaces'] as $interface ){
			$ifs[]=$interface['ifname'];
		}
	}

	// grab all stations / interface
	if(is_array($ifs)){
		echo "--- STATIONS ----------------------------------------------------------------\n";
		$r=$owa->UbusListStations($ifs);
		print_r($r);		
	}
}


// -------------------------------------------------------------------
function TestUbusCall($path,$meth, $arg=array()){
	global $owa;
	echo str_pad("## $path - $meth ",80,'#')."\n";
	$r=$owa->UbusCall($path,$meth, $arg);
	if($r){
		print_r($r);
		return $r;
	}
	else{
		echo " -> ERROR: ";
		print_r($owa->GetErrors());
	}
	echo "\n";
}

?>