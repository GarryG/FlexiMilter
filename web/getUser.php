<?
	$userID=$_GET["userID"];
	$userID=$userID+0;
	include "auth.php";
	if ($status!="A") {
		$result=Array("error"=>"-1","val"=>"insufficient rights");
		echo json_encode($result);
		die();
	}
	$q="select * from user where userid=$userID";
	$res=mysqli_query($db,$q);
	$num=mysqli_num_rows($res);
	if ($num!=1) {
		$result=Array("error"=>"-1","val"=>"User not found");
		echo json_encode($result);
		die();
	}
	$row=mysqli_fetch_assoc($res);
	$result=Array("error"=>"0","username"=>$row["username"],"email"=>$row["email"],"ustatus"=>$row["status"]);
	echo json_encode($result);
?>