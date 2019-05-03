<?php
// error_reporting(E_ALL);
//This is a more advanced version of the forecast script
//It uses file caching and feed failure to better handle when NOAA is down
//
//  Version 2.00 - 15-Jan-2007 - modified Tom's script for XHTML 1.0-Strict
//  Version 2.01 - 14-Feb-2007 - modified for ERH->CRH.noaa.gov redirects (no point forecasts)
//  Version 2.02 - 02-Mar-2007 - added auto-failover to CRH and better include in page.
//  Version 2.03 - 29-Apr-2007 - modified for /images/wtf -> /forecast/images change
//  Version 2.04 - 05-Jun-2007 - improvement to auto-failover
//  Version 2.05 - 29-Jun-2007 - additional check for alternative no-icon forecast, then failover.
//  Version 2.06 - 24-Nov-2007 - rewrite for zone forecast, intrepret icons from text-only forecast
//  Version 2.07 - 24-Nov-2007 - support new zone forecast with different temp formats
//  Version 2.08 - 25-Nov-2007 - fix zone forecast icons, new temp formats supported
//  Version 2.09 - 26-Nov-2007 - add support for new temperature phrases and below zero temps
//  Version 2.10 - 17-Dec-2007 - added safety features from Mike Challis http://www.carmosaic.com/weather/
//  Version 2.11 - 20-Dec-2007 - added cache-refresh on request, fixed rising/falling temp arrow
//  Version 2.12 - 31-Dec-2007 - fixed New Year"s to New Year's display problem
//  Version 2.13 - 01-Jan-2008 - added integration features for carterlake/WD/PHP/AJAX template set
//  Version 2.14 - 14-Jan-2009 - corrected Zone forecast parsing for below zero temperatures
//  Version 2.15 - 28-Feb-2010 - updated Zone forecast parsing for new phrases
//  Version 2.16 - 14-Dec-2010 - added support for warning messages from NWS and $forecastwarnings string
//  Version 2.17 - 08-Jan-2011 - fixed validation issue when NWS uses '&' in condition
//  Version 2.18 - 27-Feb-2011 - added support for common cache directory
//  Version 3.00 - 12-Mar-2011 - support for multi-forecast added by Curly at http://www.ricksturf.com/
//  Version 3.01 - 01-Oct-2011 - added support for alternative animated icon set from http://www.meteotreviglio.com/
//  Version 3.02 - 05-Oct-2011 - corrected warning links to forecast.weather.gov
//  Version 3.03 - 02-Jul-2012 - added fixes for NWS website changes
//  Version 3.04 - 03-Jul-2012 - added fixes for W3C validation issues
//  Version 3.05 - 05-Jul-2012 - added fixes for Zone forecast use with new NWS website design
//  Version 3.06 - 07-Jul-2012 - fixed validation issue for Rising/Falling temp arrows with new NWS website design
//  Version 3.07 - 09-Aug-2012 - fixed failover to Zone forecast with new NWS website design
//  Version 3.08 - 23-Nov-2012 - fixed issue with Zone forecast parsing due to NWS website changes
//  Version 3.09 - 28-Jun-2013 - added fixes for Zone forecast parsing due to NWS website changes
//  Version 3.10 - 18-Nov-2013 - fixed issue with Zone forecast URL due to NWS website changes
//  Version 3.11 - 13-Mar-2014 - fixed point forecast text non-display due to NWS website changes
//  Version 3.12 - 15-Mar-2014 - fixes for Zone forecast, warnings and auto-correct old URLs
//  Version 3.13 - 17-Mar-2015 - fixes for Zone forecast w/new NWS site design
//  Version 3.14 - 31-Mar-2015 - fixes for NWS site changes
//  Version 3.15 - 13-May-2015 - fix for Zone forecast w/NWS website change
//  Version 3.16 - 15-May-2015 - fixes for different Zone forecast format in failover
//  Version 4.00 - 06-Jul-2015 - added support for DualImage processing
//  Version 4.01 - 07-Jul-2015 - fixed HTML for $forecasticons for new NWS website 
//  Version 4.02 - 07-Nov-2015 - fixed Zone forecast when using .gif icons issue
//  Version 4.03 - 27-Feb-2018 - switched to curl processing and https for weather.gov access
//  Version 4.04 - 12-Apr-2018 - fixes for Zone forecast Warning extraction for weather.gov changes
//  Version 4.05 - 21-May-2018 - fix for NWS point-printable forecast page change
//
$Version = 'advforecast2.php (multi) - V4.05 - 21-May-2018';
//
//import NOAA Forecast info
//data ends up in four different arrays:
//$forecasticons[x]  x = 0 thru 9   This is the icon and text around it
//$forecasttemp[x] x= 0 thru 9    This is forecast temperature with styling
//$forecasttitles[x]  x = 0 thru 12   This is the title word for the text forecast time period
//$forecasttext[x]  x = 0 thru 12  This is the detail text for the text forecast time period
//
//$forecastupdated  This is the time of last update
//$forecastcity    This is the city name for the forecast
//$forecastoffice  This is the NWS Office providing the forecast
//$forecastwarnings This is the text/links to NWS Warnings, Watches, Advisories, Outlooks, Special Statements
//
//Also, in order for this to work correctly, you need the NOAA icons (or make your own...
//there are 750!). These need to be placed in the path where the original NOAA icons
//are located. In my case, they are at: \forecast\images\
//properly (so make a folder in your web HTML root called "forecast", then make a folder in it
//called "images", and place the icons in this folder)
//
//http://saratoga-weather.org/saratoga-icons.zip
//
//URL below --MUST BE-- the Printable Point Forecast from the NOAA website
//
//Not every area of the US has a printable point forecast
//
//This script will ONLY WORK with a printable point forecast!
//
//To find yours in your area:
//
//Go to www.weather.gov
//Put your city, state in the search box and press Search
//Scroll down to the "Additional Forecasts & Info" on the page displayed
//Click on Printable Forecast
// copy the URL from your browser into the $fileName variable below.
// Also put your NOAA Warning Zone (like ssZnnn) in caps in the $NOAAZone variable below.
//
// also set your NOAA warning zone here to use for automatic backup in case
// the point printable forecast is not available.
//
// ----------------------SETTINGS---------------------------------------------

