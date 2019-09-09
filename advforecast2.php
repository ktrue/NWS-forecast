<?php

// error_reporting(E_ALL);
// This is a more advanced version of the forecast script
// It uses file caching and feed failure to better handle when NOAA is down
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
//
//  Version 5.00 - 22-Apr-2017 - complete rewrite to use api.weather.gov JSON feeds for forecasts and alerts
//  Version 5.01 - 26-Apr-2017 - switch to use test NWS site for data waiting for June 19, 2017 prod cutover
//  Version 5.02 - 22-May-2017 - added temperature trend indicator
//  Version 5.03 - 16-Jul-2017 - if point forecast HTTP return code > 400, force zone forecast fetch
//  Version 5.04 - 11-Oct-2017 - fix for stale point-forecast via API to failover to Zone forecast
//  Version 5.05 - 27-Feb-2018 - more fixes for point-forecast/refetch fail->Zone failover
//  Version 5.06 - 12-Apr-2018 - fix PHP warning when no alerts available
//  Version 5.07 - 14-Apr-2018 - add caching for point->gridpoint forecast URLs + WFO info on Zone fcst
//  Version 5.08 - 25-May-2018 - fixes for point/zone JSON changes from api.weather.gov
//  Version 5.09 - 26-May-2018 - added new NWS API icons for tropical storm/hurricane
//  Version 5.10 - 13-Apr-2019 - fix for HTTP/2 responses from api.weather.gov
//  Version 5.11 - 30-Apr-2019 - use new point->meta->gridpoint method for point forecast URL data
//  Version 5.12 - 01-May-2019 - fix for link URL at bottom of page
//  Version 5.13 - 08-Sep-2019 - fix for link URL with NWS discontinuation of forecast-v3.weather.gov site
//

$Version = 'advforecast2.php (JSON) - V5.13 - 08-Aug-2019';

//
// import NOAA Forecast info
// data ends up in four different arrays:
// $forecasticons[x]  x = 0 thru 13   This is the icon and text around it
// $forecasttemp[x] x= 0 thru 13    This is forecast temperature with styling
// $forecasttitles[x]  x = 0 thru 13   This is the title word for the text forecast time period
// $forecasttext[x]  x = 0 thru 13  This is the detail text for the text forecast time period
//
// $forecastupdated  This is the time of last update
// $forecastcity    This is the city name for the forecast
// $forecastoffice  This is the NWS Office providing the forecast
// $forecastwarnings This is the text/links to NWS Warnings, Watches, Advisories, Outlooks, Special Statements
//
// Also, in order for this to work correctly, you need the NOAA icons (or make your own...
// there are 750!).
//
// http://saratoga-weather.org/saratoga-icons2.zip
// unzip the above and upload to your site (preserving the directory structure):
//
// ./forecast/images (for the static images)
// ./forecast/icon-templates (for the dual-image icon master files)
// ./DualImage.php (script to create the dual-image icons as required by the forecast)
//
// The URL(s) below --MUST BE-- the Printable Point Forecast from the NOAA website
// Not every area of the US has a printable point forecast
//
// This script will ONLY WORK with a printable point forecast of either the OLD format like:
// http://forecast.weather.gov/MapClick.php?
//  CityName=Saratoga&state=CA&site=MTR&textField1=37.2639&textField2=-122.022
//  &e=1&TextType=2
//
// or the new format (after June 24, 2019 ) of
// https://forecast.weather.gov/point/37.2639,-122.022
//
// To find yours in your area:
//
// Go to www.weather.gov
// Put your city, state in the search box and press Search
// copy the URL from your browser into the $fileName variable below.
// Also put your NOAA Warning Zone (like ssZnnn) in caps in the $NOAAZone variable below.
// It is used for automatic backup in case the point printable forecast is not available.
//
// ----------------------SETTINGS---------------------------------------------
// V3.00 -- this following array can be used for multiple forecasts in *standalone* mode
//  for Saratpga template use, add a $SITE['NWSforecasts'] entry in Settings.php to have these entries.
//  To activate the definitions below, replace the /* with //* to uncomment the array definition
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
  'CAZ072|South Lake Tahoe, CA|http://forecast.weather.gov/MapClick.php?CityName=Tahoe Vista&state=CA&site=MTR&textField1=39.2425194&textField2=-120.0604858&e=1&TextType=2',
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
  'ARZ044|Little Rock, AR|https://forecast.weather.gov/MapClick.php?lat=34.7224&lon=-92.3541&unit=0&lg=english&FcstType=text&TextType=2',
  "NYZ040|Hessville, NY|https://forecast.weather.gov/MapClick.php?lat=42.8739&lon=-74.687&unit=0&lg=english&FcstType=text&TextType=2",
  "NYZ049|Albany, NY|http://forecast.weather.gov/MapClick.php?CityName=Albany&state=NY&site=MTR&textField1=42.6525&textField2=-73.757&e=0&TextType=2",
  "NYZ056|Binghamton, NY|http://forecast.weather.gov/MapClick.php?CityName=Binghamton&state=NY&site=MTR&textField1=42.0956&textField2=-75.910&e=0&TextType=2",
  "MAZ017|Boston, MA|http://forecast.weather.gov/MapClick.php?CityName=Boston&state=MA&site=MTR&textField1=42.3586&textField2=-71.060&e=0&TextType=2",
  "MNZ060|Robbinsdale, MN|http://forecast.weather.gov/MapClick.php?lat=45.02233988655459&lon=-93.34722518920898&site=mpx&unit=0&lg=en&FcstType=text&TextType=2",
	"FLZ112|Upper Grand Lagoon, FL|http://forecast.weather.gov/MapClick.php?lat=30.1981&lon=-85.8077&unit=0&lg=english&FcstType=text&TextType=2",
	"MSZ066|Ellisville, MS|https://forecast.weather.gov/MapClick.php?lat=31.62265&lon=-89.23384&unit=0&lg=english&FcstType=text&TextType=2",
  "NEZ066|Lincoln, NE|https://forecast.weather.gov/MapClick.php?CityName=Lincoln&state=NE&site=OAX&textField1=40.8164&textField2=-96.6882&e=0&TextType=2",
	"IAZ066|Clinton, IA|https://forecast-v3.weather.gov/point/41.839,-90.192",

);
//*/
//*
//
$NOAAZone = 'CAZ513'; // change this line to your NOAA warning zone.
// set $fileName to the URL for the point-printable forecast for your area
// NOTE: this value (and $NOAAZone) will be overridden by the first entry in $NWSforecasts if it exists.
$fileName = "http://forecast.weather.gov/MapClick.php?CityName=Saratoga&state=CA&site=MTR&textField1=37.2639&textField2=-122.022&e=1&TextType=2";
//*/
$showTwoIconRows = true; // =true; show all icons, =false; show 9 icons in one row (new V5.00)
//
$showZoneWarning = true; // =true; show note when Zone forecast used. =false; suppress Zone warning (new V5.00)
// $iconDir = './forecast/imagesPNG-86x86/'; // testing only
// $iconDir = './forecast/imagesGIF-55x58/'; // testing only
$iconDir = './forecast/images/';
$iconType = '.jpg'; // default type='.jpg' -- use '.gif' for animated icons from http://www.meteotreviglio.com/
$cacheFileDir = './'; // default cache file directory
$iconHeight = 55; // default height of conditions icon (saratoga-icons.zip)
$iconWidth = 55; // default width of conditions icon  (saratoga-icons.zip)
$refreshTime = 600; // default refresh of cache 600=10 minutes
//
$ourTZ = 'America/Los_Angeles'; // default timezone (new V5.00)
// $timeFormat = 'd-M-Y g:ia T';  // Fri, 31-Mar-2006 6:35pm TZone (new V5.00)
$timeFormat = 'g:i a T M d, Y'; // 3:17 am PST Jan 28, 2017 (new V5.00, like old forecast timestamp display)
//
// ----------------------END OF SETTINGS--------------------------------------
// changing stuff below this may cause you issues when updates to the script are done.
// you change it, you own it :)
//
$useProdNWS = false; // =false, use preview V3 sites, =true, use production sites (after June 24, 2019)
//
$forceDualIconURL = false; // for TESTING prior to 7-Jul-2015 when new icons were used by NWS
// following is for FUTURE 
$getGridpointData = false; // =true; for getting gridpoint data, =false for not getting the data
//
// following is for FUTURE hourly forecast/graphics..
$getHourlyData = false; // =true; for getting hourly data, =false for not getting the data

if(file_exists('Settings.php')) { include_once('Settings.php'); }
// overrides from Settings.php if available
global $SITE;
if (isset($SITE['NWSforecasts']))    {$NWSforecasts = $SITE['NWSforecasts']; }
if (isset($SITE['cacheFileDir']))    {$cacheFileDir = $SITE['cacheFileDir']; }
if (isset($SITE['noaazone']))        {$NOAAZone = $SITE['noaazone'];}
if (isset($SITE['fcsturlNWS']))      {$fileName = $SITE['fcsturlNWS'];}
if (isset($SITE['fcsticonsdir']))    {$iconDir = $SITE['fcsticonsdir'];}
if (isset($SITE['fcsticonstype']))   {$iconType = $SITE['fcsticonstype'];}
if (isset($SITE['fcsticonsheight'])) {$iconHeight = $SITE['fcsticonsheight'];}
if (isset($SITE['fcsticonswidth']))  {$iconWidth = $SITE['fcsticonswidth'];}
if (isset($SITE['tz']))              {$ourTZ = $SITE['tz'];} // V5.00
if (isset($SITE['timeFormat']))      {$timeFormat = $SITE['timeFormat'];} // V5.00
if (isset($SITE['showTwoIconRows'])) {$showTwoIconRows = $SITE['showTwoIconRows'];} // V5.00
if (isset($SITE['showZoneWarning'])) {$showZoneWarning = $SITE['showZoneWarning'];} // V5.00
// end of overrides from Settings.php

$doDebug = (isset($_REQUEST['debug']) and preg_match('|y|i',$_REQUEST['debug']))?true:false;

if(isset($_REQUEST['rows'])) {
	if($_REQUEST['rows'] == '1') {$showTwoIconRows = false; }
	if($_REQUEST['rows'] == '2') {$showTwoIconRows = true;  }
}

$doDebug = (isset($_REQUEST['debug']) and preg_match('|y|i', $_REQUEST['debug'])) ? true : false;

if (isset($_REQUEST['rows'])) {
  if ($_REQUEST['rows'] == '1') {
    $showTwoIconRows = false;
  }

  if ($_REQUEST['rows'] == '2') {
    $showTwoIconRows = true;
  }
}

// hosts providing API and Forecasts and Alerts

if ($useProdNWS) {
  // final production URLs
  define('APIURL', "https://api.weather.gov");
  define('FCSTURL', "https://forecast.weather.gov");
  define('ALERTAPIURL', 'https://api.weather.gov/alerts?active=1&point=');
  define('ALERTURL', 'https://alerts.weather.gov/products/');
}
else {
  // pre-production/testing URLs
  define('APIURL', "https://api.weather.gov");
  define('FCSTURL', "https://forecast-v3.weather.gov");
  define('ALERTAPIURL','https://api.weather.gov/alerts?active=1&point=');
  define('ALERTURL', 'https://alerts-v2.weather.gov/products/'); 
}

// get the selected zone code
$haveZone = '0';
if (!empty($_GET['z']) && preg_match("/^[0-9]+$/i", htmlspecialchars($_GET['z']))) {
  $haveZone = htmlspecialchars(strip_tags($_GET['z'])); // valid zone syntax from input
}
$DualImageAvailable = file_exists("./DualImage.php") ? true : false;
// $DualImageAvailable = false;

if (isset($_REQUEST['convert'])) { // display new URLs if requested
  gen_new_settings();
  exit;
}

if (!isset($NWSforecasts[0])) {
  // print "<!-- making NWSforecasts array default -->\n";
  $NWSforecasts = array(
    "$NOAAZone||$fileName"
  ); // create default entry
}

//  print "<!-- NWSforecasts\n".print_r($NWSforecasts,true). " -->\n";
// Set the default zone. The first entry in the $SITE['NWSforecasts'] array.

list($Nz, $Nl, $Nn) = explode('|', $NWSforecasts[0] . '|||');
$NOAAZone = $Nz;
$NOAAlocation = $Nl;
$fileName = $Nn;
$newFormat = false;

if (!isset($NWSforecasts[$haveZone])) {
  $haveZone = 0;
}

// locations added to the drop down menu and set selected zone values

$dDownMenu = '';

for ($m = 0; $m < count($NWSforecasts); $m++) { // for each locations
  list($Nzone, $Nlocation, $Nname) = explode('|', $NWSforecasts[$m] . '|||');
  $dDownMenu.= "     <option value=\"" . $m . "\">" . $Nlocation . "</option>\n";
  if ($haveZone == $m) {
    $NOAAZone = $Nzone;
    $NOAAlocation = $Nlocation;
    $fileName = $Nname;
  }
}

// build the drop down menu

$ddMenu = '';

// create menu if at least two locations are listed in the array

if (isset($NWSforecasts[0]) and isset($NWSforecasts[1])) {
  $ddMenu.= '<tr align="center">
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

		// -->

	</script>
 <form action="" method="get">
 <p><select name="z" onchange="this.form.submit()">
 <option value=""> - Select Forecast - </option>
' . $dDownMenu . $ddMenu . '     </select></p>
	 <div><noscript><pre><input name="submit" type="submit" value="Get Forecast" /></pre></noscript></div>
	 </form>
	</td>
 </tr>
';
}

// You can now force the cache to update by adding ?force=1 to the end of the URL

if (empty($_REQUEST['force'])) $_REQUEST['force'] = "0";
$Force = $_REQUEST['force'];
$forceBackup = false;

if ($Force > 1) {
  $forceBackup = true;
}

$cacheName = $cacheFileDir . "forecast-" . $NOAAZone . "-$haveZone-json.txt";

// dont change the $backupfileName!
// new Zone URL with V5.00:

$Status = "<!-- $Version on PHP " . phpversion() . "-->\n<!-- RAW NWS URL: $fileName  zone=$NOAAZone -->\n";

