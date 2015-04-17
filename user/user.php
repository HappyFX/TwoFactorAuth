<?php
/**
 * TwoFactorAuth User Administration page - This script provides all management actions available
 * to a single user
 *
 * @author Arno0x0x - https://twitter.com/Arno0x0x
 * @license GPLv3 - licence available here: http://www.gnu.org/copyleft/gpl.html
 * @link https://github.com/Arno0x/
 */

//------------------------------------------------------
// Include config file
require_once("../config.php");

//-----------------------------------------------------
// Import required libraries
require_once(DBMANAGER_LIB);

// Allow included script to be included from this script
define('INCLUSION_ENABLED',true);
	
//------------------------------------------------------
// Restore session
session_name(SESSION_NAME);
session_start();

// Check the currently logged user is NOT admin
if (!(isset($_SESSION["authenticated"]) && $_SESSION["authenticated"] === true)) {
	http_response_code(401);
	exit();
}

//------------------------------------------------------
// Retrieve the currently logged user from the session
$username = $_SESSION["username"];

//------------------------------------------------------
// Main processing
try {
    $dbManager = new DBManager(USER_SQL_DATABASE_FILE);
    
    //------------------------------------------------------
    // Check if an action was requested on the admin page
    if (isset($_POST["action"])) {
		//---------------------------------------------
		// Parse all possible actions
		switch($_POST["action"]) {
			// Show the change password form for the selected user
			case "chgPwdForm":
			        $overlay = "changePasswordForm.php";
			    break;
			    
			// Show the change password form for the selected user
			case "changePassword":
			    if (isset($_POST["password"])) {
			    	if($dbManager->updatePassword($username,$username)) {
			    	    $message = "[SUCCESS] Password successfully changed for user ".$username;
			    	}
			    	else {
			    	    $message = "[ERROR] Could not change password for user ".$username.". Impossible to write into the user database";
			    	}
				}
			    break;
			
			// Show the QRCode, for current GAuth secret, for selected user
			case "showQRCode":
		        require_once(GAUTH_LIB);
		        
		        // Create GoogleAuth object
	            $gauth = new GoogleAuthenticator();
		        
		        if (($secret = $dbManager->getGauthSecret($username))) {
			        // Create the QRCode as PNG image
                    $randomString = bin2hex(openssl_random_pseudo_bytes (15));
                    $qrcodeimg = QRCODE_TEMP_DIR.$randomString.".png";
                    $gauth->getQRCode($username,$secret,$qrcodeimg,QRCODE_TITLE);
                    
                    $overlay = "showQRCode.php";
		        }
			    break;
			    
			// Renew the GAuth secret  for selected user and show the corresponding QRCode
			case "renewGAuthSecret":
		    	require_once(GAUTH_LIB);
		        
		        // Create GoogleAuth object
	            $gauth = new GoogleAuthenticator();
	            $secret = $gauth->createSecret();
	            
		        if (($dbManager->updateGauthSecret($username,$secret))) {
			        // Create the QRCode as PNG image
                    $randomString = bin2hex(openssl_random_pseudo_bytes (15));
                    $qrcodeimg = QRCODE_TEMP_DIR.$randomString.".png";
                    $gauth->getQRCode($username,$secret,$qrcodeimg,QRCODE_TITLE);
                    
                    $overlay = "showQRCode.php";
		        }
			    break;
		} 
	} 
} catch (Exception $e) {
    	echo "<h1>ERROR - Impossible to open the user database</h1>";
    	exit();
	}
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TwoFactorAuth</title>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container" style="margin-top: 10px">
<div class="row">
	<div class="col-sm-8 col-sm-offset-2">
	    <div class="panel panel-default">
			<div class="panel-heading" style="text-align: center">
				<span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
				<span class="panel-title"><strong>Logged as <?php echo $username; ?></strong></span>
				<a href="logout.php"><span style="font-size: 1.5em" class="fa fa-power-off pull-right"></span></a>
            </div> 	<!-- End of panel heading -->
            <br>
            <form id="userAction" class="form text-center" action="user.php" method="post">
        		<button type="submit" name="action" value="chgPwdForm" class="btn btn-primary"><span class="fa fa-refresh"></span> <span class="fa fa-lock"></span> Change password</button>
        		<button type="submit" name="action" value="showQRCode" class="btn btn-primary"><span class="fa fa-barcode"></span> Show QR code</button>
        		<button onclick="return confirmGAScrt();" type="submit" name="action" value="renewGAuthSecret" class="btn btn-primary"><span class="fa fa-refresh"></span> <span class="fa fa-key"></span> Renew Secret</button>
			</form>
			<br>
            <?php if (isset($message)) echo "<div class='message'>".$message."</div>";	?>
        </div>
	</div> <!-- End of column classes -->
</div> <!-- End of row -->
</div> <!-- End of container -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<?php
	if (isset($overlay)) {
        echo "<div id=\"overlay\" class=\"blackOut\"><span class=\"boxWrapper\"><div class=\"box\">";
        require_once($overlay);
        echo"</div></span></div>";
    }
?>
<!-- Bootstrap core JavaScript
================================================== -->
<!-- Placed at the end of the document so the pages load faster -->
<!-- Latest compiled JQuery lib -->
<script>
function confirmGAScrt() {
	return (confirm("Are you SURE you want to renew your secret ?"));
}
</script>
</body>
</html>