// V3.00 -- this following array can be used for multiple forecasts in standalone mode
//  for template use, add a $SITE['NWSforecasts'] entry in Settings.php to have these entries.
//  to activate the definitions below, replace the /* with //* to uncomment the array definition 
/*
$NWSforecasts = array(
 // the entries below are for testing use.. replace them with your own entries if using the script
 // outside the AJAX/PHP templates.
 // ZONE|Location|point-forecast-URL  (separated by | characters
"CAZ513|Saratoga, CA (WRH)|http://forecast.weather.gov/MapClick.php?CityName=Saratoga&state=CA&site=MTR&textField1=37.2639&textField2=-122.022&e=1&TextType=2",
"NEZ052|Omaha, NE (CRH)|http://forecast.weather.gov/MapClick.php?lat=41.2586&lon=-95.9378&unit=0&lg=english&FcstType=text&TextType=2",
"ALZ266|Gulf Shores, AL (SRH)|http://forecast.weather.gov/MapClick.php?CityName=Gulf+Shores&state=AL&site=MOB&textField1=30.27&textField2=-87.7015&e=0&TextType=2",
'MDZ022|Salisbury, MD (ERH)|http://forecast.weather.gov/MapClick.php?lat=38.36818&lon=-75.59761&unit=0&lg=english&FcstType=text&TextType=2',
'AKZ101|Anchorage, AK (ARH)|http://forecast.weather.gov/MapClick.php?lat=61.21806&lon=-149.90027780000003&unit=0&lg=english&FcstType=text&TextType=2',
'HIZ005|Honolulu, HI (HRH)|http://forecast.weather.gov/MapClick.php?lat=21.30694&lon=-157.85833330000003&unit=0&lg=english&FcstType=text&TextType=2',
'IAZ068|Riverdale, IA|http://forecast.weather.gov/MapClick.php?lat=41.5354&lon=-90.4671&unit=0&lg=english&FcstType=text&TextType=2',
'MEZ030|Bar Harbor, ME|http://forecast.weather.gov/MapClick.php?lat=44.76&lon=-67.5477&unit=0&lg=english&FcstType=text&TextType=2',
'TXZ147|Fairfield, TX|http://forecast.weather.gov/MapClick.php?&lat=31.7188&lon=-96.1655&lg=english&FcstType=text&TextType=2',
'SDZ021|Millbank, SD|http://forecast.weather.gov/MapClick.php?lat=45.194&lon=-96.6869&unit=0&lg=english&FcstType=text&TextType=2',
'MNZ034|Brainerd, MN|http://forecast.weather.gov/MapClick.php?lat=46.3544&lon=-94.1941&unit=0&lg=english&FcstType=text&TextType=2',
'COZ010|Vail, CO|http://forecast.weather.gov/MapClick.php?lat=39.5864&lon=-106.3822&unit=0&lg=english&FcstType=text&TextType=2',
'CAZ072|South Lake Tahoe, CA|http://forecast.weather.gov/MapClick.php?lat=38.93333&lon=-119.98333&unit=0&lg=english&FcstType=text&TextType=2',
'WAZ037|Colville, WA|http://forecast.weather.gov/MapClick.php?lat=48.5433&lon=-117.8951&unit=0&lg=english&FcstType=text&TextType=2',
'ILZ014|Hoffman Estates, IL|http://forecast.weather.gov/MapClick.php?lat=42.03921&lon=-88.11001&unit=0&lg=english&FcstType=text&TextType=2',
'NDZ027|Grand Forks,  ND|http://forecast.weather.gov/MapClick.php?lat=47.9169&lon=-97.072&unit=0&lg=english&FcstType=text&TextType=2',
'MTZ055|Bozeman, MT|http://forecast.weather.gov/MapClick.php?lat=45.6354&lon=-111.0633&unit=0&lg=english&FcstType=text&TextType=2',
"AZZ023|Phoenix|http://forecast.weather.gov/MapClick.php?CityName=Phoenix&state=AZ&site=PSR&textField1=33.646&textField2=-112.007&e=0&TextType=2",
'KSZ078|Dodge City, KS|http://forecast.weather.gov/MapClick.php?lat=37.7528&lon=-100.0171&unit=0&lg=english&FcstType=text&TextType=2',
'OKZ020|Stillwater, OK|http://forecast.weather.gov/MapClick.php?lat=36.1156&lon=-97.0584&unit=0&lg=english&FcstType=text&TextType=2',
'GAZ034|Lawrenceville, GA|http://forecast.weather.gov/MapClick.php?CityName=Lawrenceville&state=GA&site=FFC&lat=33.9495&lon=-83.9922&TextType=2',
'ARZ011|Rockhouse, AR|http://forecast.weather.gov/MapClick.php?lat=36.289&lon=-93.7258&unit=0&lg=english&FcstType=text&TextType=2',
'ARZ040|Mena, AR|http://forecast.weather.gov/MapClick.php?lat=34.6036&lon=-94.2631&unit=0&lg=english&FcstType=text&TextType=2',
'MOZ090|Springfield, MO|http://forecast.weather.gov/MapClick.php?lat=37.1962&lon=-93.2861&unit=0&lg=english&FcstType=text&TextType=2',
'MTZ019|Plentywood, MT|http://forecast.weather.gov/MapClick.php?lat=48.7747&lon=-104.5625&unit=0&lg=english&FcstType=text&TextType=2',
'ARZ044|Little Rock, AR|http://forecast.weather.gov/MapClick.php?lat=34.7224&lon=-92.3541&unit=0&lg=english&FcstType=text&TextType=2',
    "NYZ040|Hessville, NY|http://forecast.weather.gov/MapClick.php?CityName=Hessville&state=NY&site=MTR&textField1=42.8739&textField2=-74.687&e=1&TextType=2",
"NYZ049|Albany, NY|http://forecast.weather.gov/MapClick.php?CityName=Albany&state=NY&site=MTR&textField1=42.6525&textField2=-73.757&e=0&TextType=2",
"NYZ056|Binghamton, NY|http://forecast.weather.gov/MapClick.php?CityName=Binghamton&state=NY&site=MTR&textField1=42.0956&textField2=-75.910&e=0&TextType=2",
"MAZ017|Boston, MA|http://forecast.weather.gov/MapClick.php?CityName=Boston&state=MA&site=MTR&textField1=42.3586&textField2=-71.060&e=0&TextType=2",
"MNZ060|Robbinsdale, MN|http://forecast.weather.gov/MapClick.php?lat=45.02233988655459&lon=-93.34722518920898&site=mpx&unit=0&lg=en&FcstType=text&TextType=2"
); 
//*/

//
 $NOAAZone = 'CAZ513';  // change this line to your NOAA warning zone.
// set $fileName to the URL for the point-printable forecast for your area
 $fileName = "http://forecast.weather.gov/MapClick.php?CityName=Saratoga&state=CA&site=MTR&textField1=37.2639&textField2=-122.022&e=1&TextType=2";
//
#$iconDir = './forecast/imagesPNG-86x86/';
#$iconDir = './forecast/imagesGIF-55x58/';
$iconDir = './forecast/images/';
$iconType = '.jpg';        // default type='.jpg' -- use '.gif' for animated icons from http://www.meteotreviglio.com/
$cacheFileDir = './';      // default cache file directory
$iconHeight = 55;  // default height of conditions icon (saratoga-icons.zip)
$iconWidth  = 55;  // default width of conditions icon  (saratoga-icons.zip)
$refreshTime = 600; // default refresh of cache 600=10 minutes
// ----------------------END OF SETTINGS--------------------------------------
$forceDualIconURL = false; // for TESTING prior to 7-Jul-2015 when new icons were used by NWS
//
// overrides from Settings.php if available
if(file_exists('Settings.php')) { include_once('Settings.php'); }

global $SITE;
if (isset($SITE['NWSforecasts']))   {$NWSforecasts = $SITE['NWSforecasts']; }
if (isset($SITE['cacheFileDir']))   {$cacheFileDir = $SITE['cacheFileDir']; }
if (isset($SITE['noaazone'])) 	{$NOAAZone = $SITE['noaazone'];}
if (isset($SITE['fcsturlNWS'])) 	{$fileName = $SITE['fcsturlNWS'];}
if (isset($SITE['fcsticonsdir'])) 	{$iconDir = $SITE['fcsticonsdir'];}
if (isset($SITE['fcsticonstype'])) 	{$iconType = $SITE['fcsticonstype'];}
if (isset($SITE['fcsticonsheight'])) 	{$iconHeight = $SITE['fcsticonsheight'];}
if (isset($SITE['fcsticonswidth'])) 	{$iconWidth = $SITE['fcsticonswidth'];}
// end of overrides from Settings.php

$doDebug = (isset($_REQUEST['debug']) and preg_match('|y|i',$_REQUEST['debug']))?true:false;
// get the selected zone code
$haveZone = '0';
if (!empty($_GET['z']) && preg_match("/^[0-9]+$/i", htmlspecialchars($_GET['z']))) {
  $haveZone = htmlspecialchars(strip_tags($_GET['z']));  // valid zone syntax from input
} 
$DualImageAvailable = file_exists("./DualImage.php")?true:false;
#$DualImageAvailable = false;
if(!isset($NWSforecasts[0])) {
	// print "<!-- making NWSforecasts array default -->\n";
	$NWSforecasts = array("$NOAAZone||$fileName"); // create default entry
}
//  print "<!-- NWSforecasts\n".print_r($NWSforecasts,true). " -->\n";
// Set the default zone. The first entry in the $SITE['NWSforecasts'] array.
list($Nz,$Nl,$Nn) = explode('|',$NWSforecasts[0].'|||');
$NOAAZone = $Nz;
$NOAAlocation = $Nl;
$fileName = $Nn;
$newFormat = false;

if(!isset($NWSforecasts[$haveZone])) {
	$haveZone = 0;
}

// locations added to the drop down menu and set selected zone values
$dDownMenu = '';
for ($m=0;$m<count($NWSforecasts);$m++) { // for each locations
  list($Nzone,$Nlocation,$Nname) = explode('|',$NWSforecasts[$m].'|||');
  $dDownMenu .= "     <option value=\"".$m."\">".$Nlocation."</option>\n";
  if($haveZone == $m) {
    $NOAAZone = $Nzone;
    $NOAAlocation = $Nlocation;
    $fileName = $Nname;
  }
}


