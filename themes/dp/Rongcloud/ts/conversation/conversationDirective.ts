/// <reference path="../../typings/tsd.d.ts"/>

var conversationDirective = angular.module("RongWebIMWidget.conversationDirective", ["RongWebIMWidget.conversationController"]);

conversationDirective.directive("rongConversation", [function() {

    return {
        restrict: "E",
        templateUrl: "./ts/conversation/conversation.tpl.html",
        controller: "conversationController",
        link: function(scope: any, ele: angular.IRootElementService) {
            //安卓上取消键盘输入框不失去焦点
            angular.element(document).bind("touchstart", function(e) {
                var inputMsg=document.getElementById("inputMsg");
                if(e.target != inputMsg){
                  inputMsg.blur();
                }

            })
        }
    }
}]);

conversationDirective.directive("swipeEmoji", [function() {
    return {
        restrict: "E",
        scope: {
            content: "="
        },
        templateUrl: './ts/conversation/swipeEmoji.tpl.html',
        link: function(scope: any, ele: angular.IRootElementService, attr: angular.IAttributes) {
            var data = [{ emojis: [], current: true }]

            var bullets = document.getElementById('position').getElementsByTagName('li');

            scope.data = [];
            var emojiList = null;

            scope.$parent.$watch("showemoji", function(newVal, oldVal) {
                if (newVal === oldVal)
                    return;
                if (!emojiList) {
                    emojiList = RongIMLib.RongIMEmoji.emojis.slice(0, 84).concat();
                    while (emojiList.length) {
                        scope.data.push({
                            emojis: emojiList.splice(0, 23),
                            show: false
                        })
                    }
                    scope.data[0].show = true;
                    setTimeout(function() {
                        var swipe = new window.Swipe(document.getElementById('slider'), {
                            continuous: true,
                            callback: function(pos) {
                                var i = bullets.length;
                                while (i--) {
                                    scope.data[i].show = false;
                                }
                                scope.data[pos].show = true;
                                scope.$apply()
                            }
                        });
                    }, 500);

                }
            });


            scope.delete = function() {
                var reg = /\[[\u4e00-\u9fa5]+\]$/
                if (reg.test(scope.content.messageContent)) {
                    scope.content.messageContent = scope.content.messageContent.replace(reg, function() {
                        return "";
                    });
                } else {
                    scope.content.messageContent = scope.content.messageContent.substr(0, scope.content.messageContent.length - 1);
                }
            }
        }
    }
}]);

conversationDirective.directive("emoji", [function() {
    return {
        restrict: "E",
        scope: {
            item: "=",
            content: "="
        },
        template: '<div style="display:inline-block"></div>',
        link: function(scope: any, ele: angular.IRootElementService, attr: angular.IAttributes) {

            ele.find("div").append(scope.item);
            ele.on("click", function(e) {
                scope.content.messageContent = scope.content.messageContent || "";
                scope.content.messageContent = scope.content.messageContent.replace(/\n$/, "");
                scope.content.messageContent = scope.content.messageContent + scope.item.children[0].getAttribute("name");
                scope.$parent.$apply();
                e.preventDefault();
            })

        }
    }
}]);

