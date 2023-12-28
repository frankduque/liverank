<?php
# Configurações do servidor
$config["ip"] = "localhost";
$config["ports"] = array(
  "provider" => 29300,
  "gamedbd" => 29400,
  "gdeliveryd" => 29100,
);

# Configurações do Mysql

$config['mysql'] = array(
  "host" => "localhost",
  "usuario" => "root",
  "senha" => "",
  "db" => "pw"
);

# Canal de envio das mensagens

$config['canal'] = 11;


/* Lista de Canais

 0 - Geral
 1 - Global
 2 - Grupo
 3 - Cl�
 4 - ?
 5 - Aviso
 6 - Aviso laranja
 7 - Com�rcio
 8 - ?
 9 - broadcast
 10 - Aviso azul
 11 - Rosa
 12 - Mensageiro
 13 - Branco
 14 - Aviso branco dnv
 15 - InterServidor

*/