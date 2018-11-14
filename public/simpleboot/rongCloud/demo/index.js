var demo = angular.module("demo", ["RongWebIMWidget"]);

demo.controller("main", ["$scope", "WebIMWidget", "$http", function($scope, WebIMWidget, $http) {

    $scope.targetType = 1; //1：私聊 更多会话类型查看http://www.rongcloud.cn/docs/api/js/global.html#ConversationType
    $scope.targetId = '3';

    //注意实际应用中 appkey 、 token 使用自己从融云服务器注册的。
    var config = {
        appkey: '6tnym1br64ed7',
        token: "48INJSerW8d4oC0FpsQT4z52MsknxL9vO3s0bTmXa+ix3DM9sNy34D3Q3qmO227s8lId7odxwNY=",
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
    RongDemo.common(WebIMWidget, config, $scope);

}]);