<?
include "auth.php";
if ($status!="A" && $status!="U") {
	header("location: main.php?err=".urlencode("Access denied - insufficient authorization"));
	die();
}
	$ISADMIN=false;
	$q="select * from filter ";
	if ($status=="A") {
		$ISADMIN=true;
		$q=$q.",user where filter.userid=user.userid ";
	}
	else $q=$q."where userid=$auserid ";
	$q=$q."order by prio,id";
	$res=mysqli_query($db,$q);
	$num=mysqli_num_rows($res);
	$act=Array("D"=>"Deny","A"=>"Accept","F"=>"Fwd");
	if ($num>0) {
		$num=mysqli_num_rows($res);
		for ($t=0;$t<$num;$t++) {
			$row=mysqli_fetch_assoc($res);
			$data[$t]['id']=$row['id'];
			if ($ISADMIN) $data[$t]['owner']=$row['username'];
			else $data[$t]['owner']="";
			$data[$t]['from']=$row['sender'];
			$data[$t]['to']=$row['rcpt'];
			$data[$t]['subject']=$row['subject'];
			$data[$t]['header']=$row['headerf'].":".$row['headerv'];
			if ($data[$t]['header']==":") $data[$t]['header']="-";
			$data[$t]['timestmp']=$row['endts'];
			$data[$t]['pri']=$row['prio']."   ";
			$data[$t]['action']=$act[$row['action']];
		}
	}
	echo json_encode($data);
?>