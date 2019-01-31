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
      <div class="container col-xs-12 "><p>Global Rules:</p>
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
    <div class="col-lg-offset-1 col-lg-10 col-xs-12"><form action="users.php" method="post" id="globaldata">
      <div class="container col-xs-12 "><div class="container col-xs-12" id="globaledit">
          <div class="input-group input-group-sm"><span class="input-group-addon defaultbgcolor" id="inputdescsm">Filter-Type:</span>
            <select class="selectpicker" title="Select filter type" data-width="auto" id="field" name="field">
              <option value='4'>IPv4</option>
              <option value='6'>IPv6</option>
              <option value='H'>Remote Host</option>
              <option value='E'>HELO request</option>
              <option value='F'>SMTP From</option>
              <option value='T'>SMTP To</option>
            </select>&nbsp;<input type="hidden" id="id" value="0">
            </div><br />
          <div class="input-group input-group-sm" id="grp_pattern" style="display:none"><span class="input-group-addon defaultbgcolor" id="inputdescsm">Match Pattern:</span>
          	<input type="hidden" id="id">
            <input name="pattern" type="text" class="form-control-ip" id="matchpattern" placeholder="enter pattern" size="40" maxlength="128">&nbsp;
            <select class="selectpicker" title="Type" data-width="auto" id="matchtype" name="matchtype">
              <option value='P'>Simple</option>
              <option value='R'>Regex</option></select>
          </div>
          <div class="input-group input-group-sm" id="grp_ipv4" hidden="hidden" style="display:none"><span class="input-group-addon defaultbgcolor" id="inputdescsm">IPv4 Address:</span>
            <input name="ipv4" type="text" class="form-control-ip" id="ipv4" > / <input name="ipv4m" type="text" class="form-control-ip" id="ipv4m">&nbsp;
            <select class="selectpicker" data-width="auto" id="bits" name="bits" data-header="Select:" data-size="10">
              <option value="32">32</option><option value="31">31</option><option value="30">30</option><option value="29">29</option><option value="28">28</option><option value="27">27</option><option value="26">26</option><option value="25">25</option><option value="24">24</option><option value="23">23</option><option value="22">22</option><option value="21">21</option><option value="20">20</option><option value="19">19</option><option value="18">18</option><option value="17">17</option><option value="16">16</option><option value="15">15</option><option value="14">14</option><option value="13">13</option><option value="12">12</option><option value="11">11</option><option value="10">10</option><option value="9">9</option><option value="8">8</option><option value="7">7</option><option value="6">6</option><option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option><option value="0">0</option><option value="-1">Custom</option>
            </select>
          </div>
          <div class="input-group input-group-sm" id="grp_ipv6" style="display:none"><span class="input-group-addon defaultbgcolor" id="inputdescsm">IPv6 Address:</span>
            <input name="ipv6" type="text" class="form-control-ip" id="ipv6"> /&nbsp;
            <select class="selectpicker" data-width="auto" id="bits6" name="bits6" data-size="10" data-header="Select:"><option value="128">128</option><option value="127">127</option><option value="126">126</option><option value="125">125</option><option value="124">124</option><option value="123">123</option><option value="122">122</option><option value="121">121</option><option value="120">120</option><option value="119">119</option><option value="118">118</option><option value="117">117</option><option value="116">116</option><option value="115">115</option><option value="114">114</option><option value="113">113</option><option value="112">112</option><option value="111">111</option><option value="110">110</option><option value="109">109</option><option value="108">108</option><option value="107">107</option><option value="106">106</option><option value="105">105</option><option value="104">104</option><option value="103">103</option><option value="102">102</option><option value="101">101</option><option value="100">100</option><option value="99">99</option><option value="98">98</option><option value="97">97</option><option value="96">96</option><option value="95">95</option><option value="94">94</option><option value="93">93</option><option value="92">92</option><option value="91">91</option><option value="90">90</option><option value="89">89</option><option value="88">88</option><option value="87">87</option><option value="86">86</option><option value="85">85</option><option value="84">84</option><option value="83">83</option><option value="82">82</option><option value="81">81</option><option value="80">80</option><option value="79">79</option><option value="78">78</option><option value="77">77</option><option value="76">76</option><option value="75">75</option><option value="74">74</option><option value="73">73</option><option value="72">72</option><option value="71">71</option><option value="70">70</option><option value="69">69</option><option value="68">68</option><option value="67">67</option><option value="66">66</option><option value="65">65</option><option value="64">64</option><option value="63">63</option><option value="62">62</option><option value="61">61</option><option value="60">60</option><option value="59">59</option><option value="58">58</option><option value="57">57</option><option value="56">56</option><option value="55">55</option><option value="54">54</option><option value="53">53</option><option value="52">52</option><option value="51">51</option><option value="50">50</option><option value="49">49</option><option value="48">48</option><option value="47">47</option><option value="46">46</option><option value="45">45</option><option value="44">44</option><option value="43">43</option><option value="42">42</option><option value="41">41</option><option value="40">40</option><option value="39">39</option><option value="38">38</option><option value="37">37</option><option value="36">36</option><option value="35">35</option><option value="34">34</option><option value="33">33</option><option value="32">32</option><option value="31">31</option><option value="30">30</option><option value="29">29</option><option value="28">28</option><option value="27">27</option><option value="26">26</option><option value="25">25</option><option value="24">24</option><option value="23">23</option><option value="22">22</option><option value="21">21</option><option value="20">20</option><option value="19">19</option><option value="18">18</option><option value="17">17</option><option value="16">16</option><option value="15">15</option><option value="14">14</option><option value="13">13</option><option value="12">12</option><option value="11">11</option><option value="10">10</option><option value="9">9</option><option value="8">8</option><option value="7">7</option><option value="6">6</option><option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option><option value="0">0</option></select>
          </div>
          <br /><div class="input-group input-group-sm"><span class="input-group-addon defaultbgcolor" id="inputdescsm">Action:</span>
          	<select class="selectpicker" data-width="auto" id="action" name="action" data-header="Select:"><option value="C" data-icon="glyphicon-arrow-down">Continue</option><option value="B" data-icon="glyphicon-remove">Block</option><option value="W" data-icon="glyphicon-ok">Allow</option></select>
          </div><br />
          <div class="input-group input-group-sm"><span class="input-group-addon defaultbgcolor" id="inputdescsm">Priority:</span>
            <input name="prio" type="number" class="form-control" id="prio" size="4" maxlength="3" value="128">
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
        <p><font color="red">Delete blacklist rule?</font></p>
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

	  			$('#ipv4').ipAddress();
	  			$('#ipv4m').ipAddress();
				$('#ipv6').ipAddress({v:6});
	            $("#global-table").tabulator({
              		height:"300px", // set height of table (optional)
					ajaxURL:"globalbw.php",
					selectable:1,
					fitColumns:true, //fit columns to width of table (optional)
					responsiveLayout:true,
                    columns:[ //Define Table Columns
                    	{title:"Match type", field:"mtch", sorter:"string", sortable:false,width:120, headerFilter:function(cell,value,data){
							var editor=$("<select><option value='' /><option value='IPv4'>IPv4</option><option value='IPv6'>IPv6</option><option value='Rmt Host'>Rmt Host</option><option value='HELO'>HELO</option><option value='SMTP From'>SMTP From</option><option value='SMTP To'>SMTP To</option></select>");
							editor.css({"padding":"4px","width":"100%","box-sizing":"border-box"});
							editor.val(value);
							if (cell.hasClass("tabulator-cell")) {
								setTimeout(function(){editor.focus();},100);
							}
							editor.on("change blue",function(e){
								cell.trigger("editval",editor.val());
							});
							return editor;
						}},
                    	{title:"Match Value", field:"mvalue", sorter:"string", sortable:false,headerFilter:true},
                    	{title:"Rule action", field:"action", sorter:"string", sortable:false,width:"100px",headerFilter:function(cell,value,data){
							var editor=$("<select><option value='' /><option value='Black'>Blacklist</option><option value='White'>Whitelist</option><option value='Cont'>Continue</option></select>");
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
						$.getJSON("getGlobal.php",{id:id},
							function(data) {
								if (data.error!=0) {
									alert("Error reading Black/Whitelist entry - "+data.val);
									$("#id").val(0);
									$("#field").val("").selectpicker("render");
									$("#matchtype").val("").selectpicker("render");
									$("#matchpattern").val("");
									$("#ipv4").val("");
									$("#ipv4m").val("");
									$("#bits").val("").selectpicker("render");
									$("#ipv6").val("");
									$("#bits6").val("").selectpicker("render");
									$("#action").val("").selectpicker("render");
									$("#prio").val("128");
									$("#updateBtn").text("Create rule");
									doFields($("#field").val());
									$("#deleteBtn").prop("disabled",true);
								}
								else {
									$("#id").val(data.id);
									$("#field").val(data.field).selectpicker("render");
									$("#matchtype").val(data.matchtype).selectpicker("render");
									$("#matchpattern").val(data.pattern);
									$("#ipv4").val(int2ip4str(data.patternnum));
									$("#ipv4m").val(int2ip4str(data.patternmask));
			var t;
			for (t=1;t<=32;t++) {
				v=(Math.pow(2,32)-Math.pow(2,32-t));
				if (v==data.patternmask) {
					$("#bits").val(t).selectpicker('render');
					break;
				}
			}
			if (t==33)	// no match for CIDR masks
				$("#bits").val("-1").selectpicker('render');
									$("#ipv6").val("");
									$("#bits6").val("").selectpicker("render");
									$("#action").val(data.action).selectpicker("render");
									$("#prio").val(data.prio);
									doFields(data.field);
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

$(function() {
	//$( "#Dialog1" ).dialog(); 
});

	function ip4str2int(s) {
		var ipl=0;
		s.split('.').forEach(function(o) {
			ipl<<=8;
			ipl+=parseInt(o);
		});
		return(ipl>>>0);
	};
	
	function int2ip4str(i) {
		return((i>>>24) +'.' + (i>>16 & 255) +'.' + (i>>8 & 255) +'.' + (i & 255) );
	};

	$("#ipv4").on('focusout',function(e) {
		var m=$("#ipv4").val();
		m=m.replace(/___/g,"0").replace(/_*/g,"")
		alert(m);
	});

	var maskold;
	$("#ipv4m").on('focus',function(e) {
		maskold=$("#ipv4m").val();
		console.log("Mask old:"+maskold);
		if (maskold=="___.___.___.___") maskold="0.0.0.0";
	});
	
	$("#ipv4m").on('focusout',function(e) {
		var m=$("#ipv4m").val();
		m=m.replace(/___/g,"0").replace(/_*/g,"")
		if (m!=maskold) {
			ip=$("#ipv4").val();
			ip=ip4str2int(ip);
			ma=ip4str2int(m);
			ip&=ma;
			$("#ipv4").val(int2ip4str(ip));
			var t;
			for (t=1;t<=32;t++) {
				v=(Math.pow(2,32)-Math.pow(2,32-t));
				if (v==ma) {
					$("#bits").val(t).selectpicker('render');
					break;
				}
			}
			if (t==33)	// no match for CIDR masks
				$("#bits").val("-1").selectpicker('render');
		}
	});
	$("#ipv6").on('focusout',function(e) {
		var m=$("#ipv6").val();
		m=m.replace(/____/g,"0").replace(/_*/g,"")
		alert(m);
	});
	$("#bits").change(function() {
		maskold=$("ipv4m").val();
		bitsel=$("#bits").val();
		if (bitsel>-1) {
			v=(Math.pow(2,32)-Math.pow(2,32-bitsel));
			m=int2ip4str(v);
			$("#ipv4m").val(m);
			if (maskold!=m) {
				ip=$("#ipv4").val();
				ip=ip4str2int(ip);
				ma=ip4str2int(m);
				ip&=ma;
				$("#ipv4").val(int2ip4str(ip));
			}
		}
		else {
			// leave ipv4m unchanged
		}
	});
	
	function doFields(field) {
		$("#grp_pattern").hide();
		$("#grp_ipv4").hide();
		$("#grp_ipv6").hide();
		switch (field) {
			case "4":
				$("#grp_ipv4").show();
				break;
			case "6":
				$("#grp_ipv6").show();
				break;
			case "H":
			case "E":
			case "T":
			case "F":
				$("#grp_pattern").show();
				break;
		}
	};
	
	$("#addButton").click(function() {
		$("#id").val(0);
		$("#field").val("4").selectpicker("render");
		$("#matchtype").val("P").selectpicker("render");
		$("#matchpattern").val("%");
		$("#ipv4").val("0.0.0.0");
		$("#ipv4m").val("0.0.0.0");
		$("#bits").val("0").selectpicker("render");
		$("#ipv6").val("::");
		$("#bits6").val("0").selectpicker("render");
		$("#action").val("C").selectpicker("render");
		$("#prio").val("128");
		$("#updateBtn").text("Create rule");
		doFields("4");
		$("#result").text("");
		$("#deleteBtn").prop("disabled",true);
		$("#global-table").tabulator("deselectRow");
	});
	
	$("#deleteBtnConfirm").click(function() {
//		$('#deleted').modal();
		$.getJSON("deleteGlobal.php",{
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
	
	$("#updateBtn").click(function() {
		$("#result").text("");
		if ($("#field").val()=="6") {
			$("#matchpattern").val($("#ipv6").val()+"/"+$("#bits6"));
		}
		$.getJSON("updateGlobal.php",{
			id:$("#id").val(),
			field:$("#field").val(),
			matchtype:$("#matchtype").val(),
			pattern:$("#matchpattern").val(),
			patternnum:ip4str2int($("#ipv4").val()),
			patternmask:ip4str2int($("#ipv4m").val()),
			action:$("#action").val(),
			prio:$("#prio").val()},
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
	
	$("#field").change(function() {
		field=$("#field").val();
		doFields(field);
	});
    
  </script>
</body>
</html>