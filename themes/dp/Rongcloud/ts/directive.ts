var directive = angular.module("RongWebIMWidget.directive", []);

directive.filter('trustHtml', ["$sce", function($sce: angular.ISCEService) {
    return function(str: any) {
        return $sce.trustAsHtml(str);
    }
}]);

directive.filter("historyTime", ["$filter", function($filter: angular.IFilterService) {
    return function(time: Date) {
        var today = new Date();
        if (time.toDateString() === today.toDateString()) {
            return $filter("date")(time, "HH:mm");
        } else if (time.toDateString() === new Date(today.setTime(today.getTime() - 1)).toDateString()) {
            return "昨天" + $filter("date")(time, "HH:mm");
        } else {
            return $filter("date")(time, "yyyy-MM-dd HH:mm");
        }
    };
}]);

directive.directive("myTap", [function() {
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

    return function(scope, ele, attrs) {
        if (isTouch) {
            var valid;
            ele.bind("touchstart", function() {
                valid = true;
            });

            ele.bind("touchend", function() {
                if (valid) {
                    scope.$apply(attrs.myTap);
                }
                valid = false;
            });
        } else {
            ele.bind("click", function() {
                scope.$apply(attrs.myTap);
            })
        }


    }
}]);
