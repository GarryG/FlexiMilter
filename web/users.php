<?php
include "auth.php";
if ($status!="A")
	header("location: main.php?err=".urlencode("Access to user editor denied - insufficient authorization"));
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FLEXI-milter Admin</title>
    <!-- Bootstrap -->
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/flexi.css" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap-select.min.css">

	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
		  <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
  <script src="js/jquery-1.11.3.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/bootstrap-select.min.js"></script>
  <script src="js/i18n/defaults-de-DE.min.js"></script>
  <script src="js/sha.js"></script>
<script type="text/javascript">
        var authUser="<? echo $username;?>";
        var authStatus="<? echo $status;?>";
</script>

  </head>
<body class="jumbotron" id="mainjumbo" style="padding-top: 55px">
<div>
  <div class="row">
    <div class="col-lg-3 col-lg-offset-1">
      <form action="users.php" method="post" name="users" id="usersContainer">
      <div class="container"><p>Users:</p>
        <div class="container" id="userinfo">
          <div>
<select name="users" size="12" class="defaultbgcolor" id="users">
<!-- gets filled dynamically --> 
        <option value="0" style="font-style:italic">Create New User   </option>
</select>
      </div><br />
      <div class="container"><div class="btn-toolbar col-lg-offset-1 col-lg-12" role="toolbar">
          <button type="button" class="btn btn-default defaultbgcolor defaultbutton" onClick="deleteUser()" disabled id="deleteButton">Delete User</button>
      </div>
      </div></div>
    </div></form>
    </div>
    <div class="col-lg-1"></div>
    <div class="col-lg-6"><form action="users.php" method="post" id="userdata">
      <div class="container"><p>User Information:<input type="input" hidden="true" id="userid" value="0"></p>
        <div class="container" id="userinfo">
          <div class="input-group input-group-sm"><span class="input-group-addon defaultbgcolor" id="inputdescsm">Username:</span>
            <input name="username" type="text" required="required" class="form-control" id="username" placeholder="enter username" size="40" maxlength="44" pattern="(?=[a-zA-Z0-9])*{4,}" title="At least 4 letters and/or numbers"><input type="hidden" id="userid">
          </div><br />
          <div class="input-group input-group-sm"><span class="input-group-addon defaultbgcolor" id="inputdescsm">E-Mail:</span>
            <input name="emailadr" type="text" class="form-control" id="emailadr" placeholder="user primary email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,3}$" title="Must be syntactically correct email address" size="40" maxlength="127">
          </div><br />
          <div class="input-group input-group-sm"><span class="input-group-addon defaultbgcolor" id="inputdescsm">Password:</span>
            <input name="password" type="password" class="form-control" id="password" placeholder="enter password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters" size="40" maxlength="64">
          </div><br />
          <div class="input-group input-group-sm"><span class="input-group-addon defaultbgcolor" id="inputdescsm">Status:</span>
            <select class="selectpicker" title="Please select" data-width="auto" id="ustatus" name="ustatus">
              <option data-icon="glyphicon-user" value="U">regular user</option>
              <option data-icon="glyphicon-king" value="A">admin user</option>
              <option data-icon="glyphicon-remove-sign" value="D">disabled</option>
            </select>&nbsp;
          </div><br />
                <div class="container"><div class="btn-toolbar col-lg-offset-1 col-lg-12" role="toolbar">
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-default defaultbgcolor defaultbutton" id="updateBtn" onClick="addupdateuser()">Add/Update User</button>
          </div><div id="result" class="col-sm-offset-1 col-sm-4 col-med-4 col-lg-6 text-success">&nbsp;</div>
        </div>
      </div></form>
    </div>
  </div>
