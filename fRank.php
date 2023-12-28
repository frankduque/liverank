<?php

require('config.php');
require('mensagens.php');
require('items.php');


if (!isset($argv[2])) {
    $argv[1]();
} else {
    $argv[1]($argv[2]);
}

function enviarMensagem($linha = null)
{
    global $config;
    global $mensagens;
    if (@fsockopen('127.0.0.1', $config["ports"]['gamedbd'], $errCode, $errStr, 1)) {
        if (strpos($linha, "type=2") !== false or strpos($linha, "type=258") !== false) {
            $attacker = explode("=", explode(":", $linha)[8])[1];
            $attackerRole = getRoleBase($attacker);
            $attacked = explode("=", explode(":", $linha)[6])[1];
            $attackedRole = getRoleBase($attacked);
            $mysqli = new mysqli($config['mysql']['host'], $config['mysql']['usuario'], $config['mysql']['senha'], $config['mysql']['db']);
            if (!$mysqli->connect_errno) {
                $data = date("Y-m-d H:i:s");
                $sqlInsert = "INSERT INTO `fRank`(`data`, `Kid`, `Did`) VALUES ('$data','$attacker','$attacked')";
                $query = $mysqli->query($sqlInsert);
                $nKillsSelect = "SELECT count(*) as nkills FROM `fRank` WHERE `Kid` = '$attacker'";
                $result = $mysqli->query($nKillsSelect);
                if ($result->num_rows === 0) {
                    $nkills = 0;
                } else {
                    $res = $result->fetch_assoc();
                    $nkills = $res['nkills'];
                }
                $nDeathsSelect = "SELECT count(*) as ndeaths FROM `fRank` WHERE `Did` = '$attacker'";
                $result = $mysqli->query($nDeathsSelect);
                if ($result->num_rows === 0) {
                    $ndeaths = 0;
                } else {
                    $res = $result->fetch_assoc();
                    $ndeaths = $res['ndeaths'];
                }
            }
            $key = mt_rand(0, count($mensagens) - 1);
            $mensagens[$key] = str_replace('{{morreu}}', $attackedRole['name'], $mensagens[$key]);
            $mensagens[$key] = str_replace('{{atacou}}', $attackerRole['name'], $mensagens[$key]);
            $mensagens[$key] .= ". Kill: $nkills; Death: $ndeaths";
            chatInGame($mensagens[$key]);
        } else {
            exit;
        }
    } else {
        echo "servidor offline";
    }
}

