<?
function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}


	$userID=$_GET["userID"];
	$userID=$userID+0;
	$rcpt=test_input($_GET["rcpt"]);
	
	include "auth.php";
	if ($status=="A") {		// User is admin, anything goes
		$result=Array("error"=>"0","vla"=>"OK");
		echo json_encode($result);
		die();
	}
	if ($status!="U") {		// not a permitted user? Nope!
		$result=Array("error"=>"-1","val"=>"insufficient rights");
		echo json_encode($result);
		die();
	}
	/*
		Recipient check done on table rcpts. Any rcpt selected by user must follow permissions listed there.
		
		Possible entries in table rcpts may be:
		
		%@some.domain		[P]
		.*@some.domain		[R]
		
		as well as any more specific versions of the pattern ("like") and regex wildcards
		
		User might enter:
		
		user@some.domain	-> match with any of the rcpts based on the type
		%@some.domain		-> any non-letter/number character (anything NOT permitted by RFCs)
							   should be forbidden, as it's near impossible to check if the resulting pattern would be allowed
	*/
//	echo $rcpt;
	if (!(preg_match("/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD",$rcpt,$rslt))) {
		$result=Array("error"=>"-1","val"=>"Invalid email address or forbidden characters");
		echo json_encode($result);
		print_r($rslt);
		die();
	}

	$q="select * from rcpts where userid=$userID and (('$rcpt' like rcpt and rcpttype='P') or ('$rcpt' rlike rcpt and rcpttype='R'))";
	$res=mysqli_query($db,$q);
	$num=mysqli_num_rows($res);
	if ($num!=1) {
		$result=Array("error"=>"-1","val"=>"Email address not permitted");
		echo json_encode($result);
		die();
	}
	$row=mysqli_fetch_assoc($res);
	$result=Array("error"=>"0", "val"=>"OK");
	echo json_encode($result);
?>
