<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>微聊</title>
    <link href="https://cdn.bootcss.com/layer/2.3/skin/layer.css" rel="stylesheet">
    <link rel="stylesheet" href="./static/css/main.css">
    <script src="https://cdn.bootcss.com/vue/2.6.10/vue.js"></script>
    <script src="https://cdn.bootcss.com/jquery/3.4.1/jquery.js"></script>
    <script src="https://cdn.bootcss.com/layer/2.3/layer.js"></script>
<body>
<div id="app" class="container">
    <div class="row">
        <div class="col-3">
            <div class="online_window">
                <div class="me_info">
                    <div class="me_item">
                        <div class="me_avatar">
                            <img src="static/images/1.jpg" alt="" />
                        </div>
                        <div class="me_status">
                            <div class="me_username">
                                <span>✏️</span> 哈哈
                            </div>
                            <div class="me_income">
                                欢迎来到聊天室
                            </div>
                        </div>
                    </div>
                </div>
                <div class="online_list">
                    <div class="online_list_header">
                        车上乘客
                    </div>
                    <div class="online_item">
                        <div class="online_avatar">
                            <img src="static/images/1.jpg" alt="" />
                        </div>
                        <div class="online_status">
                            <div class="online_username">
                                在线用户1
                            </div>
                        </div>
                    </div>
                    <div class="online_item">
                        <div class="online_avatar">
                            <img src="static/images/1.jpg" alt="" />
                        </div>
                        <div class="online_status">
                            <div class="online_username">
                                在线用户2
                            </div>
                        </div>
                    </div>
                    <div class="online_item">
                        <div class="online_avatar">
                            <img src="static/images/1.jpg" alt="" />
                        </div>
                        <div class="online_status">
                            <div class="online_username">
                                在线用户3
                            </div>
                        </div>
                    </div>
                </div>
                <div class="online_count">
                    车上乘客 <span>6</span> 位
                </div>
            </div>
        </div>
        <div class="clo-9">
            <div class="talk_window">
                <div class="windows_top">
                    <div class="windows_top_left"><span class="online-list">欢迎乘坐特快列车</span> </div>
                    <div class="windows_top_right">
                        <a href="#" target="_blank"
                           style="color: #999">查看源码</a>
                    </div>
                    <div class="windows_body" id="chat-window">

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var app = new Vue({
        el: "#app",
        data: {
            WebsocketServer: '{{ $server }}',
            WebsocketInstance: undefined,
            Reconnect: false,
            ReconnectTimer: null,
            HeartBeatTimer: null,
            ReconnectBox: null,
            currentUser: {
                username: '未知',
                intro: '未知',
                fd: 0,
                avatar: 0,
            },
            roomUser: {},
            roomChat: [],
            up_recv_time: 0,
        },
        created: function () {
            this.connect();
        },
        mounted: function () {

        },
        methods: {
            connect () {
                const username = localStorage.getItem('username');
                if (username) {
                    this.WebsocketServer += '?username=' + encodeURIComponent(username)
                }
                this.WebsocketInstance = new WebSocket(this.WebsocketServer);
                this.WebsocketInstance.onopen = (event) => {
                    // 断线重连
                    if (this.ReconnectBox) {
                        layer.close(this.ReconnectBox);
                        this.ReconnectBox = null;
                        clearInterval(this.ReconnectTimer);
                    }
                    // 前端循环心跳
                    this.HeartBeatTimer = setInterval(() => {
                        this.WebsocketInstance.send('PING');
                    }, 1000 * 60);
                    // 请求获取自己的用户信息和在线列表
                    this.release('index', 'info');
                    this.release('index', 'online');
                };

                this.WebsocketInstance.onmessage = (event) => {
                    console.log(event);
                }
            },

            release (controller, action, params) {
                controller = controller || 'index';
                action = action || 'index';
                params = params || {};
                const message = {
                    controller: controller,
                    action: action,
                    params: params,
                };
                this.WebsocketInstance.send(JSON.stringify(message))
            }
        }
    });
</script>
</body>
</html>