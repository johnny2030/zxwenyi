/// <reference path="../typings/tsd.d.ts"/>

var widget = angular.module("RongWebIMWidget", ["RongWebIMWidget.conversationServer", "RongWebIMWidget.directive",
    "RongWebIMWidget.conversationListServer", "RongIMSDKModule", "Evaluate", 'ng-iscroll'
]);

$(function() {
    //rem
    var winW = document.documentElement.clientWidth;
    var desW = 1242;
    var fontSize = 100;
    var rem = desW / fontSize;
    if (winW > desW) {
        winW = desW;
    }
    document.documentElement.style.fontSize = winW / rem + 'px';
})

widget.factory("providerdata", [function() {
    var obj = {
        _cacheUserInfo: <WidgetModule.UserInfo[]>[],
        getCacheUserInfo: function(id) {
            for (var i = 0, len = obj._cacheUserInfo.length; i < len; i++) {
                if (obj._cacheUserInfo[i].userId == id) {
                    return obj._cacheUserInfo[i];
                }
            }
            return null;
        },
        addUserInfo: function(user: WidgetModule.UserInfo) {
            var olduser = obj.getCacheUserInfo(user.userId);
            if (olduser) {
                angular.extend(olduser, user);
            } else {
                obj._cacheUserInfo.push(user);
            }
        }
    };
    return obj;
}]);

widget.factory("widgetConfig", [function() {
    return {}
}]);