// build the drop down menu
$ddMenu = '';
// create menu if at least two locations are listed in the array
if (isset($NWSforecasts[0]) and isset($NWSforecasts[1])) {
	$ddMenu .= '<tr align="center">
      <td style="font-size: 14px; font-family: Arial, Helvetica, sans-serif">
      <script type="text/javascript">
        <!--
        function menu_goto( menuform ){
         selecteditem = menuform.logfile.selectedIndex ;
         logfile = menuform.logfile.options[ selecteditem ].value ;
         if (logfile.length != 0) {
          location.href = logfile ;
         }
        }
        //-->
      </script>
     <form action="" method="get">
     <p><select name="z" onchange="this.form.submit()">
     <option value=""> - Select Forecast - </option>
'.$dDownMenu.
		$ddMenu . '     </select></p>
     <div><noscript><pre><input name="submit" type="submit" value="Get Forecast" /></pre></noscript></div>
     </form>
    </td>
   </tr>
';
}

// This is version 1.2 with Ken's modifications from Saratoga Weather
// http://saratoga-weather.org/

// You can now force the cache to update by adding ?force=1 to the end of the URL

if ( empty($_REQUEST['force']) )
        $_REQUEST['force']="0";

$Force = $_REQUEST['force'];

$forceBackup = false;
if ($Force > 1) {$forceBackup = true; }

$cacheName = $cacheFileDir."forecast-".$NOAAZone."-$haveZone.txt"; 

// dont change the next line....
//$backupfileName = "http://forecast.weather.gov/MapClick.php?zoneid=$NOAAZone&TextType=2";
// new Zone URL with V3.10:
$backupfileName = "https://forecast.weather.gov/MapClick.php?zoneid=$NOAAZone&zflg=1";
// /MapClick.php?zoneid=CAZ513&zflg=1

if (isset($_REQUEST['sce']) && strtolower($_REQUEST['sce']) == 'view' ) {
   //--self downloader --
   $filenameReal = __FILE__;
   $download_size = filesize($filenameReal);
   header('Pragma: public');
   header('Cache-Control: private');
   header('Cache-Control: no-cache, must-revalidate');
   header("Content-type: text/plain");
   header("Accept-Ranges: bytes");
   header("Content-Length: $download_size");
   header('Connection: close');

   readfile($filenameReal);
   exit;
}
$fcstPeriods = array( // for filling in the '<period> Through <period>' zone forecasts.
'Monday','Monday Night',
'Tuesday','Tuesday Night',
'Wednesday','Wednesday Night',
'Thursday','Thursday Night',
'Friday','Friday Night',
'Saturday','Saturday Night',
'Sunday','Sunday Night',
'Monday','Monday Night',
'Tuesday','Tuesday Night',
'Wednesday','Wednesday Night',
'Thursday','Thursday Night',
'Friday','Friday Night',
'Saturday','Saturday Night',
'Sunday','Sunday Night'
);

$usingFile = "";

// autocorrect the point-forecast URL if need be
/* from: http://forecast.weather.gov/MapClick.php?CityName=Rathdrum&state=ID&site=MTR&textField1=47828&textField2=-116.842&e=0&TextType=2
// to: 
http://forecast.weather.gov/MapClick.php?lat=47.82761&lon=-116.8703167338295&unit=0&lg=english&FcstType=text&TextType=2
*/

$Status = "<!-- $Version -->\n<!-- NWS URL: $fileName -->\n<!-- zone=$NOAAZone -->\n";

if(preg_match('|textField1=|i',$fileName)) {
	$newlatlong = '';
	preg_match('|textField1=([\d\.]+)|i',$fileName,$matches);
	if(isset($matches[1])) {$newlatlong .= 'lat='.$matches[1];}
	preg_match('|textField2=([-\d\.]+)|i',$fileName,$matches);
	if(isset($matches[1])) {$newlatlong .= '&lon='.$matches[1];}
	
	$newurl = 'http://forecast.weather.gov/MapClick.php?'.$newlatlong.'&unit=0&lg=english&FcstType=text&TextType=2';
	$Status .= "<!-- corrected NWS URL='$newurl' -->\n";
	$fileName = $newurl;
}
if(strpos($fileName,'http://') !== false) {
	$fileName = str_replace('http://','https://',$fileName);
	$Status .= "<!-- replaced http with https for NWS URL='$fileName' -->\n";
}

if($forceDualIconURL) {
	$fileName = str_replace('forecast.weather.gov','dualicons-forecast.weather.gov',$fileName);
	$Status .= "<!-- using dual-icon URL ='$fileName' -->\n";
//	$backupfileName = str_replace('forecast.weather.gov','dualicons-forecast.weather.gov',$backupfileName);
//	$Status .= "<!-- using dual-icon backup URL ='$backupfileName' -->\n";
}

if ($Force==1) {
      $html = ADV_fetchUrlWithoutHanging($fileName,$cacheName);
	  $fSize = strlen($html);
      $Status .= "<!-- loading $fileName - $fSize bytes -->\n";
      if (preg_match('/Temporary|Location:|defaulting to|window\.location\.href\=/Uis',$html)) {
		 print "<!-- redirect found in \n".htmlspecialchars($html)." -->\n";
         $usingFile = "(Zone forecast)";
         $html = ADV_fetchUrlWithoutHanging($backupfileName,$cacheName);
 	     $fSize = strlen($html);
         $Status .= "<!-- loading $backupfileName - $fSize bytes -->\n";
      }
      $fp = fopen($cacheName, "w");
      if ($fp) {
        $write = fputs($fp, $html);
        fclose($fp);
      } else {
            $Status .= "<!-- unable to write cache file $cacheName -->\n";
      }
  }

if ($Force==2) {
      $html = ADV_fetchUrlWithoutHanging($backupfileName,$cacheName);
	  $fSize = strlen($html);
      $Status .= "<!-- loading $backupfileName - $fSize bytes -->\n";
      $fp = fopen($cacheName, "w");
      if ($fp) {
        $write = fputs($fp, $html);
        fclose($fp);
      } else {
            $Status .= "<!-- unable to write cache file $cacheName -->\n";
      }
      $usingFile = "(Zone forecast)";
  }

// The number 1800 below is the number of seconds the cache will be used instead of pulling a new file
// 1800 = 60s x 30m so it retreives every 30 minutes.

if (file_exists($cacheName) and filemtime($cacheName) + $refreshTime > time()) {  // 1800
      $html = implode('', file($cacheName));
	  $fSize = strlen($html);
      $Status .= "<!-- loading $cacheName - $fSize bytes -->\n";
      if (preg_match('/Temporary|Location:|defaulting to|window\.location\.href\=/Uis',$html)) {
         $usingFile = "(Zone forecast)";
         $html = ADV_fetchUrlWithoutHanging($backupfileName,$cacheName);
		 $fSize = strlen($html);
		 $Status .= "<!-- loading $backupfileName - $fSize bytes -->\n";
      }
    } else {
      $html = ADV_fetchUrlWithoutHanging($fileName,$cacheName);
	  $fSize = strlen($html);
      $Status .= "<!-- loading $fileName - $fSize bytes -->\n";
      if (preg_match('/Temporary|Location:|defaulting to|window\.location\.href\=/Uis',$html)) {
         $usingFile = "(Zone forecast)";
         $html = ADV_fetchUrlWithoutHanging($backupfileName,$cacheName);
		  $fSize = strlen($html);
		  $Status .= "<!-- loading $backupfileName - $fSize bytes -->\n";
      }
      $fp = fopen($cacheName, "w");
      if ($fp) {
        $write = fputs($fp, $html);
        fclose($fp);
      } else {
        $Status .= "<!-- unable to write cache file $cacheName -->\n";
      }
}

if (isset($_REQUEST['test'])) {
  $tfile = "./forecast-" . trim($_REQUEST['test']) . '.txt';
  if(file_exists($tfile)) {
    $Status .= "<!-- using $tfile for testing -->\n";
    $html = implode('',file($tfile));
  } else {
    $Status .= "<!-- unable to locate $tfile for testing -->\n";
  }
}
$isZone = (preg_match('|Zone Area Forecast for|i',$html) or
           preg_match('|Zone Forecast for|i',$html))?true:false; ; // here with Zone forecast sans icons
$Status .= "<!-- isZone='$isZone' -->\n";

