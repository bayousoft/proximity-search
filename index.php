<html>
<body>
<style>

	th{text-align: left;}

</style>

<div>Current location:</div>
<div id="current-location"></div>
<br/>

<form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post">
    <label>ZIP Code:
<input maxlength="5" name="zipcode" id="zipcode" size="6" type="text" /></label>
 
    <label>Select a radius in miles:</label>
<select name="distance">
<option>5</option>
<option>10</option>
<option>25</option>
<option>50</option>
<option>100</option>
</select>
 
<input name="submit" type="submit" value="Submit" />
</form>

<?php
if(isset($_POST['submit'])) {
	echo "<div>Searching for zip code: " . $_POST['zipcode'] . "</div>";
	echo "<div>Radius: " . $_POST['distance'] . " miles</div>";
     if(!preg_match('/^[0-9]{5}$/', $_POST['zipcode'])) {
          echo "<strong>You did not enter a properly formatted ZIP Code.</strong> Please try again.\n";
     }
     elseif(!preg_match('/^[0-9]{1,3}$/', $_POST['distance'])) {
          echo "<strong>You did not enter a properly formatted distance.</strong> Please try again.\n";
     }
     else {
          //connect to db server; select database
          $link = mysql_connect('127.0.0.1', 'root', '') or die('Cannot connect to database server');
          mysql_select_db('proximity') or die('Cannot select database');
 
          //query for coordinates of provided ZIP Code
          if(!$rs = mysql_query("SELECT * FROM zipcodes WHERE zip_code = '$_POST[zipcode]' LIMIT 1")) {
               echo "<strong>There was a database error attempting to retrieve your ZIP Code.</strong> Please try again.\n";
          }
          else {
               if(mysql_num_rows($rs) == 0) {
                    echo "<strong>No database match for provided ZIP Code.</strong> Please enter a new ZIP Code.\n";
               }
               else {
               		
               		
                    //if found, set variables
					while ($row = mysql_fetch_assoc($rs)) {
	                    $lat1 = $row['latitude'];
	                    $lon1 = $row['longitude'];

					}                    
                    $d = $_POST['distance'];
                    //earth's radius in miles
                    $r = 3959;
 
                    //compute max and min latitudes / longitudes for search square
                    $latN = rad2deg(asin(sin(deg2rad($lat1)) * cos($d / $r) + cos(deg2rad($lat1)) * sin($d / $r) * cos(deg2rad(0))));
                    $latS = rad2deg(asin(sin(deg2rad($lat1)) * cos($d / $r) + cos(deg2rad($lat1)) * sin($d / $r) * cos(deg2rad(180))));
                    $lonE = rad2deg(deg2rad($lon1) + atan2(sin(deg2rad(90)) * sin($d / $r) * cos(deg2rad($lat1)), cos($d / $r) - sin(deg2rad($lat1)) * sin(deg2rad($latN))));
                    $lonW = rad2deg(deg2rad($lon1) + atan2(sin(deg2rad(270)) * sin($d / $r) * cos(deg2rad($lat1)), cos($d / $r) - sin(deg2rad($lat1)) * sin(deg2rad($latN))));
 
                    //display information about starting point
                    //provide max and min latitudes / longitudes

                    //echo "<table class='bordered' cellspacing='0'>\n";
                    //echo "<tbody><tr><th>City</th><th>State</th><th>Lat</th><th>Lon</th><th>Max Lat (N)</th><th>Min Lat (S)</th><th>Max Lon (E)</th><th>Min Lon (W)</th></tr>\n";
                    //echo "<tr><td>$row[city]</td><td>$row[state]</td><td>$lat1</td><td>$lon1</td><td>$latN</td><td>$latS</td><td>$lonE</td><td>$lonW</td></tr>\n";
                    //echo "</tbody></table>\n\n";
 
                    //find all coordinates within the search square's area
                    //exclude the starting point and any empty city values
                    $query = "SELECT * FROM zipcodes WHERE (latitude <= $latN AND latitude >= $latS AND longitude <= $lonE AND longitude >= $lonW) AND (latitude != $lat1 AND longitude != $lon1) AND city != '' ORDER BY state, city, latitude, longitude";
                    if(!$rs = mysql_query($query)) {
                         echo "<strong>There was an error selecting nearby ZIP Codes from the database.</strong>\n";
                    }
                    elseif(mysql_num_rows($rs) == 0) {
                         echo "<strong>No nearby ZIP Codes located within the distance specified.</strong> Please try a different distance.\n";
                    }
                    else {
                         //output all matches to screen
                    	$num_rows = mysql_num_rows($rs);
                    	echo "<div>Matching rows: " . $num_rows . "</div><br/>";
                         echo "<table width='100%' class='bordered' cellspacing='0'>\n";
                         echo "<tbody><tr><th>City</th><th>State</th><th>ZIP Code</th><th>Latitude</th><th>Longitude</th><th>Miles, Point A To B</th></tr>\n";
                         while($row = mysql_fetch_array($rs)) {
                              echo "<tr><td>$row[city]</td><td>$row[state]</td><td>$row[zip_code]</td><td>$row[latitude]</td><td>$row[longitude]</td><td>";
                              echo acos(sin(deg2rad($lat1)) * sin(deg2rad($row['latitude'])) + cos(deg2rad($lat1)) * cos(deg2rad($row['latitude'])) * cos(deg2rad($row['longitude']) - deg2rad($lon1))) * $r;
                              echo "</td></tr>\n";
                         }
                         echo "</tbody></table>\n\n";
                    }
               }
          }
     }
}
?>

<script>
function retrieve_zip(callback)
{
	try { if(!google) { google = 0; } } catch(err) { google = 0; } // Stupid Exceptions
	if(navigator.geolocation) // FireFox/HTML5 GeoLocation
	{
		navigator.geolocation.getCurrentPosition(function(position)
		{
			zip_from_latlng(position.coords.latitude,position.coords.longitude,callback);
		});
	}
	else if(google && google.gears) // Google Gears GeoLocation
	{
		var geloc = google.gears.factory.create('beta.geolocation');
		geloc.getPermission();
		geloc.getCurrentPosition(function(position)
		{
			zip_from_latlng(position.latitude,position.longitude,callback);
		},function(err){});
	}
}
function zip_from_latlng(latitude,longitude,callback)
{
	// Setup the Script using Geonames.org's WebService
		var script = document.createElement("script");
		script.src = "http://ws.geonames.org/findNearbyPostalCodesJSON?lat=" + latitude + "&lng=" + longitude + "&callback=" + callback;
	// Run the Script
		document.getElementsByTagName("head")[0].appendChild(script);
}
function example_callback(json)
{
	// Now we have the data!  If you want to just assume it's the 'closest' zipcode, we have that below:
	zip = json.postalCodes[0].postalCode;
	country = json.postalCodes[0].countryCode;
	state = json.postalCodes[0].adminName1;
	county = json.postalCodes[0].adminName2;
	place = json.postalCodes[0].placeName;
	document.getElementById("zipcode").value = zip;
}
retrieve_zip("example_callback"); // Alert the User's Zipcode
</script>

<script>
var x=document.getElementById("current-location");
function getLocation()
  {
  if (navigator.geolocation)
    {
    navigator.geolocation.getCurrentPosition(showPosition);
    }
  else{x.innerHTML="Geolocation is not supported by this browser.";}
  }
function showPosition(position)
  {
  x.innerHTML="Latitude: " + position.coords.latitude + 
  "<br>Longitude: " + position.coords.longitude; 
  }

getLocation();

</script>

</body>
</html>