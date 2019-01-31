<?
        include "auth.php";

        if ($status!="A") {
                $result=Array("error"=>"-1","val"=>"insufficient rights");
                echo json_encode($result);
                die();
        }

// sanitize form values - from http://www.w3schools.com/php/php_form_validation.asp
function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
        include "auth.php";
		
        $id=test_input($_GET["id"]);
		$id=$id+0;
		$field=test_input($_GET["field"]);
		$matchtype=test_input($_GET["matchtype"]);
		$pattern=test_input($_GET["pattern"]);
		$patternnum=test_input($_GET["patternnum"])+0;
		$patternmask=test_input($_GET["patternmask"])+0;
		$action=test_input($_GET["action"]);
		$prio=test_input($_GET["prio"])+0;
		
		$err=0;
		$errm="";

		// do basic tests on input
		if ($id<0) { echo json_encode(Array("error"=>"-1","val"=>"Illegal rule ID ".$id)); die(); }

		if (!preg_match("/^[46EHFT]$/",$field))  { echo json_encode(Array("error"=>"-1","val"=>"Please select valid filter type")); die(); }
		if (!preg_match("/^[CBW]$/",$action))  { echo json_encode(Array("error"=>"-1","val"=>"Please select valid action")); die(); }
		if (preg_match("/^[46]$/",$field)) if ($matchtype=="") $matchtype="P";
		if (!preg_match("/^[PR]$/",$matchtype))  { echo json_encode(Array("error"=>"-1","val"=>"Please select valid matchtype")); die(); }

		if ($field=="4") {
			if ($patternnum<0) { echo json_encode(Array("error"=>"-1","val"=>"Illegal IPv4 network address ".$patternnum)); die(); }
			if ($patternnum<0) { echo json_encode(Array("error"=>"-1","val"=>"Illegal IPv4 network address ".$patternnum)); die(); }
		}
		else {
			$patternnum=0;
			$patternmask=0;
		}
		if ($field=="6") {
			$pattern=filter_var($pattern, FILTER_VALIDATE_IP);
			if ($pattern==false) { echo json_encode(Array("error"=>"-1","val"=>"Illegal IPv6 network address")); die(); }
		}
		
		if ($id==0) {	// Create entry
			$q="insert into bwlist values (0,'".mysqli_real_escape_string($db,$field)."','".
				mysqli_real_escape_string($db,$matchtype)."','".
				mysqli_real_escape_string($db,$pattern)."',$patternnum,$patternmask,'".
				mysqli_real_escape_string($db,$action)."',$prio)";
			$res=mysqli_query($db,$q);
			if ($db->errno!=0) {
				$result=Array("error"=>"-1","val"=>"MySQL-Error:".mysqli_error($db));
				echo json_encode($result);
				die();
			}
			else {
				$id=mysqli_insert_id($db);
				$result=Array("error"=>0,"val"=>"Entry created", "id"=>$id);
			}
		}
		else {				// Update rule
	        $q="select * from bwlist where id=$id";
    	    $res=mysqli_query($db,$q);
        	$num=mysqli_num_rows($res);
        	if ($num!=1) {
                $result=Array("error"=>"-1","val"=>"Entry not found");
                echo json_encode($result);
                die();
			}
	        $row=mysqli_fetch_assoc($res);
			$q="update bwlist set field=\"".mysqli_real_escape_string($db,$field)."\",matchtype=\"".mysqli_real_escape_string($db,$matchtype)."\",";
			$q=$q."pattern=\"".mysqli_real_escape_string($db,$pattern)."\",";
			$q=$q."patternnum=$patternnum, patternmask=$patternmask,";
			$q=$q."action=\"".mysqli_real_escape_string($db,$action)."\",prio=$prio";
			$q=$q." where id=$id";
			$res=mysqli_query($db,$q);
			if ($db->errno!=0) {
				$result=Array("error"=>"-1","val"=>"MySQL-Error:".mysqli_error($db)."($q)", "id"=>$id);
				echo json_encode($result);
				die();
			}
			else $result=Array("error"=>"0","val"=>"Entry successfully updated","id"=>$id);
        }
		if ($result==undefined)
	        $result=Array("error"=>"0","val"=>"OK", "id"=>$id);
        echo json_encode($result);
?>