if ($isZone) { // using the zone forecast
  $usingFile = "(Zone forecast)";
  $Conditions = array();  // prepare for parsing the icon based on the text forecast
  load_cond_data(); // initialize the conditions to look for

  $start = strpos($html, 'panel-danger');
  if($start) {
	$finish = strpos($html,"</ul>",$start);
	$length = $finish-$start;
	$rawwarn = substr($html, $start, $length);
	$Status .= "<!-- rawwarn length=".strlen($rawwarn)." start=$start -->\n";
	$Status .= "<!-- rawwarn\n".htmlentities($rawwarn)." -->\n";
   
	preg_match_all('|<li><a id="hazard-.*" href="([^"]+)".*>(.*)</a></li>|Uis',$rawwarn,$warns);
	$Status .= "<!-- warns \n".print_r($warns,true)." -->\n";
   } else {
	$warns = array();
  }

  $startgrab = strpos($html, 'zone-forecast-body');
  if ($startgrab === false) {$startgrab = strpos($html,'id="detailed-forecast-body"'); }
if ($startgrab === false) {$startgrab = strpos($html,'<tr valign ="top"><td colspan="2" valign="top" align="left">'); }
  $start = $startgrab;
  $finish = strpos($html, '<div id="additional_forecasts">',$start);
  if($finish == false) {$finish = strpos($html,"</ul>",$start); }
if($finish == false) {$finish = strpos($html,'<hr><br></td></tr>',$start); }
  $length = $finish-$start;
  $forecastop = substr($html, $start, $length);

$Status .= "<!-- start=$start finish=$finish length=$length -->\n";
/*
<div class="row row-odd row-forecast">
<div class="col-sm-3 col-lg-2 forecast-label"><b>Rest Of Today</b></div>
<div class="col-sm-9 col-lg-10 forecast-text">Mostly cloudy early in the morning then 
becoming partly cloudy. Highs in the mid 70s. Northwest winds 5 to 10 mph.</div>
</div>
*/
//     slice off the text forecast from the Zone forecast
    preg_match_all('|<div class="row row-\S+ row-forecast"><div .* forecast-label"><b>([^<]+)</b>.* forecast-text">(.*)</div>\s*</div>|Uis', $forecastop, $headers);
	if(!isset($headers[2][1])) {
      preg_match_all('|<b>([^<]+): </b>(.*)<br>|Uis', $forecastop, $headers);
	}
        $forecaststuff = $headers[2];
           $Status .= "<!-- text zone forecast \n" . print_r($headers,true) . "-->\n";

//     Breakup multi-day forecasts if needed
        $i = 0;
    foreach ($headers[1] as $j => $period) {
      if (preg_match('/^(.*) (Through|And) (.*)/i',$period,$mtemp)) { // got period1 thru period2
                list($fcstLow,$fcstHigh) = explode("\t",split_fcst($headers[2][$j]));
                $startPeriod = $mtemp[1];
                $periodType = $mtemp[2];
                $endPeriod = $mtemp[3];
                $startIndex = 0;
                $endIndex = 0;
                $Status .= "<!-- splitting $periodType '$period'='" . $headers[2][$j] . "' -->\n";
                for ($k=0;$k<count($fcstPeriods);$k++) { // find Starting and ending period indices
                  if(!$startIndex and $startPeriod == $fcstPeriods[$k] ) {
                         $startIndex = $k;
                  }
                  if($startIndex and !$endIndex and $endPeriod == $fcstPeriods[$k]) {
                        $endIndex= $k;
                        break;
                  }
                 }

                for ($k=$startIndex;$k<=$endIndex;$k++) { // now generate the period names and appropriate fcst
                  if(preg_match('|night|i',$fcstPeriods[$k])) {
                        $forecasttext[$i] = $fcstLow;
                   } else {
                        $forecasttext[$i] = $fcstHigh;
                   }
                   $forecasttitles[$i] = $fcstPeriods[$k];
                   $Status .= "<!-- $periodType $j, $i, '" .
                                          $forecasttitles[$i] . "'='" . $forecasttext[$i] . "' -->\n";
                   $i++;
                 }
           continue;
         }

         $forecasttitles[$i] = $period;
         $forecasttext[$i] = strip_tags($headers[2][$j]);
         $Status .= "<!-- normal $j, $i, '" . $forecasttitles[$i] . "'='" . $forecasttext[$i] . "' -->\n";
         $i++;

   } // end of multi-day forecast split

   for ($i=0;$i<=min(8,count($headers[1])-1);$i++) { // intrepet the text for icons, summary, temp, PoP
          list($forecasticons[$i],$forecasttemp[$i],$forecastpop[$i]) =
                explode("\t",make_icon($forecasttitles[$i],$forecasttext[$i]) );
		  $forecasticons[$i] = preg_replace('/&/','&amp;',$forecasticons[$i]);
   }

 } else { // format is point printable forecast &TextType=2

     
  $startgrab = strpos($html, '<table width="100%"');
  $start = strpos($html, '<tr valign ="top" align="center">',$startgrab);
  $finish = strpos($html, '</tr></table>',$start);
  $length = $finish-$start;
  $forecastop = substr($html, $start, $length);
// print "<!-- forecastop \n".print_r($forecastop,true)." -->\n";
  // Chop up each icon html and place in array
  preg_match_all("|<td.*>(.*)</td>|Uis", $forecastop, $headers);
//  $Status .= "<!-- td match headers \n".print_r($headers,true)." -->\n";
  if(isset($headers[1][0])) {
	$forecasticons = $headers[1];
	$Status .= "<!-- old td format found -->\n";
  } else {
    preg_match_all('|<li class="forecast-tombstone">(.*)</li>|Uis',$forecastop,$headers);
//      print "<!-- li match headers \n".print_r($headers,true)." -->\n";
    if(isset($headers[1])) {
	  $forecasticons = $headers[1]; 
	  $Status .= "<!-- new li format found -->\n";
//      print "<!-- forecasticons \n".print_r($forecasticons,true)." -->\n";
	  $newFormat = true;
	  if(strpos($iconType,'gif') !== false) {
		  $DualImageAvailable = false;
		  $Status .= "<!-- DualImage not supported for .gif icon set. -->\n";
	  }
	  if($DualImageAvailable) {
		  $Status .= "<!-- DualImage capability available -->\n";
	  } else {
		  $Status .= "<!-- DualImage will not be done..missing DualImage.php file -->\n";
	  }
    }
   }

}

