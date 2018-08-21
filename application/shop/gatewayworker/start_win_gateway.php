<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

// 自动加载类
require_once __DIR__ . '/../../../vendor/autoload.php';
use \GatewayClient\Gateway;
use \Workerman\Worker;

include "gateway_config.php";

// gateway 进程
$gateway = new Gateway("Websocket://0.0.0.0:7272");
// 设置名称，方便status时查看
$gateway->name = 'z9168';
// 设置进程数，gateway进程数建议与cpu核数相同
$gateway->count = 4;
// 分布式部署时请设置成内网ip（非127.0.0.1）192.168.31.138
$gateway->lanIp = $register_ip;
// 内部通讯起始端口，假如$gateway->count=4，起始端口为4000
// 则一般会使用4000 4001 4002 4003 4个端口作为内部通讯端口
$gateway->startPort = 2900;
// 心跳间隔
$gateway->pingInterval = 60;
// 心跳数据
$gateway->pingData = '{"type":"ping"}';
// 服务注册地址
$gateway->registerAddress =  "$register_ip:" . $register_port;
//代表服务端允许客户端不响应心跳
//$gateway->pingNotResponseLimit = 0;

// 当客户端连接上来时，设置连接的onWebSocketConnect，即在websocket握手时的回调
$gateway->onConnect = function ($connection) {
    $connection->onWebSocketConnect = function ($connection, $http_header) {
        // 可以在这里判断连接来源是否合法，不合法就关掉连接
        // $_SERVER['HTTP_ORIGIN']标识来自哪个站点的页面发起的websocket链接
        if($_SERVER['HTTP_ORIGIN'] != 'http://chat.workerman.net')
         {
			$connection->close();
        }

        echo "链接成功";
    };
};

// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
