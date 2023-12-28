<?php
$config_itens["gm_receber"] = true;
$config_itens["level_minimo"] = 20;
$config_itens["cultivo_minimo"] = 2;
$config_itens["canal"] = 9;

$itens = [];

//adicionando um item
$itens[] = array(
	'id' => 21652,
	'tipo' => 'item',
	'nome' => 'Cupom Perfeito',
	'pos' => 0,
	'count' => 1,
	'max_count' => 1000,
	'data' => '',
	'proctype' => 0,
	'expire_date' => 0,
	'guid1' => 0,
	'guid2' => 0,
	'mask' => 0
);


//adicionando gold
$itens[] = array(
	'tipo' => 'gold',
	'nome' => '100 Gold',
	'quantidade' => 100	
);

//adicionando moedas
$itens[] = array(
	'tipo' => 'moedas',
	'nome' => '10k moedas',
	'quantidade' => 10000	
);
