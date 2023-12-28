#!/bin/bash
SERVICE="fRank.sh"
if pgrep -x "$SERVICE" >/dev/null
then
    echo "O ranking ja esta sendo executado."
  exit 1
else
  nohup ./fRank.sh > /dev/null 2>&1 &
  echo "Ranking Iniciado"
fi
