var demo = angular.module("demo", ["RongWebIMWidget"]);
demo.controller("main", ["$scope",'$http', "WebIMWidget", function($scope,$http,
    WebIMWidget) {
    $scope.targetType = 1; //1：私聊 更多会话类型查看http://www.rongcloud.cn/docs/api/js/global.html#ConversationType
    $http({
        method:'GET',
        url:'/zxwenyi/index.php',
        params:{
            'g':'portal',
            'm':'rong',
            'a':'get_data'
        }
    }).success(function (res) {
        $scope.targetId = res.sendUser.id;
        $scope.name = res.sendUser.name;
        stat(res);
    }).error(function (error) {
        alert("请求失败");
    });
    function stat(data){
        WebIMWidget.init({
            appkey: data.appkey,
            token: data.token,
            displayConversationList:true,
        });
    };
    $scope.setconversation = function() {
        WebIMWidget.setConversation($scope.targetType, $scope.targetId, $scope.name);
    };
    $scope.show = function () {
        WebIMWidget.show();
    };
    WebIMWidget.setUserInfoProvider(function(targetId,obj){
        $http({
            method:'GET',
            url:'/zxwenyi/index.php',
            params:{
                'g':'portal',
                'm':'rong',
                'a':'get_user',
                'userId':targetId
            }
        }).success(function (res) {
            obj.onSuccess({name:res.name,userId:res.id,portraitUri:res.photo});
        }).error(function (error) {
            alert("请求失败");
        })
    });
}]);
