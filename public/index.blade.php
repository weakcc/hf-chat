<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>微聊</title>
    <link href="https://cdn.bootcss.com/layer/2.3/skin/layer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.staticfile.org/amazeui/2.7.2/css/amazeui.min.css">
    <link rel="stylesheet" href="./static/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
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
                            <img :src="currentUser.avatar" alt="" />
                        </div>
                        <div class="me_status">
                            <div class="me_username">
                                <span>✏️</span> @{{ currentUser.username }}
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
                    <template v-for="user in roomUser">
                        <div class="online_item">
                            <div class="online_avatar">
                                <img :src=user.avatar :alt="user.username" />
                            </div>
                            <div class="online_status">
                                <div class="online_username">
                                    @{{user.username}}
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="online_count">
                    车上乘客 <span>@{{ currentCount }}</span> 位
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
                </div>
                <div class="windows_body" id="chat-window" v-scroll-bottom>
                    <ul class="am-comments-list am-comments-list-flip">
                        <template v-for="chat in roomChat">
                            <template v-if="chat.type === 'tips'">
                                <div class="chat-tips">
                                    <span class="am-badge am-badge-primary am-radius">@{{  chat.content }}</span>
                                </div>
                            </template>
                            <template v-else>
                                <div v-if="chat.sendTime" class="chat-tips">
                                    <span class="am-radius" style="color: #666666">@{{ chat.sendTime }}</span>
                                </div>
                                <article class="am-comment" :class="{ 'am-comment-flip' : chat.fd == currentUser.userFd }">
                                    <a href="#link-to-user-home">
                                        <img :src="chat.avatar" alt="" class="am-comment-avatar"
                                             width="48" height="48"/>
                                    </a>
                                    <div class="am-comment-main">
                                        <header class="am-comment-hd">
                                            <div class="am-comment-meta">
                                                <a href="#link-to-user" class="am-comment-author">@{{  chat.username }}</a>
                                            </div>
                                        </header>
                                        <div class="am-comment-bd">
                                            <div class="bd-content">
                                                <template v-if="chat.type === 'text'">
                                                    @{{  chat.content }}
                                                </template>
                                                <template v-else-if="chat.type === 'image'">
                                                    <img :src="chat.content" width="100%">
                                                </template>
                                                <template v-else>
                                                    @{{  chat.content }}
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </template>
                        </template>
                    </ul>
                </div>
                <div class="windows_input">
                    <div class="am-btn-toolbar">
                        <div class="am-btn-group am-btn-group-xs">
                            <button type="button" class="am-btn"><i class="am-icon am-icon-picture-o"></i>
                            </button>
                            <input type="file" id="fileInput" style="display: none" accept="image/*">
                        </div>
                    </div>
                    <div class="input-box">
                        <label for="text-input" style="display: none"></label>
                        <textarea id="text-input" cols="30" rows="10" title="请输入内容" v-model="desc"></textarea>
                    </div>
                    <div class="toolbar">
                        <div class="left"></a>
                        </div>
                        <div class="right">
                            <button class="send" @click="clickBtnSend">发送消息 ( Enter )</button>
                        </div>
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
            desc: '',
        },
        created: function () {
            this.connect();
        },
        mounted: function () {

        },
        computed: {
            currentCount() {
                return Object.getOwnPropertyNames(this.roomUser).length - 1;
            }
        },
        directives: {
            scrollBottom: {
                componentUpdated: function (el) {
                    el.scrollTop = el.scrollHeight
                }
            }
        },
        methods: {
            connect() {
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
                    try {
                        if (event.data == 'PONG') {
                            return;
                        }
                        const data = JSON.parse(event.data);
                        if (data.sendTime) {
                            if (this.up_recv_time + 10 * 1000 > (new Date(data.sendTime)).getTime()) {
                                this.up_recv_time = (new Date(data.sendTime)).getTime();
                                data.sendTime = null;
                            } else {
                                this.up_recv_time = (new Date(data.sendTime)).getTime();
                            }
                        }
                        switch (data.action) {
                            case 101: {
                                // 收到管理员消息
                                this.roomChat.push({
                                    type: data.type ? data.type : 'text',
                                    fd: 0,
                                    content: data.content,
                                    avatar: 'https://www.gravatar.com/avatar/3ee60266a353746d6aab772fb9e2d398?s=200&d=identicon',
                                    username: '列车乘务员',
                                });
                                break;
                            }
                            case 103: {
                                const fd = data.fromUserFd;
                                const broadcastMsg = {
                                    type: data.type,
                                    fd: fd,
                                    content: data.content,
                                    avatar: this.roomUser[fd].avatar,
                                    username: this.roomUser[fd].username,
                                    sendTime: data.sendTime,
                                };
                                this.roomChat.push(broadcastMsg);
                                break;
                            }
                            case 201: {
                                // 刷新自己的信息
                                console.log(data);
                                this.currentUser.intro = data.content.intro;
                                this.currentUser.avatar = data.content.avatar;
                                this.currentUser.fd = data.content.fd;
                                this.currentUser.username = data.content.username;
                                break;
                            }
                            case 202: {
                                // 当前用户列表
                                this.roomUser = data.list;
                                break;
                            }
                            case 203: {
                                // 新用户上线
                                this.$set(this.roomUser, data.fd, data.content);
                                this.roomChat.push({
                                    type: 'tips',
                                    content: `乘客 ${data.content.username} 已登车`,
                                });
                                break;
                            }
                            case 204: {
                                // 用户离线
                                const name = this.roomUser[data.fd].username;
                                this.$delete(this.roomUser, data.fd);
                                this.roomChat.push({
                                    type: 'tips',
                                    content: `乘客 ${name} 下车了`,
                                });
                                break;
                            }
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }
            },
            release(controller, action, params) {
                controller = controller || 'index';
                action = action || 'index';
                params = params || {};
                const message = {
                    controller: controller,
                    action: action,
                    params: params,
                };
                this.WebsocketInstance.send(JSON.stringify(message))
            },
            broadcastTextMessage(content) {
                this.release('index', 'message', {content: content, type: 'text'});
            },
            doReconnect() {
                clearInterval(this.HeartBeatTimer);
                this.ReconnectBox = layer.msg('已断开，正在重连...', {
                    scrollbar : false,
                    shade     : 0.3,
                    shadeClose: false,
                    time      : 0,
                    offset    : 't'
                });
                this.ReconnectTimer = setInterval(function () {
                    this.connect();
                }, 1000)
            },
            clickBtnSend() {
                const desc = this.desc;
                if (desc.trim() === '') {
                    layer.tips('请输入消息内容', '.windows_input', {
                        tips: [1, '#3595CC'],
                        time: 2000
                    });
                } else {
                    if (this.WebsocketInstance && this.WebsocketInstance.readyState === 1) {
                        this.broadcastTextMessage(desc);
                        this.desc = '';
                    } else {
                        layer.tips('连接已断开', '.windows_input', {
                            tips: [1, '#ff4f4f'],
                            time: 2000
                        });
                    }
                }
            }
        }
    });
</script>
</body>
</html>