function enviarItemAleatorio()
{
    global $config;
    global $config_itens;
    global $itens;
    //verifica se o servidor esta online
    if (!@fsockopen('127.0.0.1', $config["ports"]['gamedbd'], $errCode, $errStr, 1)) {
        echo "servidor offline" . PHP_EOL;
        exit;
    }

    //verifica se existem usuários online
    $online = getOnlineList();
    if (count($online) == 0) {
        echo "nenhum usuário online" . PHP_EOL;
        exit;
    }

    //verifica se algum item foi adicionado
    if (count($itens) == 0) {
        echo "nenhum item adicionado" . PHP_EOL;
        exit;
    }

    $usuario_valido = false;
    do {
        //verifica se ainda existem usuários online
        if (count($online) == 0) {
            echo "nenhum usuário restante" . PHP_EOL;
            exit;
        }


        //seleciona um usuário aleatório
        $key = mt_rand(0, count($online) - 1);
        $user = $online[$key];

        //busca os dados do personagem
        $role = getRoleStatus($user['roleid']);

        //verifica se o personagem possui o level minimo
        if ($role['level'] < $config_itens['level_minimo']) {
            echo "level minimo não atingido" . PHP_EOL;
            unset($online[$key]);
            continue;
        }

        //verifica se o personagem possui o cultivo minimo
        if ($role['level2'] < $config_itens['cultivo_minimo']) {
            echo "cultivo minimo não atingido" . PHP_EOL;
            unset($online[$key]);
            continue;
        }

        //verifica se o usuário é um gm
        if (verifica_gm($user['userid'])) {
            echo "usuário é um gm" . PHP_EOL;
            unset($online[$key]);
            continue;
        }

        $usuario_valido = true;

    } while (!$usuario_valido);

    //seleciona um item aleatório
    $key = mt_rand(0, count($itens) - 1);
    $item = $itens[$key];

    //verifica se o item é gold
    if ($item['tipo'] == 'gold') {
        //é gold

        //envia a mensagem no 
        chatItem("^ffffff O jogador &{$user['name']}& ^ffffff acabou  ganhar ^33cc33 {$item['quantidade']} gold");

        //adiciona o gold no usuário
        sendGold($user['userid'], $item['quantidade'] * 100);

    }

    //verifica se o item é moedas
    if ($item['tipo'] == 'moedas') {
        //envia a mensagem no chat
        chatItem("^ffffff O jogador &{$user['name']}& ^ffffff acabou  ganhar ^33cc33 {$item['quantidade']} gold");
        ("^ffffff O jogador &{$user['name']}& ^ffffff acabou de ganhar ^33cc33 {$item['nome']}");

        //envia o item para o usuário
        sendMail($user['roleid'], "Parabéns", "Você acabou de ganhar um item", $item['quantidade'], []);
    }

    //verifica se o item é um item
    if ($item['tipo'] == 'item') {

        //envia a mensagem no chat
        chatItem("^ffffff O jogador &{$user['name']}& ^ffffff acabou  ganhar ^33cc33 {$item['quantidade']} gold");
        ("^ffffff O jogador &{$user['name']}& ^ffffff acabou de ganhar ^33cc33 {$item['count']} {$item['nome']}");

        //envia o item para o usuário
        sendMail($user['roleid'], "Parabéns", "Você acabou de ganhar um item", 0, $item);
    }


}
function chatInGame($texto)
{
    global $config;
    $pack = pack("CCN", $config['canal'], 0, 0) . packString($texto) . packOctet('');
    SendToProvider(createHeader(120, $pack));
    return true;
}
function chatItem($texto)
{
    global $config_itens;
    $pack = pack("CCN", $config_itens['canal'], 0, 0) . packString($texto) . packOctet('');
    SendToProvider(createHeader(120, $pack));
    return true;
}

function sendMail($receiver, $title, $context, $money, $item = array())
{
    if ($item === array()) {
        $item = array(
            'id' => 0,
            'pos' => 0,
            'count' => 0,
            'max_count' => 0,
            'data' => '',
            'proctype' => 0,
            'expire_date' => 0,
            'guid1' => 0,
            'guid2' => 0,
            'mask' => 0
        );
    }

    $pack = pack("NNCN", 344, 1025, 3, $receiver) . packString($title) . packString($context);
    $pack .= marshal($item, array(
        'id' => 'int',
        'pos' => 'int',
        'count' => 'int',
        'max_count' => 'int',
        'data' => 'octets',
        'proctype' => 'int',
        'expire_date' => 'int',
        'guid1' => 'int',
        'guid2' => 'int',
        'mask' => 'int'
    ));
    $pack .= pack("N", $money);

    return SendToDelivery(createHeader(4214, $pack));
}

function verifica_gm($user_id)
{
    global $config;
    //conecta no banco de dados
    $mysqli = new mysqli($config['mysql']['host'], $config['mysql']['usuario'], $config['mysql']['senha'], $config['mysql']['db']);

    //verifica se a conexão foi bem sucedida
    if ($mysqli->connect_errno) {
        echo "Falha ao conectar ao MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
        exit;
    }

    //verifica se o usuário é um gm
    $sql = "SELECT unique(userid) as userid FROM `auth`";

    //executa a query
    $result = $mysqli->query($sql);

    //verifica se o usuário é um gm
    if ($result->num_rows === 0) {
        return false;
    } else {
        while ($row = $result->fetch_assoc()) {
            if ($row['userid'] == $user_id) {
                return true;
            }
        }
    }
    return false;


}

function packInt($data)
{
    return pack("N", $data);
}

function packByte($data)
{
    return pack("C", $data);
}

