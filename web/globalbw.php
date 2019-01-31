<?
include "auth.php";
if ($status!="A") {
	header("location: main.php?err=".urlencode("Access to user editor denied - insufficient authorization"));
	die();
}

	$q="select * from bwlist order by prio,id";
	$res=mysqli_query($db,$q);
	$num=mysqli_num_rows($res);
	$mv=Array("4"=>"IPv4","6","IPv6","H"=>"Rmt Host","E"=>"HELO","F"=>"SMTP From","T"=>"SMTP To");
	$act=Array("B"=>"Blacklist","W"=>"Whitelist","C"=>"Continue");
	if ($num>0) {
		$num=mysqli_num_rows($res);
		for ($t=0;$t<$num;$t++) {
			$row=mysqli_fetch_assoc($res);
			$data[$t]['id']=$row['id'];
			$data[$t]['mtch']=$mv[$row['field']];
			switch ($row['field']) {
				case '4':
					$data[$t]['mvalue']=long2ip($row['patternnum'])."/".long2ip($row['patternmask']);
					break;
				//case '6':
				//	break;
				default:
					$data[$t]['mvalue']=$row['pattern'];
					break;
			}
			$data[$t]['pri']=$row['prio']."   ";
			$data[$t]['action']=$act[$row['action']];
		}
		echo json_encode($data);
	}
?>