// saratoga-weather.org mod: fix up html for XHTML 1.0-Strict
//     $Status .= "<!-- \n" . print_r($forecasticons,true) . "-->\n";
  for ($i=0;$i<count($forecasticons);$i++) {
	 // $Status .= "<!-- raw forecasticons[$i]='".$forecasticons[$i]."' -->\n";
	 $forecasticons[$i] = preg_replace('|/images/wtf/small|Uis',
	 '/forecast/images',$forecasticons[$i]);
	 $forecasticons[$i] = preg_replace('|/images/wtf|Uis',
	 '/forecast/images',$forecasticons[$i]);
	 $forecasticons[$i] = preg_replace('|newimages/medium|Uis',
	 '/forecast/images',$forecasticons[$i]);
//                    $forecasticons[$i] = preg_replace('|"images/|Uis',
//                   '"/forecast/images/',$forecasticons[$i]);
	$forecasticons[$i] = preg_replace('|/forecast/images/|Uis',
	 $iconDir,$forecasticons[$i]);
	 if(!preg_match('/\.(png|jpg|gif|php)[^"]*"/Uis',$forecasticons[$i])) {
		 # oops... missing icon, substitute NA icon instead
		 $Status .= "<!-- forecasticons[$i]='".$forecasticons[$i]."' -->\n";
		 $forecasticons[$i] = preg_replace('|'.$iconDir.'|Uis',$iconDir.'na.png',$forecasticons[$i]);
		 $Status .= "<!-- replaced missing icon $i with na.png -->\n";
	 }

	 $forecasticons[$i] = preg_replace('|\.png|Uis',$iconType,$forecasticons[$i]); // support .gif icons
#	 $forecasticons[$i] = preg_replace('|<br><br>\s+$|is',"",$forecasticons[$i]);
	 $forecasticons[$i] = preg_replace('|<br><br>|is',"<br>",$forecasticons[$i]);
	 $forecasticons[$i] = preg_replace('|<img|is','<br><img',$forecasticons[$i]);
#	 $forecasticons[$i] = preg_replace('|" ><br>|is','" /><br />',$forecasticons[$i]);
	 
	 $forecasticons[$i] = preg_replace('|<font color="(.*)\">(.*)</font>|Uis',
	   "<span style=\"color: $1;\">$2</span>",$forecasticons[$i]);

//     $Status .= "<!-- forecasticons $i \n".print_r($forecasticons[$i],true)." -->\n";
	 preg_match_all('|<br>([^<]+)(<span.*</span>.*)|is',$forecasticons[$i],$matches);
	 if(!isset($matches[2][0])) {
		 preg_match_all('|<p[^>]*>(.*)</p>|Uis',$forecasticons[$i],$matches);
	 }
//    $Status .= "<!-- matches\n ".print_r($matches,true). "-->\n";
	if($newFormat) {# use the July 7, 2015 format
/*  [1] => Array
        (
            [0] => Thursday<br><br>
            [1] => <img src="DualImage.php?i=bkn&j=tsra&jp=0" alt="Thursday: A slight chance of showers and thunderstorms after noon.  Partly sunny, with a high near 52." title="Thursday: A slight chance of showers and thunderstorms after noon.  Partly sunny, with a high near 52." class="forecast-icon">
            [2] => Partly Sunny<br>then Slight<br>Chance<br>T-storms<br><br>
            [3] => High: 52 &deg;F
        )
		
    [1] => Array
        (
            [0] => Friday<br>
            [1] => <br><img src="DualImage.php?i=fg&j=sct" alt="Friday: Patchy fog before 11am.  Otherwise, mostly sunny, with a high near 80." title="Friday: Patchy fog before 11am.  Otherwise, mostly sunny, with a high near 80." class="forecast-icon">
            [2] => Patchy Fog<br>then Mostly<br>Sunny<br><br>
            [3] => High: 80 &deg;F
        )

*/

      $forecasttemp[$i] = $matches[1][3];

	  if(preg_match('|High|i',$forecasttemp[$i])) {
		  $color = '#FF0000';
		  $tHL = 'Hi';
	  } else {
		  $color = '#0000FF';
		  $tHL = 'Lo';
	  }
	  
	  $tIcon = str_replace('">','"/><br/>',$matches[1][1]); # HTML Strict
	  
	  if(!$DualImageAvailable and preg_match('|DualImage.php\?([^"]+)"|',$tIcon,$tMatch)) {
		  # Oops.. take first image and posible PoP for static image instead.
		  $targs = $tMatch[1];
		  $Status .= "<!-- replacing 'DualImage.php?".$targs."' ";
		  parse_str($targs,$ta);
		  $fn = '';
		  if(isset($ta['i'])) {$fn = $ta['i']; }
		  if(isset($ta['ip']) and $ta['ip']>0) { $fn .= $ta['ip']; }
		  if($fn <> '') {
			  $fn = $iconDir. $fn . $iconType;
			  $Status .= " with '$fn' -->\n";
		      $tIcon = str_replace('DualImage.php?'.$targs,$fn,$tIcon);
		  }
	  }
	  $forecasttemp[$i] = preg_replace('|^(.*) (.*)&deg;F|Uis',
	   "$tHL <span style=\"color: $color;\">$2 &deg;F</span>",$forecasttemp[$i]);

	  $forecasticons[$i] = $matches[1][0] . $tIcon . $matches[1][2];
	  $forecasticons[$i] = str_replace('<br>','<br/>',$forecasticons[$i]);
	  $Status .= "<!-- a. forecasticons[$i] = '".$forecasticons[$i]."' -->\n\n";
	} else {
      if(isset($matches[2][0]) and preg_match('|<img .*>|i',$matches[2][0])) {
	    $t = $matches[2][0];
//<img src="/images/wtf/small/bkn.png" width="55" height="58" alt="Becoming Sunny" title="Becoming Sunny" >
		$t = preg_replace('|<img src="([^"]+)".*alt="([^"]+)"[^>]*>|i',
			"<img src=\"\\1\" style=\"border: none;\" alt=\"\\2\" title=\"\\2\" /><br>",$t);
		   $matches[2][0] = $t;
		$forecasticons[$i] = str_replace('<br>','<br/>',$forecasticons[$i]);
   
	    $Status .= "<!-- b. forecasticons[$i] = '".$forecasticons[$i]."' -->\n\n";

	  }
	}
	 if (! $isZone and !$newFormat) {
	   $forecasttemp[$i] = $matches[1][0] . $matches[2][0]; // just the temp line
	   # mchallis added security feature
	   $forecasttemp[$i] = strip_tags($forecasttemp[$i], '<b><br><br/><img><span>');
	 }
	 // remove the temp from the forecasticons
	 if(isset($matches[0][0])) {
	   $forecasticons[$i] = preg_replace('|'.$matches[0][0].'|is','',$forecasticons[$i]);
	 }
	 // fix up the <br> to be <br /> for XHTML compatibility
	   $forecasticons[$i] = str_replace('<br>','<br/>',$forecasticons[$i]);
	   # mchallis added security feature
	   $forecasticons[$i] = strip_tags($forecasticons[$i], '<b><br><br/><img><span>');
//                   $forecasttemp[$i] = preg_replace('|<br>|Uis','<br />',$forecasttemp[$i]);
//                   $forecasttemp[$i] = trim($forecasttemp[$i]);
	   $forecasticons[$i] = preg_replace('/&/','&amp;',$forecasticons[$i]);
	   $forecasticons[$i] = preg_replace('|Thunderstorm|','T-Storm',$forecasticons[$i]);
	   $forecasticons[$i] = preg_replace('|height="[^"]+"|i',"height=\"$iconHeight\"",
	      $forecasticons[$i]);
	   $forecasticons[$i] = preg_replace('|width="[^"]+"|i',"width=\"$iconWidth\"",$forecasticons[$i]);

  }

//          $Status .= "<!-- \n" . print_r($forecasticons,true) . "-->\n";