if (isset($_REQUEST['sce']) && strtolower($_REQUEST['sce']) == 'view') {

  // --self downloader --

  $filenameReal = __FILE__;
  $download_size = filesize($filenameReal);
  header('Pragma: public');
  header('Cache-Control: private');
  header('Cache-Control: no-cache, must-revalidate');
  header("Content-type: text/plain,charset=ISO-8859-1");
  header("Accept-Ranges: bytes");
  header("Content-Length: $download_size");
  header('Connection: close');
  readfile($filenameReal);
  exit;
}

global $fcstPeriods, $NWSICONLIST;
load_lookups();
$usingFile = "";

if (!function_exists('date_default_timezone_set')) {
  if (!ini_get('safe_mode')) {
    putenv("TZ=$ourTZ"); // set our timezone for 'as of' date on file
  }
}
else {
  date_default_timezone_set($ourTZ);
}

if (version_compare(PHP_VERSION, '5.3', '<')) {
  print "<p>This script requires PHP V5.3+ to operate. You are running PHP " . PHP_VERSION . "<br/>";
  print "Please update PHP to be able to run $Version script</p>\n";
  $forecasttitles = array();
  $forecasttext = array();
  $forecasticons = array();
  $forecasttemp = array();
  print $Status;
  exit;
}

// grab the meta info for point, zone and forecast office + gridpoint URLs to use

if (strpos($iconType, 'gif') !== false and $DualImageAvailable) {
  $DualImageAvailable = false;
  $Status.= "<!-- DualImage function is not available with $iconType icons;     -->\n";
  $Status.= "<!-- first image of any dual-image found will be displayed instead -->\n";
}

$oldFileName = $fileName;
$backupfileName = APIURL . "/zones/forecast/$NOAAZone/forecast";
list($fileName, $pointURL) = convert_filename($fileName, $NOAAZone); // correct the filename if necessary to API format

$META = array();
$META = get_meta_info($cacheName, $fileName, $backupfileName);

if (isset($META['forecastZone']) and $META['forecastZone'] !== $NOAAZone) {
  $Status.= "<!-- WARNING: NOAAZone='$NOAAZone' is not correct.  Will use '" . $META['forecastZone'] . "' for this point location per NWS. -->\n";
}


// V5.11 - use the META data for the real gridpoint forecast and zone forecast URLs.
$pointURL = $META['forecastURL'];
$fileName = $META['forecastURL'];
$backupfileName = APIURL . "/zones/forecast/".$META['forecastZone']."/forecast";
$Status .= "<!-- point forecastURL = '$pointURL' -->\n";
$Status .= "<!-- zone  forecastURL = '$backupfileName' -->\n";

$html = '';
$lastURL = '';

if ($Force == 1 or 
    !file_exists($cacheName) or 
		(file_exists($cacheName) and filemtime($cacheName) + $refreshTime < time())) {

  $html = ADV_fetchUrlWithoutHanging($fileName);
  $stuff = explode("\r\n\r\n",$html); // maybe we have more than one header due to redirects.
  $content = (string)array_pop($stuff); // last one is the content
  $headers = (string)array_pop($stuff); // next-to-last-one is the headers
  preg_match('/HTTP\/\S+ (\d+)/', $headers, $m);
	//$Status .= "<!-- m=".print_r($m,true)." -->\n";
	//$Status .= "<!-- html=".print_r($html,true)." -->\n";
	$lastRC = (string)$m[1];
  if ($lastRC >= '400') {
    $Force = 2;
    $Status.= "<!-- Oops.. point forecast unavailable RC=" . $lastRC . " - using Zone instead -->\n";
  } else {
    $lastURL = $fileName; // remember if error encountered
    $fSize = strlen($html);
    $Status.= "<!-- loaded point-forecast $fileName - $fSize bytes -->\n";
		if (strpos($content, '{') !== false and $lastRC == '200') { // got a file.. save it
			$fp = fopen($cacheName, "w");
			if ($fp) {
				$write = fputs($fp, $html);
				fclose($fp);
				$Status.= "<!-- wrote cache file $cacheName -->\n";
			} else {
				$Status.= "<!-- unable to write cache file $cacheName -->\n";
			}
		}
		
  }
}

if ($Force == 2) {
  $usingFile = "(Zone forecast)";
  $html = ADV_fetchUrlWithoutHanging($backupfileName);
  $lastURL = $backupfileName; // remember if error encountered
  $fSize = strlen($html);
  $Status.= "<!-- loaded $usingFile $backupfileName - $fSize bytes -->\n";
  if (strpos($html, '{') !== false) { // got a file.. save it
    $fp = fopen($cacheName, "w");
    if ($fp) {
      $write = fputs($fp, $html);
      fclose($fp);
      $Status.= "<!-- wrote cache file $cacheName -->\n";
    }
    else {
      $Status.= "<!-- unable to write cache file $cacheName -->\n";
    }
  }
}

if (strlen($html) < 50) { // haven't loaded it by fetch.. load from cache
  $html = file_get_contents($cacheName);
  $fSize = strlen($html);
  $Status.= "<!-- loaded cache file $cacheName - $fSize bytes -->\n";
  $stuff = explode("\r\n\r\n",$html); // maybe we have more than one header due to redirects.
  $content = (string)array_pop($stuff); // last one is the content
  $headers = (string)array_pop($stuff); // next-to-last-one is the headers


  if (preg_match('/Temporary|Location:|defaulting to|window\.location\.href\=/Uis', $headers)) {
    $usingFile = "(Zone forecast)";
    $html = ADV_fetchUrlWithoutHanging($backupfileName);
    $lastURL = $backupfileName; // remember if error encountered
    $fSize = strlen($html);
    $Status.= "<!-- loaded $usingFile $backupfileName - $fSize bytes -->\n";
  }
}

if ($Force != 2) {

  // check point-forecast age and load zone forecast if too old

  preg_match('!"updated":\s*"([^"]+)"!is', $html, $matches);
  if (isset($matches[1])) {
    $age = time() - strtotime($matches[1]);
    if ($age > 18 * 60 * 60) {
      $agehms = sec2hmsADV($age);
      $Status.= "<!-- point forecast more than 18hrs old (age h:m:s is $agehms) .. use Zone forecast instead -->\n";
      $Force = 2;
      $usingFile = "(Zone forecast)";
      $html = ADV_fetchUrlWithoutHanging($backupfileName);
      $lastURL = $backupfileName; // remember if error encountered
      $fSize = strlen($html);
      $Status.= "<!-- loaded $usingFile $backupfileName - $fSize bytes -->\n";
      if (strpos($html, '{') !== false) { // got a file.. save it
        $fp = fopen($cacheName, "w");
        if ($fp) {
          $write = fputs($fp, $html);
          fclose($fp);
          $Status.= "<!-- wrote cache file $cacheName -->\n";
        }
        else {
          $Status.= "<!-- unable to write cache file $cacheName -->\n";
        }
      }
    }
  }
}

// now split off the headers from the contents of the return

$stuff = explode("\r\n\r\n",$html); // maybe we have more than one header due to redirects.
$content = (string)array_pop($stuff); // last one is the content
$headers = (string)array_pop($stuff); // next-to-last-one is the headers
$FCSTJSON = json_decode($content, true); // parse the JSON into an associative array

if (strlen($content > 10) and function_exists('json_last_error')) { // report status, php >= 5.3.0 only
  switch (json_last_error()) {
  case JSON_ERROR_NONE:
    $JSONerror = '- No errors';
    break;

  case JSON_ERROR_DEPTH:
    $JSONerror = '- Maximum stack depth exceeded';
    break;

  case JSON_ERROR_STATE_MISMATCH:
    $JSONerror = '- Underflow or the modes mismatch';
    break;

  case JSON_ERROR_CTRL_CHAR:
    $JSONerror = '- Unexpected control character found';
    break;

  case JSON_ERROR_SYNTAX:
    $JSONerror = '- Syntax error, malformed JSON';
    break;

  case JSON_ERROR_UTF8:
    $JSONerror = '- Malformed UTF-8 characters, possibly incorrectly encoded';
    break;

  default:
    $JSONerror = '- Unknown error';
    break;
  }

  $Status.= "<!-- JSON decode $JSONerror -->\n";
  if (jason_last_error() !== JSON_ERROR_NONE) {
    $Status.= "<!-- content='" . print_r($content, true) . " -->\n";
  }
}

/*
<!-- FCSTJSON
Array
{
"periods": [
{
"number": 1,
"name": "Today",
"startTime": "2017-05-01T07:00:00-07:00",
"endTime": "2017-05-01T18:00:00-07:00",
"isDaytime": true,
"temperature": 83,
"temperatureUnit": "F",
"temperatureTrend": null,
"windSpeed": "8 to 17 mph",
"windDirection": "NW",
"icon": "https://api.weather.gov/icons/land/day/skc?size=medium",
"shortForecast": "Sunny",
"detailedForecast": "Sunny, with a high near 83. Northwest wind 8 to 17 mph, with gusts as high as 23 mph."
},
{
"number": 2,
"name": "Tonight",
"startTime": "2017-05-01T18:00:00-07:00",
"endTime": "2017-05-02T06:00:00-07:00",
"isDaytime": false,
"temperature": 53,
"temperatureUnit": "F",
"temperatureTrend": "rising",
"windSpeed": "3 to 17 mph",
"windDirection": "NW",
"icon": "https://api.weather.gov/icons/land/night/skc?size=medium",
"shortForecast": "Clear",
"detailedForecast": "Clear. Low around 53, with temperatures rising to around 55 overnight. Northwest wind 3 to 17 mph, with gusts as high as 23 mph."
},

*/

if ($getGridpointData and isset($META['forecastGridDataURL'])) {

  // grab the gridpoint JSON if need be

  get_gridpoint_data($cacheName, $META['forecastGridDataURL'], $Force, 3600);
}

if ($getHourlyData and isset($META['forecastHourlyURL'])) {

  // grab the Hourly JSON if need be

  get_hourly_data($cacheName, $META['forecastHourlyURL'], $Force, 3600);
}


// now process the point or zone forecast

