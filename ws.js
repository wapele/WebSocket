    var ws
    ,heartCheck = {
        timeout: 60000,//60秒
        timeoutObj: null,
        serverTimeoutObj: null,
        reset: function(){
            clearTimeout(this.timeoutObj);
            clearTimeout(this.serverTimeoutObj);
            return this;
        },
        start: function(){
            var self = this;
            this.timeoutObj = setTimeout(function(){
                //这里发送一个心跳，后端收到后，返回一个心跳消息，
                //onmessage拿到返回的心跳就说明连接正常
                ws.send("HeartBeat");
                self.serverTimeoutObj = setTimeout(function(){//如果超过一定时间还没重置，说明后端主动断开了
                    ws.close();//如果onclose会执行reconnect，我们执行ws.close()就行了.如果直接执行reconnect 会触发onclose导致重连两次
                }, self.timeout)
            }, this.timeout)
        }
    }
    ,socket={
        lockReconnect:false
        ,status:{
            0:"连接尚未建立"
            ,1:"服务器已经连接"
            ,2:"连接正在关闭"
            ,3:"未与服务器进行连接"
        },        
        initEventHandle:function(url){
            var self = this;
            ws.onclose = function () {
                $("#status").text("关闭连接！");
                self.reconnect(url);
            };
            ws.onerror = function () {                
                self.reconnect(url);
            };
            ws.onopen = function () {
                $("#status").text(socket.status[ws.readyState]);
                //心跳检测重置
                heartCheck.reset().start();
            };
            ws.onmessage = function (event) {
                //如果获取到消息，心跳检测重置
                console.log(event);
                $("#status").text(event.data);
                //拿到任何消息都说明当前连接是正常的
                heartCheck.reset().start();
            }
        },
        reconnect:function(url){
            if(this.lockReconnect) return;
            this.lockReconnect = true;
            let self = this;
            //没连接上会一直重连，设置延迟避免请求过多
            setTimeout(function () {
                self.createWebSocket(url);
                self.lockReconnect = false;
            }, 2000);
        },
        createWebSocket:function(url){
            try {
                ws = new WebSocket(url);
                this.initEventHandle(url);
                
            } catch (e) {
                this.reconnect(this.url);
            }  
            
        },

    }
    
    