</div>
<div class="container" id=""><form method="post" id="userform" accept-charset="UTF-8">
<? include "navbar.html" ?>
</div>
	<!-- Include all compiled plugins (below), or include individual files as needed --> 
  <script type="text/javascript">
  function refreshUserList(selected)
  {
	  $.getJSON("getUserList.php",{},
		  function(data) {
			  var enableDel=false;
			  if (data.error!=0) {
				  alert("Error getting user list - "+data.val);
			  }
			  else {
				  $("#users").empty();
				  for (t=0;t<data.num;t++) {
					  if (data.userList[t].ustatus=="D") {
						  $("#users").append($("<option></option>")
						  	.attr("value",data.userList[t].userid)
							.attr("style","text-decoration:line-through")
							.text(data.userList[t].username));
						  if (data.userList[t].userid==selected) enableDel=true;
					  }
					  else if (data.userList[t].ustatus=="A") {
						  $("#users").append($("<option></option>")
						  	.attr("value",data.userList[t].userid)
							.attr("style","font-weight:bold")
							.text(data.userList[t].username));
					  }
					  else {
						  $("#users").append($("<option></option>")
						  	.attr("value",data.userList[t].userid).text(data.userList[t].username));
					  }
				  }
				  $("#users").val(selected);
				  if (enableDel==true)
						$("#deleteButton").prop("disabled",false);
				  else
						$("#deleteButton").prop("disabled",true);
				  $("#users").append($("<option></option>")
				  		.attr("value","0").attr("style","font-style:italic")
						.text("Create New User"));

			  }
		  }
	 );
  }
  $(document).ready(function(){
	  //$('.selectpicker').selectpicker();
	  refreshUserList();
  });
  
$(function() {
	//$( "#Dialog1" ).dialog(); 
});

  	$("#users").change(function() {
		selval=$("select").val();
		if (selval==0 || selval=="") {
			//
			$("#username").val("");
            $("#userid").val("0");
			$("#emailadr").val("");
			$("#password").val("");
			$("#ustatus").val("").selectpicker('render');
			$("#updateBtn").text("Add user");
			$("#result").text("");
		}
		else {
			$.getJSON("getUser.php",{userID:selval},
				function(data) {
					if (data.error!=0) {
						alert("Error reading user data - " + data.val);
						$("#username").val("");
			            $("#userid").val("0");
						$("#emailadr").val("");
						$("#password").val("");
						$("#ustatus").val("").selectpicker('render');
						$("#result").text("");
						$("#deleteButton").prop("disabled",true);					}
					else {
						$("#username").val(data.username);
                        $("#userid").val(selval);
						$("#emailadr").val(data.email);
						$("#password").val("unchangedPassw0rd!");
						$("#ustatus").val(data.ustatus).selectpicker('render');
						if (data.ustatus=="D")
							$("#deleteButton").prop("disabled",false);
						else
							$("#deleteButton").prop("disabled",true);
						$("#updateBtn").text("Update user");
						$("#result").text("");
					}
				}
			);
		}
	});
    
	function deleteUser()
	{
		selval=$("select").val();
		if (selval==0 || selval=="") {
			alert("Error: No user selected!");
		}
		else {
			if ($("#users option:selected").text()==authUser) {
				alert("Error: can't delete yourself!");
				die();
			}
			$.getJSON("deleteUser.php",{userID:selval},
				function(data) {
					if (data.error!=0) {
						alert("Error deleting user  - " + data.val);
					}
					refreshUserList();
				});
		}
	}

    function addupdateuser()
    {
		if ($("#users option:selected").text()==authUser) {
			if ($("#ustatus").val()!="A") {
				alert("Error: can't remove admin rights from current user");
				die();
			}
		}
		pw=SHA256($("#password").val());
		if ($("#password").val().length<8) {
			alert("Password too short!\n");
			die();
		}
		$.getJSON("updateUser.php",{userid:selval, username:$("#username").val(),
			email:$("#emailadr").val(),password:pw,ustatus:$("#ustatus").val()},
				function(data) {
					if (data.error!=0) {
						$("#result").html("<font color='red'><b>Error:"+data.val+"</b></font>");
					}
					else {
						refreshUserList(selval);
						$("#result").text(data.val);
					}
				}
				);
    };
  </script>
</body>
</html>