if (isset($FCSTJSON['periods'][0]['icon']) and 
		strpos($FCSTJSON['periods'][0]['icon'], 'icons') !== false) { // got a point forecast

  // -------------- POINT forecast process -----------------

  $isZone = false;
  $rawForecasts = $FCSTJSON['periods'];
  $Status.= "<!-- point forecast processing -->\n\n";
  /*
  <!-- rawForecasts
  Array
  (
  [0] => Array
  (
  [number] => 1
  [name] => Today
  [startTime] => 2016-11-16T08:00:00-08:00
  [endTime] => 2016-11-16T18:00:00-08:00
  [isDaytime] => 1
  [temperature] => 66
  [windSpeed] => 15 mph
  [windDirection] => NW
  [icon] => https://api-v1.weather.gov/icons/land/day/rain_showers,20/sct,20?size=medium
  [shortForecast] => Slight Chance Rain Showers then Mostly Sunny
  [detailedForecast] => A slight chance of rain showers, mainly before 10am. Mostly sunny, with a high near 66. Northwest wind around 15 mph, with gusts as high as 20 mph. Chance of precipitation is 20%. New rainfall amounts less than a tenth of an inch possible.
  )
  [1] => Array
  (
  [number] => 2
  [name] => Tonight
  [startTime] => 2016-11-16T18:00:00-08:00
  [endTime] => 2016-11-17T06:00:00-08:00
  [isDaytime] =>
  [temperature] => 45
  [windSpeed] =>  6 to 15 mph
  [windDirection] => NW
  [icon] => https://api-v1.weather.gov/icons/land/night/sct?size=medium
  [shortForecast] => Partly Cloudy
  [detailedForecast] => Partly cloudy, with a low around 45. Northwest wind 6 to 15 mph, with gusts as high as 20 mph.
  )
  */
  $rawUpdated = $FCSTJSON['updated'];
  if (isset($META['timeZone'])) {
    date_default_timezone_set($META['timeZone']);
  }

  $forecastupdated = date($timeFormat, strtotime($rawUpdated));
	$ourPoint = '';
	if (preg_match('|/([^/]+)/forecast|i', $fileName, $matches)) {
		$ourPoint = $matches[1];
	}

	if(is_array($FCSTJSON['geometry']) and isset($FCSTJSON['geometry'][0])) {
	  $forecastlatlong = $FCSTJSON['geometry'][0];
		} else {
		  if(!is_array($FCSTJSON['geometry'])) {
				$forecastlatlong = $FCSTJSON['geometry'];
			} else {

		    $forecastlatlong = $ourPoint;
			}
	}
	
  $forecastcity = $NOAAlocation;
  $i = 0;
  foreach($rawForecasts as $ptr => $FCST) {

    // we'll be setting up:
    // $forecasticons[x]  x = 0 thru 13   This is the icon and text around it
    // $forecasttemp[x] x= 0 thru 13    This is forecast temperature with styling
    // $forecasttitles[x]  x = 0 thru 13   This is the title word for the text forecast time period
    // $forecasttext[x]  x = 0 thru 13  This is the detail text for the text forecast time period
    // $Status .= "<!-- FCST[$i]\n".print_r($FCST,true)." -->\n";

    if (strlen($FCST['name']) < 5) { // sometimes have a left-over entry w/o a name.. skip it
      continue;
    }

    $forecastcond[$i] = $FCST['shortForecast'];
    $forecasticon[$i] = convert_to_local_icon($FCST['icon']);
    if ($FCST['isDaytime']) {
      $color = '#FF0000';
      $tHL = 'Hi';
    }
    else {
      $color = '#0000FF';
      $tHL = 'Lo';
    }

    $tTrend = '';
    if (!is_null($FCST['temperatureTrend'])) {
      if ($FCST['temperatureTrend'] == 'rising') {
        $tTrend = ' &uarr;';
      }

      if ($FCST['temperatureTrend'] == 'falling') {
        $tTrend = ' &darr;';
      }
    }

    $forecasttemp[$i] = "$tHL <span style=\"color: $color;\">" . round($FCST['temperature'], 0) . " &deg;F$tTrend</span>";
    $forecasttitles[$i] = $FCST['name'];
    $forecasttext[$i] = str_replace("\n", ' ', $FCST['detailedForecast']); // sub blank for embedded NL
    $forecasticons[$i] = make_local_icon($forecasticon[$i], $forecasttitles[$i], $forecastcond[$i], $forecasttemp[$i], $forecasttext[$i]);
    $forecasticons[$i] = preg_replace('/&/', '&amp;', $forecasticons[$i]);
    if ($doDebug) {
      $Status.= "<!-- i=$i name='" . $forecasttitles[$i] . "' " . "cond='" . $forecastcond[$i] . "' " . "temp='" . $forecasttemp[$i] . "' " . "\nrawicon='" . $FCST['icon'] . "' " . "\ncvticon='" . $forecasticon[$i] . "' " . "\ndetail='" . $forecasttext[$i] . "' " . "\nicon='" . $forecasticons[$i] . "' -->\n\n";
    }

    $i++;
  } // end foreach FCST point processing
}
elseif (isset($FCSTJSON['periods'])) { // got a ZONE forecast

  // $Status .= "<!-- FCSTJSON \n".print_r($FCSTJSON,true)." -->\n";
  // -------------- ZONE forecast process -----------------

  $isZone = true;
  $Status.= "<!-- ZONE forecast processing -->\n\n";
  $rawForecasts = $FCSTJSON['periods'];
  $rawUpdated = $FCSTJSON['updated'];
  if (isset($META['timeZone'])) {
    date_default_timezone_set($META['timeZone']);
  }

  $forecastupdated = date($timeFormat, strtotime($rawUpdated));
  $forecastlatlong = $NOAAZone;
  $forecastcity = $NOAAlocation;
  $usingFile = "(Zone forecast)";
  $Conditions = array(); // prepare for parsing the icon based on the text forecast
  load_cond_data(); // initialize the conditions to look for
  /*
  <!-- FCSTJSON
  Array
  (
  [periods] => Array
  (
  [0] => Array
  (
  [number] => 1
  [name] => Today
  [detailedForecast] => Partly sunny this morning
  )
  [1] => Array
  (
  [number] => 2
  [name] => Tonight
  [detailedForecast] => Partly cloudy. Lows around 40. North winds 5 to 10 mph
  with gusts up to 20 mph.
  )
  [2] => Array
  (
  [number] => 3
  [name] => Saturday
  [detailedForecast] => Partly sunny in the morning
  )
  [3] => Array
  (
  [number] => 4
  [name] => Saturday Night
  [detailedForecast] => Mostly cloudy with a 40 percent chance of
  showers. Lows in the lower 40s. Northeast winds 5 to 10 mph
  )
  */

  //     Breakup multi-day forecasts if needed

  $i = 0;

  //  foreach ($headers[1] as $j => $period) {

  foreach($FCSTJSON['periods'] as $j => $FCST) {
    $period = $FCST['name'];
    $fcsttext = $FCST['detailedForecast'];

    // $Status .= "<!-- raw $j='$period' '$fcsttext' -->\n";

    $fcsttext = str_replace("\n", ' ', $fcsttext);     // remove embedded new-line characters
		$fcsttext = preg_replace('|\s+|is',' ',$fcsttext); // remove extra spaces
    if (strpos($fcsttext, '&&') !== false) {
      $fcsttext = substr($fcsttext, 0, strpos($fcsttext, '&&') - 1);
    }

    $fcsttext = trim($fcsttext);
    $Status.= "<!-- adj FCSTJSON['periods'][$j]='$period' '$fcsttext' -->\n";
    if (preg_match('/^(.*) (Through|And) (.*)/i', $period, $mtemp)) { // got period1 thru period2
      list($fcstLow, $fcstHigh) = explode("\t", split_fcst($fcsttext));
      $startPeriod = $mtemp[1];
      $periodType = $mtemp[2];
      $endPeriod = $mtemp[3];
      $startIndex = 0;
      $endIndex = 0;
      $Status.= "<!-- splitting $periodType '$period'='$fcsttext' -->\n";
      for ($k = 0; $k < count($fcstPeriods); $k++) { // find Starting and ending period indices
        if (!$startIndex and $startPeriod == $fcstPeriods[$k]) {
          $startIndex = $k;
        }

        if ($startIndex and !$endIndex and $endPeriod == $fcstPeriods[$k]) {
          $endIndex = $k;
          break;
        }
      }

      for ($k = $startIndex; $k <= $endIndex; $k++) { // now generate the period names and appropriate fcst
        if (preg_match('|night|i', $fcstPeriods[$k])) {
          $forecasttext[$i] = $fcstLow;
        }
        else {
          $forecasttext[$i] = $fcstHigh;
        }

        $forecasttitles[$i] = $fcstPeriods[$k];
        $Status.= "<!-- $periodType $j, $i, '" . $forecasttitles[$i] . "'='" . $forecasttext[$i] . "' -->\n";
        $i++;
      }

      $Status.= "<!-- end splitting -->\n\n";
      continue;
    }

    $forecasttitles[$i] = $period;
    $forecasttext[$i] = strip_tags($fcsttext);
    $Status.= "<!-- normal $j, $i, '" . $forecasttitles[$i] . "'='" . $forecasttext[$i] . "' -->\n\n";
    $i++;
  } // end of multi-day forecast split
  $nfcsts = $i - 1;
  for ($i = 0; $i <= $nfcsts; $i++) { // interpret the text for icons, summary, temp, PoP
    list($forecasticons[$i], $forecasttemp[$i], $forecastpop[$i]) = explode("\t", make_zone_icon($forecasttitles[$i], $forecasttext[$i]));
    $forecasticons[$i] = preg_replace('/&/', '&amp;', $forecasticons[$i]);
    if ($doDebug) {
      $Status.= "<!-- i=$i name='" . $forecasttitles[$i] . "' " . "temp='" . $forecasttemp[$i] . "' " . "\ndetail='" . $forecasttext[$i] . "' " . "\nicon='" . $forecasticons[$i] . "' -->\n\n";
    }
  } // end interpret text for icons

  // end forecast ZONE processing

}
else { // Oops.. got neither point nor zone forecast.
  $forecasttitles = array();
  $forecasttext = array();
  $forecasticons = array();
  $forecasttemp = array();
  $PrintMode = true;
  if (isset($doPrintNWS) && !$doPrintNWS) {
    $PrintMode = false;;
  }

  if (isset($_REQUEST['inc']) && strtolower($_REQUEST['inc']) == 'noprint') {
    $PrintMode = false;
  }

  if ($PrintMode) { ?>
  <table style="width: 640px; border: none;">
    <tr style="text-align: center;">
      <td><b>National Weather Service Forecast for: </b><span style="color: green;">
           <?php
    echo $NOAAlocation; ?></span>
      </td>
    </tr>
    <?php
    echo $ddMenu ?>
   </table>
<?php
  }

  if ($PrintMode) {
    print "<p>Sorry.. the forecast for $NOAAlocation is not available at this time.</p>\n";
  }

  print $Status;
  if ($PrintMode and strlen($headers) > 0) {
    print "<p>NWS server $lastURL has an error.</p>\n";
    print "<p>View the source of this page for additional information in HTML comments.</p>\n";
  }

  $forecastValid = false;
  return; // back to the calling program (if any)
} // end zone/point/nada forecast processing
$forecastValid = true;
$Status.= "<!-- forecast updated '$rawUpdated' ($forecastupdated) for lat,long or zone '$forecastlatlong' -->\n";

// -- now get an alert for the zone (if any)

$forecastwarnings = '';
$alertCacheName = str_replace('.txt', '-alerts.txt', $cacheName);
$alerthtml = '';

if ($Force == 1 or $Force == 2 or !file_exists($alertCacheName) or (file_exists($alertCacheName) and filemtime($alertCacheName) + 300 < time())) {
  $alerthtml = ADV_fetchUrlWithoutHanging($alertURL);
  $fSize = strlen($alerthtml);
  $Status.= "<!-- loaded alert for $NOAAZone from $alertURL - $fSize bytes -->\n";
  if (preg_match('/Location: (.*)\r\n/Uis', $alerthtml, $matches)) {
    $newLoc = $matches[1];
    $newurl = APIURL . $newLoc;
    $alerthtml = ADV_fetchUrlWithoutHanging($newurl);
    $fSize = strlen($alerthtml);
    $Status.= "<!-- loaded alert from redirect $newurl - $fSize bytes -->\n";
  }

  if (strpos($alerthtml, '[') !== false) { // got a file.. save it
    $fp = fopen($alertCacheName, "w");
    if ($fp) {
      $write = fputs($fp, $alerthtml);
      fclose($fp);
      $Status.= "<!-- wrote cache file $alertCacheName -->\n";
    }
    else {
      $Status.= "<!-- unable to write cache file $alertCacheName -->\n";
    }
  }
}
elseif (file_exists($alertCacheName)) {
  $alerthtml = file_get_contents($alertCacheName);
  $Status.= "<!-- alerts loaded from $alertCacheName -->\n";
}
else {
  $Status.= "<!-- alerts information not available -->\n";
}

list($alertHeaders, $alertContents) = explode("\r\n\r\n", $alerthtml . "\r\n\r\n");

if (strlen($alertContents) > 1) { // got some alerts.. process
  $ALERTJSON = json_decode($alertContents, true);
  if($doDebug) {$Status.= "<!-- ALERTJSON\n" . print_r($ALERTJSON, true) . " -->\n";}
  /*
  <!-- ALERTJSON
  Array
  (
  [0] => Array
  (
  [id] => NWS-IDP-PROD-2135097-1993381
  [event] => Wind Advisory
  [product_issued] => 2016-11-19T11:29:39+00:00
  [product_expires] => 2016-11-19T18:00:00+00:00
  [event_starts] => 2016-11-19T11:29:00+00:00
  [event_ends] => 2016-11-20T02:00:00+00:00
  [headline] => Wind Advisory issued November 19 at 3:29AM PST expiring November 19 at 6:00PM PST by NWS San Francisco CA
  [urgency] => Expected
  [severity] => Moderate
  [certainty] => Likely
  [description] => * TIMING...TO 6 PM SATURDAY EVENING.
  * LOCATION...SAN FRANCISCO...SAN FRANCISCO BAY SHORELINE...SAN
  FRANCISCO PENINSULA COAST...SANTA CLARA VALLEY...EAST BAY
  HILLS...SANTA CRUZ MOUNTAINS.
  */

  // use the API alerts feed

  if (is_array($ALERTJSON) and count($ALERTJSON) > 0 and isset($ALERTJSON['@graph'])) {
    $Status.= "<!-- preparing " . count($ALERTJSON['@graph']) . " warning links -->\n";
    $Status.= "<!-- now " . date($timeFormat) . " (" . gmdate($timeFormat) . ") -->\n";
    foreach($ALERTJSON['@graph'] as $i => $ALERT) {
      $expireUTC = strtotime($ALERT['expires']);
      $Status.= "<!-- alert expires " . date($timeFormat, $expireUTC) . " (" . $ALERT['expires'] . ") -->\n";
      if (time() < $expireUTC) {
        $forecastwarnings.= '<a href="' . ALERTURL . $ALERT['id'] . '"' . ' title="' . $ALERT['headline'] . "\n---\n" . $ALERT['description'] . '" target="_blank">' . '<strong><span style="color: red">' . $ALERT['event'] . "</span></strong></a><br/>\n";
      }
      else {
        $Status.= "<!-- alert " . $ALERT['id'] . " " . $ALERT['headline'] . " expired - " . $ALERT['expires'] . " -->\n";
      }
    }
  }
  else {
    $Status.= "<!-- no current hazard alerts for $NOAAZone -->\n";
  }
} // end API feed processing

/*
if(!file_put_contents($URLcacheFile,serialize($URLcache))) {
	$Status .= "<!-- Error: unable to save URLcache $URLcacheFile -->\n";
} else {
	$Status .= "<!-- URL cache saved to $URLcacheFile with ".count($URLcache). " entries. -->\n";
}
*/

$forecastoffice = '';

if (isset($META['WFOname'])) {
  $forecastoffice = "National Weather Service " . $META['WFOname'];
}

if (isset($META['city']) and isset($META['state'])) {
  $forecastcity = $META['city'] . ', ' . $META['state'];
}

$IncludeMode = false;
$PrintMode = true;

if (isset($doPrintNWS) && !$doPrintNWS) {
  print $Status; // <------ print before return
  return;
}

if (isset($_REQUEST['inc']) && strtolower($_REQUEST['inc']) == 'noprint') {
  print $Status; // <------ print before return
  return;
}

if (isset($_REQUEST['inc']) && strtolower($_REQUEST['inc']) == 'y') {
  $IncludeMode = true;
}

if (isset($doIncludeNWS)) {
  $IncludeMode = $doIncludeNWS;
}

// finally, we can print the results if desired

if (!$IncludeMode and $PrintMode) { ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>NWS Forecast for <?php
  echo $forecastcity; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>
<body style="font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px; background-color:#FFFFFF">

<?php
}

print $Status;

// if the forecast text is blank, prompt the visitor to force an update

if (strlen($forecasttext[0]) < 2 and $PrintMode) {
  if (!isset($PHP_SELF)) {
    $PHP_SELF = $_SERVER['PHP_SELF'];
  }

  echo '<br/><br/>Forecast blank? <a href="' . $PHP_SELF . '?force=1">Force Update</a><br/><br/>';
}

