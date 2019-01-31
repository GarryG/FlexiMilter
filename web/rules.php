<?php
include "auth.php";
if ($status=="A") {
	// User is an admin user
	$ISADMIN=true;
}
else if ($status=="U") {
	$ISADMIN=false;
}
else {
    header("location: main.php?err=".urlencode("Access denied - insufficient authorization"));	// shouldn't even get to here ...
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FLEXI-milter Personal Rules</title>
    <!-- Bootstrap -->
	<link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/tabulator.css" rel="stylesheet">
	<link href="css/flexi.css" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap-select.min.css">

	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
		  <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
  <script src="js/jquery-1.11.3.min.js"></script>
  <script src="jquery-ui-1.12.1/jquery-ui.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/bootstrap-select.min.js"></script>
  <script src="js/i18n/defaults-de-DE.min.js"></script>
  <script src="js/sha.js"></script>
  <script src="js/tabulator.min.js"></script>
  <script src="js/jquery.input-ip-address-control-1.0.min.js"></script>

<script type="text/javascript">
        var authUser="<? echo $username;?>";
        var authStatus="<? echo $status;?>";
</script>

  </head>
<body class="jumbotron" id="mainjumbo" style="padding-top: 55px">
<div>
  <div class="row">
    <div class="col-lg-offset-1 col-lg-10 col-xs-12">
      <form action="global.php" method="post" name="global" id="globalContainer">
      <div class="container col-xs-12 ">
        <p>Personal Rules:</p>
        <div class="container col-xs-12" id="globalinfo">
          <div id="global-table"></div>
          <div><br /></div>
          <div class="container"><div class="btn-toolbar col-lg-offset-1 col-xs-offset-0 col-lg-12 col-xs-12" role="toolbar">
            <button type="button" class="btn btn-default defaultbgcolor defaultbutton" id="addButton">Add Rule</button>
          </div></div>
        </div>
      </div>
      </form>
    </div>
    <div class="col-xs-12"><p /></div>
    <div class="col-lg-offset-1 col-lg-10 col-xs-12"><form method="post" id="filterdata">
      <div class="container col-xs-12 "><div class="container col-xs-12" id="globaledit">
      <? if ($ISADMIN) { ?>
          <div class="input-group input-group-sm"><span class="input-group-addon defaultbgcolor" id="inputdescsm">Owner:</span>
            <select class="selectpicker" title="Select owner" data-width="auto" id="owner" name="owner">
<?
			$q="select userid,username,status from user where status='A' or status='U' order by username";
			$res=mysqli_query($db,$q);
			echo "<option value='0'>None</option>\n";
			if ($res) {
				$num=mysqli_num_rows($res);
				for ($t=0;$t<$num;$t++) {
					$data=mysqli_fetch_assoc($res);
					echo "<option value='".$data["userid"]."'";
					if ($auserid==$data["userid"]) echo " selected='selected'";
					echo "'>".$data["username"];
					if ($data["status"]=="A") echo " (A)";
					echo "</option>\n";
				}
			}
?>            
            </select>&nbsp;
            </div><br />
      <? } ?>
          <div class="input-group input-group-sm" id="dsender" ><span class="input-group-addon defaultbgcolor" id="inputdescsm">Sender:</span>
            <input name="sender" type="text" class="form-control-ip" id="sender" size="90%" maxlength="128" width="90%">&nbsp;
            <select class="selectpicker" data-width="auto" id="sendertype" name="sendertype" data-size="10" data-header="Select:"><option value="P">Pattern</option><option value="R">Regex</option></select><input name="id" type="hidden" id="id">
          </div><br />
          <div class="input-group input-group-sm" id="drcpt" ><span class="input-group-addon defaultbgcolor" id="inputdescsm">Recipient:</span>
            <input name="rcpt" type="text" class="form-control-ip" id="rcpt" size="90%" maxlength="128">&nbsp;
            <? if ($status=="A") { ?>
            <select class="selectpicker" data-width="auto" id="rcpttype" name="rcpttype" data-size="10" data-header="Select:"><option value="P" selected="selected">Pattern</option><option value="R">Regex</option></select>
	    <? } else {?>
	    <? } ?>
            <div id="WarnRcpt" hidden="hidden">&nbsp;<font color="red"><b>Recipient not permitted</b></font></div>
          </div><br />
          <div class="input-group input-group-sm" id="dsubject" ><span class="input-group-addon defaultbgcolor" id="inputdescsm">Subject:</span>
            <input name="subject" type="text" class="form-control-ip" id="subject" size="90%" maxlength="128">&nbsp;
            <select class="selectpicker" data-width="auto" id="subjecttype" name="rsubjecttype" data-size="10" data-header="Select:"><option value="P">Pattern</option><option value="R">Regex</option></select>
          </div><br />
          <div class="input-group input-group-sm" id="dheaderf" ><span class="input-group-addon defaultbgcolor" id="inputdescsm">Headerfield:</span>
            <input name="headerf" type="text" class="form-control-ip" id="headerf" size="90%" maxlength="128">&nbsp;
            <select class="selectpicker" data-width="auto" id="headertype" name="headertype" data-size="10" data-header="Select:"><option value="P">Pattern</option><option value="R">Regex</option></select>
          </div><br />
          <div class="input-group input-group-sm" id="dheaderv" ><span class="input-group-addon defaultbgcolor" id="inputdescsm">Headervalue:</span>
            <input name="headerv" type="text" class="form-control-ip" id="headerv" size="90%" maxlength="128">&nbsp;
            <select class="selectpicker" data-width="auto" id="headervtype" name="headervtype" data-size="10" data-header="Select:"><option value="P">Pattern</option><option value="R">Regex</option></select>
          </div><br />
          <div class="input-group input-group-sm"><span class="input-group-addon defaultbgcolor" id="inputdescsm">Action:</span>
          	<select class="selectpicker" data-width="auto" id="action" name="action" data-header="Select:"><option value="A" data-icon="glyphicon-ok">Accept</option><option value="D" data-icon="glyphicon-remove">Deny</option><option value="F" data-icon="glyphicon-arrow-right">Forward</option></select>
          </div><br />
          <div class="input-group input-group-sm"><span class="input-group-addon defaultbgcolor" id="inputdescsm">Priority:</span>
            <input name="prio" type="number" class="form-control" id="prio" size="4" maxlength="3" value="128">
          </div><br />
          <div class="input-group input-group-sm" id="dheaderv" ><span class="input-group-addon defaultbgcolor" id="inputdescsm">Forward to:</span>
            <input name="forward" type="text" class="form-control-ip" id="forward" size="90%" maxlength="128">&nbsp;
          </div><br />
          <div class="input-group input-group-sm" id="dheaderv" ><span class="input-group-addon defaultbgcolor" id="inputdescsm">Comment:</span>
            <input name="comment" type="text" class="form-control-ip" id="comment" size="90%" maxlength="128">&nbsp;
          </div><br />
          <div class="input-group input-group-sm" id="dheaderv" ><span class="input-group-addon defaultbgcolor" id="inputdescsm">Header:</span>
            <input name="tag" type="text" class="form-control-ip" id="tag" size="90%" maxlength="128">&nbsp;
          </div><br />
          <div id="result" class="col-sm-offset-1 col-sm-11 col-med-11 col-lg-11 text-success"></div>
          <div class="container"><div class="btn-toolbar col-lg-offset-1 col-lg-2" role="toolbar">
          	<div btn-toolbar col-offset-1 col-lg-2><button type="button" class="btn btn-default defaultbgcolor defaultbutton" id="updateBtn">Update Rule</button></div>
          </div>
          <div class="btn-toolbar col-lg-offset-1 col-lg-2" role="toolbar">
          	<div btn-toolbar col-offset-0 col-lg-2><button type="button" class="btn btn-default defaultbgcolor defaultbutton" id="deleteBtn" disabled data-toggle="modal" data-target="#deleteRequest">Delete Rule</button></div>
          </div></div>
      </div></form>
    </div></div>
    <div class="col-xs-12"><p /></div>
  </div>
</div>
<div class="container" id=""><form method="post" id="userform" accept-charset="UTF-8">
<? include "navbar.html" ?>
</div>
<div class="modal fade" id="deleteRequest" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">>
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
<!--        <h5 class="modal-title">Modal title</h5>-->
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><font color="red">Delete this rule?</font></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary defaultbgcolor" id="deleteBtnConfirm" data-dismiss="modal">Delete rule</button>
        <button type="button" class="btn btn-secondary defaultbgcolor" data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>
	<!-- Include all compiled plugins (below), or include individual files as needed --> 
  <script type="text/javascript">
  $.browser="undefined";
  function refreshList(selected)
  {
	  $("#global-table").tabulator("setData").tabulator({renderComplete:function(data){
		  if (selected>0)
			  $("#global-table").tabulator("selectRow", selected);
	  }});

  }

  $(document).ready(function(){
	            $("#global-table").tabulator({
              		height:"300px", // set height of table (optional)
					ajaxURL:"userrules.php",
					selectable:1,
					fitColumns:true, //fit columns to width of table (optional)
					responsiveLayout:true,
                    columns:[ //Define Table Columns
                    	// 
						{title:"Owner", field:"owner", sorter:"string", sortable:false,headerFilter:true,width:120},
						// 
                    	{title:"From", field:"from", sorter:"string", sortable:false,headerFilter:true},
                    	{title:"To", field:"to", sorter:"string", sortable:false,headerFilter:true},
                    	{title:"Subject", field:"subject", sorter:"string", sortable:false,headerFilter:true},
                    	{title:"Header", field:"header", sorter:"string", sortable:false,headerFilter:true},
                    	{title:"Valid to", field:"timestmp", sorter:"date", sortable:false,headerFilter:true},
                    	{title:"Action", field:"action", sorter:"string", sortable:false,width:"100px",headerFilter:function(cell,value,data){
							var editor=$("<select><option value='' /><option value='Deny'>Deny</option><option value='Accept'>Accept</option><option value='Fwd'>Forward</option></select>");
							editor.css({"padding":"4px","width":"100%","box-sizing":"border-box"});
							editor.val(value);
							if (cell.hasClass("tabulator-cell")) {
								setTimeout(function(){editor.focus();},100);
							}
							editor.on("change blur",function(e){
								cell.trigger("editval",editor.val());
							});
							return editor;
						}},
                    	{title:"Priority", field:"pri", sorter:"number",sortable:false, align:"center",width:"100px",headerFilter:"number"},
                    ],
                    rowClick:function(e, id, data, row){
						$("#result").text("");
						$.getJSON("getUserRule.php",{id:id},
							function(data) {
								if (data.error!=0) {
									alert("Error reading rule entry - "+data.val);
									$("#id").val(0);
									$("#owner").val(null).selectpicker("render");
									$("#sendertype").val("P").selectpicker("render");
									$("#rcpttype").val("P").selectpicker("render");
									$("#subjecttype").val("P").selectpicker("render");
									$("#headertype").val("P").selectpicker("render");
									$("#headervtype").val("P").selectpicker("render");
									$("#sender").val("%");
									$("#rcpt").val("%");
									$("#subject").val("%");
									$("#headerv").val("%");
									$("#headerf").val("");
									$("#action").val("A").selectpicker("render");
									$("#prio").val("128");
									$("#comment").val("");
									$("#tag").val("");
									$("#forward").val("");
									$("#updateBtn").text("Create rule");
									$("#deleteBtn").prop("disabled",true);
								}
								else {
									$("#id").val(data.id);
									$("#owner").val(data.userid).selectpicker("render");
									$("#sendertype").val(data.sendertype).selectpicker("render");
									$("#rcpttype").val(data.rcpttype).selectpicker("render");
									$("#subjecttype").val(data.subjecttype).selectpicker("render");
									$("#headertype").val(data.headertype).selectpicker("render");
									$("#headervtype").val(data.headervtype).selectpicker("render");
									$("#sender").val(data.sender);
									$("#rcpt").val(data.rcpt);
									$("#subject").val(data.subject);
									$("#headerv").val(data.headerv);
									$("#headerf").val(data.headerf);
									$("#action").val(data.action).selectpicker("render");
									$("#prio").val(data.prio);
									$("#forward").val(data.forward);
									$("#comment").val(data.comment);
									$("#tag").val(data.tag);
									$("#updateBtn").text("Update rule");
									$("#deleteBtn").prop("disabled",false);
								}
							});
					},
          		});
  });
  
  $(window).resize(function(){
    $("#global-table").tabulator("redraw");
  });

	$("#addButton").click(function() {
									$("#id").val(0);
									$("#owner").val("<? echo $auserid; ?>").selectpicker("render");
									$("#sendertype").val("P").selectpicker("render");
									$("#rcpttype").val("P").selectpicker("render");
									$("#subjecttype").val("P").selectpicker("render");
									$("#headertype").val("P").selectpicker("render");
									$("#headervtype").val("P").selectpicker("render");
									$("#sender").val("%");
									$("#rcpt").val("%");
									$("#subject").val("%");
									$("#headerv").val("%");
									$("#headerf").val("");
									$("#action").val("A").selectpicker("render");
									$("#prio").val("128");
									$("#comment").val("");
									$("#tag").val("");
									$("#forward").val("");
									$("#updateBtn").text("Create rule");
									doFields($("#field").val());
									$("#deleteBtn").prop("disabled",true);

	});
	
	$("#deleteBtnConfirm").click(function() {
//		$('#deleted').modal();
		$.getJSON("deleteFilter.php",{
			id:$("#id").val()},
			function(data) {
				if (data.error!=0) {
					$("#result").html("<font color='red'><b>Error:"+data.val+"</b></font>");
					$("#result").text(data.val);
				}
				else {
					$("#id").val(0);
					$("#deleteBtn").prop("disabled",true);
					$("#updateBtn").text("Recover rule");
					refreshList(0);
				}
			}
		);
	});

	$.postJSON = function(url, data, func) { $.post(url+(url.indexOf("?") == -1 ? "?" : "&")+"callback=?", data, func, "json"); }

	$("#updateBtn").click(function() {
		$("#result").text("");
		$.getJSON("updateFilter.php",{
			id:$("#id").val(),
			owner:$("#owner").val(),
			rcpt:$("#rcpt").val(),
			sender:$("#sender").val(),
			subject:$("#subject").val(),
			headerf:$("#headerf").val(),
			headerv:$("#headerv").val(),
			rcpttype:$("#rcpttype").val(),
			sendertype:$("#sendertype").val(),
			subjecttype:$("#subjecttype").val(),
			headertype:$("#headertype").val(),
			headervtype:$("#headervtype").val(),
			action:$("#action").val(),
			resultcode:0,
			xresultcode:"",
			resultmsg:"",
			prio:$("#prio").val(),
			endts:$("#endts").val(),
			forward:$("#forward").val(),
			comment:$("#comment").val(),
			tag:$("#tag").val(),
			filtercol:""
			},
			function(data) {
				if (data.error!=0) {
					$("#result").html("<font color='red'><b>Error:"+data.val+"</b></font>");
					$("#result").text(data.val);
				}
				else {
					$("#id").val(data.id);
					refreshList(data.id);
					$("#updateBtn").text("Update rule");
				}
			}
		);
	});
	
  </script>
</body>
</html>