// end saratoga-weather.org XHTML 1.0-Strict mod

      if ($isZone) { // special handling for ERH->CRH redirection
/*
Last Update</a>: </td>
                        	<td>850 AM PDT MON MAR 16 2015</td>
                    	</tr>
*/
      // Grab the Last Update date and time.
      preg_match('|Last Update</a>(.*?)<br></td>|', $html, $betweenspan);
	  if(!isset($betweenspan[1])) { 
	  // <b>Last Update: </b></a>150 AM PST FRI NOV 23 2012</td>
	    preg_match('|Last Update</a>: </div>\s*<div.*>(.*)</div>|Us', $html, $betweenspan);		
	  }
	  if(!isset($betweenspan[1])) {
		preg_match('|<b>Last Update: </b></a>([^<]+)</td>|Uis',$html,$betweenspan);
	  }
      $forecastupdated  = $betweenspan[1];
      # mchallis added security feature
      $forecastupdated = strip_tags(trim($forecastupdated));
// saratoga-weather.org mod:
          // Grab the NWS Forecast for (city name)
          preg_match('|class="white1">\s*(.*)<a href|',$html,$betweenspan);
		  if(!isset($betweenspan[1])) {
            preg_match('|<p class="myforecast-location"><a [^>]+>Zone Area Forecast for ([^<]+)</a></p>|',$html,$betweenspan);
		  }
		  if(!isset($betweenspan[1])) {
			preg_match('|<b>NWS Forecast for: ([^<]+)</b>|Uis',$html,$betweenspan);
		  }
          $forecastcity  = $betweenspan[1];
          # mchallis added security feature
          $forecastcity = strip_tags($forecastcity, '<b><br><img><span>');
          // Grab the Issued by office
		  $Status .= "<!-- forecastcity = '$forecastcity' -->\n";
/*<h2>For More Weather Information:</h2>
				<p><a href="http://www.wrh.noaa.gov/mtr">San Francisco Bay Area/Monterey, CA  Local Forecast Office</a></p>
<p class="moreInfo"><b>More Information:</b></p><p><a id="localWFO" href="http://www.wrh.noaa.gov/mtr" title="San Francisco Bay Area/Monterey, CA">
				*/
		  preg_match('|<a id="localWFO".*title="(.*)">|Uis',$html,$betweenspan);
		  if(!isset($betweenspan[1])) {
			 preg_match('|</font><br>Issued by: ([^<]+)<br>|Uis',$html,$betweenspan);
		  }
//		  $Status .= "<!-- forecastoffice \n".htmlspecialchars(print_r($betweenspan,true))."-->\n";
          if(isset($betweenspan[1])) {
            $forecastoffice  = 'NWS ' . trim($betweenspan[1]);
		  } else {
			$forecastoffice = '';
		  }
		  $Status .= "<!-- forecastoffice='$forecastoffice' -->\n";


          } else { // begin regular handling

      // Now get just the bottom of the NWS page for editing
//      preg_match('|<td colspan="2" valign="top" align="left">(.*)<hr><br>|Us', $html, $betweenspan);
//      $forecast  = $betweenspan[1];

$startgrab = strpos($html, '<td colspan="2" valign="top" align="left"');
$start = strpos($html, '<td colspan="2" valign="top" align="left"',$startgrab+1); // need second one
$finish = strpos($html, '<hr><br>',$start);
$length = $finish-$start;
$forecast = substr($html, $start, $length);
// print "<!-- forecast start=$start finish=$finish length=$length '\n".$forecast."\n' -->\n";
# mchallis added security feature
	preg_match_all('|<a href="(.*)"><span class="[^"]+">(.*)</span></a>|Uis',$forecast,$warns);

    if($newFormat) {
	  $start = strpos($html,'</li></tr></table></td></tr>');
	  $forecast = substr($html,$start);
	  preg_match_all('|<b>([^:]+): </b>(.*)<br>\s+<br>|Uis',$forecast,$matches);
	  // $Status .= "<!-- detail matches \n".print_r($matches,true)." -->\n";
	  $forecasttitles = $matches[1];
	  $forecasttext = $matches[2];
	  	
	  $start = strpos($html,'<br><div align="left">');
	  $forecast = substr($html,$start);
	  preg_match_all('|<a href="(.*)"><span class="[^"]+">(.*)</span></a>|Uis',$forecast,$warns);
	  
	} elseif(strpos($forecast,'<ul class="point-forecast-7-day">') !==false) {  
	  // using newer style point printable page
	   $Status .= "<!-- new ul/li forecast detected -->\n";
	  // Chop up each title text and place in array
	  preg_match_all('|<li class="row[^"]+"><span class="label">(.*)</span>|Ui', $forecast, $headers);
	  $forecasttitles = $headers[1];

	  // Chop up each forecast text and place in array
	  preg_match_all('|<li class="row.*</span>(.*)</li>|Ui', $forecast, $headers);
	  $forecasttext = $headers[1];
		
	} else { // old style
	
	
//	  print "<!-- forecastwarnings \n".print_r($warns,true)." -->\n";
	  $forecast = strip_tags($forecast, '<b><br><img><span>');

	  // Chop up each title text and place in array
	  preg_match_all('|<b>(.*): </b>|Ui', $forecast, $headers);
	  $forecasttitles = $headers[1];

	  // Chop up each forecast text and place in array
	  preg_match_all('|</b>(.*)<br>|Ui', $forecast, $headers);
	  $forecasttext = $headers[1];
	}
	# BOF mchallis added security feature
	for ($i=0;$i<count($forecasttitles);$i++) {
	  $forecasttitles[$i] = strip_tags($forecasttitles[$i], '<b><br><img><span>');
	  $forecasttext[$i]   = strip_tags($forecasttext[$i], '<b><br><img><span>');
	}
	# EOF mchallis added security feature

	// Grab the Last Update date and time.
	preg_match('|<b>Last Update: </b></a>(.*?)</td>|', $html, $betweenspan);
	$forecastupdated  = $betweenspan[1];
	//    $forecastupdated  = preg_replace('|<[^>]+>|Uis','',$forecastupdated); // remove html markup


// saratoga-weather.org mod:
		// Grab the NWS Forecast for (city name)
		preg_match('|<b>NWS Forecast for: (.*?)</b>|is',$html,$betweenspan);
		$forecastcity  = $betweenspan[1];
		# mchallis added security feature
		$forecastcity = strip_tags($forecastcity, '<b><br><img><span>');

		// Grab the Issued by office
		preg_match('|Issued by: (.*?)<br>|',$html,$betweenspan);
		$forecastoffice  = $betweenspan[1];
		# mchallis added security feature
		$forecastoffice = strip_tags($forecastoffice, '<b><br><img><span>');

		} // end regular handling
		  
  // format warnings if found
  $forecastwarnings = '';
//  print "<!-- warns \n".print_r($warns,true)." -->\n";
  if(isset($warns[1]) and count($warns[1])>0) {
	 $Status .= "<!-- preparing warning links -->\n";
	 for ($i=0;$i<count($warns[1]);$i++) {
		 $warns[1][$i] = htmlentities($warns[1][$i]); // make links XHTML 1.0-Strict
		$forecastwarnings .= '<a href="http://forecast.weather.gov/' . $warns[1][$i] . '" target="_blank">' .
		   '<strong><span style="color: red">'. $warns[2][$i] . "</span></strong></a><br/>\n";
	 }
	  
  }


  $IncludeMode = false;
  $PrintMode = true;

  if (isset($doPrintNWS) && ! $doPrintNWS ) {
      return;
  }
  if (isset($_REQUEST['inc']) &&
      strtolower($_REQUEST['inc']) == 'noprint' ) {
          return;
  }

if (isset($_REQUEST['inc']) && strtolower($_REQUEST['inc']) == 'y') {
  $IncludeMode = true;
}
if (isset($doIncludeNWS)) {
  $IncludeMode = $doIncludeNWS;
}

// end saratoga-weather.org mod

//------------------------------------------------------------------------------------------
function ADV_fetchUrlWithoutHanging($url,$cacheName) {
// get contents from one URL and return as string 
  global $Status, $needCookie;
  $useFopen = false;
  $overall_start = time();
  if (! $useFopen) {
   // Set maximum number of seconds (can have floating-point) to wait for feed before displaying page without feed
   $numberOfSeconds=6;   

// Thanks to Curly from ricksturf.com for the cURL fetch functions

  $data = '';
  $domain = parse_url($url,PHP_URL_HOST);
  $theURL = str_replace('nocache','?'.$overall_start,$url);        // add cache-buster to URL if needed
  $Status .= "<!-- curl fetching '$theURL' -->\n";
  $ch = curl_init();                                           // initialize a cURL session
  curl_setopt($ch, CURLOPT_URL, $theURL);                         // connect to provided URL
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);                 // don't verify peer certificate
  curl_setopt($ch, CURLOPT_USERAGENT, 
    'Mozilla/5.0 (advforecast2.php - saratoga-weather.org)');

  curl_setopt($ch,CURLOPT_HTTPHEADER,                          // request LD-JSON format
     array (
         "Accept: text/html,text/plain"
     ));

  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $numberOfSeconds);  //  connection timeout
  curl_setopt($ch, CURLOPT_TIMEOUT, $numberOfSeconds);         //  data timeout
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);              // return the data transfer
  curl_setopt($ch, CURLOPT_NOBODY, false);                     // set nobody
  curl_setopt($ch, CURLOPT_HEADER, true);                      // include header information
//  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);              // follow Location: redirect
//  curl_setopt($ch, CURLOPT_MAXREDIRS, 1);                      //   but only one time
  if (isset($needCookie[$domain])) {
    curl_setopt($ch, $needCookie[$domain]);                    // set the cookie for this request
    curl_setopt($ch, CURLOPT_COOKIESESSION, true);             // and ignore prior cookies
    $Status .=  "<!-- cookie used '" . $needCookie[$domain] . "' for GET to $domain -->\n";
  }

  $data = curl_exec($ch);                                      // execute session

  if(curl_error($ch) <> '') {                                  // IF there is an error
   $Status .= "<!-- curl Error: ". curl_error($ch) ." -->\n";        //  display error notice
  }
  $cinfo = curl_getinfo($ch);                                  // get info on curl exec.