if ($PrintMode) {
  $tw = ($iconWidth + 8) * (1 + count($forecasticons) / 2);
  if ($tw < 620) {
    $tw = 620;
  }

  $wdth = $iconWidth + 10 . 'px';
?>
  <table style="width: <?php
  echo $tw; ?>px; border: none;">
    <tr style="text-align: center;">
      <td><b>National Weather Service Forecast for: </b><span style="color: green;">
           <?php
  echo $forecastcity; ?></span><br />
        Issued by: <?php
  echo $forecastoffice; ?>
      </td>
    </tr>
    <tr>
      <td style="text-align: center">Updated: <?php
  echo $forecastupdated; ?>
          </td><!--end forecastupdated-->
    </tr>
    <?php
  echo $ddMenu ?>
    <tr>
	  <td style="text-align: center; font-size: 18px; margin: 0px auto;"><b><?php
  echo $NOAAlocation; ?></b>
      <?php
  if ($showZoneWarning and $isZone <> '') {
    print "<br/><div style=\"border: 1px solid red; padding: 5px; font-size: 12px; margin: 3px;\">";
    print "<small>The detailed point forecast weather data is not currently available.<br/>";
    print "The zone forecast data for $NOAAZone (" . $META['zoneName'] . ") ";
    print "will be displayed<br/>until the point forecast data is again available.";
		if(isset($META['WFOphone']) and isset($META['WFOemail'])) {
			$t = parse_url($META['forecastGridDataURL']);
			print "<br/>If this persists, contact the NWS ".$META['WFOname']." WFO <br/> at ".
			  $META['WFOphone'] . " or email at ". $META['WFOemail'].
				"<br/> to have them update the point forecast for " . $t['path'] . 
				" on " . $t['host'];
		}
    print "</small></div>\n";
  }

?></td>
    </tr>
    <tr>
     <td align="center">&nbsp;
   <table style="width: <?php
  echo $tw; ?>px; border: none; border-collapse: collapse; border-spacing: 2px;">
<?php
  if ($showTwoIconRows) { // show all icons in two rows

    // now loop over the $forecasticons array to build the two table rows with icons

    if (stripos($forecasttitles[0], 'night') !== false) {
      $iStart = - 1;
    }
    else {
      $iStart = 0;
    }

    print "<tr>\n";
    for ($i = 0; $i <= count($forecasticons); $i = $i + 2) { // day icon+cond
      print '<td style="width: ' . $wdth . '; text-align: center; vertical-align: top;">';
      if (isset($forecasticons[$i + $iStart])) {
        print $forecasticons[$i + $iStart];
      }
      else {
        print "&nbsp;";
      }

      print "</td>\n";
    }

    print "</tr>\n";
    print "<tr>\n";
    for ($i = 0; $i <= count($forecasticons); $i = $i + 2) { // day temperatures
      print '<td style="width: ' . $wdth . '; text-align: center; vertical-align: top;">';
      if (isset($forecasttemp[$i + $iStart])) {
        print $forecasttemp[$i + $iStart];
      }
      else {
        print "&nbsp;";
      }

      print "</td>\n";
    }

    print "</tr>\n";
    print "<tr><td colspan=\"8\">&nbsp;</td></tr>\n";
    print "<tr>\n";
    for ($i = 1; $i <= count($forecasticons) + 1; $i = $i + 2) { // night icons+conds
      print '<td style="width: ' . $wdth . '; text-align: center; vertical-align: top;">';
      if (isset($forecasticons[$i + $iStart])) {
        print $forecasticons[$i + $iStart];
      }
      else {
        print "&nbsp;";
      }

      print "</td>\n";
    }

    print "</tr>\n";
    print "<tr>\n";
    for ($i = 1; $i <= count($forecasticons) + 1; $i = $i + 2) { // night temperatures
      print '<td style="width: ' . $wdth . '; text-align: center; vertical-align: top;">';
      if (isset($forecasttemp[$i + $iStart])) {
        print $forecasttemp[$i + $iStart];
      }
      else {
        print "&nbsp;";
      }

      print "</td>\n";
    }

    print "</tr>\n";
    print "</table>\n\n";
    print "</td>\n</tr>\n";
  }
  else { // show only first 9 icons (old style)
?>
      <tr valign ="top" align="center">
<?php
    for ($i = 0; $i < 9; $i++) {
      print "<td style=\"width: 11%;\"><span style=\"font-size: 8pt;\">$forecasticons[$i]</span></td>\n";
    }

?>
  </tr>
  <tr valign ="top" align="center">
  <?php
    for ($i = 0; $i < 9; $i++) {
      print "<td style=\"width: 11%;\">$forecasttemp[$i]</td>\n";
    }

?>
  </tr>
  </table>
  </td>
 </tr>
<?php
  } // end show only first 9 icons (old style)
?>
</table>
  <p><?php
  if ($forecastwarnings <> '') {
    print $forecastwarnings;
  }

?>&nbsp;</p>

<table style="width: <?php
  echo $tw; ?>px; border: none; border-collapse: collapse; border-spacing: 2px;">
<?php
  for ($i = 0; $i < count($forecasttitles); $i++) {
    print "<tr>\n";
    print "<td style=\"width: 20%; text-align: left; vertical-align: top;\"><b>$forecasttitles[$i]</b><br />&nbsp;<br /></td>\n";
    print "<td style=\"width: 80%; text-align: left; vertical-align: top;\">$forecasttext[$i]</td>\n";
    print "</tr>\n";
  }

?>
</table>

<p>&nbsp;</p>
<p>Forecast from <a href="<?php
  if (strlen($usingFile) > 0) {
    echo htmlspecialchars('https://forecast.weather.gov/MapClick.php?zoneid='.$META['forecastZone'].'&zflg=1');
  }
  else {
		list($lat,$lon) = explode(',',$META['point']);
		echo htmlspecialchars("https://forecast.weather.gov/MapClick.php?lat=$lat&lon=$lon&unit=0&lg=english");
//    echo htmlspecialchars( FCSTURL."/point/".$META['point']);
  } ?>">NOAA-NWS</a>
for <?php
  echo $forecastcity; ?>. <?php
  if ($isZone) {
    echo "(Zone forecast for " . $META['zoneName'] . ")";
  }
  else {
    echo $usingFile;
  }

  if ($iconType == '.gif') {
    print "<br/>Animated forecast icons courtesy of <a href=\"http://www.meteotreviglio.com/\">www.meteotreviglio.com</a>.";
  }

?>
</p>
<?php
} // end printmode

if (!$IncludeMode and $PrintMode) { ?>
</body>
</html>
<?php
}
// end mainline code -- used functions are below
// ------------------------------------------------------------------------------------------
// FUNCTIONS

function ADV_fetchUrlWithoutHanging($inurl)
{

  // get contents from one URL and return as string

  global $Status, $needCookie /*, $URLcache */;
  $useFopen = false;
  $overall_start = time();
  if (!$useFopen) {

    // Set maximum number of seconds (can have floating-point) to wait for feed before displaying page without feed

    $numberOfSeconds = 6;
		$url = $inurl;
    // Thanks to Curly from ricksturf.com for the cURL fetch functions

    $data = '';
    $domain = parse_url($url, PHP_URL_HOST);
    $theURL = str_replace('nocache', '?' . $overall_start, $url); // add cache-buster to URL if needed
    $Status.= "<!-- curl fetching '$theURL' -->\n";
    $ch = curl_init(); // initialize a cURL session
    curl_setopt($ch, CURLOPT_URL, $theURL); // connect to provided URL
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // don't verify peer certificate
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (advforecast2.php (JSON) - saratoga-weather.org)');
//    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:58.0) Gecko/20100101 Firefox/58.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, // request LD-JSON format
    array(
      "Accept: application/ld+json"
    ));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $numberOfSeconds); //  connection timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, $numberOfSeconds); //  data timeout
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return the data transfer
    curl_setopt($ch, CURLOPT_NOBODY, false); // set nobody
    curl_setopt($ch, CURLOPT_HEADER, true); // include header information

      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);              // follow Location: redirect
      curl_setopt($ch, CURLOPT_MAXREDIRS, 1);                      //   but only one time

    if (isset($needCookie[$domain])) {
      curl_setopt($ch, $needCookie[$domain]); // set the cookie for this request
      curl_setopt($ch, CURLOPT_COOKIESESSION, true); // and ignore prior cookies
      $Status.= "<!-- cookie used '" . $needCookie[$domain] . "' for GET to $domain -->\n";
    }

    $data = curl_exec($ch); // execute session
    if (curl_error($ch) <> '') { // IF there is an error
      $Status.= "<!-- curl Error: " . curl_error($ch) . " -->\n"; //  display error notice
    }

    $cinfo = curl_getinfo($ch); // get info on curl exec.
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
		if($url !== $cinfo['url'] and $cinfo['http_code'] == 200 and
		   strpos($url,'/points/') > 0 and strpos($cinfo['url'],'/gridpoints/') > 0) {
			# only cache point forecast->gridpoint forecast successful redirects
			$Status .= "<!-- note: fetched '".$cinfo['url']."' after redirect was followed. -->\n";
			//$URLcache[$inurl] = $cinfo['url'];
			//$Status .= "<!-- $inurl added to URLcache -->\n";
		}

    $Status.= "<!-- HTTP stats: " . " RC=" . $cinfo['http_code'];
		if (isset($cinfo['primary_ip'])) {
			$Status .= " dest=" . $cinfo['primary_ip'];
		}
    if (isset($cinfo['primary_port'])) {
      $Status .= " port=" . $cinfo['primary_port'];
    }

    if (isset($cinfo['local_ip'])) {
      $Status.= " (from sce=" . $cinfo['local_ip'] . ")";
    }

    $Status.= "\n      Times:" . 
		" dns=" . sprintf("%01.3f", round($cinfo['namelookup_time'], 3)) . 
		" conn=" . sprintf("%01.3f", round($cinfo['connect_time'], 3)) . 
		" pxfer=" . sprintf("%01.3f", round($cinfo['pretransfer_time'], 3));
    if ($cinfo['total_time'] - $cinfo['pretransfer_time'] > 0.0000) {
      $Status.= " get=" . sprintf("%01.3f", round($cinfo['total_time'] - $cinfo['pretransfer_time'], 3));
    }

    $Status.= " total=" . sprintf("%01.3f", round($cinfo['total_time'], 3)) . " secs -->\n";

    // $Status .= "<!-- curl info\n".print_r($cinfo,true)." -->\n";

    curl_close($ch); // close the cURL session

    // $Status .= "<!-- raw data\n".$data."\n -->\n";
    $stuff = explode("\r\n\r\n",$data); // maybe we have more than one header due to redirects.
    $content = (string)array_pop($stuff); // last one is the content
    $headers = (string)array_pop($stuff); // next-to-last-one is the headers

    if ($cinfo['http_code'] <> '200') {
      $Status.= "<!-- headers returned:\n" . $headers . "\n -->\n";
    }

    return $data; // return headers+contents
  }
  else {

    //   print "<!-- using file_get_contents function -->\n";

    $STRopts = array(
      'http' => array(
        'method' => "GET",
        'protocol_version' => 1.1,
        'header' => "Cache-Control: no-cache, must-revalidate\r\n" . 
					"Cache-control: max-age=0\r\n" . 
					"Connection: close\r\n" . 
					"User-agent: Mozilla/5.0 (advforecast2.php - saratoga-weather.org)\r\n" . 
					"Accept: application/ld+json\r\n"
      ) ,
      'ssl' => array(
        'method' => "GET",
        'protocol_version' => 1.1,
				'verify_peer' => false,
        'header' => "Cache-Control: no-cache, must-revalidate\r\n" . 
					"Cache-control: max-age=0\r\n" . 
					"Connection: close\r\n" . 
					"User-agent: Mozilla/5.0 (advforecast2.php - saratoga-weather.org)\r\n" . 
					"Accept: application/ld+json\r\n"
      )
    );
    $STRcontext = stream_context_create($STRopts);
    $T_start = ADV_fetch_microtime();
    $xml = file_get_contents($inurl, false, $STRcontext);
    $T_close = ADV_fetch_microtime();
    $headerarray = get_headers($url, 0);
    $theaders = join("\r\n", $headerarray);
    $xml = $theaders . "\r\n\r\n" . $xml;
    $ms_total = sprintf("%01.3f", round($T_close - $T_start, 3));
    $Status.= "<!-- file_get_contents() stats: total=$ms_total secs -->\n";
    $Status.= "<-- get_headers returns\n" . $theaders . "\n -->\n";

    //   print " file() stats: total=$ms_total secs.\n";

    $overall_end = time();
    $overall_elapsed = $overall_end - $overall_start;
    $Status.= "<!-- fetch function elapsed= $overall_elapsed secs. -->\n";

    //   print "fetch function elapsed= $overall_elapsed secs.\n";

    return ($xml);
  }
} // end ADV_fetchUrlWithoutHanging

// ------------------------------------------------------------------

function ADV_fetch_microtime()
{
  list($usec, $sec) = explode(" ", microtime());
  return ((float)$usec + (float)$sec);
}

// ------------------------------------------------------------------------------------------
// split off Low and High from multiday forecast

function split_fcst($fcst)
{
  global $Status;
  $f = explode(". ", $fcst . ' ');
  $lowpart = 0;
  $highpart = 0;
  foreach($f as $n => $part) { // find the Low and High sentences
    if (preg_match('/Low/i', $part)) {
      $lowpart = $n;
    }

    if (preg_match('/High/i', $part)) {
      $highpart = $n;
    }
  }

  $f[$lowpart] = preg_replace('|(\d+) below|s', "-$1", $f[$lowpart]);
  $f[$lowpart] = preg_replace('/( above| below| zero)/s', '', $f[$lowpart]);
  $f[$lowpart].= '.';
  $f[$highpart] = preg_replace('|(\d+) below|s', "-$1", $f[$highpart]);
  $f[$highpart] = preg_replace('/( above| below| zero)/s', '', $f[$highpart]);
  $f[$highpart].= '.';
  $replpart = min($lowpart, $highpart) - 1;
  $fcststr = '';
  for ($i = 0; $i <= $replpart; $i++) {
    $fcststr.= $f[$i] . '. ';
  } // generate static fcst text
  $fcstLow = $fcststr . ' ' . $f[$lowpart];
  $fcstHigh = $fcststr . ' ' . $f[$highpart];
  return ("$fcstLow\t$fcstHigh");
}

// ------------------------------------------------------------------------------------------
// function make_zone_icon: parse text and find suitable icon from zone forecast text for period

