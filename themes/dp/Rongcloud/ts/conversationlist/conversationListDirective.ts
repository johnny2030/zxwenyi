/// <reference path="../../typings/tsd.d.ts"/>

var conversationListDir = angular.module("RongWebIMWidget.conversationListDirective", ["RongWebIMWidget.conversationListController"]);

conversationListDir.directive("rongConversationList", [function() {

    return {
        restrict: "E",
        templateUrl: "./ts/conversationlist/conversationList.tpl.html",
        controller: "conversationListController",
        link: function(scope: any, ele: angular.IRootElementService) {

        }
    }
}]);

conversationListDir.directive("conversationItem", ["conversationServer", "conversationListServer",
    function(conversationServer: ConversationServer, conversationListServer: conversationListServer) {
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
            link: function(scope: any, ele: angular.IRootElementService, attr: angular.IAttributes) {
                var item = <any>ele[0].querySelector(".rongcloud-chat_item");
                var deletebox = ele[0].querySelector(".rongcloud-delete_box");
                var start, left, width = deletebox.clientWidth;
                var Emove = function(e) {
                    var move = e.changedTouches[0].clientX - start;
                    var marginleft = left + move;
                    if (marginleft < 0 && marginleft > -width) {
                        item.style["margin-left"] = marginleft + "px";
                    } else if (marginleft > 0) {
                        item.style["margin-left"] = 0 + "px";
                    } else if (marginleft < -width) {
                        item.style["margin-left"] = -width + "px";
                    }
                }
                item.addEventListener("touchstart", function(e) {
                    width = deletebox.clientWidth;
                    start = (e.changedTouches[0].clientX);
                    item.className = "rongcloud-chat_item";
                    left = parseFloat(item.style["margin-left"]) || 0;
                    document.addEventListener("touchmove", Emove);
                    document.addEventListener("touchend", End);
                });

                var End = function(e) {
                    var move = e.changedTouches[0].clientX - start;
                    var marginleft = left + move;
                    if (marginleft > -width / 2) {
                        item.className = "rongcloud-chat_item rongcloud-chat_item_m rongcloud-normal";
                        item.style["margin-left"] = "0px";
                    } else {
                        item.className = "rongcloud-chat_item rongcloud-chat_item_m rongcloud-remove";
                        item.style["margin-left"] = -width + "px";
                    }
                    document.removeEventListener("touchmove", Emove);
                    document.removeEventListener("touchend", End);
                }



                ele.on("click", function() {
                    conversationServer.onConversationChangged(new WidgetModule.Conversation(scope.item.targetType, scope.item.targetId, scope.item.title))
                    RongIMLib.RongIMClient.getInstance().clearUnreadCount(scope.item.targetType, scope.item.targetId, {
                        onSuccess: function() {

                        },
                        onError: function() {

                        }
                    })
                    conversationListServer.updateConversations();
                });

                scope.remove = function(e) {
                    e.stopPropagation();

                    RongIMLib.RongIMClient.getInstance().removeConversation(scope.item.targetType, scope.item.targetId, {
                        onSuccess: function() {
                            // if (conversationServer.current && conversationServer.current.targetType == scope.item.targetType && conversationServer.current.targetId == scope.item.targetId) {
                            //     conversationServer.onConversationChangged(new WidgetModule.Conversation());
                            // }
                            conversationListServer.updateConversations();
                        },
                        onError: function(error) {
                            console.log(error);
                        }
                    });

                }
            }
        }
    }]);
