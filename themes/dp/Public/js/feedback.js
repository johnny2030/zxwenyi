/*!
 * ======================================================
 * FeedBack Template For MUI (http://dev.dcloud.net.cn/mui)
 * =======================================================
 * @version:1.0.0
 * @author:cuihongbao@dcloud.io
 */
(function() {
	var starIndex = 0;
	 //应用评分
	 mui('.icons').on('tap','i',function(){
         var index = parseInt(this.getAttribute("data-index"));
         var val = 0;
         var num = parseInt(index/2);
         var ber = index%2;
         var parent = this.parentNode;
         var children = parent.children;
         var reset = 0;
         if(this.classList.contains("mui-icon-star")){
             for(var i=0;i<num;i++){
                 children[i].classList.remove('mui-icon-star');
                 children[i].classList.remove('mui-icon-starhalf');
                 children[i].classList.add('mui-icon-star-filled');
                 reset = children[i].getAttribute("data-index");
                 if (reset%2 > 0) {
                     reset = parseInt(reset) + 1;
                     children[i].setAttribute("data-index",reset);
				 }
             }
             val = index - 1;
         }else{
             if (ber == 0){
             	if (starIndex > index){
                    for (var i = num; i < 5; i++) {
                        children[i].classList.add('mui-icon-star');
                        children[i].classList.remove('mui-icon-star-filled');
                        children[i].classList.remove('mui-icon-starhalf');
                        reset = children[i].getAttribute("data-index");
                        if (reset%2 > 0) {
                            reset = parseInt(reset) + 1;
                            children[i].setAttribute("data-index",reset);
                        }
                    }
                    val = index - 1;
				} else {
                    for (var i = num-1; i < 5; i++) {
                        children[i].classList.remove('mui-icon-starhalf');
                    }
                    val = index - 1;
				}
             }else {
                 for (var i = num; i < 5; i++) {
                     if (i == num){
                         children[i].classList.add('mui-icon-starhalf');
                     } else {
                         children[i].classList.add('mui-icon-star');
                         children[i].classList.remove('mui-icon-star-filled');
                         children[i].classList.remove('mui-icon-starhalf');
                     }
                 }
                 val = index + 1;
             }
         }
         this.setAttribute("data-index",val);
         $("#elte").val(index);
         starIndex = index;
  	});
})();