function make_zone_icon($day, $textforecast)
{
  global $Conditions, $Status, $iconDir, $iconType, $doDebug, $iconHeight, $iconWidth;

  $icon = "<strong>";
  if (strpos($day, ' ') !== false) {
    $icon.= wordwrap($day, 10, "<br/>", false) . '<br/>';
  }
  else {
    $icon.= $day . '<br/><br/>';
  }

  $icon.= '</strong>';
  $temperature = 'n/a';
  $pop = '';
  $iconimage = 'na.jpg';
  $condition = 'N/A';
  if (preg_match('|(\S+) (\d+) percent|', $textforecast, $mtemp)) { // found a PoP
    $pop = $mtemp[2];
  }

  if (preg_match('|Chance of precipitation is (\d+)\s*%|', $textforecast, $mtemp)) { // found a zone pop
    $pop = $mtemp[1];
    if ($doDebug) {
      $Status.= "<!-- pop='$pop' found -->\n";
    }
  }

  // handle negative temperatures in zone forecast

  $textforecast = preg_replace('/([\d|-]+) below/i', "-$1", $textforecast);
  $textforecast = preg_replace('/zero/', '0', $textforecast);
  if (preg_match('/(High[s]{0,1}|Low[s]{0,1}|Temperatures nearly steady|Temperatures falling to|Temperatures rising to|Near steady temperature|with a high|with a low) (in the upper|in the lower|in the mid|in the low to mid|in the lower to mid|in the mid to upper|in the|around|near|nearly|above|below|from) ([\d|-]+)[s]{0,1}/i', $textforecast, $mtemp)) { // found temp
    if ($doDebug) {
      $Status.= "<!-- mtemp " . print_r($mtemp, true) . " -->\n";
    }

    if (isset($mtemp[1]) and preg_match('|with a |', $mtemp[1])) {
      $mtemp[1] = ucfirst(preg_replace('|with a |', '', $mtemp[1]));
      if ($doDebug) {
        $Status.= "<!-- mtemp modded to " . print_r($mtemp, true) . " -->\n";
      }
    }

    if (substr($mtemp[1], 0, 1) == 'T' or substr($mtemp[1], 0, 1) == 'N') { // use day for highs/night for lows if 'Temperatures nearly steady'
      $mtemp[1] = 'Highs';
      if (preg_match('|night|i', $day)) {
        $mtemp[1] = 'Lows';
      }
    }

    $tcolor = '#FF0000';
    if (strtoupper(substr($mtemp[1], 0, 1)) == 'L') {
      $tcolor = '#0000FF';
    }

    $temperature = ucfirst(substr($mtemp[1], 0, 2) . ' <span style="color: ' . $tcolor . '">');
    $t = $mtemp[3]; // the raw temp
    if (preg_match('/(low to mid|lower to mid|mid to upper|upper|lower|mid)/', $mtemp[2], $ttemp)) {
      if ($doDebug) {
        $Status.= "<!-- ttemp " . print_r($ttemp, true) . " -->\n";
      }

      $t = $t + 5;
      if ($ttemp[1] == 'upper') {
        $temperature.= '&gt;' . $t;
      }

      if ($ttemp[1] == 'lower') {
        $temperature.= '&lt;' . $t;
      }

      if ($ttemp[1] == 'mid') {
        $temperature.= '&asymp;' . $t;
      }

      if ($ttemp[1] == 'low to mid' or $ttemp[1] == 'lower to mid') {
        $t = $t - 2;
        $temperature.= '&asymp;' . $t;
      }

      if ($ttemp[1] == 'mid to upper') {
        $t = $t + 2;
        $temperature.= '&asymp;' . $t;
      }
    }

    if (!isset($ttemp[1]) and !preg_match('/(near|around)/', $mtemp[2])) {

      // special case '[Highs|Lows] in the dds.'

      $t = $t + 5;
      $temperature.= '&asymp;' . $t;
    }
    elseif (preg_match('/(near|around)/', $mtemp[2], $ttemp)) {
      $temperature.= '&asymp;' . $mtemp[3];
    }

    $temperature.= '&deg;F</span>';
  }

  if (preg_match('/(Highs|Lows) ([\d|-]+) to ([\d|-]+)/i', $textforecast, $mtemp)) { // temp range forecast
    $tcolor = '#FF0000';
    if (strtoupper(substr($mtemp[1], 0, 1)) == 'L') {
      $tcolor = '#0000FF';
    }

    $temperature = ucfirst(substr($mtemp[1], 0, 2) . ' <span style="color: ' . $tcolor . '">');
    $tavg = sprintf("%0d", round(($mtemp[3] + $mtemp[2]) / 2, 0));
    $temperature.= '&asymp;' . $tavg . '&deg;F</span>';
  }

  if ($temperature == 'n/a') {
    if (preg_match('|night|i', $day)) {
      $temperature = 'Lo ' . $temperature;
    }
    else {
      $temperature = 'Hi ' . $temperature;
    }
  }

  // now look for harshest conditions first.. (in order in -data file

  reset($Conditions); // Do search in load order
  foreach($Conditions as $cond => $condrec) { // look for matching condition
    if (preg_match("!$cond!i", $textforecast, $mtemp)) {
      list($dayicon, $nighticon, $condition) = explode("\t", $condrec);
      if (preg_match('|night|i', $day)) {
        $iconimage = $nighticon . $pop . $iconType;
      }
      else {
        $iconimage = $dayicon . $pop . $iconType;
      }

      break;
    }
  } // end of conditions search
  $iconimage = preg_replace('|skc\d+|', 'skc', $iconimage); // handle funky SKC+a POP in forecast.
  $shortCond = str_replace('hunderstorm', "-Storm", $condition);
  $icon.= '<img src="' . $iconDir . $iconimage .
	  '" height="' . $iconHeight . '" width="' . $iconWidth . '" ' .
		'alt="' . $condition . '" title="' . $condition . '" /><br/>' . $shortCond;
  return ("$icon\t$temperature\t$pop");
} // end make_zone_icon function

// ------------------------------------------------------------------------------------------
// load the $Conditions array for icon selection based on key phrases

function load_cond_data()
{
  global $Conditions, $Status;
  $Condstring = '
#
cond|tornado|nsvrtsra|nsvrtsra|Severe storm|
cond|showery or intermittent. Some thunder|scttsra|nscttsra|Showers storms|
cond|thunder possible|scttsra|nscttsra|Showers storms|
cond|thunder|tsra|ntsra|Thunder storm|
cond|rain and sleet|raip|nraip|Rain Sleet|
cond|freezing rain and snow|fzra_sn|nfzra_sn|FrzgRn Snow|
cond|snow and freezing rain|fzra_sn|nfzra_sn|FrzgRn Snow|
cond|chance of snow and rain|rasn|nrasn|Chance Snow/Rain|
cond|chance of rain and snow|rasn|nrasn|Chance Snow/Rain|
cond|rain and snow|rasn|nrasn|Rain and Snow|
cond|rain or snow|rasn|nrasn|Rain or Snow|
cond|freezing rain|fzra|fzra|Freezing Rain|
cond|rain likely|ra|nra|Rain likely|
cond|snow showers|sn|nsn|Snow showers|
cond|showers likely|shra|nshra|Showers likely|
cond|chance showers|shra|nshra|Chance showers|
cond|isolated showers|shra|nshra|Isolated showers|
cond|scattered showers|shra|nshra|Scattered showers|
cond|chance of rain|ra|nra|Chance rain|
cond|rain|ra|nra|Rain|
cond|mix|rasn|rasn|Mix|
cond|sleet|ip|nip|Sleet|
cond|snow|sn|nsn|Snow|
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
cond|partly sunny and windy|wind_sct|nwind_sct|Partly Sunny|
cond|mostly sunny and windy|wind_few|nwind_few|Mostly Sunny|
cond|partly sunny and breezy|wind_sct|nwind_sct|Partly Sunny|
cond|mostly sunny and breezy|wind_few|nwind_few|Mostly Sunny|
cond|partly sunny|sct|nsct|Partly Sunny|
cond|mostly sunny|few|nfew|Mostly Sunny|
cond|mostly clear|few|nfew|Mostly Clear|
cond|sunny|skc|nskc|Sunny|
cond|clear|skc|nskc|Clear|
cond|fair|few|nfew|Fair|
cond|cloud|bkn|nbkn|Variable Clouds|
#
';
  $config = explode("\n", $Condstring);
  foreach($config as $key => $rec) { // load the parser condition strings
    $recin = trim($rec);
    if ($recin and substr($recin, 0, 1) <> '#') { // got a non comment record
      list($type, $keyword, $dayicon, $nighticon, $condition) = explode('|', $recin . '|||||');
      if (isset($type) and strtolower($type) == 'cond' and isset($condition)) {
        $Conditions["$keyword"] = "$dayicon\t$nighticon\t$condition";
      }
    } // end if not comment or blank
  } // end loading of loop over config recs
} // end of load_cond_data function

// ------------------------------------------------------------------------------------------