function packFloat($data)
{
    return strrev(pack("f", $data));
}

function packShort($data)
{
    return pack("n", $data);
}

function marshal($pack, $struct)
{
    $cycle = false;
    $data = '';
    foreach ($struct as $key => $val) {
        if (substr($key, 0, 1) == "@")
            continue;
        if (is_array($val)) {
            if ($cycle) {
                if ($cycle > 0) {
                    $count = $cycle;
                    for ($i = 0; $i < $count; $i++) {
                        $data .= marshal($pack[$key][$i], $val);
                    }
                }
                $cycle = false;
            } else {
                $data .= marshal($pack[$key], $val);
            }
        } else {
            switch ($val) {
                case 'int':
                    $data .= packInt((int) $pack[$key]);
                    break;
                case 'byte':
                    $data .= packByte($pack[$key]);
                    break;
                case 'cuint':
                    $arrkey = substr($key, 0, -5);
                    $cui = isset($pack[$arrkey]) ? count($pack[$arrkey]) : 0;
                    $cycle = ($cui > 0) ? $cui : -1;
                    $data .= cuint($cui);
                    break;
                case 'octets':
                    if ($pack[$key] === array())
                        $pack[$key] = '';
                    $data .= packOctet($pack[$key]);
                    break;
                case 'name':
                    if ($pack[$key] === array())
                        $pack[$key] = '';
                    $data .= packString($pack[$key]);
                    break;
                case 'short':
                    $data .= packShort($pack[$key]);
                    break;
                case 'float':
                    $data .= packFloat($pack[$key]);
                    break;
                case 'cat1':
                case 'cat2':
                case 'cat4':
                    $data .= $pack[$key];
                    break;
            }
        }
    }
    return $data;
}
function sendGold($id, $quantidade)
{
    $pack = pack('N*', $id, $quantidade);
    $data = SendToGamedBD(createHeader(521, $pack));

}
function packString($data)
{
    $data = iconv("UTF-8", "UTF-16LE//TRANSLIT//IGNORE", $data);
    return cuint(strlen($data)) . $data;
}

function cuint($data)
{
    if ($data < 64)
        return strrev(pack("C", $data));
    else if ($data < 16384)
        return strrev(pack("S", ($data | 0x8000)));
    else if ($data < 536870912)
        return strrev(pack("I", ($data | 0xC0000000)));
    return strrev(pack("c", -32) . pack("i", $data));
}

function packOctet($data)
{
    $data = pack("H*", (string) $data);
    return cuint(strlen($data)) . $data;
}

function SendToProvider($data)
{
    global $config;
    return SendToSocket($data, $config['ports']['provider']);
}

function SendToDelivery($data)
{
    global $config;
    return SendToSocket($data, $config['ports']['gdeliveryd'], true);
}

function SendToSocket($data, $port, $RecvAfterSend = false, $buf = null)
{
    global $config;
    $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_connect($sock, $config['ip'], $port);
    if ($RecvAfterSend)
        socket_recv($sock, $tmp, 8192, 0);
    socket_send($sock, $data, strlen($data), 0);
    switch (3) {
        case 1:
            socket_recv($sock, $buf, 65536, 0);
            break;
        case 2:
            $buffer = socket_read($sock, 1024, PHP_BINARY_READ);
            while (strlen($buffer) == 1024) {
                $buf .= $buffer;
                $buffer = socket_read($sock, 1024, PHP_BINARY_READ);
            }
            $buf .= $buffer;
            break;
        case 3:
            $tmp = 0;
            $buf .= socket_read($sock, 1024, PHP_BINARY_READ);
            if (strlen($buf) >= 8) {
                unpackCuint($buf, $tmp);
                $length = unpackCuint($buf, $tmp);
                while (strlen($buf) < $length) {
                    $buf .= socket_read($sock, 1024, PHP_BINARY_READ);
                }
            }
            break;
    }
    socket_close($sock);
    return $buf;
}

