/// <reference path="../typings/tsd.d.ts"/>

var widget = angular.module("RongWebIMWidget");

widget.service("RongCustomerService", ["WebIMWidget", function(WebIMWidget: WebIMWidget) {
    var cs = <ICustomerService>{};
    var defaultconfig = <any>{
        __isCustomerService: true
    };

    cs.init = function(config) {
        angular.extend(defaultconfig, config)
        cs._config = config;

        WebIMWidget.init({
            appkey: config.appkey,
            token: config.token,
            onSuccess: function(e) {
                config.onSuccess && config.onSuccess(e);
            },
        });
        WebIMWidget.onShow = function() {
            WebIMWidget.setConversation(WidgetModule.EnumConversationType.CUSTOMER_SERVICE, config.customerServiceId, "客服");
        }
        WebIMWidget.onCloseBefore = function(obj) {
            obj.close({ showEvaluate: true });
        }

    }

    cs.show = function() {
        WebIMWidget.show();
    }

    cs.hidden = function() {
        WebIMWidget.hidden();
    }

    cs.Postion = Postion;

    return cs;
}]);

interface ICustomerService {
    init(config: CustomerServiceConfig): void
    show(): void
    hidden(): void
    Postion: any
    _config: any
}

interface CustomerServiceConfig {
    appkey: string
    token: string
    customerServiceId: string
    position: Postion
    onSuccess(par?: any): void
}
enum Postion {
    left = 1, right = 2
}
