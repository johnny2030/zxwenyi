var demo = angular.module("demo", ["RongWebIMWidget"]);

demo.controller("main", ["$scope", "WebIMWidget", "$http", function($scope, WebIMWidget, $http) {

    $scope.targetType = 1; //1：私聊 更多会话类型查看http://www.rongcloud.cn/docs/api/js/global.html#ConversationType
    $http({
        method:'GET',
        url:"http://tieqiao.zzzpsj.com/index.php?g=Admin&m=Chat&a=get_data",
        params:{}
    }).success(function (res) {
        $scope.targetId = res.manager_user.id;
        $scope.name = res.manager_user.name;
        stat(res);
    }).error(function (error) {
        alert("请求失败");
    });
    function stat(data){
        var config = {
            appkey: data.appkey,
            token: data.token,
            displayConversationList: true,
            style:{
                left:3,
                bottom:3,
                width:430
            },
            onSuccess: function(id) {
                $scope.user = id;
                document.title = '用户：' + id;
                console.log('连接成功：' + id);
            },
            onError: function(error) {
                console.log('连接失败：' + error);
            }
        };
    }
    RongDemo.common(WebIMWidget, config, $scope);

}]);