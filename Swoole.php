<?php
namespace app\api\controller;
use swoole_websocket_server;
class Swoole 
{
   protected $server;
   public function index(){
        cache("fd",[]);
        $this->server = new \swoole_websocket_server("0.0.0.0", 9501);
        $this->server->on('open', function (swoole_websocket_server $server, $request) {
                $mchid=$request->get['mchid'];
                
                $fd=cache("fd");
                $fd[$request->fd]=$mchid;
                cache("fd",$fd);
                //dump($request);
               echo "客户机:{$request->fd}已接入\n";
            });
        $this->server->on('message', function (swoole_websocket_server $server, $frame) {
                echo "收到{$frame->fd}内容:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
                $server->push($frame->fd, date("Y-m-d H:i:s"));//心跳机制
                /* 
                //群发比如聊天
                foreach ($this->server->connections as $fd) {
                    $this->server->push($fd, $frame->data);
                }
                $server->push($this->fd, "this is server"+$this->fd);
                */
                
            });
        $this->server->on('close', function ($ser, $fd) {
                $fds=cache("fd");
                dump($fds);dump($fd);
                //if (isset($fds[$fd])){unset($fds[$fd]);}
                cache("fd",$fds);
                echo "客户机 {$fd} 关闭\n";
        });
            
        $this->server->on('request', function ($request, $response) {
                if ($request->server['request_uri']=="/favicon.ico"){return ;}
                // 接收http请求从get获取message参数的值，给用户推送                
                $msg=@$request->get['msg'] ?? "hello";
                $ip=$request->server["remote_addr"];
                $fds=cache("fd");
                $mchid=@$request->get['mchid'];//私发
                if ($mchid){
                    $fds=array_flip($fds);
                    dump($fds);
                    if (isset($fds[$mchid])){
                        $this->server->push($fds[$mchid], json_encode(["data"=>$request->get['msg']],JSON_UNESCAPED_UNICODE));
                    }else{
                        dump($fds);
                    }
                }
                /*
                //群发
                foreach($fds as $fd=>$uid){
                    $this->server->push($fd, json_encode(["data"=>$request->get['msg']],JSON_UNESCAPED_UNICODE));
                }
                */
                //$response->status(200);
                $response->end("success");
                return  "success";
        });
            
        $this->server->start();
       
    }
}