function unpackCuint($data, &$p)
{
    $hex = hexdec(bin2hex(substr($data, $p, 1)));
    $min = 0;
    if ($hex < 0x80) {
        $size = 1;
    } else if ($hex < 0xC0) {
        $size = 2;
        $min = 0x8000;
    } else if ($hex < 0xE0) {
        $size = 4;
        $min = 0xC0000000;
    } else {
        $p++;
        $size = 4;
    }
    $data = (hexdec(bin2hex(substr($data, $p, $size))));
    $unpackCuint = $data - $min;
    $p += $size;
    return $unpackCuint;
}

function createHeader($opcode, $data)
{
    return cuint($opcode) . cuint(strlen($data)) . $data;
}

function SendToGamedBD($data)
{
    global $config;
    return SendToSocket($data, $config['ports']['gamedbd']);
}

function deleteHeader($data)
{
    $length = 0;
    unpackCuint($data, $length);
    unpackCuint($data, $length);
    $length += 8;
    $data = substr($data, $length);
    return $data;
}

function unpackLong($data)
{
    $set = unpack('N2', $data);
    return $set[1] << 32 | $set[2];
}

function unpackOctet($data, &$tmp)
{
    $p = 0;
    $size = unpackCuint($data, $p);
    $octet = bin2hex(substr($data, $p, $size));
    $tmp = $tmp + $p + $size;
    return $octet;
}

function unpackString($data, &$tmp)
{
    $size = (hexdec(bin2hex(substr($data, $tmp, 1))) >= 128) ? 2 : 1;
    $octetlen = (hexdec(bin2hex(substr($data, $tmp, $size))) >= 128) ? hexdec(bin2hex(substr($data, $tmp, $size))) - 32768 : hexdec(bin2hex(substr($data, $tmp, $size)));
    $pp = $tmp;
    $tmp += $size + $octetlen;
    return mb_convert_encoding(substr($data, $pp + $size, $octetlen), "UTF-8", "UTF-16LE");
}