/*
curl info sample
Array
(
[url] => http://saratoga-weather.net/clientraw.txt
[content_type] => text/plain
[http_code] => 200
[header_size] => 266
[request_size] => 141
[filetime] => -1
[ssl_verify_result] => 0
[redirect_count] => 0
  [total_time] => 0.125
  [namelookup_time] => 0.016
  [connect_time] => 0.063
[pretransfer_time] => 0.063
[size_upload] => 0
[size_download] => 758
[speed_download] => 6064
[speed_upload] => 0
[download_content_length] => 758
[upload_content_length] => -1
  [starttransfer_time] => 0.125
[redirect_time] => 0
[redirect_url] =>
[primary_ip] => 74.208.149.102
[certinfo] => Array
(
)

[primary_port] => 80
[local_ip] => 192.168.1.104
[local_port] => 54156
)
*/
  $Status .= "<!-- HTTP stats: " .
    " RC=".$cinfo['http_code'];
	if(isset($cinfo['primary_ip'])) {
		$Status .= " dest=".$cinfo['primary_ip'] ;
	}
	if(isset($cinfo['primary_port'])) { 
	  $Status .= " port=".$cinfo['primary_port'] ;
	}
	if(isset($cinfo['local_ip'])) {
	  $Status .= " (from sce=" . $cinfo['local_ip'] . ")";
	}
	$Status .= 
	"\n      Times:" .
    " dns=".sprintf("%01.3f",round($cinfo['namelookup_time'],3)).
    " conn=".sprintf("%01.3f",round($cinfo['connect_time'],3)).
    " pxfer=".sprintf("%01.3f",round($cinfo['pretransfer_time'],3));
	if($cinfo['total_time'] - $cinfo['pretransfer_time'] > 0.0000) {
	  $Status .=
	  " get=". sprintf("%01.3f",round($cinfo['total_time'] - $cinfo['pretransfer_time'],3));
	}
    $Status .= " total=".sprintf("%01.3f",round($cinfo['total_time'],3)) .
    " secs -->\n";

  //$Status .= "<!-- curl info\n".print_r($cinfo,true)." -->\n";
  curl_close($ch);                                              // close the cURL session
  //$Status .= "<!-- raw data\n".$data."\n -->\n"; 
  $i = strpos($data,"\r\n\r\n");
  $headers = substr($data,0,$i);
  $content = substr($data,$i+4);
  if($cinfo['http_code'] <> '200') {
    $Status .= "<!-- headers returned:\n".$headers."\n -->\n"; 
  }
  return $data;                                                 // return headers+contents

 } else {
//   print "<!-- using file_get_contents function -->\n";
   $STRopts = array(
	  'http'=>array(
	  'method'=>"GET",
	  'protocol_version' => 1.1,
	  'header'=>"Cache-Control: no-cache, must-revalidate\r\n" .
				"Cache-control: max-age=0\r\n" .
				"Connection: close\r\n" .
				"User-agent: Mozilla/5.0 (advforecast2.php - saratoga-weather.org)\r\n" .
				"Accept: text/html,text/plain\r\n"
	  ),
	  'https'=>array(
	  'method'=>"GET",
	  'protocol_version' => 1.1,
	  'header'=>"Cache-Control: no-cache, must-revalidate\r\n" .
				"Cache-control: max-age=0\r\n" .
				"Connection: close\r\n" .
				"User-agent: Mozilla/5.0 (advforecast2.php - saratoga-weather.org)\r\n" .
				"Accept: text/html,text/plain\r\n"
	  )
	);
	
   $STRcontext = stream_context_create($STRopts);

   $T_start = ADV_fetch_microtime();
   $xml = file_get_contents($url,false,$STRcontext);
   $T_close = ADV_fetch_microtime();
   $headerarray = get_headers($url,0);
   $theaders = join("\r\n",$headerarray);
   $xml = $theaders . "\r\n\r\n" . $xml;

   $ms_total = sprintf("%01.3f",round($T_close - $T_start,3)); 
   $Status .= "<!-- file_get_contents() stats: total=$ms_total secs -->\n";
   $Status .= "<-- get_headers returns\n".$theaders."\n -->\n";
//   print " file() stats: total=$ms_total secs.\n";
   $overall_end = time();
   $overall_elapsed =   $overall_end - $overall_start;
   $Status .= "<!-- fetch function elapsed= $overall_elapsed secs. -->\n"; 
//   print "fetch function elapsed= $overall_elapsed secs.\n"; 
   return($xml);
 }

}    // end ADV_fetchUrlWithoutHanging
// ------------------------------------------------------------------

function ADV_fetch_microtime()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}
   
// ----------------------------------------------------------


// split off Low and High from multiday forecast
function split_fcst($fcst) {

  global $Status;

  $f = explode(". ",$fcst . ' ');
  $lowpart = 0;
  $highpart = 0;
  foreach ($f as $n => $part) {  // find the Low and High sentences
    if(preg_match('/Low/i',$part)) { $lowpart = $n; }
        if(preg_match('/High/i',$part)) { $highpart = $n; }
  }

  $f[$lowpart] = preg_replace('|(\d+) below|s',"-$1",$f[$lowpart]);
  $f[$lowpart] = preg_replace('/( above| below| zero)/s','',$f[$lowpart]);
  $f[$lowpart] .= '.';

  $f[$highpart] = preg_replace('|(\d+) below|s',"-$1",$f[$highpart]);
  $f[$highpart] = preg_replace('/( above| below| zero)/s','',$f[$highpart]);
  $f[$highpart] .= '.';

  $replpart = min($lowpart,$highpart)-1;

  $fcststr = '';

  for ($i=0;$i<=$replpart;$i++) {$fcststr .= $f[$i] . '. '; } // generate static fcst text

  $fcstLow = $fcststr . ' ' . $f[$lowpart];
  $fcstHigh = $fcststr . ' ' . $f[$highpart];

  return("$fcstLow\t$fcstHigh");

}
//------------------------------------------------------------------------------------------

// function make_icon: parse text and find suitable icon from zone forecast text for period

function make_icon($day,$textforecast) {
  global $Conditions,$Status,$iconType,$doDebug,$iconHeight,$iconWidth;
  $iconDir = '/forecast/images/'; // will be substituted correctly by main script
  if (preg_match('| |i',$day) ) {
    $icon = "<strong>" . preg_replace('| |','<br/>',$day,1) . '</strong><br/>';
  } else {
    $icon = "<strong>" . $day . '</strong><br/><br/>';
  }
  $temperature = 'n/a';
  $pop = '';
  $iconimage = 'na.jpg';
  $condition = 'N/A';

  if (preg_match('|(\S+) (\d+) percent|',$textforecast,$mtemp)) { // found a PoP
//    $Status .= "<!-- chance of '" . $mtemp[1] . "'='" . $mtemp[2] . "' -->\n";
    $pop = $mtemp[2];
  }
  
  if(preg_match('|Chance of precipitation is (\d+)\s*%|',$textforecast,$mtemp)) { // found a zone pop
    $pop = $mtemp[1];
	if($doDebug) {$Status .= "<!-- pop='$pop' found -->\n";}
  }
  // handle negative temperatures in zone forecast
  $textforecast = preg_replace('/([\d|-]+) below/i',"-$1",$textforecast);
  $textforecast = preg_replace('/zero/','0',$textforecast);
  
  if (preg_match('/(High[s]{0,1}|Low[s]{0,1}|Temperatures nearly steady|Temperatures falling to|Temperatures rising to|Near steady temperature|with a high|with a low) (in the upper|in the lower|in the mid|in the low to mid|in the lower to mid|in the mid to upper|in the|around|near|nearly|above|below|from) ([\d|-]+)[s]{0,1}/i',$textforecast,$mtemp)) { // found temp
    if($doDebug) {$Status .= "<!-- mtemp " . print_r($mtemp,true) . " -->\n";}
	    if (isset($mtemp[1]) and preg_match('|with a |',$mtemp[1])) { 
		  $mtemp[1] = ucfirst(preg_replace('|with a |','',$mtemp[1]));
          if($doDebug) {$Status .= "<!-- mtemp modded to " . print_r($mtemp,true) . " -->\n";}
	    }
        if (substr($mtemp[1],0,1) == 'T' or substr($mtemp[1],0,1) == 'N') { // use day for highs/night for lows if 'Temperatures nearly steady'
          $mtemp[1] = 'Highs';
          if (preg_match('|night|i',$day)) {
            $mtemp[1] = 'Lows';
          }
        }
        $tcolor = '#FF0000';
        if (strtoupper(substr($mtemp[1],0,1)) == 'L') {
          $tcolor = '#0000FF';
        }
        $temperature = ucfirst(substr($mtemp[1],0,2) . ' <span style="color: ' . $tcolor . '">');
        $t = $mtemp[3]; // the raw temp
        if (preg_match('/(low to mid|lower to mid|mid to upper|upper|lower|mid|in the)/',$mtemp[2],$ttemp) ) {
          if($doDebug) {$Status .= "<!-- ttemp " . print_r($ttemp,true) . " -->\n";}
          $t = $t + 5;
          if ($ttemp[1] == 'upper') {
            $temperature .= '&gt;' . $t;
          }
          if ($ttemp[1] == 'lower') {
            $temperature .= '&lt;' . $t ;
          }
          if ($ttemp[1] == 'mid') {
            $temperature .= '&asymp;' . $t;
          }
          if ($ttemp[1] == 'in the') {
            $temperature .= '&asymp;' . $t;
          }
          if ($ttemp[1] == 'low to mid' or $ttemp[1] == 'lower to mid') {
            $t = $t -2;
            $temperature .= '&asymp;' . $t;
          }
          if ($ttemp[1] == 'mid to upper') {
            $t = $t + 2;
            $temperature .= '&asymp;' . $t;
          }
        }
        if (preg_match('/(near|around)/',$mtemp[2],$ttemp) ) {
          $temperature .= '&asymp;' . $mtemp[3];
        }
    $temperature .= '&deg;F</span>';
  }

  if (preg_match('/(Highs|Lows) ([\d|-]+) to ([\d|-]+)/i',$textforecast,$mtemp) ) { // temp range forecast
          $tcolor = '#FF0000';
        if (strtoupper(substr($mtemp[1],0,1)) == 'L') {
          $tcolor = '#0000FF';
        }
        $temperature = ucfirst(substr($mtemp[1],0,2) . ' <span style="color: ' . $tcolor . '">');

    $tavg = sprintf("%0d",round(($mtemp[3] + $mtemp[2]) / 2,0));
    $temperature .= '&asymp;' . $tavg . '&deg;F</span>';
  }

//  $Status .= "<!-- '$day'='$textforecast' -->\n";
   // now look for harshest conditions first.. (in order in -data file
 reset($Conditions);  // Do search in load order
 foreach ($Conditions as $cond => $condrec) { // look for matching condition

   if(preg_match("!$cond!i",$textforecast,$mtemp)) {
     list($dayicon,$nighticon,$condition) = explode("\t",$condrec);
         if (preg_match('|night|i',$day)) {
           $iconimage = $nighticon . $pop . $iconType;
         } else {
           $iconimage = $dayicon . $pop . $iconType;
         }
         break;
   }
 } // end of conditions search
 
  $iconimage = preg_replace('|skc\d+|','skc',$iconimage); // handle funky SKC+a POP in forecast.

  $icon .= '<img src="' . $iconDir . $iconimage . '" height="'.$iconHeight.'" width="'.$iconWidth.'" ' .
     'alt="' . $condition . '" title="' . $condition . '" /><br/>' . $condition;

  return("$icon\t$temperature\t$pop");

} // end make_icons function
//------------------------------------------------------------------------------------------

