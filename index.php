<?php
	require("jsonRPC/jsonRPCClient.php");
	require("sanitize.inc.php");
	require('phpqrcode/qrlib.php');
	require("config.php");
	
	$mysqli = new mysqli($database_host, $database_user, $database_pass, $database);

	if(isset($_REQUEST['action'])) $action = sanitize_paranoid_string($_REQUEST['action']); else $action = "";
	if(isset($_REQUEST['address'])) $address = sanitize_paranoid_string($_REQUEST['address']); else $address = "";
	if(isset($_REQUEST['amount'])) $amount = sanitize_float($_REQUEST['amount']); else $amount = "ARO";	
	if(isset($_REQUEST['currency'])) $currency = strtoupper(sanitize_paranoid_string($_REQUEST['currency'])); else $currency = "";	
	if(isset($_REQUEST['message'])) $message = sanitize_html_string($_REQUEST['message']); else $message = "";
	if(isset($_REQUEST['callback_url'])) $callback_url = urldecode(($_REQUEST['callback_url'])); else $callback_url = "";
	
	switch($action) {
		case 'request_payment':
			if(!filter_var($callback_url, FILTER_VALIDATE_URL) && $callback_url) {
				$pay_load['error'] = "bad callback url";
				echo json_encode($pay_load);
				exit;
			}

			if($currency != 'ARO') {
				$cmc_api="https://api.coinmarketcap.com/v2/ticker/3024/?convert=$currency";
				$results = file_get_contents($cmc_api);

				$data = json_decode($results);
				$price = $data->data->quotes->$currency->price;
				if(!$price) {
					$pay_load['error'] = "Was not able to convert from $currency";
					echo json_encode($pay_load);
					exit;
				}
				else
					$aro_amount = round($amount / $price,$round_price);
			}
			else 
				$aro_amount = $amount;
			
			$pay_load['aro_amount']=$aro_amount;

			$bitcoin = new jsonRPCClient("http://$json_rpc_user:$json_rpc_pass@$json_rpc_server/");
			$address=$bitcoin->getnewaddress();
	
			$pay_load['address']=$address;
			$link = "arosend|$address|$aro_amount|$message";
			$pay_load['link']=$link;

			$qr_file_location="qr/$address.png";
			QRcode::png($link, $qr_file_location,NULL,5);
			
			if(file_exists($qr_file_location))
				$pay_load['qr']=$_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST'].$aro_dir."/".$qr_file_location;
			else
				$pay_load['error']="QR failed to generate, make sure your web account has write access to the qr folder.  Save location $qr_file_location";

			$sql="insert into aro_payments (aro_address,aro_amount,message) values ('$address',$aro_amount,'$message')";
			$mysqli->query($sql);

			if($address && $callback_url)
				file_get_contents($callback_url);
		break;
		case 'check_payment':
			$node_api = "http://$node_server/api.php?q=getTransactions&account=$address";
			$results = file_get_contents($node_api);
			$data = json_decode($results);

			foreach($data->data as $trans) {
				if($trans->type == 'credit') {
					$aro_actual =$trans->val;
					$pay_load['aro_actual']=$aro_actual;
					break;
				}
			}
				
			if(!$aro_actual)
				$pay_load['aro_actual'] = 0;

			$sql="select aro_amount from aro_payments where aro_address = '$address'";
			
			$result = $mysqli->query($sql);
			$row = $result->fetch_assoc();
			$aro_amount = $row['aro_amount'];
			$pay_load['aro_expected']=$aro_amount;
			
			if($auto_withdraw && $aro_actual > 0) {
				$bitcoin = new jsonRPCClient("http://$json_rpc_user:$json_rpc_pass@$json_rpc_server/");
				$bitcoin->walletpassphrase($wallet_pw);
				$balance = $bitcoin->getbalance("*");
				
				if($aro_actual >= $balance)
					$aro_actual=$balance-$bank;
				
				$txid = $bitcoin->sendtoaddress($wallet_address,floatval($aro_actual));
				$pay_load['txids'] = array('txid' => $txid, 'amount' => $aro_actual);
			}
		break;
		case 'getbalance':
			$bitcoin = new jsonRPCClient("http://$json_rpc_user:$json_rpc_pass@$json_rpc_server/");
			$balance = $bitcoin->getbalance("*");
			$pay_load['balance']=$balance;
		break;
		case 'collect_funds':
			$bitcoin = new jsonRPCClient("http://$json_rpc_user:$json_rpc_pass@$json_rpc_server/");
			$bitcoin->walletpassphrase($wallet_pw);
			$balance = $bitcoin->getbalance("*");
			
			if(!$amount)
				$amount=$balance-$bank;

			if($amount >= $balance)
				$amount=$balance-$bank;

			if($aro_actual > 0) {
				$txid = $bitcoin->sendtoaddress($wallet_address,floatval($amount));
				$pay_load['txid'] = array('txid' => $txid, 'amount' => $amount);
			}
			else
				$pay_load['error'] = "Insufficient funds, trying lowering the bank value.  Balance: $balance";
		break;
		default:
			$pay_load['message'] = "ARO Payments is online!";
		}

	echo json_encode($pay_load);

?>