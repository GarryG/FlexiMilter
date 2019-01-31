<?
        include "auth.php";

        if ($status!="A" && $status!="U") {
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


	
        $id=test_input($_GET["id"]);
		$id=$id+0;
		$owner=test_input($_GET["owner"])+0;
		$action=test_input($_GET["action"]);
		$prio=test_input($_GET["prio"])+0;
		$rcpt=test_input($_GET["rcpt"]);
		$sender=test_input($_GET["sender"]);
		$subject=test_input($_GET["subject"]);
		$headerf=test_input($_GET["headerf"]);
		$headerv=test_input($_GET["headerv"]);
		$sendertype=test_input($_GET["sendertype"]);
		$rcpttype=test_input($_GET["rcpttype"]);
		$subjecttype=test_input($_GET["subjecttype"]);
		$headertype=test_input($_GET["headertype"]);
		$headervtype=test_input($_GET["headervtype"]);
		$resultcode=test_input($_GET["resultcode"])+0;
		$xresultcode=test_input($_GET["xresultcode"]);
		$resultmsg=test_input($_GET["resultmsg"]);
		$endts=test_input($_GET["endts"])+0;
		$forward=test_input($_GET["forward"]);
		$comment=test_input($_GET["comment"]);
		$tag=test_input($_GET["tag"]);
		$filtercol=test_input($_GET["filtercol"]);

		if ($status!="A") {
			$owner=$auserid;		// force rule userid for non-Admin-User
			$rcpttype='P';
		}
		
		$err=0;
		$errm="";

		// do basic tests on input
		if ($id<0) { echo json_encode(Array("error"=>"-1","val"=>"Illegal rule ID ".$id)); die(); }

		if (!preg_match("/^[ADF]$/",$action))  { echo json_encode(Array("error"=>"-1","val"=>"Please select valid action")); die(); }
		if (!preg_match("/^[PR]$/",$rcpttype))  { echo json_encode(Array("error"=>"-1","val"=>"Please select valid recipient match type")); die(); }
		if (!preg_match("/^[PR]$/",$sendertype))  { echo json_encode(Array("error"=>"-1","val"=>"Please select valid sender match type")); die(); }
		if (!preg_match("/^[PR]$/",$subjecttype))  { echo json_encode(Array("error"=>"-1","val"=>"Please select valid subject match type")); die(); }
		if (!preg_match("/^[PR]$/",$headertype))  { echo json_encode(Array("error"=>"-1","val"=>"Please select valid header field match type")); die(); }
		if (!preg_match("/^[PR]$/",$headervtype))  { echo json_encode(Array("error"=>"-1","val"=>"Please select valid header value match type")); die(); }
		if ($status!="A") {	// check if recipient is valid
		    $q="select * from rcpts where userid=$auserid and (('$rcpt' like rcpt and '$rcpttype'='P') or ('$rcpt' rlike rcpt and '$rcpttype'='R'))";
		    $res=mysqli_query($db,$q);
		    $num=mysqli_num_rows($res);
		    if ($num!=1) {
			    $result=Array("error"=>"-1","val"=>"Email address not permitted $q");
			    echo json_encode($result);
			    die();
		    }
		}

		if ($id==0) {	// Create entry
			$q="insert into filter values (".mysqli_real_escape_string($db,$owner).",0,'".
				mysqli_real_escape_string($db,$rcpt)."','".
				mysqli_real_escape_string($db,$sender)."','".
				mysqli_real_escape_string($db,$subject)."','".
				mysqli_real_escape_string($db,$headerf)."','".
				mysqli_real_escape_string($db,$headerv)."','".
				mysqli_real_escape_string($db,$rcpttype)."','".
				mysqli_real_escape_string($db,$sendertype)."','".
				mysqli_real_escape_string($db,$subjecttype)."','".
				mysqli_real_escape_string($db,$headertype)."','".
				mysqli_real_escape_string($db,$headervtype)."','".
				mysqli_real_escape_string($db,$action)."',$resultcode,'".
				mysqli_real_escape_string($db,$xresultcode)."','".
				mysqli_real_escape_string($db,$resultmsg)."',$prio,$endts,'".
				mysqli_real_escape_string($db,$forward)."','".
				mysqli_real_escape_string($db,$comment)."','".
				mysqli_real_escape_string($db,$filtercol).
				mysqli_real_escape_string($db,$tag)."','".
				"')";
			$res=mysqli_query($db,$q);

			if ($db->errno!=0) {
				syslog(LOG_ALERT,mysqli_error($db));
				$result=Array("error"=>"-1","val"=>"MySQL-Error:".mysqli_error($db)." Query: $q");
				echo json_encode($result);
				die();
			}
			else {
				$id=mysqli_insert_id($db);
				$result=Array("error"=>0,"val"=>"Entry created", "id"=>$id);
			}
		}
		else {				// Update rule
	        $q="select * from filter where id=$id";
			if ($status=="U") $q=$q." and userid=$auserid";	// enforce user id ...
    	    $res=mysqli_query($db,$q);
        	$num=mysqli_num_rows($res);
        	if ($num!=1) {
                $result=Array("error"=>"-1","val"=>"Rule entry not found or not owner");
                echo json_encode($result);
                die();
			}
	        //$row=mysqli_fetch_assoc($res);		// not really needed to read that entry ...
			$q="update filter set ";
			$q=$q."rcpt=\"".mysqli_real_escape_string($db,$rcpt)."\",";
			$q=$q."sender=\"".mysqli_real_escape_string($db,$sender)."\",";
			$q=$q."subject=\"".mysqli_real_escape_string($db,$subject)."\",";
			$q=$q."headerf=\"".mysqli_real_escape_string($db,$headerf)."\",";
			$q=$q."headerv=\"".mysqli_real_escape_string($db,$headerv)."\",";
			$q=$q."rcpttype=\"".mysqli_real_escape_string($db,$rcpttype)."\",";
			$q=$q."sendertype=\"".mysqli_real_escape_string($db,$sendertype)."\",";
			$q=$q."subjecttype=\"".mysqli_real_escape_string($db,$subjecttype)."\",";
			$q=$q."headertype=\"".mysqli_real_escape_string($db,$headertype)."\",";
			$q=$q."headervtype=\"".mysqli_real_escape_string($db,$headervtype)."\",";
			$q=$q."action=\"".mysqli_real_escape_string($db,$action)."\",";
			$q=$q."resultcode=\"".mysqli_real_escape_string($db,$resultcode)."\","; // shouldn't need that for int?
			$q=$q."xresultcode=\"".mysqli_real_escape_string($db,$xresultcode)."\",";
			$q=$q."resultmsg=\"".mysqli_real_escape_string($db,$resultmsg)."\",";
			$q=$q."prio=\"".mysqli_real_escape_string($db,$prio)."\",";     // dito
			$q=$q."endts=\"".mysqli_real_escape_string($db,$endts)."\",";
			$q=$q."forward=\"".mysqli_real_escape_string($db,$forward)."\",";
			$q=$q."comment=\"".mysqli_real_escape_string($db,$comment)."\",";
			$q=$q."tag=\"".mysqli_real_escape_string($db,$tag)."\",";
			$q=$q."filtercol=\"".mysqli_real_escape_string($db,$filtercol)."\"";

			$q=$q." where id=$id";	// user id was enforced earlier ... id as unique field is sufficient
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
