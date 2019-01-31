<?
	$id=$_GET["id"];
	$id=$id+0;
	include "auth.php";
	
	if ($status!="A" && $status!="U") {
		$result=Array("error"=>"-1","val"=>"insufficient rights");
		echo json_encode($result);
		die();
	}
	$q="select * from filter where id=$id";
	if ($status!="A") {
		$q=$q." and userid=$auserid";		// non-Admin users can only read their own rules
	}
	$res=mysqli_query($db,$q);
	$num=mysqli_num_rows($res);
	if ($num!=1) {
		$result=Array("error"=>"-1","val"=>"Rule not found or access denied");
		echo json_encode($result);
		die();
	}
	$row=mysqli_fetch_assoc($res);
	$result=Array("error"=>"0");
	foreach ($row as $key=>$val) {
		$result[$key]=$val;
	}
	echo json_encode($result);
?>