# advforecast2.php (NOAA/NWS Forecast for all NWS areas)

<img src="./forecast-sample.png" alt="Sample Output">

Tom's [advforecast.php](http://www.carterlake.org/advforecast.php.txt) script takes the point-printable forecast from NOAA for Western, Eastern, Southern and Central region NOAA websites, and converts to a set of icons/conditions/temperatures and text forecast for inclusion on your webpage. My mods add XHTML 1.0-Strict output and the ability to handle a redirection for county forecasts (offered by www.crh.noaa.gov) in case a point-printable forecast is not available. See these threads for the erh2crh version ([http://www.wxforum.net/viewtopic.php?p=3768](https://www.wxforum.net/viewtopic.php?p=3768), and [http://www.weather-watch.com/smf/index.php/topic,22504.0.html](https://www.weather-watch.com/smf/index.php/topic,22504.0.html) ) The latest version of this script also offers automatic failover to the Zone forecast if the point-printable forecast is not available. Set "$NOAAZone = 'ssZnnn';" in the code for your NOAA warning zone.

The script supports the ?force=1 parameter to reload the cache file, and also a ?force=2 to load the cache file from the backup County Zone forecast.

To use, you'll need an [icon set](https://saratoga-weather.org/saratoga-icons2.zip) uploaded in the /forecast directory on your website, and to set the $fileName variable inside the script to the URL for the point-printable forecast.  The icon set includes two subfolders ./forecast/images for static images and ./forecast/icon-templates static images for use with the DualImage.php script (included) to generate 6-hour forecast icons (V5.x only).

With V2.06 and up, the new parsing of the failover Zone forecast requires additional icons (available below) to be placed on your website.

In July, 2015, the NOAA-NWS updated the icon set and provided a new dual-image icon to display 6-hour conditions for the 12-hour forecast icon. This icon is dynamically generated by a DualImage.php script (available below in the UPDATES file). With the DualImage.php script and associated icon templates installed, the V4.00+ version of advforecast2.php will support dynamic generation of dual images when needed. The script will fall-back to showing just the first 6-hour image if the DualImage.php is not available, OR if the meteotriviglio animated GIF images are used.

In June, 2017, the NWS forecast.weather.gov website **had** planned a major renovation and would deprecate the use of /MapClick.php?lat={latitude}&lon={longitude} for finding the weather at your location. V5.00 of advforecast2.php now uses the new api.weather.gov JSON feeds to generate the icons/forecast text as the V4.x and prior scripts would have no longer worked via page-scraping.  
**That conversion by NWS is on indefinite hold** so either version V4.0x or V5.0x will work on your site at this time.  

Also new with the V5.x script is the number of icons available rose from 9 to 14 and the option to display the old-format 9 icons or the new format with two rows of (up-to) 8 icons is controlled by one switch variable ($showTwoIconRows in the script, or $SITE['showTwoIconRows'] in Settings.php).


On February 27, 2018, the NWS switched forecast.weather.gov to HTTPS only, so advforecast2.php V4.02 and earlier will not work as those versions supported only HTTP access. The advforecast2.php V4.03 was released to handle HTTPS access and should be the last release of the venerable page-scraper script. Please note that the Version 5.x script uses the beta api.weather.gov JSON feed and already used HTTPS only.  
The V5.09 script is included in the Base-USA Saratoga template. For those not wanting to use the beta code, you can download the V4.05 script below and use with your template. Both V4.05 and V5.x versions will continue to have support while the api.weather.gov is still in beta test.

**Note:** to use either 4.0x or 5.0x version of the script, it is important to **continue using the point-printable version of the URL** for your location based on the current [https://www.weather.gov/](https://www.weather.gov/) website. Your links should resemble:

**https://forecast.weather.gov/MapClick.php?lat=nn.nnnn&lon=-nnn.nnnn&unit=0&lg=english&FcstType=text&TextType=2**

with the **&unit=0&lg=english&FcstType=text&TextType=2** at the end after the lat= and lon= arguments.


## Sample code

```
<?php  
$doIncludeNWS = true;  
include("advforecast2.php"); ?>
```

You can also include it 'silently' and print just a few (or all) the contents where you'd like it on the page

```
<?php  
$doPrintNWS = false;  
require("advforecast2.php"); ?>  
```

then on your page, the following code would display just the current and next time period forecast:

```
<div class="codebox"> <table>  
<tr align="center" valign="top">  
<?php print "<td>$forecasticons[0]</td><td>$forecasticons[1]</td>\n"; ?>  
</tr>  
<tr align="center" valign="top">  
<?php print "<td>$forecasttemp[0]</td><td>$forecasttemp[1]</td>\n"; ?>  
</tr>  
</table></div>
```


Or if you'd like to include the immediate forecast with text for the next two cycles:

```
<table>  
<tr valign="top">  
<?php print "<td align=\"center\">$forecasticons[0]<br />$forecasttemp[0]</td>\n"; ?>  
<?php print "<td align=\"left\" valign=\"middle\">$forecasttext[0]</td>\n"; ?>  
</tr>  
<tr valign="top">  
<?php print "<td align=\"center\">$forecasticons[1]<br />$forecasttemp[1]</td>\n"; ?>  
<?php print "<td align=\"left\" valign=\"middle\">$forecasttext[1]</td>\n"; ?>  
</tr>  
</table>
```
for easy standalone (non-template) usage.  The hints on how what to provide in $SITE[] variables can be found in the script just below the settings info in the script.  For advforecast2.php (JSON) that reads

```
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
```
  so the including page can have

```
<?php
$SITE = array();
global $SITE;
$doPrintNWS = false;
$SITE['NWSforecasts'] = array(  
  // the entries below are for testing use.. replace them with your own entries if using the script  
  // outside the AJAX/PHP templates.  
  // ZONE|Location|point-forecast-URL  (separated by | characters  
     "CAZ513|Saratoga, CA (WRH)|http://forecast.weather.gov/MapClick.php?CityName=Saratoga&state=CA&site=MTR&textField1=37.2639&textField2=-122.022&e=1&TextType=2",
);
$SITE['cacheFileDir'] = './';
$SITE['noaazone'] = 'CAZ513';
$SITE['fcsturlNWS'] = 'http://forecast.weather.gov/MapClick.php?CityName=Saratoga&state=CA&site=MTR&textField1=37.2639&textField2=-122.022&e=1&TextType=2';
$SITE['fcsticonsdir'] = './forecast/images/';
$SITE['fcsticonstype'] = '.jpg';
$SITE['fcsticonsheight'] = 55;
$SITE['fcsticonswidth'] = 55;
$SITE['tz'] = 'America/Los_Angeles';
$SITE['timeFormat'] = 'g:i a T M d, Y';
$SITE['showTwoIconRows'] = true;
$SITE['showZoneWarning'] = true;
include_once("advforecast2.php");?>
```

to make the script have all the required customizations applied without modifying the advforecast2.php script itself, and you can add whatever printing you want .. the data is what you get to use
