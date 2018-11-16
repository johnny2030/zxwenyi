(function () {
/*
    将相同代码拆出来方便维护
 */
window.RongDemo = {
    common: function (WebIMWidget, config, $scope) {
        WebIMWidget.init(config);

        WebIMWidget.setUserInfoProvider(function(targetId,obj){
            $http({
                method:'GET',
                url:"{:U('chat/get_user')}",
                params:{
                    'userId':targetId
                }
            }).success(function (res) {
                obj.onSuccess({name:res.name,userId:res.id,portraitUri:res.photo});
            }).error(function (error) {
                alert("请求失败");
            })
        });

        $scope.setconversation = function () {
            if (!!$scope.targetId) {
                WebIMWidget.setConversation(Number($scope.targetType), $scope.targetId, $scope.name);
                WebIMWidget.show();
            }
        };

        $scope.show = function() {
            WebIMWidget.show();
        };

        $scope.hidden = function() {
            WebIMWidget.hidden();
        };

        WebIMWidget.show();
    }
}

})();