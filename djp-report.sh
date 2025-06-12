#!/bin/bash

http_response=$(curl -m 5 -l -k -X POST -o /dev/null -s -w "%{http_code}\n" https://10.245.192.37/v1/token)
now=$(date)

#check if api return bad request response
if [ $http_response == "400" ]; then
    echo "Connected to djp server $now"
    cd /var/www/html/bmall-v2 && php bin/console app:send-report-djp
else
    echo "Connection to djp-server failed $now"
fi