widget.factory("WebIMWidget", ["$q", "conversationServer",
    "conversationListServer", "providerdata", "widgetConfig", "RongIMSDKServer",
    function($q: angular.IQService, conversationServer: ConversationServer,
        conversationListServer: conversationListServer, providerdata: providerdata,
        widgetConfig: widgetConfig, RongIMSDKServer: RongIMSDKServer) {

        var WebIMWidget = <WebIMWidget>{};

        var messageList = {};

        var defaultconfig = <Config>{
            displayMinButton: true,
            displayConversationList: false
        }

        WebIMWidget.display = false;

        WebIMWidget.init = function(config: Config) {

            if (!window.RongIMLib || !window.RongIMLib.RongIMClient) {
                widgetConfig.config = config;
                return;
            }

            angular.extend(defaultconfig, config);

            var eleconversation = document.getElementById("rong-conversation");
            var eleconversationlist = document.getElementById("rong-conversation-list");
            var eleminbtn = document.getElementById("rong-widget-minbtn");

            widgetConfig.displayConversationList = defaultconfig.displayConversationList;
            widgetConfig.displayMinButton = defaultconfig.displayMinButton;
            if (widgetConfig.displayConversationList) {
                eleconversationlist && (eleconversationlist.style["display"] = "inline-block");
            } else {
                eleconversationlist && (eleconversationlist.style["display"] = "none");
            }

            if (widgetConfig.displayMinButton) {
                eleminbtn && (eleminbtn.style.display = "none");
            } else {
                eleminbtn && (eleminbtn.style.display = "inline-block");
            }
            
            if (RongIMLib.RongIMEmoji) {
                RongIMLib.RongIMEmoji.init();
            }

            if (RongIMLib.RongIMVoice) {
                RongIMLib.RongIMVoice.init();
            }

            RongIMSDKServer.init(defaultconfig.appkey);

            RongIMSDKServer.connect(defaultconfig.token).then(function(userId) {
                console.log("connect success:" + userId);
                if (WidgetModule.Helper.checkType(defaultconfig.onSuccess) == "function") {
                    defaultconfig.onSuccess(userId);
                }
                if (WidgetModule.Helper.checkType(providerdata.getUserInfo) == "function") {
                    providerdata.getUserInfo(userId, {
                        onSuccess: function(data) {
                            conversationServer.loginUser.id = data.userId;
                            conversationServer.loginUser.name = data.name;
                            conversationServer.loginUser.portraitUri = data.portraitUri;
                        }
                    });
                }

                conversationListServer.updateConversations();
                if (conversationServer._onConnectSuccess) {
                    conversationServer._onConnectSuccess();
                }
            }, function(err) {
                if (err.tokenError) {
                    if (defaultconfig.onError && typeof defaultconfig.onError == "function") {
                        defaultconfig.onError({ code: 0, info: "token 无效" });
                    }
                } else {
                    if (defaultconfig.onError && typeof defaultconfig.onError == "function") {
                        defaultconfig.onError({ code: err.errorCode });
                    }
                }
            })

            RongIMSDKServer.setConnectionStatusListener({
                onChanged: function(status) {
                    WebIMWidget.connected = false;
                    switch (status) {
                        //链接成功
                        case RongIMLib.ConnectionStatus.CONNECTED:
                            console.log('链接成功');
                            WebIMWidget.connected = true;
                            break;
                        //正在链接
                        case RongIMLib.ConnectionStatus.CONNECTING:
                            console.log('正在链接');
                            break;
                        //其他设备登陆
                        case RongIMLib.ConnectionStatus.KICKED_OFFLINE_BY_OTHER_CLIENT:
                            console.log('其他设备登录');
                            break;
                        case RongIMLib.ConnectionStatus.NETWORK_UNAVAILABLE:
                            console.log("网络不可用");

                            break;
                    }
                    if (WebIMWidget.onConnectStatusChange) {
                        WebIMWidget.onConnectStatusChange(status);
                    }
                    if (conversationListServer._onConnectStatusChange) {
                        conversationListServer._onConnectStatusChange(status);
                    }
                }
            });

            RongIMSDKServer.setOnReceiveMessageListener({
                onReceived: function(data) {
                    console.log(data);
                    var msg = WidgetModule.Message.convert(data);

                    switch (data.messageType) {
                        case WidgetModule.MessageType.VoiceMessage:
                            msg.content.isUnReade = true;
                        case WidgetModule.MessageType.TextMessage:
                        case WidgetModule.MessageType.LocationMessage:
                        case WidgetModule.MessageType.ImageMessage:
                        case WidgetModule.MessageType.RichContentMessage:
                            addMessageAndOperation(msg);
                            break;
                        case WidgetModule.MessageType.ContactNotificationMessage:
                            //好友通知自行处理
                            break;
                        case WidgetModule.MessageType.InformationNotificationMessage:
                            addMessageAndOperation(msg);
                            break;
                        case WidgetModule.MessageType.UnknownMessage:
                            // 转成灰条消息
                            addMessageAndOperation(msg);
                            break;
                        default:
                            //未捕获的消息类型
                            break;
                    }
                    if (WidgetModule.Helper.checkType(providerdata.getUserInfo) == "function") {
                        providerdata.getUserInfo(msg.senderUserId, {
                            onSuccess: function(data) {
                                if (msg.content && data) {
                                    msg.content.userInfo = new WidgetModule.UserInfo(data.userId, data.name, data.portraitUri);
                                }
                            }
                        })
                    }
                    if (WebIMWidget.onReceivedMessage) {
                        WebIMWidget.onReceivedMessage(msg);
                    }
                    conversationServer.onReceivedMessage(msg);

                    if (WebIMWidget.display && conversationServer.current && conversationServer.current.targetType == msg.conversationType && conversationServer.current.targetId == msg.targetId) {
                        RongIMLib.RongIMClient.getInstance().clearUnreadCount(conversationServer.current.targetType, conversationServer.current.targetId, {
                            onSuccess: function() {

                            },
                            onError: function() {

                            }
                        })
                    }
                    conversationListServer.updateConversations().then(function() { });
                }
            });


        }

        function addMessageAndOperation(msg: WidgetModule.Message) {
            var hislist = conversationServer._cacheHistory[msg.conversationType + "_" + msg.targetId] = conversationServer._cacheHistory[msg.conversationType + "_" + msg.targetId] || []
            if (hislist.length == 0) {
                // hislist.push(new WidgetModule.GetHistoryPanel());
                hislist.push(new WidgetModule.TimePanl(msg.sentTime));
            }
            conversationServer._addHistoryMessages(msg);
        }

        WebIMWidget.setConversation = function(targetType: number, targetId: string, title: string) {
            conversationServer.onConversationChangged(new WidgetModule.Conversation(targetType, targetId, title));
        }

        WebIMWidget.setUserInfoProvider = function(fun) {
            providerdata.getUserInfo = fun;
        }

        WebIMWidget.setGroupInfoProvider = function(fun) {
            providerdata.getGroupInfo = fun;
        }

        WebIMWidget.EnumConversationListPosition = WidgetModule.EnumConversationListPosition;

        WebIMWidget.EnumConversationType = WidgetModule.EnumConversationType;

        WebIMWidget.show = function() {
            WebIMWidget.display = true;
            WebIMWidget.fullScreen = false;
        }
        WebIMWidget.hidden = function() {
            WebIMWidget.display = false;
        }

        WebIMWidget.getCurrentConversation = function() {
            return conversationServer.current;
        }

        return WebIMWidget;
    }]);

