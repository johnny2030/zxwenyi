/// <reference path="../typings/tsd.d.ts"/>
var widget = angular.module("RongWebIMWidget", ["RongWebIMWidget.conversationServer", "RongWebIMWidget.directive",
    "RongWebIMWidget.conversationListServer", "RongIMSDKModule", "Evaluate", 'ng-iscroll'
]);
$(function () {
    //rem
    var winW = document.documentElement.clientWidth;
    var desW = 1242;
    var fontSize = 100;
    var rem = desW / fontSize;
    if (winW > desW) {
        winW = desW;
    }
    document.documentElement.style.fontSize = winW / rem + 'px';
});
widget.factory("providerdata", [function () {
        var obj = {
            _cacheUserInfo: [],
            getCacheUserInfo: function (id) {
                for (var i = 0, len = obj._cacheUserInfo.length; i < len; i++) {
                    if (obj._cacheUserInfo[i].userId == id) {
                        return obj._cacheUserInfo[i];
                    }
                }
                return null;
            },
            addUserInfo: function (user) {
                var olduser = obj.getCacheUserInfo(user.userId);
                if (olduser) {
                    angular.extend(olduser, user);
                }
                else {
                    obj._cacheUserInfo.push(user);
                }
            }
        };
        return obj;
    }]);
widget.factory("widgetConfig", [function () {
        return {};
    }]);