conversationDirective.directive('contenteditableDire', function() {
    return {
        restrict: 'A',
        require: '?ngModel',
        link: function(scope: any, element: angular.IRootElementService, attrs: angular.IAttributes, ngModel: angular.INgModelController) {
            function replacemy(e: string) {
                return e.replace(new RegExp("<[\\s\\S.]*?>", "ig"), "");
            }
            var domElement = <any>element[0];

            scope.$watch(function() {
                return ngModel.$modelValue;
            }, function(newVal: string) {
                if (document.activeElement === domElement) {
                    return;
                }
                if (newVal === '' || newVal === attrs["placeholder"]) {
                    domElement.innerHTML = attrs["placeholder"];
                    domElement.style.color = "#a9a9a9";
                }
            });
            element.bind('focus', function() {
                if (domElement.innerHTML == attrs["placeholder"]) {
                    domElement.innerHTML = '';
                }
                domElement.style.color = '';
            });
            element.bind('blur', function() {
                if (domElement.innerHTML === '') {
                    domElement.innerHTML = attrs["placeholder"];
                    domElement.style.color = "#a9a9a9";
                }
            });


            if (!ngModel) return;

            element.bind("paste", function(e: any) {
                var that = this, ohtml = that.innerHTML;
                timeoutid && clearTimeout(timeoutid);
                var timeoutid = setTimeout(function() {
                    that.innerHTML = replacemy(that.innerHTML);
                    ngModel.$setViewValue(that.innerHTML);
                    timeoutid = null;
                }, 50);
            });

            ngModel.$render = function() {
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

conversationDirective.directive("ctrlEnterKeys", ["$timeout", function($timeout: angular.ITimeoutService) {
    return {
        restrict: "A",
        require: '?ngModel',
        scope: {
            fun: "&",
            ctrlenter: "=",
            content: "="
        },
        link: function(scope: any, element: angular.IRootElementService, attrs: angular.IAttributes, ngModel: angular.INgModelController) {
            scope.ctrlenter = scope.ctrlenter || false;
            element.bind("keypress", function(e: any) {
                if (scope.ctrlenter) {
                    if (e.ctrlKey === true && e.keyCode === 13 || e.keyCode === 10) {
                        scope.fun();
                        scope.$parent.$apply();
                        e.preventDefault();
                    }
                } else {
                    if (e.ctrlKey === false && e.shiftKey === false && (e.keyCode === 13 || e.keyCode === 10)) {
                        scope.fun();
                        scope.$parent.$apply();
                        e.preventDefault();
                    } else if (e.ctrlKey === true && e.keyCode === 13 || e.keyCode === 10) {
                        //ctrl+enter 换行
                    }
                }
            })
        }
    }
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
conversationDirective.directive("textmessage", [function() {
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
    }
}]);

conversationDirective.directive("imagemessage", [function() {
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
        link: function(scope: any, ele: angular.IRootElementService, attr: any) {
            var img = new Image();
            img.src = scope.msg.imageUri;
            setTimeout(function() {
                $('#rebox_' + scope.$id).rebox({ selector: 'a' }).bind("rebox:open", function() {
                    //jQuery rebox 点击空白关闭
                    var rebox = <any>document.getElementsByClassName("rebox")[0];
                    rebox.onclick = function(e: any) {
                        if (e.target.tagName.toLowerCase() != "img") {
                            var rebox_close = <any>document.getElementsByClassName("rebox-close")[0];
                            rebox_close.click();
                            rebox = null; rebox_close = null;
                        }
                    }
                });
            })
            img.onload = function() {
                // scope.$apply(function() {
                //     scope.msg.content = scope.msg.imageUri
                // });
            }
            scope.showBigImage = function() {

            }
        }
    }
}])

conversationDirective.directive("includinglinkmessage", [function() {
    return {
        restrict: "E",
        scope: { msg: "=" },
        template: '<div class="">' +
        '<div class="rongcloud-Message-text">' +
        '<pre class="rongcloud-Message-entry" style="">' +
        '维护中 由于我们的服务商出现故障，融云官网及相关服务也受到影响，给各位用户带来的不便，还请谅解。  您可以通过 <a href="#">【官方微博】</a>了解</pre>' +
        '</div>' +
        '</div>'
    }
}]);


conversationDirective.directive("voicemessage", ["$timeout", function($timeout: angular.ITimeoutService) {
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
        link: function(scope, ele, attr) {
            scope.msg.duration = parseInt(scope.msg.duration || scope.msg.content.length / 1024);

            RongIMLib.RongIMVoice.preLoaded(scope.msg.content);

            scope.play = function() {
                RongIMLib.RongIMVoice.stop(scope.msg.content);
                if (!scope.isplaying) {
                    scope.msg.isUnReade = false;
                    RongIMLib.RongIMVoice.play(scope.msg.content, scope.msg.duration);
                    scope.isplaying = true;
                    if (scope.timeoutid) {
                        $timeout.cancel(scope.timeoutid);
                    }
                    scope.timeoutid = $timeout(function() {
                        scope.isplaying = false;
                    }, scope.msg.duration * 1000);
                } else {
                    scope.isplaying = false;
                    $timeout.cancel(scope.timeoutid);
                }
            }

        }
    }
}]);


conversationDirective.directive("locationmessage", [function() {
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
    }
}]);

conversationDirective.directive("richcontentmessage", [function() {
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
    }
}]);
