<?
// sanitize form values - from http://www.w3schools.com/php/php_form_validation.asp
function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
        include "auth.php";

        $userID=test_input($_GET["userid"]);
		$userID=$userID+0;
		$username=test_input($_GET["username"]);
		$email=test_input($_GET["email"]);
		$password=test_input($_GET["password"]);
		$ustatus=test_input($_GET["ustatus"]);
		
		$err=0;
		$errm="";

		// do basic tests on input
		if (strlen($username)<4) { echo json_encode(Array("error"=>"-1","val"=>"Username too short")); die(); }
		if (strlen($username)>44) { echo json_encode(Array("error"=>"-1","val"=>"Username too long")); die(); }
		if (!preg_match("/^[a-zA-Z0-9]*$/",$username)) { echo json_encode(Array("error"=>"-1","val"=>"Username contains illegal chars - only letters and digits permitted")); die(); }
		if (strlen($email)>127) { echo json_encode(Array("error"=>"-1","val"=>"email address too long")); die(); }
		if (!filter_var($email,FILTER_VALIDATE_EMAIL))  { echo json_encode(Array("error"=>"-1","val"=>"Invalid email address")); die(); }
		//if (strlen($password)<8)  { echo json_encode(Array("error"=>"-1","val"=>"Password too short - minimum 8 characters")); die(); }
		if ($password=="06c065b234922a3293b3c6067976c9893331127923b6da359d18149f38f1cd5e") {
			if ($userID==0) { echo json_encode(Array("error"=>"-1","val"=>"Password needed for user creation")); die(); }
			$password="";		// password wasn't changed in the form
		}
		if (!preg_match("/^[AUD]$/",$ustatus))  { echo json_encode(Array("error"=>"-1","val"=>"Please select user status ")); die(); }
		
        if ($status!="A") {
                $result=Array("error"=>"-1","val"=>"insufficient rights");
                echo json_encode($result);
                die();
        }
		if ($userID==0) {	// Create User
			$q="insert into user values (0,'".mysqli_real_escape_string($db,$username)."','".mysqli_real_escape_string($db,$email)."','".hash("sha256",$password)."','$ustatus')";
			$res=mysqli_query($db,$q);
			if ($db->errno!=0) {
				$result=Array("error"=>"-1","val"=>"MySQL-Error:".mysqli_error($db));
				echo json_encode($result);
				die();
			}
			else $result=Array("error"=>0,"val"=>"User created");
		}
		else {				// Update User
	        $q="select * from user where userid=$userID";
    	    $res=mysqli_query($db,$q);
        	$num=mysqli_num_rows($res);
        	if ($num!=1) {
                $result=Array("error"=>"-1","val"=>"User not found");
                echo json_encode($result);
                die();
			}
	        $row=mysqli_fetch_assoc($res);
			$q="update user set username=\"".mysqli_real_escape_string($db,$username)."\",email=\"".mysqli_real_escape_string($db,$email)."\",";
			if (strlen($password)>=8) $q=$q."password=\"".mysqli_real_escape_string($db,$password)."\",";
			$q=$q."status=\"".mysqli_real_escape_string($db,$ustatus)."\" where userid=$userID";
			$res=mysqli_query($db,$q);
			if ($db->errno!=0) {
				$result=Array("error"=>"-1","val"=>"MySQL-Error:".mysqli_error($db));
				echo json_encode($result);
				die();
			}
			else $result=Array("error"=>"0","val"=>"User successfully updated");
        }
		if ($result==undefined)
	        $result=Array("error"=>"0","val"=>"OK");
        echo json_encode($result);
?>