function load_lookups()
{

  // static lookup arrays

  global $fcstPeriods, $NWSICONLIST;
  $fcstPeriods = array( // for filling in the '<period> Through <period>' zone forecasts.
    'Monday',
    'Monday Night',
    'Tuesday',
    'Tuesday Night',
    'Wednesday',
    'Wednesday Night',
    'Thursday',
    'Thursday Night',
    'Friday',
    'Friday Night',
    'Saturday',
    'Saturday Night',
    'Sunday',
    'Sunday Night',
    'Monday',
    'Monday Night',
    'Tuesday',
    'Tuesday Night',
    'Wednesday',
    'Wednesday Night',
    'Thursday',
    'Thursday Night',
    'Friday',
    'Friday Night',
    'Saturday',
    'Saturday Night',
    'Sunday',
    'Sunday Night'
  );
  /* original list:
  $NWSICONLIST = array(
  #  index is new icon name
  #  imagename is original image name (for which we have icons available)
  #  PoP flag controls if PoP is written on output or not.
  #  ScePosition controls if image is pulled from Left, Middle or Right of source image
  #  Text Name - optional, just a reminder of what it means
  #  imgname | PoP?=[YN] | ScePosition=[LMR] | Text Name (optional)
  'bkn' =>  'bkn|N|L|Broken Clouds',
  'night/bkn' =>  'nbkn|N|L|Night Broken Clouds',
  'blizzard' =>  'blizzard|Y|L|Blizzard',
  'night/blizzard' =>  'nblizzard|Y|L|Night Blizzard',
  'cold' =>  'cold|Y|L|Cold',
  'night/cold' =>  'cold|Y|L|Cold',
  #  'cloudy' =>  'cloudy|Y|L|Overcast (old cloudy)',
  'dust' =>  'du|N|M|Dust',
  'night/dust' =>  'ndu|N|M|Night Dust',
  #  'fc' =>  'fc|N|L|Funnel Cloud',
  #  'nsvrtsra' =>  'nsvrtsra|N|L|Funnel Cloud (old)',
  'few' =>  'few|Y|L|Few Clouds',
  'night/few' =>  'nfew|Y|L|Night Few Clouds',
  #  'fg' =>  'fg|N|R|Fog',
  #  'br' =>  'br|Y|R|Fog / mist old',
  #  'fu' =>  'fu|N|L|Smoke',
  'fog' =>  'nfg|N|R|Night Fog',
  'night/fog' =>  'nfg|N|R|Night Fog',
  'fzra' =>  'fzra|Y|L|Freezing rain',
  'night/fzra' =>  'nfzra|Y|L|Night Freezing Rain',
  #  'fzrara' =>  'fzrara|Y|L|Rain/Freezing Rain (old)',
  'snow_fzra' =>  'fzra_sn|Y|L|Freezing Rain/Snow',
  'night/snow_fzra' =>  'nfzra_sn|Y|L|Night Freezing Rain/Snow',
  #  'mix' =>  'mix|Y|L|Freezing Rain/Snow',
  #  'hi_bkn' =>  'hi_bkn|Y|L|Broken Clouds (old)',
  #  'hi_few' =>  'hi_few|Y|L|Few Clouds (old)',
  #  'hi_sct' =>  'hi_sct|N|L|Scattered Clouds (old)',
  #  'hi_skc' =>  'hi_skc|N|L|Clear Sky (old)',
  #  'hi_nbkn' =>  'hi_nbkn|Y|L|Night Broken Clouds (old)',
  #  'hi_nfew' =>  'hi_nfew|Y|L|Night Few Clouds (old)',
  #  'hi_nsct' =>  'hi_nsct|N|L|Night Scattered Clouds (old)',
  #  'hi_nskc' =>  'hi_nskc|N|L|Night Clear Sky (old)',
  #  'hi_nshwrs' =>  'hi_nshwrs|Y|R|Night Showers',
  #  'hi_ntsra' =>  'hi_ntsra|Y|L|Night Thunderstorm',
  #  'hi_shwrs' =>  'hi_shwrs|Y|R|Showers',
  #  'hi_tsra' =>  'hi_tsra|Y|L|Thunderstorm',
  'hur_warn' =>  'hur_warn|N|L|Hurrican Warning',
  'night/hur_warn' =>  'hur_warn|N|L|Hurrican Warning',
  'hur_watch' =>  'hur_watch|N|L|Hurricane Watch',
  'night/hur_watch' =>  'hur_watch|N|L|Hurricane Watch',
  #  'hurr' =>  'hurr|N|L|Hurrican Warning old',
  #  'hurr-noh' =>  'hurr-noh|N|L|Hurricane Watch old',
  'hazy' =>  'hz|N|L|Haze',
  'night/hazy' =>  'hz|N|L|Haze',
  #  'hazy' =>  'hazy|N|L|Haze old',
  'hot' =>  'hot|N|R|Hot',
  'night/hot' =>  'hot|N|R|Hot',
  'sleet' =>  'ip|Y|L|Ice Pellets',
  'night/sleet' =>  'nip|Y|L|Night Ice Pellets',
  #  'minus_ra' =>  'minus_ra|Y|L|Stopped Raining',
  #  'ra1' =>  'ra1|N|L|Stopped Raining (old)',
  #  'mist' =>  'mist|N|R|Mist (fog) (old)',
  #  'ncloudy' =>  'ncloudy|Y|L|Overcast night(old ncloudy)',
  #  'ndu' =>  'ndu|N|M|Night Dust',
  #  'nfc' =>  'nfc|N|L|Night Funnel Cloud',
  #  'nbr' =>  'nbr|Y|R|Night Fog/mist (old)',
  #  'nfu' =>  'nfu|N|L|Night Smoke',
  #  'nmix' =>  'nmix|Y|30|Night Freezing Rain/Snow (old)',
  #  'nrasn' =>  'nrasn|Y|M|Night Snow (old)',
  #  'pcloudyn' =>  'pcloudyn|Y|L|Night Partly Cloudy (old)',
  #  'nscttsra' =>  'nscttsra|Y|M|Night Scattered Thunderstorm',
  #  'nsn_ip' =>  'nsn_ip|Y|L|Night Snow/Ice Pellets (old)',
  #  'nwind' =>  'nwind|N|5|Night Windy/Clear (old)',
  'ovc' =>  'ovc|N|L|Overcast',
  'night/ovc' =>  'novc|N|L|Night Overcast',
  'rain' =>  'ra|Y|30|Rain',
  'night/rain' =>  'nra|Y|30|Night Rain',
  'rain_sleet' =>  'raip|Y|M|Rain/Ice Pellets',
  'night/rain_sleet' =>  'nraip|Y|M|Night Rain/Ice Pellets',
  'rain_fzra' =>  'ra_fzra|Y|30|Rain/Freezing Rain',
  'night/rain_fzra' =>  'nra_fzra|Y|30|Night Freezing Rain',
  'rain_snow' =>  'ra_sn|Y|M|Rain/Snow',
  'night/rain_snow' =>  'nra_sn|Y|M|Night Rain/Snow',
  #  'rasn' =>  'rasn|Y|M|Rain/Snow (old)',
  'sct' =>  'sct|N|L|name',
  'night/sct' =>  'nsct|N|L|Night Scattered Clouds',
  #  'pcloudy' =>  'pcloudy|Y|L|Partly Cloudy (old)',
  #  'scttsra' =>  'scttsra|Y|M|name',
  'rain_showers' =>  'shra|Y|10|Rain Showers',
  'night/rain_showers' =>  'nshra|Y|8|Night Rain Showers',
  #  'shra2' =>  'shra2|N|10|Rain Showers (old)',
  'skc' =>  'skc|N|L|Clear',
  'night/skc' =>  'nskc|N|L|Night Clear',
  'snow' =>  'sn|Y|L|Snow',
  'night/snow' =>  'nsn|Y|L|Night Snow',
  'snow_sleet' =>  'snip|Y|L|Snow/Ice Pellets',
  'night/snow_sleet' =>  'nsnip|Y|L|Night Snow/Ice Pellets',
  'smoke' =>  'smoke|N|L|Smoke',
  'night/smoke' =>  'nsmoke|N|L|Smoke', // NEW - Nov-2016
  #  'sn_ip' =>  'sn_ip|Y|L|Snow/Ice Pellets (old)',
  #  'tcu' =>  'tcu|N|L|Towering Cumulus (old)',
  'tornado' =>  'tor|N|L|Tornado',
  'night/tornado' =>  'ntor|N|L|Night Tornado',
  'tsra' =>  'tsra|Y|10|Thunderstorm',
  'night/tsra' =>  'ntsra|Y|8|Night Thunderstorm',
  #  'tstormn' =>  'tstormn|N|L|Thunderstorm night (old)',
  #  'ts_nowarn' =>  'ts_nowarn|N|L|Tropical Storm',
  'ts_warn' =>  'ts_warn|N|L|Tropical Storm Warning',
  'night/ts_warn' =>  'ts_warn|N|L|Tropical Storm Warning',
  #  'tropstorm-noh' =>  'tropstorm-noh|N|L|Tropical Storm old',
  #  'tropstorm' =>  'tropstorm|N|L|Tropical Storm Warning old',
  'ts_watch' =>  'ts_watch|N|L|Tropical Storm Watch',
  'night/ts_watch' =>  'ts_watch|N|L|Tropical Storm Watch',
  #  'ts_hur_flags' =>  'ts_hur_flags|Y|L|Hurrican Warning old',
  #  'ts_no_flag' =>  'ts_no_flag|Y|L|Tropical Storm old',
  'wind_bkn' =>  'wind_bkn|N|8|Windy/Broken Clouds',
  'night/wind_bkn' =>  'nwind_bkn|N|5|Night Windy/Broken Clouds',
  'wind_few' =>  'wind_few|N|8|Windy/Few Clouds',
  'night/wind_few' =>  'nwind_few|N|5|Night Windy/Few Clouds',
  'wind_ovc' =>  'wind_ovc|N|8|Windy/Overcast',
  'night/wind_ovc' =>  'nwind_ovc|N|5|Night Windy/Overcast',
  'wind_sct' =>  'wind_sct|N|8|Windy/Scattered Clouds',
  'night/wind_sct' =>  'nwind_sct|N|5|Night Windy/Scattered Clouds',
  'wind_skc' =>  'wind_skc|N|8|Windy/Clear',
  'night/wind_skc' =>  'nwind_skc|N|5|Night Windy/Clear',
  #  'wind' =>  'wind|N|L|Windy/Clear (old)',
  'na'       => 'N|L|Not Available',
  );
  */
  $NWSICONLIST = array(

    //  index is new icon name
    //  imagename is original image name (for which we have icons available)
    //  PoP flag controls if PoP is written on output or not.
    //  ScePosition controls if image is pulled from Left, Middle or Right of source image
    //  Text Name - optional, just a reminder of what it means
    //  imgname | PoP?=[YN] | ScePosition=[LMR] | Text Name (optional)

    'bkn' => 'bkn|N|L|Broken Clouds',
    'night/bkn' => 'nbkn|N|L|Night Broken Clouds',
    'blizzard' => 'blizzard|Y|L|Blizzard',
    'night/blizzard' => 'nblizzard|Y|L|Night Blizzard',
    'cold' => 'cold|Y|L|Cold',
    'night/cold' => 'cold|Y|L|Cold',

    //  'cloudy' =>  'cloudy|Y|L|Overcast (old cloudy)',

    'dust' => 'du|N|22|Dust',
    'night/dust' => 'ndu|N|22|Night Dust',

    //  'fc' =>  'fc|N|L|Funnel Cloud',
    //  'nsvrtsra' =>  'nsvrtsra|N|L|Funnel Cloud (old)',

    'few' => 'few|Y|L|Few Clouds',
    'night/few' => 'nfew|Y|L|Night Few Clouds',

    //  'fg' =>  'fg|N|R|Fog',
    //  'br' =>  'br|Y|R|Fog / mist old',
    //  'fu' =>  'fu|N|L|Smoke',

    'fog' => 'fg|N|R|Fog',
    'night/fog' => 'nfg|N|R|Night Fog',
    'fzra' => 'fzra|Y|L|Freezing rain',
    'night/fzra' => 'nfzra|Y|L|Night Freezing Rain',

    //  'fzrara' =>  'fzrara|Y|L|Rain/Freezing Rain (old)',

    'snow_fzra' => 'fzra_sn|Y|L|Freezing Rain/Snow',
    'night/snow_fzra' => 'nfzra_sn|Y|L|Night Freezing Rain/Snow',

    //  'mix' =>  'mix|Y|L|Freezing Rain/Snow',
    //  'hi_bkn' =>  'hi_bkn|Y|L|Broken Clouds (old)',
    //  'hi_few' =>  'hi_few|Y|L|Few Clouds (old)',
    //  'hi_sct' =>  'hi_sct|N|L|Scattered Clouds (old)',
    //  'hi_skc' =>  'hi_skc|N|L|Clear Sky (old)',
    //  'hi_nbkn' =>  'hi_nbkn|Y|L|Night Broken Clouds (old)',
    //  'hi_nfew' =>  'hi_nfew|Y|L|Night Few Clouds (old)',
    //  'hi_nsct' =>  'hi_nsct|N|L|Night Scattered Clouds (old)',
    //  'hi_nskc' =>  'hi_nskc|N|L|Night Clear Sky (old)',
    //  'hi_nshwrs' =>  'hi_nshwrs|Y|R|Night Showers',
    //  'hi_ntsra' =>  'hi_ntsra|Y|L|Night Thunderstorm',
    //  'hi_shwrs' =>  'hi_shwrs|Y|R|Showers',
    //  'hi_tsra' =>  'hi_tsra|Y|L|Thunderstorm',

    'hur_warn' => 'hur_warn|N|L|Hurrican Warning',
    'night/hur_warn' => 'hur_warn|N|L|Hurrican Warning',
    "hurr_warn" => "hurr|N|L|Hurricane warning", // New NWS list
    "night/hurr_warn" => "hurr|N|L|Night Hurricane warning", // New NWS list
    'hur_watch' => 'hur_watch|N|L|Hurricane Watch',
    'night/hur_watch' => 'hur_watch|N|L|Hurricane Watch',
    "hurr_watch" => "hurr-noh|N|L|Hurricane watch", // New NWS list
    "night/hurr_watch" => "hurr-noh|N|L|Night Hurricane watch", // New NWS list
    'hurricane' =>  'hurr-noh|Y|L|Hurricane',
    'night/hurricane' =>  'hurr-noh|Y|L|Hurricane',
    //  'hurr' =>  'hurr|N|L|Hurrican Warning old',
    //  'hurr-noh' =>  'hurr-noh|N|L|Hurricane Watch old',

    'hazy' => 'hz|N|L|Haze',
    'night/hazy' => 'hz|N|L|Haze',
    "haze" => "hz|N|L|Haze", // New NWS list
    "night/haze" => "hz|N|L|Night Haze", // New NWS list

    //  'hazy' =>  'hazy|N|L|Haze old',

    'hot' => 'hot|N|R|Hot',
    'night/hot' => 'hot|N|R|Hot',
    'sleet' => 'ip|Y|L|Ice Pellets',
    'night/sleet' => 'nip|Y|L|Night Ice Pellets',

    //  'minus_ra' =>  'minus_ra|Y|L|Stopped Raining',
    //  'ra1' =>  'ra1|N|L|Stopped Raining (old)',
    //  'mist' =>  'mist|N|R|Mist (fog) (old)',
    //  'ncloudy' =>  'ncloudy|Y|L|Overcast night(old ncloudy)',
    //  'ndu' =>  'ndu|N|M|Night Dust',
    //  'nfc' =>  'nfc|N|L|Night Funnel Cloud',
    //  'nbr' =>  'nbr|Y|R|Night Fog/mist (old)',
    //  'nfu' =>  'nfu|N|L|Night Smoke',
    //  'nmix' =>  'nmix|Y|30|Night Freezing Rain/Snow (old)',
    //  'nrasn' =>  'nrasn|Y|M|Night Snow (old)',
    //  'pcloudyn' =>  'pcloudyn|Y|L|Night Partly Cloudy (old)',
    //  'nscttsra' =>  'nscttsra|Y|M|Night Scattered Thunderstorm',
    //  'nsn_ip' =>  'nsn_ip|Y|L|Night Snow/Ice Pellets (old)',
    //  'nwind' =>  'nwind|N|5|Night Windy/Clear (old)',

    'ovc' => 'ovc|N|L|Overcast',
    'night/ovc' => 'novc|N|L|Night Overcast',
    'rain' => 'ra|Y|30|Rain',
    'night/rain' => 'nra|Y|30|Night Rain',
    'rain_showers' => 'shra|Y|12|Rain Showers',
    'night/rain_showers' => 'nshra|Y|12|Night Rain Showers',
    "rain_showers_hi" => "hi_shwrs|Y|45|Rain showers (low cloud cover)", // New NWS list
    "night/rain_showers_hi" => "hi_nshwrs|Y|45|Night Rain showers (low cloud cover)", // New NWS list
    'rain_sleet' => 'raip|Y|M|Rain/Ice Pellets',
    'night/rain_sleet' => 'nraip|Y|M|Night Rain/Ice Pellets',
    'rain_fzra' => 'ra_fzra|Y|30|Rain/Freezing Rain',
    'night/rain_fzra' => 'nra_fzra|Y|30|Night Freezing Rain',
    'rain_snow' => 'ra_sn|Y|22|Rain/Snow',
    'night/rain_snow' => 'nra_sn|Y|22|Night Rain/Snow',

    //  'rasn' =>  'rasn|Y|M|Rain/Snow (old)',

    'sct' => 'sct|N|L|name',
    'night/sct' => 'nsct|N|L|Night Scattered Clouds',

    //  'pcloudy' =>  'pcloudy|Y|L|Partly Cloudy (old)',
    //  'scttsra' =>  'scttsra|Y|M|name',
    //  'shra2' =>  'shra2|N|10|Rain Showers (old)',

    'skc' => 'skc|N|L|Clear',
    'night/skc' => 'nskc|N|L|Night Clear',
    'snow' => 'sn|Y|L|Snow',
    'night/snow' => 'nsn|Y|L|Night Snow',
    'snow_sleet' => 'sn_ip|Y|L|Snow/Ice Pellets',
    'night/snow_sleet' => 'nsn_ip|Y|L|Night Snow/Ice Pellets',
    'smoke' => 'fu|N|L|Smoke',
    'night/smoke' => 'nfu|N|L|Smoke', // NEW - Nov-2016

    //  'sn_ip' =>  'sn_ip|Y|L|Snow/Ice Pellets (old)',
    //  'tcu' =>  'tcu|N|L|Towering Cumulus (old)',

    'tornado' => 'tor|N|L|Tornado',
    'night/tornado' => 'ntor|N|L|Night Tornado',
    'tsra' => 'tsra|Y|10|Thunderstorm',
    'night/tsra' => 'ntsra|Y|10|Night Thunderstorm',
    "tsra_sct" => "scttsra|Y|20|Thunderstorm (medium cloud cover)", // New NWS list
    "night/tsra_sct" => "nscttsra|Y|20|Night Thunderstorm (medium cloud cover)", // New NWS list
    "tsra_hi" => "hi_tsra|Y|L|Thunderstorm (low cloud cover)", // New NWS list
    "night/tsra_hi" => "hi_ntsra|Y|L|Night Thunderstorm (low cloud cover)", // New NWS list

    //  'tstormn' =>  'tstormn|N|L|Thunderstorm night (old)',
    //  'ts_nowarn' =>  'ts_nowarn|N|L|Tropical Storm',

    "ts_hurr_warn" => "ts_hur_flags|N|L|Tropical storm with hurricane warning in effect", // New NWS list
    "night/ts_hurr_warn" => "ts_hur_flags|N|L|Night Tropical storm with hurricane warning in effect", // New NWS list
		'tropical_storm' => 'tropstorm-noh|Y|L|Tropical Storm Warning',
		'night/tropical_storm' => 'tropstorm-noh|Y|L|Tropical Storm Warning',
    'ts_warn' => 'tropstorm|Y|L|Tropical Storm Warning',
    'night/ts_warn' => 'tropstorm|Y|L|Tropical Storm Warning',

    //  'tropstorm-noh' =>  'tropstorm-noh|N|L|Tropical Storm old',
    //  'tropstorm' =>  'tropstorm|N|L|Tropical Storm Warning old',

    'ts_watch' => 'tropstorm-noh|Y|L|Tropical Storm Watch',
    'night/ts_watch' => 'tropstorm-noh|Y|L|Tropical Storm Watch',

    //  'ts_hur_flags' =>  'ts_hur_flags|Y|L|Hurrican Warning old',
    //  'ts_no_flag' =>  'ts_no_flag|Y|L|Tropical Storm old',

    'wind_bkn' => 'wind_bkn|N|7|Windy/Broken Clouds',
    'night/wind_bkn' => 'nwind_bkn|N|7|Night Windy/Broken Clouds',
    'wind_few' => 'wind_few|N|7|Windy/Few Clouds',
    'night/wind_few' => 'nwind_few|N|7|Night Windy/Few Clouds',
    'wind_ovc' => 'wind_ovc|N|7|Windy/Overcast',
    'night/wind_ovc' => 'nwind_ovc|N|7|Night Windy/Overcast',
    'wind_sct' => 'wind_sct|N|7|Windy/Scattered Clouds',
    'night/wind_sct' => 'nwind_sct|N|7|Night Windy/Scattered Clouds',
    'wind_skc' => 'wind_skc|N|7|Windy/Clear',
    'night/wind_skc' => 'nwind_skc|N|7|Night Windy/Clear',

    //  'wind' =>  'wind|N|L|Windy/Clear (old)',

    'na' => 'na|N|L|Not Available',
  );
} // end load_lookups function

