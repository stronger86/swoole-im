<?php
/**
 * Created by PhpStorm.
 * User: pzz
 * Date: 2018/5/2
 * Time: 下午9:04
 */

class Server
{
    private $server;

    public function __construct()
    {
        $this->server = new swoole_websocket_server('0.0.0.0', 9099);

        $this->server->on('open', array($this, 'onOpen'));
        $this->server->on('message', array($this, 'onMessage'));
        $this->server->on('close', array($this, 'onClose'));

        $this->server->start();
    }

    public function onOpen(swoole_websocket_server $server, $request)
    {
        foreach ($server->connections as $fd) {
            $data['type'] = 'connection';
            $data['message'] = "欢迎游客{$request->fd}加入群聊!";
            $data['sender'] = ($request->fd == $fd) ? true : false;
            $server->push($fd, json_encode($data));
        }
    }

    public function onMessage(swoole_websocket_server $server, $frame)
    {
        $message = $frame->data;
        foreach ($server->connections as $fd) {
            $data['type'] = 'talk';
            $data['message'] = $message;
            $data['sender'] = ($frame->fd == $fd) ? true : false;
            $server->push($fd, json_encode($data));
        }
    }

    public function onClose($server, $fd)
    {
        $message = "游客{$fd}退出群聊！";
        foreach ($server->connections as $tfd) {
            $info = $server->connection_info($tfd);
            if ($info['websocket_status'] == 3 && $fd != $tfd) {
                $data['type'] = 'close';
                $data['message'] = $message;
                $data['sender'] = false;
                $server->push($tfd, json_encode($data));
            }
        }
    }
}

$server = new Server();