var demo = angular.module("demo", ["RongWebIMWidget"]);
demo.controller("main", ["$scope",'$http', "WebIMWidget", function($scope,$http,
    WebIMWidget) {
    $scope.targetType = 1; //1：私聊 更多会话类型查看http://www.rongcloud.cn/docs/api/js/global.html#ConversationType
    $http({
        method:'GET',
        url:'/index.php',
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
    $scope.setconversation = function(id) {
        $http({
            method:'GET',
            url:'/index.php',
            params:{
                'g':'portal',
                'm':'rong',
                'a':'set_time',
                'msgId':id
            }
        }).success(function (res) {
            if(res == 0){
                WebIMWidget.setConversation($scope.targetType, $scope.targetId, $scope.name);
            } else if (res == 1){
                alert("聊天对接失败");
            } else {
                alert("该咨询已经被其他医生受理");
            }
        }).error(function (error) {
            alert("请求失败");
        });
    };
    $scope.setconversation_kefu= function() {
        $http({
            method:'GET',
            url:'/index.php',
            params:{
                'g':'portal',
                'm':'rong',
                'a':'getdata_kefu'
            }
        }).success(function (res) {
            WebIMWidget.setConversation($scope.targetType, res.id, res.name);
        }).error(function (error) {
            alert("请求失败");
        });
    };
    $scope.show = function () {
        WebIMWidget.show();
    };
    WebIMWidget.setUserInfoProvider(function(targetId,obj){
        $http({
            method:'GET',
            url:'/index.php',
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
