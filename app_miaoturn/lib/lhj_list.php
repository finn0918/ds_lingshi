<?php
define('ADMIN_USERNAME','cehua'); // Admin Username
define('ADMIN_PASSWORD','123456'); // Admin Password
 
 
///////////////// Password protect ////////////////////////////////////////////////////////////////
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
           $_SERVER['PHP_AUTH_USER'] != ADMIN_USERNAME ||$_SERVER['PHP_AUTH_PW'] != ADMIN_PASSWORD) {
                        Header("WWW-Authenticate: Basic realm=\"Memcache Login\"");
                        Header("HTTP/1.0 401 Unauthorized");
 
                        echo <<<EOB
                                <html><body>
                                <h1>Rejected!</h1>
                                <big>Wrong Username or Password!</big>
                                </body></html>
EOB;
                        exit;
}



include('config.php');

$db = new PDO('mysql:host='.$c['DB_HOST'].';dbname='.$c['DB_NAME'],$c['DB_USER'],$c['DB_PWD']);
$db->exec("set names utf8");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql="SELECT * FROM " . $c['DB_TABLE'];
$stmt = $db->prepare($sql);
$stmt->execute();
$list=$stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" class="off">
<head>
  <title>中奖名单</title>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
  <style>
	.mainPlan{
		width:60%;
		margin:20px auto;
	}
	.table{
		border:1px solid #ddd;
	}
	.mainPlan th,.mainPlan .center{
		text-align:center;
	}
  </style>
</head>
<body scroll="no">
	
	<div class="container-fluid mainPlan">
	  <section class="row">
		<h3>中奖者信息</h3>
		<table class="table table-striped table-hover">
			<thead>
				<tr>
				  <th>序号</th>
				  <th>姓名</th>
				  <th>电话</th>
				  <th>奖项</th>
				  <th>中奖时间</th>
                    <th>获奖人地址</th>
				</tr>
			</thead>
			<?php 
				$i = 0;
				foreach ($list as $key=>$val){
				++$i;
			?>
			<tr>
			  <td class="center"><?php echo $i ?></td>
			  <td class="center"><?php echo $val['name'];?></td>
			  <td class="center"><?php echo $val['tel'];?></td>
			  <td class="center">
			  <?php 
				$hits = $val['hit'];
				switch($hits){
					case 1 :
						echo '5元优惠券（满59元）';
						break;
					case 2 :
						echo '20M流量';
						break;
					case 3 :
						echo '10元优惠券（满99元）';
						break;
                    case 4 :
                        echo '卡乐比薯条';
                        break;
                    case 5 :
                        echo '100M流量';
                        break;
                    case 6 :
                        echo '5元优惠券（满59元）';
                        break;
                    case 7 :
                        echo '10元优惠券（满99元）';
                        break;
                    case 8 :
                        echo '价值11元零食礼包';
                        break;
                    case 10 :
                        echo '5元优惠券（满59元）';
                        break;
                    case 11 :
                        echo '10元优惠券（满99元）';
                        break;
                    case 12 :
                        echo '价值111元零食礼包';
                        break;
				} ?>
				</td>
			  <td class="center"><?php echo date('Y-m-d H:i:s', $val['time']);?></td>
                <td class="center"><?php echo $val['address'];?></td>
			</tr>
			<?php }?>
		</table>
	  </section>
	</div>


</body>
</html>
