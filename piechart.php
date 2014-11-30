<?php


$days = isset($_GET['d']) ? (integer)$_GET['d'] : 30;
$post = isset($_GET['p']) ? (integer)$_GET['p'] : 0;

if($days>365) $days=30;
if($days<7) $days=7;
$qs = "piechart.php?";
if($post) $qs .= "p=".$post."&";
$qs .= "d=";

// build period navigation:
$nav = "Period:";

$arNav = array(
	"1 week"=>"7",
	"2 weeks"=>"14",
	"1 month"=>"30",
	"2 months"=>"60",
	"3 months"=>"91",
	"6 months"=>"182",
	"1 year"=>"365"
);
foreach ($arNav as $k=>$v){
	$nav.="<a href=\"".$qs.$v."\" ".($v==$days ? "class='sel'":"").">".$k."</a>";
}

$legend = "['Facebook','Twitter','Google+','Linkedin','Pinterest','Vkontakte']";

if($post==0) {


} else {

	$nav.= "Type:". " ".
		"<a class='sel'>"."Distribution"."</a>" .
		"<a href=\"chart.php?p=".$post."&d=".$days."\">"."Trend"."</a>";
}


?>
<html>
  <head>
  <style>
	body,a {font-size:10px; line-height:13px; font-family: sans-serif; color:#111;background-color:#fff}
	a { padding:0 5px; display:inline-block; margin-right:5px; text-decoration:none; color:#aaa}
	a.sel{ background-color:#ddd;color:#111;}
	div { font-size:20px; line-height:24px; text-align:center;}
	div a { font-size:14px; line-height:18px; display:inline-block; padding:5px; text-transform:uppercase; border:1px solid #aaa;}
	div a:hover {border:1px solid #111; background-color:#eee;color:#111}
</style>
  </head>
  <body>
	<?php echo $nav;?>
    <div id="donutchart" style="width: 95%; height: 270px;">
		<br>
		Charts are available only in the complete version.
		<br><br>
	</div>
  </body>
</html>