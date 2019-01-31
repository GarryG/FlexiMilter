<?
include_once("config.php");
session_name("fleximilter_local");
global $username,$status;

$err="";
if(isset($_POST['username']) && isset($_POST['password']))
{
	$q="select * from user where username='".mysqli_real_escape_string($db,$_POST['username'])."' and password='".
		hash("sha256",$_POST['password'])."' and status!='I'";
	$res=mysqli_query($db,$q);
	if ($res) {
	    $num=mysqli_num_rows($res);
	    if ($num==1) {	// seems legit ;)
		session_start();
		$_SESSION['username']=$_POST['username'];
		$row=mysqli_fetch_assoc($res);
		$_SESSION['userid']=$row['userid'];
		$_SESSION['status']=$row['status'];
		$username=$_POST['username'];
		$status=$_SESSION['status'];
		$auserid=$_SESSION['userid'];
	    }
	    else 
		$err="Invalid username or password";
	}
	else
	    $err="Database access failed";
	if ($err!="")
	    header("location: index.php?username=".urlencode($_POST['username'])."&err=".urlencode($err));
}
else {
	session_start();
	if(isset($_SESSION['username']) && isset($_SESSION['userid']) && isset($_SESSION['status']))
	{
	    $username=$_SESSION['username'];
	    $status=$_SESSION['status'];
		$auserid=$_SESSION['userid'];
	}
	else 
	    header("location: index.php?username=".urlencode($username)."&err=".urlencode($err));
}

?>
