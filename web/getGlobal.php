<?
	$id=$_GET["id"];
	$id=$id+0;
	include "auth.php";
	if ($status!="A") {
		$result=Array("error"=>"-1","val"=>"insufficient rights");
		echo json_encode($result);
		die();
	}
	$q="select * from bwlist where id=$id";
	$res=mysqli_query($db,$q);
	$num=mysqli_num_rows($res);
	if ($num!=1) {
		$result=Array("error"=>"-1","val"=>"BW entry not found");
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