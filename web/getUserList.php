<?
	$userID=$_GET["userID"];
	$userID=$userID+0;
	include "auth.php";
	if ($status!="A") {
		$result=Array("error"=>"-1","val"=>"insufficient rights");
		echo json_encode($result);
		die();
	}
	$q="select * from user order by username";
	$res=mysqli_query($db,$q);
	$num=mysqli_num_rows($res);
	if ($num>0) {
		$num=mysqli_num_rows($res);
		for ($t=0;$t<$num;$t++) {
			$row=mysqli_fetch_assoc($res);
			$users[$t]['username']=$row['username'];
			$users[$t]['userid']=$row['userid'];
			$users[$t]['ustatus']=$row['status'];
		}
		$result=Array("error"=>"0","num"=>$num,"userList"=>$users);
		echo json_encode($result);
	}
	else {
		$result=Array("error"=>"-1","val"=>"No users found");
		echo json_encode($result);
		die();
	}
?>