// ------------------------------------------------------------------------------------------

function convert_to_local_icon($icon)
{

  // input: https://api-v1.weather.gov/icons/land/day/rain_showers,20/sct,20?size=medium
  //        https://api-v1.weather.gov/icons/land/night/sct?size=medium
  // output: {iconDir}{icon}.{$iconType} or
  //         DualImage.php?i={lefticon}&j={righticon}&ip={leftpop}&jp={rightpop}

  global $Status, $NWSICONLIST, $iconDir, $iconType, $iconHeight, $iconWidth, $DualImageAvailable;
  $newicon = $icon; // for testing
  $uparts = parse_url($icon);
  $iparts = array_slice(explode('/', $uparts['path']) , 3); //get day|night/icon[/icon]

  // $Status .= "<!-- iparts \n".print_r($iparts,true)." -->\n";

  $daynight = ($iparts[0] == 'day') ? '' : 'night/';
  list($icon1, $pop1) = explode(',', $iparts[1] . ',');
  $doDual = false;
  if (isset($iparts[2])) {
    $doDual = true;
    list($icon2, $pop2) = explode(',', $iparts[2] . ',');
  }
  else {
    $icon2 = '';
    $pop2 = '';
  }

  // convert new API icon names to old image names

  if (isset($NWSICONLIST["${daynight}${icon1}"])) {
    list($nicon1, $rest) = explode('|', $NWSICONLIST["${daynight}${icon1}"]);
    $icon1 = $nicon1;
  }
  else {
    $Status.= "<!-- icon1='$icon1' not found - na used instead -->\n";
    $icon1 = 'na';
  }

  if ($icon2 <> '') {
    if (isset($NWSICONLIST["${daynight}${icon2}"])) {
      list($nicon2, $rest) = explode('|', $NWSICONLIST["${daynight}${icon2}"]);
      $icon2 = $nicon2;
    }
    else {
      $Status.= "<!-- icon2='$icon2' not found - na used instead -->\n";
      $icon2 = 'na';
    }
  }

  //  $Status .= "<!-- doDual='$doDual' DualImageAvailable='$DualImageAvailable' icon1=$icon1 icon2=$icon2 -->\n";

  if ($doDual and $DualImageAvailable) { // generate the DualImage.php script calling sequence for image
    $newicon = "DualImage.php?";
    $newicon.= "i=$icon1";
    if ($pop1 <> '') {
      $newicon.= "&ip=$pop1";
    }

    $newicon.= "&j=$icon2";
    if ($pop2 <> '') {
      $newicon.= "&jp=$pop2";
    }

    $Status.= "<!-- dual image '$newicon' used-->\n";
  }
  elseif (file_exists("${iconDir}${icon1}${pop1}${iconType}")) { // use the image as-is
    $newicon = "${iconDir}${icon1}${pop1}${iconType}";
    /*  } elseif ( $DualImageAvailable ) { // oops... pop icon doesn't exist but we can generate it
    $newicon = "DualImage.php?";
    $newicon .= "i=$icon1";
    if($pop1 <> '') { $newicon .= "&ip=$pop1"; }

    $Status .= "<!-- missing icon '${iconDir}${icon1}${pop1}${iconType}' .. using '$newicon' instead -->\n";
    */
  }
  elseif (file_exists("${iconDir}${icon1}${iconType}")) { // oops... pop icon doesn't exist
    $newicon = "${iconDir}${icon1}${iconType}";
    $Status.= "<!-- missing icon '${iconDir}${icon1}${pop1}${iconType}' .. " . "using '${iconDir}${icon1}${iconType}' instead -->\n";
  }
  else {
    $newicon = "${iconDir}na${iconType}";
    $Status.= "<!-- missing icon '${iconDir}${icon1}${pop1}${iconType}' .. " . "using '${iconDir}na${iconType}' instead -->\n";
  }

  return ($newicon);
} // end convert_to_local_icon

// ------------------------------------------------------------------------------------------

function make_local_icon($icon, $period, $cond, $temperature, $details)
{

  // assemble the full icon to use

  $ticon = '<strong>';
  if (strpos($period, ' ') !== false) {
    $ticon.= wordwrap($period, 10, "<br/>", false) . '<br/>';

    // $ticon = str_replace(' ','<br/>',$ticon) . '<br/>';

  }
  else {
    $ticon.= $period . '<br/><br/>';
  }

  $ticon.= '</strong>';
  $shortCond = str_replace('hunderstorm', "-Storm", $cond);
  $ticon.= '<img src="' . $icon . '" ' . "alt=\"$period: $cond\" " . "title=\"$period: $details\" /><br/>" . "$shortCond <br/>";
  $ticon = str_replace('size=medium', 'size=small', $ticon);
  return ($ticon);
} // end make_local_icon

// ------------------------------------------------------------------------------------------

function get_meta_info($mainCache, $pointURL, $zoneURL)
{

  //
  // this function saves up-to 3 accesses to the API site for metadata regarding the
  // point, zone and WFO info.  The three accesses are done, the JSON parsed and
  // saved into a -json-meta.txt file as straight JSON for use in the $META array
  // in the main script.
  // Note: the logic below assumes that the JSON-LD format is returned by the API.
  //

  global $Status, $doDebug;
  $compass = array(
    'N',
    'NNE',
    'NE',
    'ENE',
    'E',
    'ESE',
    'SE',
    'SSE',
    'S',
    'SSW',
    'SW',
    'WSW',
    'W',
    'WNW',
    'NW',
    'NNW'
  );

  // get and cache the meta information from the point, zone and WFO entries for the 'point'.
  // use a new JSON cache file to store the meta information we need
  //

  $metaCache = str_replace('-json.txt', '-json-meta.txt', $mainCache);

  // make the point-forecast URL into a metadata request URL

  $metaPointURL = str_replace('/forecast', '', $pointURL);
  $ourPoint = '';
  if (preg_match('|/([^/]+)/forecast|i', $pointURL, $matches)) {
    $ourPoint = $matches[1];
  }

  // make the zone forecast URL into a metadata request URL

  $metaZoneURL = $zoneURL;
  $metaZoneURL = str_replace('/forecast', '', $metaZoneURL);
  $metaZoneURL = str_replace('JSON-LD', 'forecast', $metaZoneURL);
  $ourZone = '';
  if (preg_match('|/([^/]+)/forecast|i', $zoneURL, $matches)) {
    $ourZone = $matches[1];
  }

  $Status.= "<!-- meta info re: point='$ourPoint' zone='$ourZone' metacache= '$metaCache' -->\n";
  $Status.= "<!-- metaZoneURL='$metaZoneURL' -->\n";
  $META = array();

  // First.. see if we've already cached this meta info.. it won't change once discovered for a point

  if (file_exists($metaCache)) {
    $recs = file_get_contents($metaCache);
    $META = json_decode($recs, true);
    $Status.= "<!-- loaded meta info from $metaCache -->\n";
    if (isset($META['point']) and $META['point'] !== $ourPoint) {

      // oops... changed the lat/long for the point .. reload the meta data

      $Status.= "<!-- point '" . $ourPoint . "' changed from '" . $META['point'] . "' - reloading meta data -->\n";
      unset($META);
      $META = array();
    }
  }
  else { // JSON ERROR
    $Status.= "<!-- Meta cache file not found .. reloading data from NWS site -->\n";
  }

  $saveNew = false;
  if (!isset($META['city']) or !isset($META['forecastURL'])) { // no city data or fcstURL in the saved metadata so load it
    $Status.= "<!-- getting metadata for point from $metaPointURL -->\n";
    $rawhtml = ADV_fetchUrlWithoutHanging($metaPointURL);
    $stuff = explode("\r\n\r\n",$rawhtml); // maybe we have more than one header due to redirects.
    $content = (string)array_pop($stuff); // last one is the content
    $headers = (string)array_pop($stuff); // next-to-last-one is the headers

    $PJSON = json_decode($content, true); // parse the JSON into an associative array
    if (json_last_error() == JSON_ERROR_NONE) { // got the point METADATA.. stuff it away
      if ($doDebug) {
        $Status.= "<!-- point raw data\n" . print_r($PJSON, true) . " -->\n";
      }

      if (isset($PJSON['relativeLocation']['city'])) {
        $META['city'] = $PJSON['relativeLocation']['city'];
        $META['state'] = $PJSON['relativeLocation']['state'];
        $distance = $PJSON['relativeLocation']['distance']['value'];
        $distance = floor(0.000621371 * $distance); // truncate to nearest whole mile
        $Status.= "<!-- distance=$distance from " . $META['city'] . " -->\n";
        if ($distance >= 2) {
          $angle = $PJSON['relativeLocation']['bearing']['value'];
          $direction = $compass[round($angle / 22.5) % 16];
          $t = $distance . ' ';
          $t.= ($distance > 1) ? "Miles" : "Mile";
          $t.= " $direction ";
          $META['city'] = $t . $META['city'];
        }

        $META['point'] = $ourPoint;
        $META['forecastOfficeURL'] = $PJSON['forecastOffice'];
				$META['forecastURL'] = $PJSON['forecast'];
        $META['forecastZoneURL'] = $PJSON['forecastZone'];
        $META['forecastZone'] = substr($META['forecastZoneURL'], strrpos($META['forecastZoneURL'], '/') + 1);
        $META['forecastHourlyURL'] = $PJSON['forecastHourly'];
        $META['forecastGridDataURL'] = $PJSON['forecastGridData'];
        $META['observationStationsURL'] = $PJSON['observationStations'];
        $META['countyZoneURL'] = $PJSON['county'];
        $META['countyZone'] = substr($META['countyZoneURL'], strrpos($META['countyZoneURL'], '/') + 1);
        $META['fireWeatherZoneURL'] = $PJSON['fireWeatherZone'];
        $META['fireWeatherZone'] = substr($META['fireWeatherZoneURL'], strrpos($META['fireWeatherZoneURL'], '/') + 1);
        $META['timeZone'] = $PJSON['timeZone'];
        $META['radarStation'] = $PJSON['radarStation'];
        $saveNew = true;
        if ($doDebug) {
          $Status.= "<!-- META data\n" . print_r($META, true) . " -->\n";
        }
      }
    }
    else { // JSON ERROR
      $Status.= "<!-- JSON ERROR with Point metadata content='" . print_r($content, true) . " -->\n";
    }
  }

  if (!isset($META['zoneName'])) { // no zone data.. load it
    $Status.= "<!-- getting metadata for forecast zone from $metaZoneURL -->\n";
    $rawhtml = ADV_fetchUrlWithoutHanging($metaZoneURL);
    $stuff = explode("\r\n\r\n",$rawhtml); // maybe we have more than one header due to redirects.
    $content = (string)array_pop($stuff); // last one is the content
    $headers = (string)array_pop($stuff); // next-to-last-one is the headers
    $PJSON = json_decode($content, true); // parse the JSON into an associative array
    if (json_last_error() == JSON_ERROR_NONE) { // got the point METADATA.. stuff it away
      if ($doDebug) {
        $Status.= "<!-- zone raw data\n" . print_r($PJSON, true) . " -->\n";
      }

      if (isset($PJSON['name'])) {
        $META['zoneName'] = $PJSON['name'];
        $saveNew = true;
      }
    }
    else { // JSON ERROR
      $Status.= "<!-- JSON ERROR with Zone metadata content='" . print_r($content, true) . " -->\n";
    }
  }

  if (!isset($META['WFOemail']) and isset($META['forecastOfficeURL'])) {

    // GET the WFO data

    $WFOmetaURL = $META['forecastOfficeURL'];
    $WFOmetaURL = str_replace('http://', 'https://', $WFOmetaURL);
    $Status.= "<!-- getting metadata for forecast office from $WFOmetaURL -->\n";
    $rawhtml = ADV_fetchUrlWithoutHanging($WFOmetaURL);
    $stuff = explode("\r\n\r\n",$rawhtml); // maybe we have more than one header due to redirects.
    $content = (string)array_pop($stuff); // last one is the content
    $headers = (string)array_pop($stuff); // next-to-last-one is the headers
    $PJSON = json_decode($content, true); // parse the JSON into an associative array
    if (json_last_error() == JSON_ERROR_NONE) { // got the point METADATA.. stuff it away
      if ($doDebug) {
        $Status.= "<!-- WFO raw data\n" . print_r($PJSON, true) . " -->\n";
      }

      if (isset($PJSON['name'])) {
        $META['WFOname'] = $PJSON['name'];
        if (isset($PJSON['address']['addressLocality']) and stripos($META['WFOname'], $PJSON['address']['addressLocality']) === false) {
          $META['WFOname'] = str_replace(',', '/' . $PJSON['address']['addressLocality'] . ',', $META['WFOname']);
        }
			  if (isset($PJSON['telephone'])) {
					$META['WFOphone'] = trim($PJSON['telephone']);
				}
				if (isset($PJSON['email'])) {
					$META['WFOemail'] = trim($PJSON['email']);
				}

        $saveNew = true;
      }
    }
    else { // JSON ERROR
      $Status.= "<!-- JSON ERROR with WFO metadata content='" . print_r($content, true) . " -->\n";
    }
  }

  if ($doDebug) {
    $Status.= "<!-- final META data\n" . print_r($META, true) . "\n saveNew=$saveNew -->\n";
  }

  if ($saveNew) { // save the file if we changed anything
    $outJSON = json_encode($META);
    $fp = fopen($metaCache, "w");
    if ($fp) {
      $write = fputs($fp, $outJSON);
      fclose($fp);
      $Status.= "<!-- wrote meta cache file $metaCache -->\n";
    }
    else {
      $Status.= "<!-- unable to write meta cache file $metaCache -->\n";
    }
  }

  $Status.= "<!-- META\n" . print_r($META, true) . " -->\n";
  return $META;
}