// load the $Conditions array for icon selection based on key phrases
function load_cond_data () {
  global $Conditions, $Status;

$Condstring = '
#
cond|tornado|nsvrtsra|nsvrtsra|Severe storm|
cond|showery or intermittent. Some thunder|scttsra|nscttsra|Showers storms|
cond|thunder possible|scttsra|nscttsra|Showers storms|
cond|thunder|tsra|ntsra|Thunder storm|
cond|rain and sleet|raip|nraip|Rain Sleet|
cond|freezing rain and snow|raip|nraip|FrzgRn Snow|
cond|rain and snow|rasn|nrasn|Rain and Snow|
cond|rain or snow|rasn|nrasn|Rain or Snow|
cond|freezing rain|fzra|fzra|Freezing Rain|
cond|rain likely|ra|nra|Rain likely|
cond|showers likely|shra|nshra|Showers likely|
cond|chance showers|shra|nshra|Chance showers|
cond|isolated showers|shra|nshra|Isolated showers|
cond|scattered showers|shra|nshra|Scattered showers|
cond|chance of rain|ra|nra|Chance rain|
cond|rain|ra|nra|Rain|
cond|mix|rasn|rasn|Mix|
cond|sleet|ip|ip|Sleet|
cond|snow|sn|nsn|Snow
cond|fog in the morning|sctfg|nbknfg|Fog a.m.|
cond|fog after midnight|sctfg|nbknfg|Fog late|
cond|fog|fg|nfg|Fog|
cond|wind chill down to -|cold|cold|Very Cold|
cond|heat index up to 1|hot|hot|Very Hot|
cond|hot|hot|hot|Hot|
cond|overcast|ovc|novc|Overcast|
cond|mostly cloudy|bkn|nbkn|Mostly Cloudy|
cond|partly cloudy|sct|nsct|Partly Cloudy|
cond|cloudy|cloudy|ncloudy|Cloudy|
cond|partly sunny|sct|nsct|Partly Sunny|
cond|mostly sunny|few|nfew|Mostly Sunny|
cond|mostly clear|few|nfew|Mostly Clear|
cond|sunny|skc|nskc|Sunny|
cond|clear|skc|nskc|Clear|
cond|fair|few|nfew|Fair|
cond|cloud|bkn|nbkn|Variable Clouds|
#
';

$config = explode("\n",$Condstring);
foreach ($config as $key => $rec) { // load the parser condition strings
  $recin = trim($rec);
  if ($recin and substr($recin,0,1) <> '#') { // got a non comment record
    list($type,$keyword,$dayicon,$nighticon,$condition) = explode('|',$recin . '|||||');

        if (isset($type) and strtolower($type) == 'cond' and isset($condition)) {
          $Conditions["$keyword"] = "$dayicon\t$nighticon\t$condition";
//          $Status .= "<!-- '$keyword'='$dayicon','$nighticon' '$condition' -->\n";
        }
  } // end if not comment or blank
} // end loading of loop over config recs

} // end of load_cond_data function
//------------------------------------------------------------------------------------------

if (! $IncludeMode and $PrintMode) { ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>NWS Forecast for <?php echo $forecastcity; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>
<body style="font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px; background-color:#FFFFFF">

<?php
}
print $Status;
// if the forecast text is blank, prompt the visitor to force an update

if (strlen($forecasttext[0])<2 and $PrintMode ) {
  if(!isset($PHP_SELF)) { $PHP_SELF = $_SERVER['PHP_SELF']; }
  echo '<br/><br/>Forecast blank? <a href="' . $PHP_SELF . '?force=1">Force Update</a><br/><br/>';

}
if ($PrintMode) {
   $tw = ($iconWidth+8) * count($forecasticons);
   if($tw < 640) {$tw = 640; }
   ?>
  <table width="<?php echo $tw; ?>" style="border: none;">
    <tr align="center">
      <td><b>National Weather Service Forecast for: </b><span style="color: green;">
           <?php echo $forecastcity; ?></span><br />
        Issued by: <?php echo $forecastoffice; ?>
      </td>
    </tr>
    <tr>
      <td align="center">Updated: <?php echo $forecastupdated; ?>
          </td><!--end forecastupdated-->
    </tr>
    <?php echo $ddMenu ?>
    <tr>
	  <td align="center" style="font-size: 18px; margin: 0px auto;"><b><?php echo $NOAAlocation; ?></b></td>
    </tr>
    <tr>
      <td align="center">&nbsp;
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
              <tr valign ="top" align="center">
        <?php
          for ($i=0;$i<count($forecasticons);$i++) {
            print "<td style=\"width: 11%;\"><span style=\"font-size: 8pt;\">$forecasticons[$i]</span></td>\n";
          }
        ?>
          </tr>
          <tr valign ="top" align="center">
          <?php
          for ($i=0;$i<count($forecasticons);$i++) {
            print "<td style=\"width: 11%;\">$forecasttemp[$i]</td>\n";
          }
          ?>
          </tr>
        </table>
     </td>
   </tr>
</table>
  <p><?php 
  if($forecastwarnings <> '') {
	  print $forecastwarnings;
  }
	?>&nbsp;</p>

<table style="border: 0" width="640">
        <?php
          for ($i=0;$i<count($forecasttitles);$i++) {
        print "<tr valign =\"top\" align=\"left\">\n";
            print "<td style=\"width: 20%;\"><b>$forecasttitles[$i]</b><br />&nbsp;<br /></td>\n";
            print "<td style=\"width: 80%;\">$forecasttext[$i]</td>\n";
                print "</tr>\n";
          }
        ?>
   </table>

<p>&nbsp;</p>
<p>Forecast from <a href="<?php if($usingFile) {
 echo htmlspecialchars($backupfileName);
 } else {
  echo htmlspecialchars($fileName);
 } ?>">NOAA-NWS</a>
for <?php echo $forecastcity; ?>. <?php echo $usingFile; ?>
<?php if($iconType <> '.jpg') {
	print "<br/>Animated forecast icons courtesy of <a href=\"http://www.meteotreviglio.com/\">www.meteotreviglio.com</a>.";
} 
?>
</p>
<?php
} // end printmode

 if (! $IncludeMode and $PrintMode ) { ?>
</body>
</html>
<?php } ?>