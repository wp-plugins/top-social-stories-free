<?php

$sn = isset($_GET['sn']) ? $_GET['sn'] : "facebook";

$qs = "colchart.php?";
$qs .= "sn=";


$nav = "Social network:";

$arNav = array(
	"all"=>"All",
	"facebook"=>"Facebook",
	"twitter"=>"Twitter",
	"google"=>"Google+",
	"linkedin"=>"Linkedin",
	"pinterest"=>"Pinterest",
	"vkontakte"=>"Vkontakte"
);

$arColors = array(
	"all"=>"#dddddd",
	"facebook"=>'#3b5999',
	"twitter"=>'#2fa0d8',
	"google"=>'#ff9900',
	"linkedin"=>'#99ccff',
	"pinterest"=>'#ff0000',
	"vkontakte"=>'#669999'
);

foreach ($arNav as $k=>$v){
	$nav.="<a href=\"".$qs.$k."\" ".($k==$sn ? "class='sel'":"").">".$v."</a>";
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
    <div id="chart_div" style="width: 95%; height: 270px;">
		<br>
		Charts are available only in the complete version.
		<br><br>
</div>
  </body>
</html>