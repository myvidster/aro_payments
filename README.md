Installation Instructions

Install php-gd

Clone aro_payments to your apache/nginx web directory.
Copy config.php.example to config.php and edit it.
Grant ownership to the apache/nginx web user account to the jsonRPC and qr folder.
Import aro_payments.sql in the contrib folder to create the aro_payments table.
Create a virtualHost in the apache/nginx config, select a port to run the payment proccess on (ie 2222)
Restart apache/nginx
On the same machine go to run curl -X GET http://127.0.0.1:2222/ and you will be greeting with 


