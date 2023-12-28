#!/bin/bash

tail -f -n0 /home/logs/world2.formatlog | grep --line-buffered 'type=258:attacker\|type=2:attacker' | while read LINE0
do	
	php fRank.php enviarMensagem "${LINE0}"

done

