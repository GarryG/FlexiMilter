<?php
include "auth.php";
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FLEXI-milter Admin</title>
    <!-- Bootstrap -->
	<link href="css/bootstrap.css" rel="stylesheet">
	<link href="css/flexi.css" rel="stylesheet">

	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
		  <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
  </head>
<body class="jumbotron" id="mainjumbo" style="padding-top: 40px">
<!-- jQuery (necessary for Bootstrap's JavaScript plugins) --> 
	<script src="js/jquery-1.11.3.min.js"></script>
  <div class="jumbotron" id="mainpage">
    <h1 class="col-lg-offset-1 col-lg-4" align="center"><img src="media/logo.png"></h1>
    <p class="col-lg-offset-0 col-lg-4">&nbsp;</p>
    <p class="col-lg-offset-0 col-lg-4">Welcome <? echo $username; ?> to the FLEXI-milter admin interface.</p>
    <p class="col-lg-offset-0 col-lg-4">Please use the menu for all of your administrational needs </p>
    <?
		if (isset($_REQUEST['err']))
			echo "<p class=\"col-lg-offset-0 col-lg-4\"><font color='#f00'>Error: ".$_REQUEST['err']."</font></p>";
			?>
  </div>
  <nav class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid">
      <!-- Brand and toggle get grouped for better mobile display -->
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#topFixedNavbar1" aria-expanded="false"><span class="sr-only">Toggle navigation</span><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></button>
        <a class="navbar-brand" href="#"></a></div>
      <!-- Collect the nav links, forms, and other content for toggling -->
      <div class="collapse navbar-collapse" id="topFixedNavbar1">
        <ul class="nav navbar-nav">
          <li class="active"><a href="main.php">About<span class="sr-only">(current)</span></a></li>
          <li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Edit<span class="caret"></span></a>
            <ul class="dropdown-menu">
              <? if ($status=='A') { ?><li><a href="users.php">Users</a></li>
              <li><a href="global.php">Global Rules</a></li><? } ?>
              <li><a href="rules.php">Personal Rules</a></li>
              <li role="separator" class="divider"></li>
              <li><a href="adr.php">Authorized Addresses</a></li>
            </ul>
          </li>
          <li><a href="logs.php">Logs</a></li>      
          <li><a href="logout.php">Logout</a></li>      
        </ul>

        <ul class="nav navbar-nav navbar-right">
          <li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Help<span class="caret"></span></a>
			<ul class="dropdown-menu">
              <li><a href="#">Action</a></li>
              <li><a href="#">Another action</a></li>
              <li><a href="#">Something else here</a></li>
              <li role="separator" class="divider"></li>
              <li><a href="#">Version</a></li>
            </ul>
          </li>

        </ul>
      </div>
      <!-- /.navbar-collapse -->
    </div>
    <!-- /.container-fluid -->
  </nav>
	<!-- Include all compiled plugins (below), or include individual files as needed --> 
	<script src="js/bootstrap.js"></script>
  </body>
</html>