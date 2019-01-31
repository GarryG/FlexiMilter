<?php
session_name("fleximilter_local");
session_start();
session_destroy();

if (isset($_SESSION['username']))
	unset($_SESSION['username']);
if (isset($_SESSION['userid']))
	unset($_SESSION['userid']);
if (isset($_SESSION['status']))
	unset($_SESSION['status']);
/* unset($_SESSION['password']); */
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
	<nav class="navbar navbar-default navbar-fixed-top">
	  <div class="container-fluid">
	    <!-- Brand and toggle get grouped for better mobile display -->
	    <div class="navbar-header">
	      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#topFixedNavbar1" aria-expanded="false"><span class="sr-only">Toggle navigation</span><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></button>
	      <a class="navbar-brand" href="#"></a></div>
	    <!-- Collect the nav links, forms, and other content for toggling -->
	    <div class="collapse navbar-collapse" id="topFixedNavbar1">
	      <ul class="nav navbar-nav">
          </ul>
	      <form class="navbar-form navbar-left" role="search">
          </form>
	      <ul class="nav navbar-nav navbar-right">
	        <li class="dropdown">
	          <ul class="dropdown-menu">
	            <li><a href="#">Action</a></li>
	            <li><a href="#">Another action</a></li>
	            <li><a href="#">Something else here</a></li>
	            <li role="separator" class="divider"></li>
	            <li><a href="#">Separated link</a></li>
              </ul>
            </li>
          </ul>
        </div>
	    <!-- /.navbar-collapse -->
      </div>
	  <!-- /.container-fluid -->
</nav>
  <div class="jumbotron" id="mainpage">
    <h1 class="col-lg-offset-1 col-lg-4" align="center"><img src="media/logo.png"></h1>
<div class="col-lg-4 col-xs-10 col-xs-offset-1 col-lg-offset-0" id="infoframe">
  <div class="container">
  <p class="col-lg-offset-0 col-md-10 col-md-offset-1 col-lg-12 col-xs-offset-0 col-xs-12">You have logged out of the FLEXI-milter admin interface.</p>
  <p class="col-lg-offset-0 col-md-10 col-md-offset-1 col-lg-12 col-xs-offset-0 col-xs-12">Goodbye!</p></div>
  <form action="main.php" method="post" name="form1" id="form1">
&nbsp;
  <div class="container" id="loginwindow">
    <div>
      <div>
        <a href="index.php">
        <center><button type="button" class="btn btn-primary defaultbgcolor defaultbutton">Return to Login</button></center>
        </a>      </div>
    </div>
  <div class="container"></div>
  </form>
</div>
<!-- Include all compiled plugins (below), or include individual files as needed --> 
	<script src="js/bootstrap.js"></script>
  </body>
</html>