widget.directive("rongWidget", [function() {
    return {
        restrict: "E",
        templateUrl: "./ts/main.tpl.html",
        controller: "rongWidgetController"
    }
}]);

widget.controller("rongWidgetController", ["$scope", "WebIMWidget", "widgetConfig", "conversationListServer", "conversationServer",
    function($scope, WebIMWidget, widgetConfig: widgetConfig,
        conversationListServer: conversationListServer, conversationServer: ConversationServer
    ) {

        $scope.main = WebIMWidget;
        $scope.widgetConfig = widgetConfig;

        WebIMWidget.show = function() {
            WebIMWidget.display = true;
            if (widgetConfig.displayConversationList) {
                conversationListServer.showSelf = true;
                conversationServer.hidden();
            } else {
                conversationListServer.showSelf = false;
                conversationServer.show();
            }
            WebIMWidget.onShow && WebIMWidget.onShow();
            setTimeout(function() {
                $scope.$apply();
            });
        }

        WebIMWidget.hidden = function() {
            WebIMWidget.display = false;
            conversationServer.hidden();
            conversationListServer.showSelf = false;
            setTimeout(function() {
                $scope.$apply();
            });
        }

        $scope.showbtn = function() {
            WebIMWidget.display = true;
            WebIMWidget.onShow && WebIMWidget.onShow();
        }

    }]);



interface widgetConfig {
    displayConversationList: boolean
    displayMinButton: boolean
    config: any
}

interface providerdata {
    getUserInfo: UserInfoProvider
    getGroupInfo: GroupInfoProvider
    getCacheUserInfo(id): WidgetModule.UserInfo
    addUserInfo(user: WidgetModule.UserInfo): void
}

interface Config {
    appkey?: string;
    token?: string;
    onSuccess?(userId: string): void;
    onError?(error: any): void;
    // animation: number;
    displayConversationList?: boolean;
    conversationListPosition?: any;
    displayMinButton?: boolean;
    style?: {
        positionFixed?: boolean;
        height?: number;
        width?: number;
        bottom?: number;
        right?: number;
        top?: number;
        left?: number;
    }
}

interface WebIMWidget {

    init(config: Config): void

    show(): void
    onShow(): void
    hidden(): void
    display: boolean
    fullScreen: boolean
    connected: boolean

    setConversation(targetType: number, targetId: string, title: string): void

    onReceivedMessage(msg: WidgetModule.Message): void

    onSentMessage(msg: WidgetModule.Message): void

    onClose(data: any): void

    onCloseBefore(obj: any): void

    onConnectStatusChange(status: number): void

    getCurrentConversation(): WidgetModule.Conversation


    setUserInfoProvider(fun: UserInfoProvider)
    setGroupInfoProvider(fun: GroupInfoProvider)

    /**
     * 静态属性
     */
    EnumConversationListPosition: any
    EnumConversationType: any
}

interface UserInfoProvider {
    (targetId: string, callback: CallBack<WidgetModule.UserInfo>): void
}

interface GroupInfoProvider {
    (targetId: string, callback: CallBack<WidgetModule.GroupInfo>): void
}

interface CallBack<T> {
    onSuccess(data: T): void
}