function unmarshal(&$rb, $struct)
{
    $cycle = false;
    $data = array();
    foreach ($struct as $key => $val) {
        if (is_array($val)) {
            if ($cycle) {
                if ($cycle > 0) {
                    for ($i = 0; $i < $cycle; $i++) {
                        $data[$key][$i] = unmarshal($rb, $val);
                        if (!$data[$key][$i])
                            return false;
                    }
                }
                $cycle = false;
            } else {
                $data[$key] = unmarshal($rb, $val);
                if (!$data[$key])
                    return false;
            }
        } else {
            $tmp = 0;
            switch ($val) {
                case 'int':
                    $un = unpack("N", substr($rb, 0, 4));
                    $rb = substr($rb, 4);
                    $data[$key] = $un[1];
                    break;
                case 'int64':
                    $un = unpack("N", substr($rb, 0, 8));
                    $rb = substr($rb, 8);
                    $data[$key] = $un[1];
                    break;
                case 'long':
                    $data[$key] = unpackLong(substr($rb, 0, 8));
                    $rb = substr($rb, 8);
                    break;
                case 'lint':
                    $un = unpack("V", substr($rb, 0, 4));
                    $rb = substr($rb, 4);
                    $data[$key] = $un[1];
                    break;
                case 'byte':
                    $un = unpack("C", substr($rb, 0, 1));
                    $rb = substr($rb, 1);
                    $data[$key] = $un[1];
                    break;
                case 'cuint':
                    $cui = unpackCuint($rb, $tmp);
                    $rb = substr($rb, $tmp);
                    if ($cui > 0)
                        $cycle = $cui;
                    else
                        $cycle = -1;
                    break;
                case 'octets':
                    $data[$key] = unpackOctet($rb, $tmp);
                    $rb = substr($rb, $tmp);
                    break;
                case 'name':
                    $data[$key] = unpackString($rb, $tmp);
                    $rb = substr($rb, $tmp);
                    break;
                case 'short':
                    $un = unpack("n", substr($rb, 0, 2));
                    $rb = substr($rb, 2);
                    $data[$key] = $un[1];
                    break;
                case 'lshort':
                    $un = unpack("v", substr($rb, 0, 2));
                    $rb = substr($rb, 2);
                    $data[$key] = $un[1];
                    break;
                case 'float2':
                    $un = unpack("f", substr($rb, 0, 4));
                    $rb = substr($rb, 4);
                    $data[$key] = $un[1];
                    break;
                case 'float':
                    $un = unpack("f", strrev(substr($rb, 0, 4)));
                    $rb = substr($rb, 4);
                    $data[$key] = $un[1];
                    break;
            }
            if ($val != 'cuint' and is_null($data[$key]))
                return false;
        }
    }
    return $data;
}
function getRoleStatus($role)
{
    $pack = pack("N*", -1, $role);
    $pack = createHeader(3015, $pack);
    $send = SendToGamedBD($pack);
    $data = deleteHeader($send);
    $user = unmarshal($data, array(
        'sversion' => 'byte',
        'level' => 'int',
        'level2' => 'int',
        'exp' => 'int',
        'sp' => 'int',
        'pp' => 'int',
        'hp' => 'int',
        'mp' => 'int',
        'posx' => 'float',
        'posy' => 'float',
        'posz' => 'float',
        'worldtag' => 'int',
        'invader_state' => 'int',
        'invader_time' => 'int',
        'pariah_time' => 'int',
        'reputation' => 'int',
        'custom_status' => 'octets',
        'filter_data' => 'octets',
        'charactermode' => 'octets',
        'instancekeylist' => 'octets',
        'dbltime_expire' => 'int',
        'dbltime_mode' => 'int',
        'dbltime_begin' => 'int',
        'dbltime_used' => 'int',
        'dbltime_max' => 'int',
        'time_used' => 'int',
        'dbltime_data' => 'octets',
        'storesize' => 'short',
        'petcorral' => 'octets',
        'property' => 'octets',
        'var_data' => 'octets',
        'skills' => 'octets',
        'storehousepasswd' => 'octets',
        'waypointlist' => 'octets',
        'coolingtime' => 'octets',
        'reserved1' => 'int',
        'reserved2' => 'int',
        'reserved3' => 'int',
        'reserved4' => 'int',
    ));

    return $user;
}

function getRoleBase($role)
{
    $pack = pack("N*", -1, $role);
    $pack = createHeader(3013, $pack);
    $send = SendToGamedBD($pack);
    $data = deleteHeader($send);
    $user = unmarshal($data, array(
        'version' => 'byte',
        'id' => 'int',
        'name' => 'name',
        'race' => 'int',
        'cls' => 'int',
        'gender' => 'byte',
        'custom_data' => 'octets',
        'config_data' => 'octets',
        'custom_stamp' => 'int',
        'status' => 'byte',
        'delete_time' => 'int',
        'create_time' => 'int',
        'lastlogin_time' => 'int',
        'forbidcount' => 'cuint',
        'forbid' => array(
            'type' => 'byte',
            'time' => 'int',
            'createtime' => 'int',
            'reason' => 'name',
        ),
        'help_states' => 'octets',
        'spouse' => 'int',
        'userid' => 'int',
        'cross_data' => 'octets',
        'reserved2' => 'byte',
        'reserved3' => 'byte',
        'reserved4' => 'byte',
    )
    );

    return $user;
}

function getOnlineList()
{
    $online = [];
    $id = 0;
    $pack = pack('N*', -1, 1, $id) . packString('1');
    $pack = createHeader(352, $pack);
    $send = SendToDelivery($pack);
    $data = deleteHeader($send);
    $data = unmarshal($data,
        array(
            'localsid' => 'int',
            'handler' => 'int',
            'userscount' => 'cuint',
            'users' => array(
                'userid' => 'int',
                'roleid' => 'int',
                'linkid' => 'int',
                'localsid' => 'int',
                'gsid' => 'int',
                'status' => 'byte',
                'name' => 'name',
            )
        )
    );
    if (isset($data['users'])) {
        foreach ($data['users'] as $user) {
            $online[] = $user;
        }
    }
    return $online;
}

?>