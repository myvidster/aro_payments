Installation Instructions

Install php-gd

Clone aro_payments to your apache/nginx web directory.<br>
Copy config.php.example to config.php and edit it.<br>
Grant ownership to the apache/nginx web user account to the jsonRPC and qr folder.<br>
Import aro_payments.sql in the contrib folder to create the aro_payments table.<br>
Create a virtualHost in the apache/nginx config, select a port to run the payment proccess on (ie 2222)<br>
Restart apache/nginx<br>
On the same machine go to run curl -X GET http://127.0.0.1:2222/ and you will be greeting with.<br>

API<br>

<b>GET /?action=request_payment&amount=:amount&currency=:currency&message=:message&callback_url=:callback_url</b><br>

Create a request to pay, supports all currencies from coinmarketcap.com. Non-aro currency is converted to aro using current rate from coinmarketcap.com. Returns a json document with QR code to be displayed to the payer, and a unique address for that particular payment (you can use it as invoice id). Message will be displayed to the client (for example, you can write "Payment for goods"). Seller and customer - system field, here you can write the application that created the request and the payer id. Keep Seller field private, it is also used for payouts. Callback_url will be requested once the invoice is paid.<br>
<pre>
<code>
example response<br>
<br>
	{	<br>
		"link" : "arosend|3CQV5mW19qDEL8XG5CZuRAPpaVU5YACMBP8PrYGgKiQtS4jZLp2FuM8hrPKF6pfXZYkoxf1nswJnLPuCaRncM1B1|44|love%20you%20mom",<br>
		"qr" : "http://localhost:2222/qr/3CQV5mW19qDEL8XG5CZuRAPpaVU5YACMBP8PrYGgKiQtS4jZLp2FuM8hrPKF6pfXZYkoxf1nswJnLPuCaRncM1B1.png",
		"address" : "3CQV5mW19qDEL8XG5CZuRAPpaVU5YACMBP8PrYGgKiQtS4jZLp2FuM8hrPKF6pfXZYkoxf1nswJnLPuCaRncM1B1"<br>
	}
</code>
</pre>
<br>
<b>GET ?action=check_payment&address=:address</b><br>
Check payment by a unique address received in the "request_payment" call.<br>
<pre>
<code>
Example response<br>
<br>
	{<br>
		"aro_expected" : 44,<br>
		"aro_actual" : 44<br>
	}<br>	
</code>
</pre>
<br>

<b>GET /?action=getbalance</b><br>
Check the total balance.
<pre>
<code>
Example response<br>
<br>
<pre>
<code>
	{<br>
		"balance":"1.42886346"<br>
	}<br>
</code>
</pre>
<br>