widget.factory("WebIMWidget", ["$q", "conversationServer",
    "conversationListServer", "providerdata", "widgetConfig", "RongIMSDKServer",
    function ($q, conversationServer, conversationListServer, providerdata, widgetConfig, RongIMSDKServer) {
        var WebIMWidget = {};
        var messageList = {};
        var defaultconfig = {
            displayMinButton: true,
            displayConversationList: false
        };
        WebIMWidget.display = false;
        WebIMWidget.init = function (config) {
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
            }
            else {
                eleconversationlist && (eleconversationlist.style["display"] = "none");
            }
            if (widgetConfig.displayMinButton) {
                eleminbtn && (eleminbtn.style.display = "none");
            }
            else {
                eleminbtn && (eleminbtn.style.display = "inline-block");
            }
            if (RongIMLib.RongIMEmoji) {
                RongIMLib.RongIMEmoji.init();
            }
            if (RongIMLib.RongIMVoice) {
                RongIMLib.RongIMVoice.init();
            }
            RongIMSDKServer.init(defaultconfig.appkey);
            RongIMSDKServer.connect(defaultconfig.token).then(function (userId) {
                console.log("connect success:" + userId);
                if (WidgetModule.Helper.checkType(defaultconfig.onSuccess) == "function") {
                    defaultconfig.onSuccess(userId);
                }
                if (WidgetModule.Helper.checkType(providerdata.getUserInfo) == "function") {
                    providerdata.getUserInfo(userId, {
                        onSuccess: function (data) {
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
            }, function (err) {
                if (err.tokenError) {
                    if (defaultconfig.onError && typeof defaultconfig.onError == "function") {
                        defaultconfig.onError({ code: 0, info: "token 无效" });
                    }
                }
                else {
                    if (defaultconfig.onError && typeof defaultconfig.onError == "function") {
                        defaultconfig.onError({ code: err.errorCode });
                    }
                }
            });
            RongIMSDKServer.setConnectionStatusListener({
                onChanged: function (status) {
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
                onReceived: function (data) {
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
                            onSuccess: function (data) {
                                if (msg.content && data) {
                                    msg.content.userInfo = new WidgetModule.UserInfo(data.userId, data.name, data.portraitUri);
                                }
                            }
                        });
                    }
                    if (WebIMWidget.onReceivedMessage) {
                        WebIMWidget.onReceivedMessage(msg);
                    }
                    conversationServer.onReceivedMessage(msg);
                    if (WebIMWidget.display && conversationServer.current && conversationServer.current.targetType == msg.conversationType && conversationServer.current.targetId == msg.targetId) {
                        RongIMLib.RongIMClient.getInstance().clearUnreadCount(conversationServer.current.targetType, conversationServer.current.targetId, {
                            onSuccess: function () {
                            },
                            onError: function () {
                            }
                        });
                    }
                    conversationListServer.updateConversations().then(function () { });
                }
            });
        };
        function addMessageAndOperation(msg) {
            var hislist = conversationServer._cacheHistory[msg.conversationType + "_" + msg.targetId] = conversationServer._cacheHistory[msg.conversationType + "_" + msg.targetId] || [];
            if (hislist.length == 0) {
                hislist.push(new WidgetModule.GetHistoryPanel());
                hislist.push(new WidgetModule.TimePanl(msg.sentTime));
            }
            conversationServer._addHistoryMessages(msg);
        }
        WebIMWidget.setConversation = function (targetType, targetId, title) {
            conversationServer.onConversationChangged(new WidgetModule.Conversation(targetType, targetId, title));
        };
        WebIMWidget.setUserInfoProvider = function (fun) {
            providerdata.getUserInfo = fun;
        };
        WebIMWidget.setGroupInfoProvider = function (fun) {
            providerdata.getGroupInfo = fun;
        };
        WebIMWidget.EnumConversationListPosition = WidgetModule.EnumConversationListPosition;
        WebIMWidget.EnumConversationType = WidgetModule.EnumConversationType;
        WebIMWidget.show = function () {
            WebIMWidget.display = true;
            WebIMWidget.fullScreen = false;
        };
        WebIMWidget.hidden = function () {
            WebIMWidget.display = false;
        };
        WebIMWidget.getCurrentConversation = function () {
            return conversationServer.current;
        };
        return WebIMWidget;
    }]);
widget.directive("rongWidget", [function () {
        return {
            restrict: "E",
            templateUrl: "./ts/main.tpl.html",
            controller: "rongWidgetController"
        };
    }]);
widget.controller("rongWidgetController", ["$scope", "WebIMWidget", "widgetConfig", "conversationListServer", "conversationServer",
    function ($scope, WebIMWidget, widgetConfig, conversationListServer, conversationServer) {
        $scope.main = WebIMWidget;
        $scope.widgetConfig = widgetConfig;
        WebIMWidget.show = function () {
            WebIMWidget.display = true;
            if (widgetConfig.displayConversationList) {
                conversationListServer.showSelf = true;
                conversationServer.hidden();
            }
            else {
                conversationListServer.showSelf = false;
                conversationServer.show();
            }
            WebIMWidget.onShow && WebIMWidget.onShow();
            setTimeout(function () {
                $scope.$apply();
            });
        };
        WebIMWidget.hidden = function () {
            WebIMWidget.display = false;
            conversationServer.hidden();
            conversationListServer.showSelf = false;
            setTimeout(function () {
                $scope.$apply();
            });
        };
        $scope.showbtn = function () {
            WebIMWidget.display = true;
            WebIMWidget.onShow && WebIMWidget.onShow();
        };
    }]);
/// <reference path="../../typings/tsd.d.ts"/>
var conversationController = angular.module("RongWebIMWidget.conversationController", ["RongWebIMWidget.conversationServer"]);
conversationController.controller("conversationController", ["$scope",'$compile',"$http",
    "conversationServer", "WebIMWidget", "conversationListServer", "widgetConfig", "providerdata",
    function ($scope, $compile, $http, conversationServer, WebIMWidget, conversationListServer, widgetConfig, providerdata) {
        var ImageDomain = "http://qiniu.jkwdr.cn/";
        var notExistConversation = true;
        var qiniuuploader;
        $scope.$parent.myScrollOptions = {
            'wrapper': {
                disablePointer: true,
                disableTouch: false,
                disableMouse: false,
                snap: false,
                click: true
            }
        };
        $scope.refreshiScroll = function () {
            setTimeout(function () {
                var sc = $scope.$parent.myScroll['wrapper'];
                console.log(sc);
                sc.refresh();
                sc.scrollTo(0, sc.wrapperHeight - sc.scrollerHeight);
            }, 500);
        };
        var position = 0;
        function recordPosition() {
            var sc = $scope.$parent.myScroll['wrapper'];
            position = sc.scrollerHeight - sc.y;
        }
        function scrollToRecord() {
            setTimeout(function () {
                var sc = $scope.$parent.myScroll['wrapper'];
                console.log(sc);
                sc.refresh();
                sc.scrollTo(0, position - sc.scrollerHeight);
            }, 500);
        }
        $scope.$watch("WebIMWidget.display", function (newVal, oldVal) {
            if (newVal === oldVal) {
            }
            else if (newVal == true) {
                $scope.refreshiScroll();
            }
        });
        conversationServer.show = function () {
            $scope.showSelf = true;
        };
        conversationServer.hidden = function () {
            $scope.showSelf = false;
        };
        $scope.currentConversation = {
            title: "",
            targetId: "",
            targetType: 0
        };
        $scope.messageList = [];
        $scope.messageContent = "";
        $scope.WebIMWidget = WebIMWidget;
        $scope.widgetConfig = widgetConfig;
        $scope.conversationServer = conversationServer;
        $scope._inputPanelState = WidgetModule.InputPanelType.person;
        $scope.showSelf = false;
        //显示表情
        $scope.showemoji = false;
        document.getElementById("wrapper").addEventListener("touchstart", function () {
            $scope.$apply(function () {
                $scope.showemoji = false;
            });
        });
        $http({
            method:'GET',
            url:'/index.php',
            params:{
                'g':'portal',
                'm':'rong',
                'a':'checkUser'
            }
        }).success(function (res) {
            if(res.type == 1){
                var subsp = document.getElementById("subsp");
                var compileFn=$compile('<ul class="rongcloud-clearfix rongcloud-voices"><a href="#" ng-click="showMore()"><li class="rongcloud-sprite-voice"></li></a></ul>');
                var $dom=compileFn($scope);
                $dom.appendTo(subsp);
            }
        }).error(function (error) {
            alert("请求失败");
        });
        $http({
            method:'GET',
            url:'/index.php',
            params:{
                'g':'portal',
                'm':'rong',
                'a':'getUpToken'
            }
        }).success(function (res) {
            $scope.qnToken = res;
        }).error(function (error) {
            alert("请求失败");
        });
        $scope.$watch("showemoji", function (newVal, oldVal) {
            if (newVal === oldVal)
                return;
            if (newVal) {
                var emj = document.getElementById("emj");
                var vic = document.getElementById("voice");
                emj.style.display = 'block';
                vic.style.display = 'none';
                $scope.wrapperbottom = {
                    bottom: "8rem"
                };
            }
            else {
                $scope.wrapperbottom = {
                    bottom: "1.8rem"
                };
            }
            $scope.refreshiScroll();
        });
        $scope.showEmj = function () {
            var emj = document.getElementById("emj");
            var vic = document.getElementById("voice");
            emj.style.display = 'block';
            vic.style.display = 'none';
        };
        $scope.showMore = function () {
            var emj = document.getElementById("emj");
            var vic = document.getElementById("voice");
            emj.style.display = 'none';
            vic.style.display = 'block';
        };
        $scope.end_chat = function () {
            $http({
                method:'GET',
                url:'/index.php',
                params:{
                    'g':'portal',
                    'm':'rong',
                    'a':'end_chat',
                    'userId':$scope.currentConversation.targetId
                }
            }).success(function (res) {
                if (res == 1){
                    alert("咨询已关闭");
                }
            }).error(function (error) {
                alert("请求失败");
            })
        };
        $scope.$watch("currentConversation.messageContent", function (newVal, oldVal) {
            if (newVal === oldVal)
                return;
            if ($scope.currentConversation) {
                RongIMLib.RongIMClient.getInstance().saveTextMessageDraft(+$scope.currentConversation.targetType, $scope.currentConversation.targetId, newVal);
            }
        });
        $scope.$watch("_inputPanelState", function (newVal, oldVal) {
            if (newVal === oldVal) {
                return;
            }
            if (newVal == WidgetModule.InputPanelType.person) {
                setTimeout(function () {
                    qiniuuploader && qiniuuploader.destroy();
                    uploadFileInit();
                });
            }
            else {
                qiniuuploader && qiniuuploader.destroy();
            }
        });
        $scope.$watch("showSelf", function (newVal, oldVal) {
            if (newVal === oldVal) {
                return;
            }
            if (newVal) {
                $scope.refreshiScroll();
                setTimeout(function () {
                    qiniuuploader && qiniuuploader.destroy();
                    uploadFileInit();
                }, 1000);
            }
            else {
                qiniuuploader && qiniuuploader.destroy();
            }
        });
        conversationServer.onConversationChangged = function (conversation) {
            if (widgetConfig.displayConversationList) {
                $scope.showSelf = true;
                conversationListServer.showSelf = false;
                $scope.WebIMWidget.display = true;
            }
            else {
                $scope.showSelf = true;
                $scope.WebIMWidget.display = true;
            }
            if (conversation && conversation.targetType == WidgetModule.EnumConversationType.CUSTOMER_SERVICE && (!conversationServer.current || conversationServer.current.targetId != conversation.targetId)) {
                RongIMLib.RongIMClient.getInstance().startCustomService(conversation.targetId, {
                    onSuccess: function () {
                    },
                    onError: function () {
                        console.log("连接客服失败");
                    }
                });
            }
            //会话为空清除页面各项值
            if (!conversation || !conversation.targetId) {
                $scope.messageList = [];
                $scope.currentConversation = null;
                conversationServer.current = null;
                setTimeout(function () {
                    $scope.$apply();
                });
                return;
            }
            conversationServer.current = conversation;
            $scope.currentConversation = conversation;
            //TODO:获取历史消息
            conversationServer._cacheHistory[conversation.targetType + "_" + conversation.targetId] = conversationServer._cacheHistory[conversation.targetType + "_" + conversation.targetId] || [];
            var currenthis = conversationServer._cacheHistory[conversation.targetType + "_" + conversation.targetId] || [];
            if (currenthis.length == 0) {
                conversationServer._getHistoryMessages(+conversation.targetType, conversation.targetId, 3).then(function (data) {
                    $scope.messageList = conversationServer._cacheHistory[conversation.targetType + "_" + conversation.targetId];
                    if ($scope.messageList.length > 0) {
                        $scope.messageList.unshift(new WidgetModule.TimePanl($scope.messageList[0].sentTime));
                        if (data.has) {
                            $scope.messageList.unshift(new WidgetModule.GetMoreMessagePanel());
                        }
                        // adjustScrollbars();
                        $scope.refreshiScroll();
                    }
                });
            }
            else {
                $scope.messageList = currenthis;
            }
            //TODO:获取草稿
            $scope.currentConversation.messageContent = RongIMLib.RongIMClient.getInstance().getTextMessageDraft(+$scope.currentConversation.targetType, $scope.currentConversation.targetId) || "";
            setTimeout(function () {
                $scope.$apply();
            });
        };
        conversationServer.onReceivedMessage = function (msg) {
            // $scope.messageList.splice(0, $scope.messageList.length);
            if ($scope.currentConversation && msg.targetId == $scope.currentConversation.targetId && msg.conversationType == $scope.currentConversation.targetType) {
                $scope.$apply();
                var systemMsg = null;
                switch (msg.messageType) {
                    case WidgetModule.MessageType.HandShakeResponseMessage:
                        conversationServer._customService.type = msg.content.data.serviceType;
                        conversationServer._customService.companyName = msg.content.data.companyName;
                        conversationServer._customService.robotName = msg.content.data.robotName;
                        conversationServer._customService.robotIcon = msg.content.data.robotIcon;
                        conversationServer._customService.robotWelcome = msg.content.data.robotWelcome;
                        conversationServer._customService.humanWelcome = msg.content.data.humanWelcome;
                        conversationServer._customService.noOneOnlineTip = msg.content.data.noOneOnlineTip;
                        if (msg.content.data.serviceType == "1") {
                            changeInputPanelState(WidgetModule.InputPanelType.robot);
                            msg.content.data.robotWelcome && (systemMsg = packReceiveMessage(RongIMLib.TextMessage.obtain(msg.content.data.robotWelcome), WidgetModule.MessageType.TextMessage));
                        }
                        else if (msg.content.data.serviceType == "3") {
                            msg.content.data.robotWelcome && (systemMsg = packReceiveMessage(RongIMLib.TextMessage.obtain(msg.content.data.robotWelcome), WidgetModule.MessageType.TextMessage));
                            changeInputPanelState(WidgetModule.InputPanelType.robotSwitchPerson);
                        }
                        else {
                            // msg.content.data.humanWelcome && (systemMsg = packReceiveMessage(RongIMLib.TextMessage.obtain(msg.content.data.humanWelcome), WidgetModule.MessageType.TextMessage));
                            changeInputPanelState(WidgetModule.InputPanelType.person);
                        }
                        $scope.evaluate.valid = false;
                        setTimeout(function () {
                            $scope.evaluate.valid = true;
                        }, 60 * 1000);
                        break;
                    case WidgetModule.MessageType.ChangeModeResponseMessage:
                        switch (msg.content.data.status) {
                            case 1:
                                conversationServer._customService.human.name = msg.content.data.name || "客服人员";
                                conversationServer._customService.human.headimgurl = msg.content.data.headimgurl;
                                changeInputPanelState(WidgetModule.InputPanelType.person);
                                break;
                            case 2:
                                if (conversationServer._customService.type == "2") {
                                    changeInputPanelState(WidgetModule.InputPanelType.person);
                                }
                                else if (conversationServer._customService.type == "1" || conversationServer._customService.type == "3") {
                                    changeInputPanelState(WidgetModule.InputPanelType.robotSwitchPerson);
                                }
                                break;
                            case 3:
                                changeInputPanelState(WidgetModule.InputPanelType.robot);
                                systemMsg = packReceiveMessage(RongIMLib.InformationNotificationMessage.obtain("你被拉黑了"), WidgetModule.MessageType.InformationNotificationMessage);
                                break;
                            case 4:
                                changeInputPanelState(WidgetModule.InputPanelType.person);
                                systemMsg = packReceiveMessage(RongIMLib.InformationNotificationMessage.obtain("已经是人工了"), WidgetModule.MessageType.InformationNotificationMessage);
                                break;
                            default:
                                break;
                        }
                        break;
                    case WidgetModule.MessageType.TerminateMessage:
                        //关闭客服
                        if (msg.content.code == 0) {
                            $scope.evaluate.CSTerminate = true;
                            $scope.close();
                        }
                        else {
                            if (conversationServer._customService.type == "1") {
                                changeInputPanelState(WidgetModule.InputPanelType.robot);
                            }
                            else {
                                changeInputPanelState(WidgetModule.InputPanelType.robotSwitchPerson);
                            }
                        }
                        break;
                    case WidgetModule.MessageType.CustomerStatusUpdateMessage:
                        switch (Number(msg.content.serviceStatus)) {
                            case 1:
                                if (conversationServer._customService.type == "1") {
                                    changeInputPanelState(WidgetModule.InputPanelType.robot);
                                }
                                else {
                                    changeInputPanelState(WidgetModule.InputPanelType.robotSwitchPerson);
                                }
                                break;
                            case 2:
                                changeInputPanelState(WidgetModule.InputPanelType.person);
                                break;
                            case 3:
                                changeInputPanelState(WidgetModule.InputPanelType.notService);
                                break;
                            default:
                                break;
                        }
                        break;
                    default:
                        break;
                }
                if (systemMsg) {
                    var wmsg = WidgetModule.Message.convert(systemMsg);
                    addCustomService(wmsg);
                    conversationServer._addHistoryMessages(wmsg);
                }
                addCustomService(msg);
                setTimeout(function () {
                    // adjustScrollbars();
                    $scope.refreshiScroll();
                }, 200);
            }
        };
        conversationServer._onConnectSuccess = function () {
            RongIMLib.RongIMClient.getInstance().getFileToken(RongIMLib.FileType.IMAGE, {
                onSuccess: function (data) {
                    conversationServer._uploadToken = data.token;
                    setTimeout(function () {
                        uploadFileInit();
                    }, 1000);
                }, onError: function () {
                }
            });
        };
        $scope.getHistory = function () {
            recordPosition();
            var arr = conversationServer._cacheHistory[$scope.currentConversation.targetType + "_" + $scope.currentConversation.targetId];
            arr.splice(0, arr.length);
            conversationServer._getHistoryMessages(+$scope.currentConversation.targetType, $scope.currentConversation.targetId, 20).then(function (data) {
                $scope.messageList = conversationServer._cacheHistory[$scope.currentConversation.targetType + "_" + $scope.currentConversation.targetId];
                $scope.messageList.unshift(new WidgetModule.TimePanl($scope.messageList[0].sentTime));
                if (data.has) {
                    conversationServer._cacheHistory[$scope.currentConversation.targetType + "_" + $scope.currentConversation.targetId].unshift(new WidgetModule.GetMoreMessagePanel());
                }
                // adjustScrollbars();
                // $scope.refreshiScroll();
                scrollToRecord();
            });
        };
        $scope.getMoreMessage = function () {
            recordPosition();
            conversationServer._cacheHistory[$scope.currentConversation.targetType + "_" + $scope.currentConversation.targetId].shift();
            conversationServer._cacheHistory[$scope.currentConversation.targetType + "_" + $scope.currentConversation.targetId].shift();
            conversationServer._getHistoryMessages(+$scope.currentConversation.targetType, $scope.currentConversation.targetId, 20).then(function (data) {
                $scope.messageList = conversationServer._cacheHistory[$scope.currentConversation.targetType + "_" + $scope.currentConversation.targetId];
                $scope.messageList.unshift(new WidgetModule.TimePanl($scope.messageList[0].sentTime));
                if (data.has) {
                    conversationServer._cacheHistory[$scope.currentConversation.targetType + "_" + $scope.currentConversation.targetId].unshift(new WidgetModule.GetMoreMessagePanel());
                }
                // $scope.refreshiScroll();
                scrollToRecord();
            });
        };
        function addCustomService(msg) {
            if (msg.conversationType == WidgetModule.EnumConversationType.CUSTOMER_SERVICE && msg.content) {
                if (conversationServer._customService.currentType == "1") {
                    msg.content.userInfo = {
                        name: conversationServer._customService.human.name || "客服人员",
                        portraitUri: conversationServer._customService.human.headimgurl || conversationServer._customService.robotIcon
                    };
                }
                else {
                    msg.content.userInfo = {
                        name: conversationServer._customService.robotName,
                        portraitUri: conversationServer._customService.robotIcon
                    };
                }
            }
            return msg;
        }
        var changeInputPanelState = function (type) {
            $scope._inputPanelState = type;
            if (type == WidgetModule.InputPanelType.person) {
                $scope.evaluate.type = 1;
                conversationServer._customService.currentType = "1";
                conversationServer.current.title = conversationServer._customService.human.name || "客服人员";
            }
            else {
                $scope.evaluate.type = 2;
                conversationServer._customService.currentType = "2";
                conversationServer.current.title = conversationServer._customService.robotName;
            }
        };
        function packDisplaySendMessage(msg, messageType) {
            var ret = new RongIMLib.Message();
            var userinfo = new RongIMLib.UserInfo(conversationServer.loginUser.id, conversationServer.loginUser.name || "我", conversationServer.loginUser.portraitUri);
            msg.user = userinfo;
            ret.content = msg;
            ret.conversationType = $scope.currentConversation.targetType;
            ret.targetId = $scope.currentConversation.targetId;
            ret.senderUserId = conversationServer.loginUser.id;
            ret.messageDirection = RongIMLib.MessageDirection.SEND;
            ret.sentTime = (new Date()).getTime() - RongIMLib.RongIMClient.getInstance().getDeltaTime();
            ret.messageType = messageType;
            return ret;
        }
        function packReceiveMessage(msg, messageType) {
            var ret = new RongIMLib.Message();
            var userinfo = null;
            msg.userInfo = userinfo;
            ret.content = msg;
            ret.conversationType = $scope.currentConversation.targetType;
            ret.targetId = $scope.currentConversation.targetId;
            ret.senderUserId = $scope.currentConversation.targetId;
            ret.messageDirection = RongIMLib.MessageDirection.RECEIVE;
            ret.sentTime = (new Date()).getTime() - RongIMLib.RongIMClient.getInstance().getDeltaTime();
            ret.messageType = messageType;
            return ret;
        }
        function closeState() {
            if (WebIMWidget.onClose && typeof WebIMWidget.onClose === "function") {
                WebIMWidget.onClose($scope.currentConversation);
            }
            if (widgetConfig.displayConversationList) {
                $scope.showSelf = false;
                conversationListServer.showSelf = true;
            }
            else {
                // $scope.WebIMWidget.display = false;
                $scope.showSelf = false;
            }
            $scope.messageList = [];
            $scope.currentConversation = null;
            conversationServer.current = null;
        }
        $scope.evaluate = {
            type: 1,
            showevaluate: false,
            valid: false,
            CSTerminate: false,
            onConfirm: function (data) {
                //发评价
                if (data) {
                    if ($scope.value == 1) {
                        RongIMLib.RongIMClient.getInstance().evaluateHumanCustomService(conversationServer.current.targetId, data.stars, data.describe, {
                            onSuccess: function () {
                            }
                        });
                    }
                    else {
                        RongIMLib.RongIMClient.getInstance().evaluateRebotCustomService(conversationServer.current.targetId, data.value, data.describe, {
                            onSuccess: function () {
                            }
                        });
                    }
                }
                RongIMLib.RongIMClient.getInstance().stopCustomeService(conversationServer.current.targetId, {
                    onSuccess: function () {
                    },
                    onError: function () {
                    }
                });
                closeState();
            },
            onCancle: function () {
                RongIMLib.RongIMClient.getInstance().stopCustomeService(conversationServer.current.targetId, {
                    onSuccess: function () {
                    },
                    onError: function () {
                    }
                });
                closeState();
            }
        };
        $scope.close = function () {
            if (WebIMWidget.onCloseBefore && typeof WebIMWidget.onCloseBefore === "function") {
                var isClose = WebIMWidget.onCloseBefore({
                    close: function (data) {
                        if (conversationServer.current.targetType == WidgetModule.EnumConversationType.CUSTOMER_SERVICE) {
                            if ($scope.evaluate.valid) {
                                $scope.evaluate.showevaluate = true;
                            }
                            else {
                                $scope.evaluate.onCancle();
                            }
                        }
                        else {
                            closeState();
                        }
                    }
                });
            }
            else {
                if (conversationServer.current.targetType == WidgetModule.EnumConversationType.CUSTOMER_SERVICE) {
                    if ($scope.evaluate.valid) {
                        $scope.evaluate.showevaluate = true;
                    }
                    else {
                        $scope.evaluate.onCancle();
                    }
                }
                else {
                    closeState();
                }
            }
        };
        $scope.send = function () {
            if (!$scope.currentConversation.targetId || !$scope.currentConversation.targetType) {
                alert("请先选择一个会话目标。");
                return;
            }
            if ($scope.currentConversation.messageContent.trim() == "") {
                return;
            }
            $http({
                method:'GET',
                url:'/index.php',
                params:{
                    'g':'portal',
                    'm':'rong',
                    'a':'check_chat',
                    'userId': $scope.currentConversation.targetId
                }
            }).success(function (res) {
                if (res == 0){
                    alert("咨询已失效，请重新咨询");
                } else {
                    var con = RongIMLib.RongIMEmoji.symbolToEmoji($scope.currentConversation.messageContent);
                    var msg = RongIMLib.TextMessage.obtain(con);
                    var userinfo = new RongIMLib.UserInfo(conversationServer.loginUser.id, conversationServer.loginUser.name, conversationServer.loginUser.portraitUri);
                    msg.user = userinfo;
                    RongIMLib.RongIMClient.getInstance().sendMessage(+$scope.currentConversation.targetType, $scope.currentConversation.targetId, msg, {
                        onSuccess: function (retMessage) {
                            conversationListServer.updateConversations().then(function () {
                            });
                        },
                        onError: function (error) {
                            console.log(error);
                        }
                    });
                    var content = packDisplaySendMessage(msg, WidgetModule.MessageType.TextMessage);
                    var cmsg = WidgetModule.Message.convert(content);
                    conversationServer._addHistoryMessages(cmsg);
                    // $scope.messageList.push();
                    // adjustScrollbars();
                    $scope.refreshiScroll();
                    $scope.currentConversation.messageContent = "";
                    if (!$scope.showemoji) {
                        var obj = document.getElementById("inputMsg");
                        WidgetModule.Helper.getFocus(obj);
                    }
                }
            }).error(function (error) {
                alert("请求失败");
            })
        };
        $scope.minimize = function () {
            WebIMWidget.display = false;
        };
        $scope.switchPerson = function () {
            RongIMLib.RongIMClient.getInstance().switchToHumanMode(conversationServer.current.targetId, {
                onSuccess: function () {
                },
                onError: function () {
                }
            });
        };
        var refreshToken = setInterval(function () {
            conversationServer._onConnectSuccess();
        }, 10 * 60 * 1000);
        function uploadFileInit() {
            qiniuuploader = Qiniu.uploader({
                // runtimes: 'html5,flash,html4',
                runtimes: 'html5,html4',
                browse_button: 'uploadfile',
                container: 'funcPanel',
                drop_element: 'Messages',
                max_file_size: '100mb',
                // flash_swf_url: '/widget/images/Moxie.swf',
                dragdrop: true,
                chunk_size: '4mb',
                // uptoken_url: "http://webim.demo.rong.io/getUploadToken",
                uptoken: $scope.qnToken,
                domain: ImageDomain,
                get_new_uptoken: false,
                unique_names: true,
                multi_selection: false,
                auto_start: true,
                init: {
                    'FilesAdded': function (up, files) {
                        plupload.each(files, function(file) {
                            if(file.type=='image/jpeg'||file.type=='image/jpg'||file.type=='image/png'||file.type=='image/gif'){
                                isUpload =true;
                            }else {
                                isUpload = false;
                                up.removeFile(file);
                                alert("上传类型只能是.jpg,.png,.gif");
                                return false;
                            }
                        });
                    },
                    'BeforeUpload': function (up, file) {
                    },
                    'UploadProgress': function (up, file) {
                    },
                    'UploadComplete': function () {
                    },
                    'FileUploaded': function (up, file, info) {
                        if (!$scope.currentConversation.targetId || !$scope.currentConversation.targetType) {
                            alert("请先选择一个会话目标。");
                            return;
                        }
                        $http({
                            method:'GET',
                            url:'/index.php',
                            params:{
                                'g':'portal',
                                'm':'rong',
                                'a':'check_chat',
                                'userId': $scope.currentConversation.targetId
                            }
                        }).success(function (res) {
                            if (res == 0){
                                alert("咨询已失效，请重新咨询");
                            } else {
                                var rs = JSON.parse(info);
                                WidgetModule.Helper.ImageHelper.getThumbnail(file.getNative(), 60000, function (obj, data) {
                                    var url = ImageDomain+rs.key;
                                    var im = RongIMLib.ImageMessage.obtain(data, url);
                                    var content = packDisplaySendMessage(im, WidgetModule.MessageType.ImageMessage);
                                    RongIMLib.RongIMClient.getInstance().sendMessage($scope.currentConversation.targetType, $scope.currentConversation.targetId, im, {
                                        onSuccess: function () {
                                            conversationListServer.updateConversations().then(function () {
                                            });
                                        },
                                        onError: function () {
                                        }
                                    });
                                    conversationServer._addHistoryMessages(WidgetModule.Message.convert(content));
                                    $scope.$apply();
                                    console.log(1);
                                    $scope.refreshiScroll();
                                });
                            }
                        }).error(function (error) {
                            alert("请求失败");
                        })
                    },
                    'Error': function (up, err, errTip) {
                        alert("上传失败up:"+up+"err:"+err+"errTip:"+errTip);
                    }
                }
            });
        }
    }]);
/// <reference path="../../typings/tsd.d.ts"/>
var conversationDirective = angular.module("RongWebIMWidget.conversationDirective", ["RongWebIMWidget.conversationController"]);
conversationDirective.directive("rongConversation", [function () {
        return {
            restrict: "E",
            templateUrl: "./ts/conversation/conversation.tpl.html",
            controller: "conversationController",
            link: function (scope, ele) {
                //安卓上取消键盘输入框不失去焦点
                angular.element(document).bind("touchstart", function (e) {
                    var inputMsg = document.getElementById("inputMsg");
                    if (inputMsg){
                        if (e.target != inputMsg) {
                            inputMsg.blur();
                        }
                    }
                });
            }
        };
    }]);
conversationDirective.directive("swipeEmoji", [function () {
        return {
            restrict: "E",
            scope: {
                content: "="
            },
            templateUrl: './ts/conversation/swipeEmoji.tpl.html',
            link: function (scope, ele, attr) {
                var data = [{ emojis: [], current: true }];
                var bullets = document.getElementById('position').getElementsByTagName('li');
                scope.data = [];
                var emojiList = null;
                scope.$parent.$watch("showemoji", function (newVal, oldVal) {
                    if (newVal === oldVal)
                        return;
                    if (!emojiList) {
                        emojiList = RongIMLib.RongIMEmoji.emojis.slice(0, 84).concat();
                        while (emojiList.length) {
                            scope.data.push({
                                emojis: emojiList.splice(0, 23),
                                show: false
                            });
                        }
                        scope.data[0].show = true;
                        setTimeout(function () {
                            var swipe = new window.Swipe(document.getElementById('slider'), {
                                continuous: true,
                                callback: function (pos) {
                                    var i = bullets.length;
                                    while (i--) {
                                        scope.data[i].show = false;
                                    }
                                    scope.data[pos].show = true;
                                    scope.$apply();
                                }
                            });
                        }, 500);
                    }
                });
                scope.delete = function () {
                    var reg = /\[[\u4e00-\u9fa5]+\]$/;
                    if (reg.test(scope.content.messageContent)) {
                        scope.content.messageContent = scope.content.messageContent.replace(reg, function () {
                            return "";
                        });
                    }
                    else {
                        scope.content.messageContent = scope.content.messageContent.substr(0, scope.content.messageContent.length - 1);
                    }
                };
            }
        };
    }]);
conversationDirective.directive("emoji", [function () {
        return {
            restrict: "E",
            scope: {
                item: "=",
                content: "="
            },
            template: '<div style="display:inline-block"></div>',
            link: function (scope, ele, attr) {
                ele.find("div").append(scope.item);
                ele.on("click", function (e) {
                    scope.content.messageContent = scope.content.messageContent || "";
                    scope.content.messageContent = scope.content.messageContent.replace(/\n$/, "");
                    scope.content.messageContent = scope.content.messageContent + scope.item.children[0].getAttribute("name");
                    scope.$parent.$apply();
                    e.preventDefault();
                });
            }
        };
    }]);
conversationDirective.directive('contenteditableDire', function () {
    return {
        restrict: 'A',
        require: '?ngModel',
        link: function (scope, element, attrs, ngModel) {
            function replacemy(e) {
                return e.replace(new RegExp("<[\\s\\S.]*?>", "ig"), "");
            }
            var domElement = element[0];
            scope.$watch(function () {
                return ngModel.$modelValue;
            }, function (newVal) {
                if (document.activeElement === domElement) {
                    return;
                }
                if (newVal === '' || newVal === attrs["placeholder"]) {
                    domElement.innerHTML = attrs["placeholder"];
                    domElement.style.color = "#a9a9a9";
                }
            });
            element.bind('focus', function () {
                if (domElement.innerHTML == attrs["placeholder"]) {
                    domElement.innerHTML = '';
                }
                domElement.style.color = '';
            });
            element.bind('blur', function () {
                if (domElement.innerHTML === '') {
                    domElement.innerHTML = attrs["placeholder"];
                    domElement.style.color = "#a9a9a9";
                }
            });
            if (!ngModel)
                return;
            element.bind("paste", function (e) {
                var that = this, ohtml = that.innerHTML;
                timeoutid && clearTimeout(timeoutid);
                var timeoutid = setTimeout(function () {
                    that.innerHTML = replacemy(that.innerHTML);
                    ngModel.$setViewValue(that.innerHTML);
                    timeoutid = null;
                }, 50);
            });
            ngModel.$render = function () {
                element.html(ngModel.$viewValue || '');
            };
            WidgetModule.Helper.browser.msie ? element.bind("keyup paste", read) : element.bind("input", read);
            function read() {
                var html = element.html();
                html = html.replace(/^<br>$/i, "");
                html = html.replace(/<br>/gi, "\n");
                if (attrs["stripBr"] && html == '<br>') {
                    html = '';
                }
                ngModel.$setViewValue(html);
            }
        }
    };
});
conversationDirective.directive("ctrlEnterKeys", ["$timeout", function ($timeout) {
        return {
            restrict: "A",
            require: '?ngModel',
            scope: {
                fun: "&",
                ctrlenter: "=",
                content: "="
            },
            link: function (scope, element, attrs, ngModel) {
                scope.ctrlenter = scope.ctrlenter || false;
                element.bind("keypress", function (e) {
                    if (scope.ctrlenter) {
                        if (e.ctrlKey === true && e.keyCode === 13 || e.keyCode === 10) {
                            scope.fun();
                            scope.$parent.$apply();
                            e.preventDefault();
                        }
                    }
                    else {
                        if (e.ctrlKey === false && e.shiftKey === false && (e.keyCode === 13 || e.keyCode === 10)) {
                            scope.fun();
                            scope.$parent.$apply();
                            e.preventDefault();
                        }
                        else if (e.ctrlKey === true && e.keyCode === 13 || e.keyCode === 10) {
                        }
                    }
                });
            }
        };
    }]);
// conversationDirective.directive("textmessage", [function() {
//     return {
//         restrict: "E",
//         scope: { msg: "=" },
//         template: '<div class="">' +
//         '<div class="Message-text"><pre class="Message-entry" ng-bind-html="msg.content|trustHtml"><br></pre></div>' +
//         '</div>'
//     }
// }]);
conversationDirective.directive("textmessage", [function () {
        return {
            restrict: "E",
            scope: { msg: "=" },
            template: '<div class="">' +
                '<div class="rongcloud-Message-text">' +
                '<div class="rongcloud-arrow-dialog"></div>' +
                // '<i class="sprite"></i>' +
                '<pre class="rongcloud-Message-entry rongcloud-userMsg" ng-bind-html="msg.content|trustHtml"><br></pre>' +
                '</div>' +
                '</div>'
        };
    }]);
conversationDirective.directive("imagemessage", [function () {
        return {
            restrict: "E",
            scope: { msg: "=" },
            template: '<div class="">' +
                '<div class="rongcloud-Message-img">' +
                '<span id="{{\'rebox_\'+$id}}" class="rongcloud-Message-entry" style="">' +
                // '<img src="images/barBg.png" alt=""/>' +
                '<a href="{{msg.imageUri}}"><img ng-src="{{msg.content}}"  data-image="{{msg.imageUri}}" alt=""/></a>' +
                '</span>' +
                '</div>' +
                '</div>',
            link: function (scope, ele, attr) {
                var img = new Image();
                img.src = scope.msg.imageUri;
                setTimeout(function () {
                    $('#rebox_' + scope.$id).rebox({ selector: 'a' }).bind("rebox:open", function () {
                        //jQuery rebox 点击空白关闭
                        var rebox = document.getElementsByClassName("rebox")[0];
                        rebox.onclick = function (e) {
                            if (e.target.tagName.toLowerCase() != "img") {
                                var rebox_close = document.getElementsByClassName("rebox-close")[0];
                                rebox_close.click();
                                rebox = null;
                                rebox_close = null;
                            }
                        };
                    });
                });
                img.onload = function () {
                    // scope.$apply(function() {
                    //     scope.msg.content = scope.msg.imageUri
                    // });
                };
                scope.showBigImage = function () {
                };
            }
        };
    }]);
conversationDirective.directive("includinglinkmessage", [function () {
        return {
            restrict: "E",
            scope: { msg: "=" },
            template: '<div class="">' +
                '<div class="rongcloud-Message-text">' +
                '<pre class="rongcloud-Message-entry" style="">' +
                '维护中 由于我们的服务商出现故障，融云官网及相关服务也受到影响，给各位用户带来的不便，还请谅解。  您可以通过 <a href="#">【官方微博】</a>了解</pre>' +
                '</div>' +
                '</div>'
        };
    }]);
conversationDirective.directive("voicemessage", ["$timeout", function ($timeout) {
        return {
            restrict: "E",
            scope: { msg: "=" },
            template: '<div class="">' +
                '<div class="rongcloud-Message-audio">' +
                '<span class="rongcloud-Message-entry" style="">' +
                '<span class="rongcloud-audioBox rongcloud-clearfix " ng-click="play()" ng-class="{\'rongcloud-animate\':isplaying}" ><i></i><i></i><i></i></span>' +
                '<div style="display: inline-block;" ><span class="rongcloud-audioTimer">{{msg.duration}}”</span><span class="rongcloud-audioState" ng-show="msg.isUnReade"></span></div>' +
                '</span>' +
                '</div>' +
                '</div>',
            link: function (scope, ele, attr) {
                scope.msg.duration = parseInt(scope.msg.duration || scope.msg.content.length / 1024);
                RongIMLib.RongIMVoice.preLoaded(scope.msg.content);
                scope.play = function () {
                    RongIMLib.RongIMVoice.stop(scope.msg.content);
                    if (!scope.isplaying) {
                        scope.msg.isUnReade = false;
                        RongIMLib.RongIMVoice.play(scope.msg.content, scope.msg.duration);
                        scope.isplaying = true;
                        if (scope.timeoutid) {
                            $timeout.cancel(scope.timeoutid);
                        }
                        scope.timeoutid = $timeout(function () {
                            scope.isplaying = false;
                        }, scope.msg.duration * 1000);
                    }
                    else {
                        scope.isplaying = false;
                        $timeout.cancel(scope.timeoutid);
                    }
                };
            }
        };
    }]);
conversationDirective.directive("locationmessage", [function () {
        return {
            restrict: "E",
            scope: { msg: "=" },
            template: '<div class="">' +
                '<div class="rongcloud-Message-map">' +
                '<span class="rongcloud-Message-entry" style="">' +
                '<div class="rongcloud-mapBox">' +
                '<img ng-src="{{msg.content}}" alt="">' +
                '<span>{{msg.poi}}</span>' +
                '</div>' +
                '</span>' +
                '</div>' +
                '</div>'
        };
    }]);
conversationDirective.directive("richcontentmessage", [function () {
        return {
            restrict: "E",
            scope: { msg: "=" },
            template: '<div class="">' +
                '<div class="rongcloud-Message-image-text">' +
                '<span class="rongcloud-Message-entry" style="">' +
                '<div class="rongcloud-image-textBox">' +
                '<h4>{{msg.title}}</h4>' +
                '<div class="rongcloud-cont rongcloud-clearfix">' +
                '<img ng-src="{{msg.imageUri}}" alt="">' +
                '<div>{{msg.content}}</div>' +
                '</div>' +
                '</div>' +
                '</span>' +
                '</div>' +
                '</div>'
        };
    }]);
/// <reference path="../../typings/tsd.d.ts"/>
var conversationServer = angular.module("RongWebIMWidget.conversationServer", ["RongWebIMWidget.conversationDirective"]);
conversationServer.factory("conversationServer", ["$q", function ($q) {
        var conversationServer = {};
        conversationServer.current = {
            targetId: "",
            targetType: 0,
            title: "",
            portraitUri: "",
            onLine: false
        };
        conversationServer.loginUser = {
            id: "",
            name: "",
            portraitUri: ""
        };
        conversationServer._cacheHistory = {};
        conversationServer._getHistoryMessages = function (targetType, targetId, number, reset) {
            var defer = $q.defer();
            RongIMLib.RongIMClient.getInstance().getHistoryMessages(targetType, targetId, reset ? 0 : null, number, {
                onSuccess: function (data, has) {
                    var msglen = data.length;
                    while (msglen--) {
                        var msg = WidgetModule.Message.convert(data[msglen]);
                        unshiftHistoryMessages(targetId, targetType, msg);
                    }
                    defer.resolve({ data: data, has: has });
                },
                onError: function (error) {
                    defer.reject(error);
                }
            });
            return defer.promise;
        };
        function adduserinfo() {
        }
        function unshiftHistoryMessages(id, type, item) {
            var arr = conversationServer._cacheHistory[type + "_" + id] = conversationServer._cacheHistory[type + "_" + id] || [];
            if (arr[0] && arr[0].sentTime && arr[0].panelType != WidgetModule.PanelType.Time && item.sentTime) {
                if (!WidgetModule.Helper.timeCompare(arr[0].sentTime, item.sentTime)) {
                    arr.unshift(new WidgetModule.TimePanl(arr[0].sentTime));
                }
            }
            arr.unshift(item);
        }
        conversationServer._addHistoryMessages = function (item) {
            var arr = conversationServer._cacheHistory[item.conversationType + "_" + item.targetId] = conversationServer._cacheHistory[item.conversationType + "_" + item.targetId] || [];
            if (arr[arr.length - 1] && arr[arr.length - 1].panelType != WidgetModule.PanelType.Time && arr[arr.length - 1].sentTime && item.sentTime) {
                if (!WidgetModule.Helper.timeCompare(arr[arr.length - 1].sentTime, item.sentTime)) {
                    arr.push(new WidgetModule.TimePanl(item.sentTime));
                }
            }
            arr.push(item);
        };
        conversationServer.onConversationChangged = function () {
            //提供接口由conversation controller实现具体操作
        };
        conversationServer.onReceivedMessage = function () {
            //提供接口由coversation controller实现具体操作
        };
        conversationServer._customService = {
            human: {}
        };
        return conversationServer;
    }]);
/// <reference path="../../typings/tsd.d.ts"/>
var conversationListCtr = angular.module("RongWebIMWidget.conversationListController", []);
conversationListCtr.controller("conversationListController", ["$scope", "conversationListServer", "WebIMWidget",
    function ($scope, conversationListServer, WebIMWidget) {
        $scope.conversationListServer = conversationListServer;
        $scope.WebIMWidget = WebIMWidget;
        conversationListServer.refreshConversationList = function () {
            setTimeout(function () {
                $scope.$apply();
            });
        };
        $scope.minbtn = function () {
            conversationListServer.showSelf = false;
        };
        $scope.connected = true;
        conversationListServer._onConnectStatusChange = function (status) {
            if (status == RongIMLib.ConnectionStatus.CONNECTED) {
                $scope.connected = true;
            }
            else {
                $scope.connected = false;
            }
            setTimeout(function () {
                $scope.$apply();
            });
        };
    }]);
/// <reference path="../../typings/tsd.d.ts"/>
var conversationListDir = angular.module("RongWebIMWidget.conversationListDirective", ["RongWebIMWidget.conversationListController"]);
conversationListDir.directive("rongConversationList", [function () {
        return {
            restrict: "E",
            templateUrl: "./ts/conversationlist/conversationList.tpl.html",
            controller: "conversationListController",
            link: function (scope, ele) {
            }
        };
    }]);
conversationListDir.directive("conversationItem", ["conversationServer", "conversationListServer",
    function (conversationServer, conversationListServer) {
        return {
            restrict: "E",
            scope: { item: "=" },
            template: '<div class="rongcloud-chat_item">' +
                '<div class="rongcloud-static_item">' +
                '<div class="rongcloud-ext">' +
                '<p class="rongcloud-attr rongcloud-clearfix" ng-show="item.unreadMessageCount>0">' +
                '<span class="rongcloud-badge badge">{{item.unreadMessageCount>99?"99+":item.unreadMessageCount}}</span>' +
                '</p>' +
                '</div>' +
                '<div class="rongcloud-photo">' +
                '<img class="rongcloud-img" ng-src="{{item.portraitUri}}" alt="">' +
                '<i class="rongcloud-Presence rongcloud-Presence--stacked rongcloud-Presence--mainBox"></i>' +
                '</div>' +
                '<div class="rongcloud-info">' +
                '<h3 class="rongcloud-nickname">' +
                '<span class="rongcloud-nickname_text">{{item.title}}</span>' +
                '</h3>' +
                '</div>' +
                '</div>' +
                '<div class="rongcloud-delete_box">' +
                '<span class="rongcloud-sprite2 rongcloud-icon_delete" ng-click="remove($event)"></span>' +
                '</div>' +
                '</div>',
            link: function (scope, ele, attr) {
                var item = ele[0].querySelector(".rongcloud-chat_item");
                var deletebox = ele[0].querySelector(".rongcloud-delete_box");
                var start, left, width = deletebox.clientWidth;
                var Emove = function (e) {
                    var move = e.changedTouches[0].clientX - start;
                    var marginleft = left + move;
                    if (marginleft < 0 && marginleft > -width) {
                        item.style["margin-left"] = marginleft + "px";
                    }
                    else if (marginleft > 0) {
                        item.style["margin-left"] = 0 + "px";
                    }
                    else if (marginleft < -width) {
                        item.style["margin-left"] = -width + "px";
                    }
                };
                item.addEventListener("touchstart", function (e) {
                    width = deletebox.clientWidth;
                    start = (e.changedTouches[0].clientX);
                    item.className = "rongcloud-chat_item";
                    left = parseFloat(item.style["margin-left"]) || 0;
                    document.addEventListener("touchmove", Emove);
                    document.addEventListener("touchend", End);
                });
                var End = function (e) {
                    var move = e.changedTouches[0].clientX - start;
                    var marginleft = left + move;
                    if (marginleft > -width / 2) {
                        item.className = "rongcloud-chat_item rongcloud-chat_item_m rongcloud-normal";
                        item.style["margin-left"] = "0px";
                    }
                    else {
                        item.className = "rongcloud-chat_item rongcloud-chat_item_m rongcloud-remove";
                        item.style["margin-left"] = -width + "px";
                    }
                    document.removeEventListener("touchmove", Emove);
                    document.removeEventListener("touchend", End);
                };
                ele.on("click", function () {
                    conversationServer.onConversationChangged(new WidgetModule.Conversation(scope.item.targetType, scope.item.targetId, scope.item.title));
                    RongIMLib.RongIMClient.getInstance().clearUnreadCount(scope.item.targetType, scope.item.targetId, {
                        onSuccess: function () {
                        },
                        onError: function () {
                        }
                    });
                    conversationListServer.updateConversations();
                });
                scope.remove = function (e) {
                    e.stopPropagation();
                    RongIMLib.RongIMClient.getInstance().removeConversation(scope.item.targetType, scope.item.targetId, {
                        onSuccess: function () {
                            // if (conversationServer.current && conversationServer.current.targetType == scope.item.targetType && conversationServer.current.targetId == scope.item.targetId) {
                            //     conversationServer.onConversationChangged(new WidgetModule.Conversation());
                            // }
                            conversationListServer.updateConversations();
                        },
                        onError: function (error) {
                            console.log(error);
                        }
                    });
                };
            }
        };
    }]);
/// <reference path="../../typings/tsd.d.ts"/>
var conversationListSer = angular.module("RongWebIMWidget.conversationListServer", ["RongWebIMWidget.conversationListDirective", "RongWebIMWidget"]);
conversationListSer.factory("conversationListServer", ["$q", "providerdata",
    function ($q, providerdata) {
        var server = {};
        server.conversationList = [];
        server.updateConversations = function () {
            var defer = $q.defer();
            RongIMLib.RongIMClient.getInstance().getConversationList({
                onSuccess: function (data) {
                    server.conversationList.splice(0, server.conversationList.length);
                    for (var i = 0, len = data.length; i < len; i++) {
                        var con = WidgetModule.Conversation.onvert(data[i]);
                        switch (con.targetType) {
                            case RongIMLib.ConversationType.PRIVATE:
                                if (WidgetModule.Helper.checkType(providerdata.getUserInfo) == "function") {
                                    (function (a, b) {
                                        providerdata.getUserInfo(a.targetId, {
                                            onSuccess: function (data) {
                                                a.title = data.name;
                                                a.portraitUri = data.portraitUri;
                                                b.conversationTitle = data.name;
                                                b.portraitUri = data.portraitUri;
                                            }
                                        });
                                    }(con, data[i]));
                                }
                                break;
                            case RongIMLib.ConversationType.GROUP:
                                if (WidgetModule.Helper.checkType(providerdata.getGroupInfo) == "function") {
                                    (function (a, b) {
                                        providerdata.getGroupInfo(a.targetId, {
                                            onSuccess: function (data) {
                                                a.title = data.name;
                                                a.portraitUri = data.portraitUri;
                                                b.conversationTitle = data.name;
                                                b.portraitUri = data.portraitUri;
                                            }
                                        });
                                    }(con, data[i]));
                                }
                                break;
                            case RongIMLib.ConversationType.CHATROOM:
                                break;
                        }
                        server.conversationList.push(con);
                    }
                    defer.resolve();
                    server.refreshConversationList();
                },
                onError: function (error) {
                    defer.reject(error);
                }
            }, null);
            return defer.promise;
        };
        server.refreshConversationList = function () {
            //在controller里刷新页面。
        };
        server.getConversation = function (type, id) {
            for (var i = 0, len = server.conversationList.length; i < len; i++) {
                if (server.conversationList[i].targetType == type && server.conversationList[i].targetId == id) {
                    return server.conversationList[i];
                }
            }
            return null;
        };
        server.addConversation = function (conversation) {
            server.conversationList.unshift(conversation);
        };
        server._onConnectStatusChange = function () { };
        server.showSelf = false;
        return server;
    }]);
/// <reference path="../typings/tsd.d.ts"/>
var widget = angular.module("RongWebIMWidget");
widget.service("RongCustomerService", ["WebIMWidget", function (WebIMWidget) {
        var cs = {};
        var defaultconfig = {
            __isCustomerService: true
        };
        cs.init = function (config) {
            angular.extend(defaultconfig, config);
            cs._config = config;
            WebIMWidget.init({
                appkey: config.appkey,
                token: config.token,
                onSuccess: function (e) {
                    config.onSuccess && config.onSuccess(e);
                }
            });
            WebIMWidget.onShow = function () {
                WebIMWidget.setConversation(WidgetModule.EnumConversationType.CUSTOMER_SERVICE, config.customerServiceId, "客服");
            };
            WebIMWidget.onCloseBefore = function (obj) {
                obj.close({ showEvaluate: true });
            };
        };
        cs.show = function () {
            WebIMWidget.show();
        };
        cs.hidden = function () {
            WebIMWidget.hidden();
        };
        cs.Postion = Postion;
        return cs;
    }]);
var Postion;
(function (Postion) {
    Postion[Postion["left"] = 1] = "left";
    Postion[Postion["right"] = 2] = "right";
})(Postion || (Postion = {}));
var directive = angular.module("RongWebIMWidget.directive", []);
directive.filter('trustHtml', ["$sce", function ($sce) {
        return function (str) {
            return $sce.trustAsHtml(str);
        };
    }]);
directive.filter("historyTime", ["$filter", function ($filter) {
        return function (time) {
            var today = new Date();
            if (time.toDateString() === today.toDateString()) {
                return $filter("date")(time, "HH:mm");
            }
            else if (time.toDateString() === new Date(today.setTime(today.getTime() - 1)).toDateString()) {
                return "昨天" + $filter("date")(time, "HH:mm");
            }
            else {
                return $filter("date")(time, "yyyy-MM-dd HH:mm");
            }
        };
    }]);
directive.directive("myTap", [function () {
        // return {
        //     controller: ["$scope", "$element", function($scope, $element) {
        //         var timeout, valid;
        //
        //         $element.bind("touchstart", function() {
        //             setTimeout(function() {
        //                 valid = true;
        //             }, 200);
        //         });
        //
        //         $element.bind("touchend", function() {
        //             if (valid) {
        //               tap()
        //             }
        //         });
        //
        //         function tap(event) {
        //             var method = $element.attr("my-tap");
        //             $scope.$event = event;
        //             $scope.$apply(method);
        //         }
        //     }]
        // }
        var isTouch = !!("ontouchstart" in window);
        return function (scope, ele, attrs) {
            if (isTouch) {
                var valid;
                ele.bind("touchstart", function () {
                    valid = true;
                });
                ele.bind("touchend", function () {
                    if (valid) {
                        scope.$apply(attrs.myTap);
                    }
                    valid = false;
                });
            }
            else {
                ele.bind("click", function () {
                    scope.$apply(attrs.myTap);
                });
            }
        };
    }]);
/// <reference path="../../typings/tsd.d.ts"/>
var evaluate = angular.module("Evaluate", []);
evaluate.directive("evaluatedir", ["$timeout", function ($timeout) {
        return {
            restrict: "E",
            scope: {
                type: "=",
                display: "=",
                enter: "&confirm",
                dcancle: "&cancle"
            },
            templateUrl: './ts/evaluate/evaluate.tpl.html',
            link: function (scope, ele) {
                var stars = [false, false, false, false, false];
                var labels = ["答非所问", "理解能力差", "一问三不知", "不礼貌"];
                scope.stars = stars.concat();
                scope.labels = labels.concat();
                scope.end = false;
                scope.displayDescribe = false;
                scope.data = {
                    stars: 0,
                    value: 0,
                    describe: "",
                    label: ""
                };
                scope.$watch("display", function (newVal, oldVal) {
                    if (newVal === oldVal) {
                        return;
                    }
                    else {
                        scope.displayDescribe = false;
                        scope.data = {
                            stars: 0,
                            value: 0,
                            describe: "",
                            label: ""
                        };
                    }
                });
                scope.confirm = function (data) {
                    if (data != undefined) {
                        if (scope.type == 1) {
                            scope.data.stars = data;
                            if (scope.data.stars != 5) {
                                scope.displayDescribe = true;
                            }
                            else {
                                confirm(scope.data);
                            }
                        }
                        else {
                            scope.data.value = data;
                            if (scope.data.value === false) {
                                scope.displayDescribe = true;
                            }
                            else {
                                confirm(scope.data);
                            }
                        }
                    }
                    else {
                        confirm(null);
                    }
                };
                scope.commit = function () {
                    confirm(scope.data);
                };
                scope.cancle = function () {
                    scope.display = false;
                    scope.dcancle();
                };
                function confirm(data) {
                    scope.end = true;
                    if (data) {
                        $timeout(function () {
                            scope.display = false;
                            scope.end = false;
                            scope.enter({ data: data });
                        }, 800);
                    }
                    else {
                        scope.display = false;
                        scope.end = false;
                        scope.enter({ data: data });
                    }
                }
            }
        };
    }]);
/// <reference path="../typings/tsd.d.ts"/>
var __extends = (this && this.__extends) || function (d, b) {
    for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p];
    function __() { this.constructor = d; }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
};
var WidgetModule;
(function (WidgetModule) {
    (function (EnumConversationListPosition) {
        EnumConversationListPosition[EnumConversationListPosition["left"] = 0] = "left";
        EnumConversationListPosition[EnumConversationListPosition["right"] = 1] = "right";
    })(WidgetModule.EnumConversationListPosition || (WidgetModule.EnumConversationListPosition = {}));
    var EnumConversationListPosition = WidgetModule.EnumConversationListPosition;
    (function (EnumConversationType) {
        EnumConversationType[EnumConversationType["PRIVATE"] = 1] = "PRIVATE";
        EnumConversationType[EnumConversationType["DISCUSSION"] = 2] = "DISCUSSION";
        EnumConversationType[EnumConversationType["GROUP"] = 3] = "GROUP";
        EnumConversationType[EnumConversationType["CHATROOM"] = 4] = "CHATROOM";
        EnumConversationType[EnumConversationType["CUSTOMER_SERVICE"] = 5] = "CUSTOMER_SERVICE";
        EnumConversationType[EnumConversationType["SYSTEM"] = 6] = "SYSTEM";
        EnumConversationType[EnumConversationType["APP_PUBLIC_SERVICE"] = 7] = "APP_PUBLIC_SERVICE";
        EnumConversationType[EnumConversationType["PUBLIC_SERVICE"] = 8] = "PUBLIC_SERVICE";
    })(WidgetModule.EnumConversationType || (WidgetModule.EnumConversationType = {}));
    var EnumConversationType = WidgetModule.EnumConversationType;
    (function (MessageDirection) {
        MessageDirection[MessageDirection["SEND"] = 1] = "SEND";
        MessageDirection[MessageDirection["RECEIVE"] = 2] = "RECEIVE";
    })(WidgetModule.MessageDirection || (WidgetModule.MessageDirection = {}));
    var MessageDirection = WidgetModule.MessageDirection;
    (function (ReceivedStatus) {
        ReceivedStatus[ReceivedStatus["READ"] = 1] = "READ";
        ReceivedStatus[ReceivedStatus["LISTENED"] = 2] = "LISTENED";
        ReceivedStatus[ReceivedStatus["DOWNLOADED"] = 4] = "DOWNLOADED";
    })(WidgetModule.ReceivedStatus || (WidgetModule.ReceivedStatus = {}));
    var ReceivedStatus = WidgetModule.ReceivedStatus;
    (function (SentStatus) {
        /**
         * 发送中。
         */
        SentStatus[SentStatus["SENDING"] = 10] = "SENDING";
        /**
         * 发送失败。
         */
        SentStatus[SentStatus["FAILED"] = 20] = "FAILED";
        /**
         * 已发送。
         */
        SentStatus[SentStatus["SENT"] = 30] = "SENT";
        /**
         * 对方已接收。
         */
        SentStatus[SentStatus["RECEIVED"] = 40] = "RECEIVED";
        /**
         * 对方已读。
         */
        SentStatus[SentStatus["READ"] = 50] = "READ";
        /**
         * 对方已销毁。
         */
        SentStatus[SentStatus["DESTROYED"] = 60] = "DESTROYED";
    })(WidgetModule.SentStatus || (WidgetModule.SentStatus = {}));
    var SentStatus = WidgetModule.SentStatus;
    var AnimationType;
    (function (AnimationType) {
        AnimationType[AnimationType["left"] = 1] = "left";
        AnimationType[AnimationType["right"] = 2] = "right";
        AnimationType[AnimationType["top"] = 3] = "top";
        AnimationType[AnimationType["bottom"] = 4] = "bottom";
    })(AnimationType || (AnimationType = {}));
    (function (InputPanelType) {
        InputPanelType[InputPanelType["person"] = 0] = "person";
        InputPanelType[InputPanelType["robot"] = 1] = "robot";
        InputPanelType[InputPanelType["robotSwitchPerson"] = 2] = "robotSwitchPerson";
        InputPanelType[InputPanelType["notService"] = 4] = "notService";
    })(WidgetModule.InputPanelType || (WidgetModule.InputPanelType = {}));
    var InputPanelType = WidgetModule.InputPanelType;
    WidgetModule.MessageType = {
        DiscussionNotificationMessage: "DiscussionNotificationMessage ",
        TextMessage: "TextMessage",
        ImageMessage: "ImageMessage",
        VoiceMessage: "VoiceMessage",
        RichContentMessage: "RichContentMessage",
        HandshakeMessage: "HandshakeMessage",
        UnknownMessage: "UnknownMessage",
        SuspendMessage: "SuspendMessage",
        LocationMessage: "LocationMessage",
        InformationNotificationMessage: "InformationNotificationMessage",
        ContactNotificationMessage: "ContactNotificationMessage",
        ProfileNotificationMessage: "ProfileNotificationMessage",
        CommandNotificationMessage: "CommandNotificationMessage",
        HandShakeResponseMessage: "HandShakeResponseMessage",
        ChangeModeResponseMessage: "ChangeModeResponseMessage",
        TerminateMessage: "TerminateMessage",
        CustomerStatusUpdateMessage: "CustomerStatusUpdateMessage"
    };
    (function (PanelType) {
        PanelType[PanelType["Message"] = 1] = "Message";
        PanelType[PanelType["InformationNotification"] = 2] = "InformationNotification";
        PanelType[PanelType["System"] = 103] = "System";
        PanelType[PanelType["Time"] = 104] = "Time";
        PanelType[PanelType["getHistory"] = 105] = "getHistory";
        PanelType[PanelType["getMore"] = 106] = "getMore";
        PanelType[PanelType["Other"] = 0] = "Other";
    })(WidgetModule.PanelType || (WidgetModule.PanelType = {}));
    var PanelType = WidgetModule.PanelType;
    var ChatPanel = (function () {
        function ChatPanel(type) {
            this.panelType = type;
        }
        return ChatPanel;
    })();
    WidgetModule.ChatPanel = ChatPanel;
    var TimePanl = (function (_super) {
        __extends(TimePanl, _super);
        function TimePanl(date) {
            _super.call(this, PanelType.Time);
            this.sentTime = date;
        }
        return TimePanl;
    })(ChatPanel);
    WidgetModule.TimePanl = TimePanl;
    var GetHistoryPanel = (function (_super) {
        __extends(GetHistoryPanel, _super);
        function GetHistoryPanel() {
            _super.call(this, PanelType.getHistory);
        }
        return GetHistoryPanel;
    })(ChatPanel);
    WidgetModule.GetHistoryPanel = GetHistoryPanel;
    var GetMoreMessagePanel = (function (_super) {
        __extends(GetMoreMessagePanel, _super);
        function GetMoreMessagePanel() {
            _super.call(this, PanelType.getMore);
        }
        return GetMoreMessagePanel;
    })(ChatPanel);
    WidgetModule.GetMoreMessagePanel = GetMoreMessagePanel;
    var TimePanel = (function (_super) {
        __extends(TimePanel, _super);
        function TimePanel(time) {
            _super.call(this, PanelType.Time);
            this.sentTime = time;
        }
        return TimePanel;
    })(ChatPanel);
    WidgetModule.TimePanel = TimePanel;
    var Message = (function (_super) {
        __extends(Message, _super);
        function Message(content, conversationType, extra, objectName, messageDirection, messageId, receivedStatus, receivedTime, senderUserId, sentStatus, sentTime, targetId, messageType) {
            _super.call(this, PanelType.Message);
        }
        Message.convert = function (SDKmsg) {
            var msg = new Message();
            msg.conversationType = SDKmsg.conversationType;
            msg.extra = SDKmsg.extra;
            msg.objectName = SDKmsg.objectName;
            msg.messageDirection = SDKmsg.messageDirection;
            msg.messageId = SDKmsg.messageId;
            msg.receivedStatus = SDKmsg.receivedStatus;
            msg.receivedTime = new Date(SDKmsg.receivedTime);
            msg.senderUserId = SDKmsg.senderUserId;
            msg.sentStatus = SDKmsg.sendStatusMessage;
            msg.sentTime = new Date(SDKmsg.sentTime);
            msg.targetId = SDKmsg.targetId;
            msg.messageType = SDKmsg.messageType;
            switch (msg.messageType) {
                case WidgetModule.MessageType.TextMessage:
                    var texmsg = new TextMessage();
                    var content = SDKmsg.content.content;
                    content = Helper.discernUrlEmailInStr(content);
                    if (RongIMLib.RongIMEmoji && RongIMLib.RongIMEmoji.emojiToHTML) {
                        content = RongIMLib.RongIMEmoji.emojiToHTML(content);
                    }
                    texmsg.content = content;
                    msg.content = texmsg;
                    break;
                case WidgetModule.MessageType.ImageMessage:
                    var image = new ImageMessage();
                    var content = SDKmsg.content.content || "";
                    if (content.indexOf("base64,") == -1) {
                        content = "data:image/png;base64," + content;
                    }
                    image.content = content;
                    image.imageUri = SDKmsg.content.imageUri;
                    msg.content = image;
                    break;
                case WidgetModule.MessageType.VoiceMessage:
                    var voice = new VoiceMessage();
                    voice.content = SDKmsg.content.content;
                    voice.duration = SDKmsg.content.duration;
                    msg.content = voice;
                    break;
                case WidgetModule.MessageType.RichContentMessage:
                    var rich = new RichContentMessage();
                    rich.content = SDKmsg.content.content;
                    rich.title = SDKmsg.content.title;
                    rich.imageUri = SDKmsg.content.imageUri;
                    msg.content = rich;
                    break;
                case WidgetModule.MessageType.LocationMessage:
                    var location = new LocationMessage();
                    var content = SDKmsg.content.content || "";
                    if (content.indexOf("base64,") == -1) {
                        content = "data:image/png;base64," + content;
                    }
                    location.content = content;
                    location.latiude = SDKmsg.content.latiude;
                    location.longitude = SDKmsg.content.longitude;
                    location.poi = SDKmsg.content.poi;
                    msg.content = location;
                    break;
                case WidgetModule.MessageType.InformationNotificationMessage:
                    var info = new InformationNotificationMessage();
                    msg.panelType = 2; //灰条消息
                    info.content = SDKmsg.content.message;
                    msg.content = info;
                    break;
                case WidgetModule.MessageType.DiscussionNotificationMessage:
                    var discussion = new DiscussionNotificationMessage();
                    discussion.extension = SDKmsg.content.extension;
                    discussion.operation = SDKmsg.content.operation;
                    discussion.type = SDKmsg.content.type;
                    discussion.isHasReceived = SDKmsg.content.isHasReceived;
                    msg.content = discussion;
                case WidgetModule.MessageType.HandShakeResponseMessage:
                    var handshak = new HandShakeResponseMessage();
                    handshak.status = SDKmsg.content.status;
                    handshak.msg = SDKmsg.content.msg;
                    handshak.data = SDKmsg.content.data;
                    msg.content = handshak;
                    break;
                case WidgetModule.MessageType.ChangeModeResponseMessage:
                    var change = new ChangeModeResponseMessage();
                    change.code = SDKmsg.content.code;
                    change.data = SDKmsg.content.data;
                    change.status = SDKmsg.content.status;
                    msg.content = change;
                    break;
                case WidgetModule.MessageType.CustomerStatusUpdateMessage:
                    var up = new CustomerStatusUpdateMessage();
                    up.serviceStatus = SDKmsg.content.serviceStatus;
                    msg.content = up;
                    break;
                case WidgetModule.MessageType.TerminateMessage:
                    var ter = new TerminateMessage();
                    ter.code = SDKmsg.content.code;
                    msg.content = ter;
                    break;
                case WidgetModule.MessageType.UnknownMessage:
                    var unk = new InformationNotificationMessage();
                    msg.panelType = 2; //灰条消息
                    unk.content = "不支持此类型消息请在其他端查看";
                    msg.content = unk;
                default:
                    console.log("未处理消息类型:" + SDKmsg.messageType);
                    break;
            }
            if (msg.content) {
                msg.content.userInfo = SDKmsg.content.user;
            }
            return msg;
        };
        return Message;
    })(ChatPanel);
    WidgetModule.Message = Message;
    var UserInfo = (function () {
        function UserInfo(userId, name, portraitUri) {
            this.userId = userId;
            this.name = name;
            this.portraitUri = portraitUri;
        }
        return UserInfo;
    })();
    WidgetModule.UserInfo = UserInfo;
    var GroupInfo = (function () {
        function GroupInfo(userId, name, portraitUri) {
            this.userId = userId;
            this.name = name;
            this.portraitUri = portraitUri;
        }
        return GroupInfo;
    })();
    WidgetModule.GroupInfo = GroupInfo;
    var TextMessage = (function () {
        function TextMessage(msg) {
            msg = msg || {};
            this.content = msg.content;
            this.userInfo = msg.userInfo;
        }
        return TextMessage;
    })();
    WidgetModule.TextMessage = TextMessage;
    var HandShakeResponseMessage = (function () {
        function HandShakeResponseMessage() {
        }
        return HandShakeResponseMessage;
    })();
    WidgetModule.HandShakeResponseMessage = HandShakeResponseMessage;
    var ChangeModeResponseMessage = (function () {
        function ChangeModeResponseMessage() {
        }
        return ChangeModeResponseMessage;
    })();
    WidgetModule.ChangeModeResponseMessage = ChangeModeResponseMessage;
    var TerminateMessage = (function () {
        function TerminateMessage() {
        }
        return TerminateMessage;
    })();
    WidgetModule.TerminateMessage = TerminateMessage;
    var CustomerStatusUpdateMessage = (function () {
        function CustomerStatusUpdateMessage() {
        }
        return CustomerStatusUpdateMessage;
    })();
    WidgetModule.CustomerStatusUpdateMessage = CustomerStatusUpdateMessage;
    var InformationNotificationMessage = (function () {
        function InformationNotificationMessage() {
        }
        return InformationNotificationMessage;
    })();
    WidgetModule.InformationNotificationMessage = InformationNotificationMessage;
    var ImageMessage = (function () {
        function ImageMessage() {
        }
        return ImageMessage;
    })();
    WidgetModule.ImageMessage = ImageMessage;
    var VoiceMessage = (function () {
        function VoiceMessage() {
        }
        return VoiceMessage;
    })();
    WidgetModule.VoiceMessage = VoiceMessage;
    var LocationMessage = (function () {
        function LocationMessage() {
        }
        return LocationMessage;
    })();
    WidgetModule.LocationMessage = LocationMessage;
    var RichContentMessage = (function () {
        function RichContentMessage() {
        }
        return RichContentMessage;
    })();
    WidgetModule.RichContentMessage = RichContentMessage;
    var DiscussionNotificationMessage = (function () {
        function DiscussionNotificationMessage() {
        }
        return DiscussionNotificationMessage;
    })();
    WidgetModule.DiscussionNotificationMessage = DiscussionNotificationMessage;
    var Conversation = (function () {
        function Conversation(targetType, targetId, title) {
            this.targetType = targetType;
            this.targetId = targetId;
            this.title = title || "";
        }
        Conversation.onvert = function (item) {
            var conver = new Conversation();
            conver.targetId = item.targetId;
            conver.targetType = item.conversationType;
            conver.title = item.conversationTitle;
            conver.portraitUri = item.senderPortraitUri;
            conver.unreadMessageCount = item.unreadMessageCount;
            return conver;
        };
        return Conversation;
    })();
    WidgetModule.Conversation = Conversation;
    var userAgent = window.navigator.userAgent;
    var Helper = (function () {
        function Helper() {
        }
        Helper.timeCompare = function (first, second) {
            var pre = first.toString();
            var cur = second.toString();
            return pre.substring(0, pre.lastIndexOf(":")) == cur.substring(0, cur.lastIndexOf(":"));
        };
        Helper.discernUrlEmailInStr = function (str) {
            var html;
            var EMailReg = /\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/gi;
            var EMailArr = [];
            html = str.replace(EMailReg, function (str) {
                EMailArr.push(str);
                return '[email`' + (EMailArr.length - 1) + ']';
            });
            var URLReg = /(((ht|f)tp(s?))\:\/\/)?((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|(www.|[a-zA-Z].)[a-zA-Z0-9\-\.]+\.(com|cn|edu|gov|mil|net|org|biz|info|name|museum|us|ca|uk|me|im))(\:[0-9]+)*(\/($|[a-zA-Z0-9\.\,\;\?\'\\\+&amp;%\$#\=~_\-]+))*/gi;
            html = html.replace(URLReg, function (str, $1) {
                if ($1) {
                    return '<a target="_blank" href="' + str + '">' + str + '</a>';
                }
                else {
                    return '<a target="_blank" href="//' + str + '">' + str + '</a>';
                }
            });
            for (var i = 0, len = EMailArr.length; i < len; i++) {
                html = html.replace('[email`' + i + ']', '<a href="mailto:' + EMailArr[i] + '">' + EMailArr[i] + '<a>');
            }
            return html;
        };
        Helper.checkType = function (obj) {
            var type = Object.prototype.toString.call(obj);
            return type.substring(8, type.length - 1).toLowerCase();
        };
        Helper.browser = {
            version: (userAgent.match(/.+(?:rv|it|ra|chrome|ie)[\/: ]([\d.]+)/) || [0, '0'])[1],
            safari: /webkit/.test(userAgent),
            opera: /opera|opr/.test(userAgent),
            msie: /msie|trident/.test(userAgent) && !/opera/.test(userAgent),
            chrome: /chrome/.test(userAgent),
            mozilla: /mozilla/.test(userAgent) && !/(compatible|webkit|like gecko)/.test(userAgent)
        };
        Helper.getFocus = function (obj) {
            obj.focus();
            if (obj.createTextRange) {
                var rtextRange = obj.createTextRange();
                rtextRange.moveStart('character', obj.value.length);
                rtextRange.collapse(true);
                rtextRange.select();
            }
            else if (obj.selectionStart) {
                obj.selectionStart = obj.value.length;
            }
            else if (window.getSelection && obj.lastChild) {
                var sel = window.getSelection();
                var tempRange = document.createRange();
                if (WidgetModule.Helper.browser.msie) {
                    tempRange.setStart(obj.lastChild, obj.lastChild.length);
                }
                else {
                    tempRange.setStart(obj.firstChild, obj.firstChild.length);
                }
                sel.removeAllRanges();
                sel.addRange(tempRange);
            }
        };
        Helper.ImageHelper = {
            getThumbnail: function (obj, area, callback) {
                var canvas = document.createElement("canvas"), context = canvas.getContext('2d');
                var img = new Image();
                img.onload = function () {
                    var target_w;
                    var target_h;
                    var imgarea = img.width * img.height;
                    if (imgarea > area) {
                        var scale = Math.sqrt(imgarea / area);
                        scale = Math.ceil(scale * 100) / 100;
                        target_w = img.width / scale;
                        target_h = img.height / scale;
                    }
                    else {
                        target_w = img.width;
                        target_h = img.height;
                    }
                    canvas.width = target_w;
                    canvas.height = target_h;
                    context.drawImage(img, 0, 0, target_w, target_h);
                    try {
                        var _canvas = canvas.toDataURL("image/jpeg", 0.5);
                        _canvas = _canvas.substr(23);
                        callback(obj, _canvas);
                    }
                    catch (e) {
                        callback(obj, null);
                    }
                };
                img.src = WidgetModule.Helper.ImageHelper.getFullPath(obj);
            },
            getFullPath: function (file) {
                window.URL = window.URL || window.webkitURL;
                if (window.URL && window.URL.createObjectURL) {
                    return window.URL.createObjectURL(file);
                }
                else {
                    return null;
                }
            }
        };
        return Helper;
    })();
    WidgetModule.Helper = Helper;
})(WidgetModule || (WidgetModule = {}));
var SDKServer = angular.module("RongIMSDKModule", []);
SDKServer.factory("RongIMSDKServer", ["$q", function ($q) {
        var RongIMSDKServer = {};
        RongIMSDKServer.init = function (appkey) {
            RongIMLib.RongIMClient.init(appkey);
        };
        RongIMSDKServer.connect = function (token) {
            var defer = $q.defer();
            RongIMLib.RongIMClient.connect(token, {
                onSuccess: function (data) {
                    defer.resolve(data);
                },
                onTokenIncorrect: function () {
                    defer.reject({ tokenError: true });
                },
                onError: function (errorCode) {
                    defer.reject({ errorCode: errorCode });
                    var info = '';
                    switch (errorCode) {
                        case RongIMLib.ErrorCode.TIMEOUT:
                            info = '连接超时';
                            break;
                        case RongIMLib.ErrorCode.UNKNOWN:
                            info = '未知错误';
                            break;
                        case RongIMLib.ConnectionState.UNACCEPTABLE_PROTOCOL_VERSION:
                            info = '不可接受的协议版本';
                            break;
                        case RongIMLib.ConnectionState.IDENTIFIER_REJECTED:
                            info = 'appkey不正确';
                            break;
                        case RongIMLib.ConnectionState.SERVER_UNAVAILABLE:
                            info = '服务器不可用';
                            break;
                        case RongIMLib.ConnectionState.NOT_AUTHORIZED:
                            info = '未认证';
                            break;
                        case RongIMLib.ConnectionState.REDIRECT:
                            info = '重新获取导航';
                            break;
                        case RongIMLib.ConnectionState.APP_BLOCK_OR_DELETE:
                            info = '应用已被封禁或已被删除';
                            break;
                        case RongIMLib.ConnectionState.BLOCK:
                            info = '用户被封禁';
                            break;
                    }
                    console.log("失败:" + info);
                }
            });
            return defer.promise;
        };
        RongIMSDKServer.getInstance = function () {
            return RongIMLib.RongIMClient.getInstance();
        };
        RongIMSDKServer.setOnReceiveMessageListener = function (option) {
            RongIMLib.RongIMClient.setOnReceiveMessageListener(option);
        };
        RongIMSDKServer.setConnectionStatusListener = function (option) {
            RongIMLib.RongIMClient.setConnectionStatusListener(option);
        };
        RongIMSDKServer.sendMessage = function (conver, targetId, content) {
            var defer = $q.defer();
            RongIMLib.RongIMClient.getInstance().sendMessage(+conver, targetId, content, {
                onSuccess: function (data) {
                    defer.resolve(data);
                },
                onError: function (errorCode, message) {
                    defer.reject({ errorCode: errorCode, message: message });
                    var info = '';
                    switch (errorCode) {
                        case RongIMLib.ErrorCode.TIMEOUT:
                            info = '超时';
                            break;
                        case RongIMLib.ErrorCode.UNKNOWN:
                            info = '未知错误';
                            break;
                        case RongIMLib.ErrorCode.REJECTED_BY_BLACKLIST:
                            info = '在黑名单中，无法向对方发送消息';
                            break;
                        case RongIMLib.ErrorCode.NOT_IN_DISCUSSION:
                            info = '不在讨论组中';
                            break;
                        case RongIMLib.ErrorCode.NOT_IN_GROUP:
                            info = '不在群组中';
                            break;
                        case RongIMLib.ErrorCode.NOT_IN_CHATROOM:
                            info = '不在聊天室中';
                            break;
                        default:
                            info = "";
                            break;
                    }
                    console.log('发送失败:' + info);
                }
            });
            return defer.promise;
        };
        RongIMSDKServer.reconnect = function (callback) {
            RongIMLib.RongIMClient.reconnect(callback);
        };
        RongIMSDKServer.clearUnreadCount = function (type, targetid) {
            var defer = $q.defer();
            RongIMLib.RongIMClient.getInstance().clearUnreadCount(type, targetid, {
                onSuccess: function (data) {
                    defer.resolve(data);
                },
                onError: function (error) {
                    defer.reject(error);
                }
            });
            return defer.promise;
        };
        RongIMSDKServer.getTotalUnreadCount = function () {
            var defer = $q.defer();
            RongIMLib.RongIMClient.getInstance().getTotalUnreadCount({
                onSuccess: function (num) {
                    defer.resolve(num);
                },
                onError: function () {
                    defer.reject();
                }
            });
            return defer.promise;
        };
        RongIMSDKServer.getConversationList = function () {
            var defer = $q.defer();
            RongIMLib.RongIMClient.getInstance().getConversationList({
                onSuccess: function (data) {
                    defer.resolve(data);
                },
                onError: function (error) {
                    defer.reject(error);
                }
            }, null);
            return defer.promise;
        };
        // RongIMSDKServer.conversationList = function() {
        //     return RongIMLib.RongIMClient._memoryStore.conversationList;
        //     // return RongIMLib.RongIMClient.conversationMap.conversationList;
        // }
        RongIMSDKServer.removeConversation = function (type, targetId) {
            var defer = $q.defer();
            RongIMLib.RongIMClient.getInstance().removeConversation(type, targetId, {
                onSuccess: function (data) {
                    defer.resolve(data);
                },
                onError: function (error) {
                    defer.reject(error);
                }
            });
            return defer.promise;
        };
        RongIMSDKServer.createConversation = function (type, targetId, title) {
            RongIMLib.RongIMClient.getInstance().createConversation(type, targetId, title);
        };
        RongIMSDKServer.getConversation = function (type, targetId) {
            var defer = $q.defer();
            RongIMLib.RongIMClient.getInstance().getConversation(type, targetId, {
                onSuccess: function (data) {
                    defer.resolve(data);
                },
                onError: function () {
                    defer.reject();
                }
            });
            return defer.promise;
        };
        RongIMSDKServer.getDraft = function (type, targetId) {
            return RongIMLib.RongIMClient.getInstance().getTextMessageDraft(type, targetId) || "";
        };
        RongIMSDKServer.setDraft = function (type, targetId, value) {
            return RongIMLib.RongIMClient.getInstance().saveTextMessageDraft(type, targetId, value);
        };
        RongIMSDKServer.clearDraft = function (type, targetId) {
            return RongIMLib.RongIMClient.getInstance().clearTextMessageDraft(type, targetId);
        };
        RongIMSDKServer.getHistoryMessages = function (type, targetId, num) {
            var defer = $q.defer();
            RongIMLib.RongIMClient.getInstance().getHistoryMessages(type, targetId, null, num, {
                onSuccess: function (data, has) {
                    defer.resolve({
                        data: data,
                        has: has
                    });
                },
                onError: function (error) {
                    defer.reject(error);
                }
            });
            return defer.promise;
        };
        RongIMSDKServer.disconnect = function () {
            RongIMLib.RongIMClient.getInstance().disconnect();
        };
        RongIMSDKServer.logout = function () {
            if (RongIMLib && RongIMLib.RongIMClient) {
                RongIMLib.RongIMClient.getInstance().logout();
            }
        };
        RongIMSDKServer.voice = {
            init: function () {
                // RongIMLib.voice.init()
            },
            play: function (content, time) {
                RongIMLib.voice.play(content, time);
            }
        };
        return RongIMSDKServer;
    }]);

angular.module('RongWebIMWidget').run(['$templateCache', function($templateCache) {
  'use strict';

  $templateCache.put('./ts/conversation/conversation.tpl.html',
    "<div id=rong-conversation class=\"rongcloud-main rongcloud-kefuList\" ng-show=showSelf><evaluatedir type=evaluate.type display=evaluate.showevaluate confirm=evaluate.onConfirm(data) cancle=evaluate.onCancle()></evaluatedir><div class=\"rongcloud-main_inner rongcloud-clearfix\"><header class=rongcloud-header><a class=rongcloud-icon_return href=\"javascript:void 0\" ng-click=close()></a><div class=rongcloud-title><a class=\"rongcloud-title_name rongcloud-online\"><i class=rongcloud-Presence></i>{{currentConversation.title}}</a></div><a href=\"javascript:void 0\"></a></header><div id=wrapper ng-iscroll ng-style=wrapperbottom><div id=scroller><div id=Messages><div class=\"rongcloud-MessagesInner rongcloud-message-scroll\"><div class=rongcloud-Message-wrapper><div ng-repeat=\"item in messageList\" ng-switch=item.panelType><div class=rongcloud-Messages-date ng-switch-when=104><b>{{item.sentTime|historyTime}}</b></div><div class=rongcloud-Messages-date ng-switch-when=105><b my-tap=getHistory()>查看历史消息</b></div><div class=rongcloud-Messages-date ng-switch-when=106><b my-tap=getMoreMessage()>获取更多消息</b></div><div class=rongcloud-sys-tips ng-switch-when=2><span>{{item.content.content}}</span></div><div class=\"rongcloud-Message rongcloud-status1\" ng-switch-when=1 ng-class=\"{'rongcloud-youMsg':item.messageDirection==2,'rongcloud-myMsg':item.messageDirection==1}\"><div class=rongcloud-Messages-unreadLine></div><div><div class=rongcloud-Message-header><a href=\"index.php?g=portal&m=doctor&a=details_s&id={{item.content.userInfo.userId}}\" ><img class=\"rongcloud-img rongcloud-u-isActionable rongcloud-Message-avatar rongcloud-avatar\" ng-src=\"{{item.content.userInfo.portraitUri||'../themes/dp/Rongcloud/images/webBg.png'}}\" src=../themes/dp/Rongcloud/images/barBg.png alt=\"\"></a></div></div><div class=rongcloud-Message-body ng-switch=item.messageType><textmessage ng-switch-when=TextMessage msg=item.content></textmessage><imagemessage ng-switch-when=ImageMessage msg=item.content></imagemessage><voicemessage ng-switch-when=VoiceMessage msg=item.content></voicemessage><locationmessage ng-switch-when=LocationMessage msg=item.content></locationmessage><richcontentmessage ng-switch-when=RichContentMessage msg=item.content></richcontentmessage></div></div></div></div></div></div></div></div><footer class=\"rongcloud-footer rongcloud-clearfix\"><div id=funcPanel style=\"display: flex;display: -webkit-flex\"><a id=uploadfile href=# class=\"rongcloud-pull-left rongcloud-message_type_btn\" ng-show=\"_inputPanelState==0\"><i class=\"rongcloud-sprite2 rongcloud-icon_message_type1\"></i></a><a href=# class=\"rongcloud-pull-left rongcloud-message_type2_btn\" ng-click=switchPerson() ng-show=\"_inputPanelState==2\"><span>转人工服务</span></a><div class=rongcloud-message_wrap><textarea id=inputMsg ng-focus=\"showemoji=false\" ctrl-enter-keys fun=send() ctrlenter=false ondrop=\"return false\" ng-model=currentConversation.messageContent placeholder=请输入文字...></textarea></div><a href=# class=\"rongcloud-pull-right rongcloud-message_type_btn rongcloud-message_type_btn2\" ng-show=\"_inputPanelState==0\"><i class=\"rongcloud-sprite2 rongcloud-message_emoji_btn\" ng-click=\"showemoji=!showemoji\"></i></a><a href=\"\" class=rongcloud-send_btn  ng-click=send()>发送</a></div><div class=rongcloud-pub-faces ng-show=showemoji id=emj><swipe-emoji content=currentConversation></swipe-emoji></div><div class=rongcloud-pub-faces ng-show=showemoji id=voice><ul><li class=voice_pressure><a href=\"\" ng-click=end_chat()><img src=../themes/dp/Rongcloud/images/close_chat.png ><span>结束咨询</span></a></li></ul></div><div class=\"rongcloud-footerBtn rongcloud-clearfix\" ng-show=showemoji id=subsp><ul class=\"rongcloud-clearfix rongcloud-emojiWrap\"><a href=#  ng-click=showEmj()><li class=rongcloud-sprite2></li></a></ul></div></footer></div></div>"
  );


  $templateCache.put('./ts/conversation/swipeEmoji.tpl.html',
    "<div class=rongcloud-swipe id=slider><div class=rongcloud-swipe-wrap><figure ng-repeat=\"item in data\"><emoji ng-repeat=\"it in item.emojis\" item=it content=$parent.content></emoji><span class=rongcloud-delete-emoji index=-1 alt=\"\" ng-click=delete()></span></figure></div><ol id=position class=rongcloud-ui-carousel-indicators><li ng-repeat=\"item in data\" ng-class=\"{'rongcloud-js-active':item.show}\"></li></ol></div>"
  );


  $templateCache.put('./ts/conversationlist/conversationList.tpl.html',
    "<div id=rong-conversation-list class=\"rongcloud-main rongcloud-kefuList\" ng-show=conversationListServer.showSelf><div class=\"rongcloud-main_inner rongcloud-clearfix\"><header class=rongcloud-header><a class=rongcloud-icon_return href=\"javascript:void 0\"></a><div class=\"rongcloud-title rongcloud-title-f\"><a class=\"rongcloud-title_name rongcloud-online\"><i class=rongcloud-Presence></i>最近联系人</a></div><a href=\"javascript:void 0\" ng-click=setconversation_kefu()><i class='chat'>咨询</i></a></header><div class=rongcloud-chatBox id=chatBox><div class=rongcloud-chatArea><div class=rongcloud-chatList><conversation-item ng-repeat=\"item in conversationListServer.conversationList\" item=item></conversation-item></div></div></div></div></div>"
  );


  $templateCache.put('./ts/evaluate/evaluate.tpl.html',
    "<div class=rongcloud-layermbox ng-show=display><div class=rongcloud-laymshade></div><div class=rongcloud-layermmain><div class=rongcloud-section><div class=rongcloud-layermchild ng-show=!end><div class=rongcloud-layermcont><div class=rongcloud-type1 ng-show=\"type==1\"><h4>&nbsp;评价客服</h4><div class=rongcloud-layerPanel1><div class=rongcloud-star><span ng-repeat=\"item in stars track by $index\"><span ng-class=\"{'rongcloud-star-on':$index<data.stars,'rongcloud-star-off':$index>=data.stars}\" ng-click=confirm($index+1)></span></span></div></div></div><div class=rongcloud-type2 ng-show=\"type==2\"><h4>&nbsp;&nbsp;是否解决了您的问题 ？</h4><div class=rongcloud-layerPanel1><a class=\"rongcloud-btn rongcloud-btnY\" ng-class=\"{'rongcloud-cur':data.value===true}\" href=javascript:void(0); ng-click=confirm(true)>是</a> <a class=\"rongcloud-btn rongcloud-btnN\" ng-class=\"{'rongcloud-cur':data.value===false}\" href=javascript:void(0); ng-click=confirm(false)>否</a></div></div><div class=rongcloud-layerPanel2 ng-show=displayDescribe><p>是否有以下情况 ？</p><div class=rongcloud-labels><span ng-repeat=\"item in labels\"><a class=rongcloud-btn ng-class=\"{'rongcloud-cur':data.label==item}\" ng-click=\"data.label=item\" href=\"\">{{item}}</a></span></div><div class=rongcloud-suggestBox><textarea name=\"\" placeholder=欢迎给我们的服务提建议~ ng-model=data.describe></textarea></div><div class=rongcloud-subBox><a class=rongcloud-btn href=\"\" ng-click=commit()>提交评价</a></div></div></div><div class=rongcloud-layermbtn><span ng-click=confirm()>跳过</span><span ng-click=cancle()>取消</span></div></div><div class=\"rongcloud-layermchild rongcloud-feedback\" ng-show=end><div class=rongcloud-layermcont>感谢您的反馈 ^ - ^ ！</div></div></div></div></div>"
  );


  $templateCache.put('./ts/main.tpl.html',
    "<div><div ng-show=main.display><rong-conversation></rong-conversation><rong-conversation-list></rong-conversation-list></div></div>"
  );

}]);
