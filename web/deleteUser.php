<?
	$userID=$_GET["userID"];		// do some extra checks?
	$userID=$userID+0;
	include "auth.php";
	if ($status!="A") {
		$result=Array("error"=>"-1","val"=>"insufficient rights");
		echo json_encode($result);
		die();
	}
	$q="delete from user where userid=$userID limit 1";
	$res=mysqli_query($db,$q);
	if (mysqli_errno($db)!=0) {
		$result=Array("error"=>"-1","val"=>"Error on delete - ".mysqli_error($db));
		echo json_encode($result);
		die();
	}
	$result=Array("error"=>"0","val"=>"User deleted");
	echo json_encode($result);
?>