// ------------------------------------------------------------------------------------------

function get_gridpoint_data($mainCache, $gridpointURL, $forceFlag, $refreshTime)
{

  //
  //

  global $Status, $doDebug;

  // get and cache the gridpoint information
  //

  $gpCache = str_replace('-json.txt', '-json-gridpoint.txt', $mainCache);
  $gpURL = str_replace('http://', 'https://', $gridpointURL);
  $Status.= "<!-- gridpoint data cache= '$gpCache' from '$gpURL' -->\n";
  if ($forceFlag > 0 or !file_exists($gpCache) or (file_exists($gpCache) and filemtime($gpCache) + $refreshTime < time())) {
    $Status.= "<!-- getting gridpoint data -->\n";
    $rawhtml = ADV_fetchUrlWithoutHanging($gpURL);
    $stuff = explode("\r\n\r\n",$rawhtml); // maybe we have more than one header due to redirects.
    $content = (string)array_pop($stuff); // last one is the content
    $headers = (string)array_pop($stuff); // next-to-last-one is the headers

    if (strlen($content) > 500) {
      $fp = fopen($gpCache, "w");
      if ($fp) {
        $write = fputs($fp, $content);
        fclose($fp);
        $Status.= "<!-- wrote " . strlen($content) . " bytes to gridpoint data cache file $gpCache -->\n";
      }
      else {
        $Status.= "<!-- unable to write gridpoint data cache file $gpCache -->\n";
      }
    }
    else {
      $Status.= "<!-- unable to obtain gridpoint data .. headers:\n" . print_r($headers, true) . "\n -->\n";
    } // end got some content
  }
  else {
    $gpAge = time() - filemtime($gpCache);
    $Status.= "<!-- gridpoint data cache is current ($gpAge secs old). -->\n";
  } // end reload gridpoint data
} // end get_gridpoint_data

// ------------------------------------------------------------------------------------------

function get_hourly_data($mainCache, $gridpointURL, $forceFlag, $refreshTime)
{

  //
  //

  global $Status, $doDebug;

  // get and cache the gridpoint information
  //

  $gpCache = str_replace('-json.txt', '-json-hourly.txt', $mainCache);
  $gpURL = str_replace('http://', 'https://', $gridpointURL);
  $Status.= "<!-- hourly data cache= '$gpCache' from '$gpURL' -->\n";
  if ($forceFlag > 0 or !file_exists($gpCache) or (file_exists($gpCache) and filemtime($gpCache) + $refreshTime < time())) {
    $Status.= "<!-- getting hourly -->\n";
    $rawhtml = ADV_fetchUrlWithoutHanging($gpURL);
    $stuff = explode("\r\n\r\n",$rawhtml); // maybe we have more than one header due to redirects.
    $content = (string)array_pop($stuff); // last one is the content
    $headers = (string)array_pop($stuff); // next-to-last-one is the headers

    if (strlen($content) > 500) {
      $fp = fopen($gpCache, "w");
      if ($fp) {
        $write = fputs($fp, $content);
        fclose($fp);
        $Status.= "<!-- wrote " . strlen($content) . " bytes to hourly data cache file $gpCache -->\n";
      }
      else {
        $Status.= "<!-- unable to write hourly data cache file $gpCache -->\n";
      }
    }
    else {
      $Status.= "<!-- unable to obtain hourly data .. headers:\n" . print_r($headers, true) . "\n -->\n";
    } // end got some content
  }
  else {
    $gpAge = time() - filemtime($gpCache);
    $Status.= "<!-- hourly data cache is current ($gpAge secs old). -->\n";
  } // end reload hourly data
} // end get_hourly_data

// ------------------------------------------------------------------------------------------

function convert_filename($inURL, $NOAAZone)
{
  global $Status, $metaURL, $zoneURL, $alertURL;
  $fileName = $inURL;

  // handle OLD formats of NWS URLS
  // autocorrect the point-forecast URL if need be

  /*

  // from: http://forecast.weather.gov/MapClick.php?CityName=Rathdrum&state=ID&site=MTR&textField1=47.828&textField2=-116.842&e=0&TextType=2
  // to:

  https://api.weather.gov/points/47.82761,-116.842/forecast
  NOTE: the lat,long must be decimal numbers with up-to 4 decimal places and no trailing zeroes
  that's why the funky code:
  $t = sprintf("%01.4f",$matches[1]);  // forces 4 decimal places on number
  $t = (float)$t;                      // trims trailing zeroes by casting to float type.
  is used to enforce those API limits.
  */
  if (preg_match('|textField1=|i', $fileName)) {
    $newlatlong = '';
    preg_match('|textField1=([\d\.]+)|i', $fileName, $matches);
    if (isset($matches[1])) {
      $t = sprintf("%01.4f", $matches[1]);
      $t = (float)$t;
      $newlatlong.= $t;
    }

    preg_match('|textField2=([-\d\.]+)|i', $fileName, $matches);
    if (isset($matches[1])) {
      $t = sprintf("%01.4f", $matches[1]);
      $t = (float)$t;
      $newlatlong.= ",$t";
    }

    $newurl = APIURL . '/points/' . $newlatlong . '/forecast';
    $metaURL = APIURL . '/points/' . $newlatlong;
    $pointURL = FCSTURL . '/point/' . $newlatlong;
    $zoneURL = FCSTURL . '/zone/' . $NOAAZone;
//    $alertURL = ALERTAPIURL.$NOAAZone;
    $alertURL = ALERTAPIURL . $newlatlong;
    return (array(
      $newurl,
      $pointURL
    ));
  }

  /*

  // from:

  http://forecast.weather.gov/MapClick.php?lat=38.36818&lon=-75.5976&unit=0&lg=english&FcstType=text&TextType=2

  // to:

  https://api.weather.gov/points/38.36818,-75.5976/forecast
  */
  if (preg_match('|lat=|i', $fileName)) {
    $newlatlong = '';
    preg_match('|lat=([\d\.]+)|i', $fileName, $matches);
    if (isset($matches[1])) {
      $t = sprintf("%01.4f", $matches[1]);
      $t = (float)$t;
      $newlatlong.= $t;
    }

    preg_match('|lon=([-\d\.]+)|i', $fileName, $matches);
    if (isset($matches[1])) {
      $t = sprintf("%01.4f", $matches[1]);
      $t = (float)$t;
      $newlatlong.= ",$t";
    }

    $newurl = APIURL . '/points/' . $newlatlong . '/forecast';
    $metaURL = APIURL . '/points/' . $newlatlong;
    $pointURL = FCSTURL . '/point/' . $newlatlong;
    $zoneURL = FCSTURL . '/zone/' . $NOAAZone;
//    $alertURL = ALERTAPIURL.$NOAAZone;
    $alertURL = ALERTAPIURL . $newlatlong;
    return (array(
      $newurl,
      $pointURL
    ));
  }

  // handle NEW format of point URL

  if (preg_match('|/point/([\d\.]+),([\-\d\.]+)|i', $fileName, $matches)) {
    $newlatlong = '';
    if (isset($matches[1])) {
      $t = sprintf("%01.4f", $matches[1]);
      $t = (float)$t;
      $newlatlong.= $t;
    }

    if (isset($matches[2])) {
      $t = sprintf("%01.4f", $matches[2]);
      $t = (float)$t;
      $newlatlong.= ",$t";
    }

    $newurl = APIURL . '/points/' . $newlatlong . '/forecast';
    $metaURL = APIURL . '/points/' . $newlatlong;
    $pointURL = FCSTURL . '/point/' . $newlatlong;
    $zoneURL = FCSTURL . '/zone/' . $NOAAZone;
//    $alertURL = ALERTAPIURL.$NOAAZone;
    $alertURL = ALERTAPIURL . $newlatlong;
    return (array(
      $newurl,
      $pointURL
    ));
  }

  return (array(
    'unk',
    $inURL
  ));
}

// ------------------------------------------------------------------------------------------

function sec2hmsADV($sec, $padHours = false)
{
  $hms = "";
  if (!is_numeric($sec)) {
    return ($sec);
  }

  // there are 3600 seconds in an hour, so if we
  // divide total seconds by 3600 and throw away
  // the remainder, we've got the number of hours

  $hours = intval(intval($sec) / 3600);

  // add to $hms, with a leading 0 if asked for

  $hms.= ($padHours) ? str_pad($hours, 2, "0", STR_PAD_LEFT) . ':' : $hours . ':';

  // dividing the total seconds by 60 will give us
  // the number of minutes, but we're interested in
  // minutes past the hour: to get that, we need to
  // divide by 60 again and keep the remainder

  $minutes = intval(($sec / 60) % 60);

  // then add to $hms (with a leading 0 if needed)

  $hms.= str_pad($minutes, 2, "0", STR_PAD_LEFT) . ':';

  // seconds are simple - just divide the total
  // seconds by 60 and keep the remainder

  $seconds = intval($sec % 60);

  // add to $hms, again with a leading 0 if needed

  $hms.= str_pad($seconds, 2, "0", STR_PAD_LEFT);

  // done!

  return $hms;
} // end sec2hmsADV function

// ------------------------------------------------------------------------------------------

function gen_new_settings()
{
  global $fileName, $NOAAZone, $NWSforecasts, $SITE, $Version, $showTwoIconRows, $showZoneWarning;
  list($newAPI, $newFileName) = convert_filename($fileName, $NOAAZone);
  $newNWSforecasts = array();
  if (!isset($NWSforecasts)) {
    $NWSforecasts = array();
    $newZone = $NOAAZone;
  }

  foreach($NWSforecasts as $m => $rec) { // for each locations
    list($Nzone, $Nlocation, $Nname) = explode('|', $NWSforecasts[$m] . '|||');
    list($newAPI, $newName) = convert_filename($Nname, $Nzone);
    if ($m == 0) {
      $newFileName = $newName;
      $newZone = $Nzone;
    }

    $newNWSforecasts[$m] = "$Nzone|$Nlocation|$newName";
  }

  header('Content-type: text/plain,charset=ISO-8859-1');
  print "// $Version \n";
  print "// settings converted to new point-forecast request URLs.\n";
  print "// Put these in ";
  print isset($SITE['noaazone']) ? 'Settings.php' : 'advforecast2.php';
  print " to update the settings there.\n";
  print "\n";
  print "// -- start of converted settings for advforecast2.php JSON version.\n";
  print isset($SITE['NWSforecasts']) ? "\$SITE['NWSforecasts']" : "\$NWSforecasts";
  print " = array(\n";
  print "// the entries below are for testing use.. replace them with your own entries if using the script\n";
  print "// outside the Saratoga AJAX/PHP templates.\n";
  print "// ZONE|Location|point-forecast-URL  (separated by | characters\n";
  foreach($newNWSforecasts as $m => $rec) {
    print " '$rec',\n";
  }

  print "\n);\n";
  print "//\n";
  print isset($SITE['noaazone']) ? "\$SITE['noaazone']" : "\$NOAAZone";
  print " = '$newZone'; // change this line to your NOAA warning zone.\n";
  print "//\n";
  print "// set ";
  print isset($SITE['fcsturlNWS']) ? "\$SITE['fcsturlNWS']" : "\$fileName";
  print "  to the URL for the point-printable forecast for your area\n";
  print "// NOTE: this value (and ";
  print isset($SITE['noaazone']) ? "\$SITE['noaazone']" : "\$NOAAZone";
  print ") will be overridden by the first entry in ";
  print isset($SITE['NWSforecasts']) ? "\$SITE['NWSforecasts']" : "\$NWSforecasts";
  print " if it exists.\n";
  print "//\n";
  print isset($SITE['fcsturlNWS']) ? "\$SITE['fcsturlNWS']" : "\$fileName";
  print " = '$newFileName';\n";
  print "//\n";
  print isset($SITE['noaazone']) ? "\$SITE['showTwoIconRows']" : "\$showTwoIconRows";
  print " = ";
  print $showTwoIconRows ? 'true;' : 'false;';
  print "   // =true; show all icons, =false; show 9 icons in one row (new V5.00)\n";
  print "//\n";
  print isset($SITE['noaazone']) ? "\$SITE['showZoneWarning']" : "\$showZoneWarning";
  print " = ";
  print $showZoneWarning ? 'true;' : 'false;';
  print "   // =true; show note when Zone forecast used. =false; suppress Zone warning (new V5.00)\n";
  print "//\n";
  print "// -- end of converted settings for advforecast2.php JSON version.\n";
} // end gen_new_settings function

// end of advforecast2.php script
