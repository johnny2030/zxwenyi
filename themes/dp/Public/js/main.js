//上传图片开始
/*调相机本地相抵上传图片*/ 
    mui.plusReady(function(){ 
        document.getElementById('userImg').addEventListener('tap',function(){ 
            if(mui.os.plus){ 
                var a=[{ 
                    title:'拍照' 
                },{ 
                    title:'从手机相册选择' 
                }]; 
                plus.nativeUI.actionSheet({ 
//                  title:'修改头像', 
                    cancel:'取消', 
                    buttons:a 
                },function(b){ 
                    switch(b.index){ 
                        case 0: 
                            break; 
                        case 1: 
                            //拍照 
                            getImages(); 
                            break; 
                        case 2: 
                            //打开相册 
                            galleryImages(); 
                            break; 
                        default: 
                            break; 
                    } 
                },false); 
            } 
        }); 
 
        //拍照 
        function getImages(){ 
            var mobileCamera=plus.camera.getCamera(); 
            mobileCamera.captureImage(function(e){ 
                plus.io.resolveLocalFileSystemURL(e,function(entry){ 
                    var path=entry.toLocalURL()+'?version='+new Date().getTime(); 
                    uploadHeadImg(path); 
                    console.log(path); 
                },function(err){ 
                    console.log("读取拍照文件错误"); 
                }); 
            },function(e){ 
                console.log("er",err); 
            },function(){ 
                filename:'_doc/head.png'; 
            }); 
        } 
 
        //本地相册选择  
        function galleryImages() {  
            plus.gallery.pick(function(a) {  
                plus.io.resolveLocalFileSystemURL(a, function(entry) {  
                    plus.io.resolveLocalFileSystemURL("_doc/", function(root) {  
                        root.getFile("head.png", {}, function(file) {  
                            //文件已存在  
                            file.remove(function() {  
                                console.log("file remove success");  
                                entry.copyTo(root, 'head.png', function(e) {  
                                        var e = e.fullPath + "?version=" + new Date().getTime();  
                                        uploadHeadImg(e); /*上传图片*/  
                                        //变更大图预览的src  
                                        //目前仅有一张图片，暂时如此处理，后续需要通过标准组件实现  
                                    },  
                                    function(e) {  
                                        console.log('copy image fail:' + e.message);  
                                    });  
                            }, function() {  
                                console.log("delete image fail:" + e.message);  
                            });  
                        }, function() {  
                            //文件不存在  
                            entry.copyTo(root, 'head.png', function(e) {  
                                    var path = e.fullPath + "?version=" + new Date().getTime();  
                                    uploadHeadImg(path); /*上传图片*/  
                                },  
                                function(e) {  
                                    console.log('copy image fail:' + e.message);  
                                });  
                        });  
                    }, function(e) {  
                        console.log("get _www folder fail");  
                    })  
                }, function(e) {  
                    console.log("读取拍照文件错误：" + e.message);  
                });  
            }, function(a) {}, {  
                filter: "image"  
            })  
        };  
 
        //上传图片 
        function uploadHeadImg(imgPath){ 
            //选中图片之后，头像当前的照片变为选择的照片 
            var mainImg=document.getElementById('userImg'); 
            mainImg.src=imgPath; 
             
            var images=new Image(); 
            images.src=imgPath; 
            var imgData=getBase64Image(images); 
            mui.ajax('/medical/index.php?g=portal&m=patient&a=upload_image',{  //第一个参数是接口名
                data:{ 
                    'imgDatas':imgData 
                }, 
                dataType:'json',//服务器返回json格式数据 
                type:'post',//HTTP请求类型 
                timeout:10000,//超时时间设置为10秒； 
                success:function(data){ 
                    if(data.status=='1'){ 
                        mui.alert('上传成功！'); 
                    } 
                }, 
                error:function(xhr,type,errorThrown){ 
                    if(type=='timeout'){ 
                        mui.alert('服务器连接超时，请稍后再试'); 
                    } 
                } 
            }); 
        } 
 
 
        //压缩图片转成base64 
        function getBase64Image(img){ 
            var canvas=document.createElement("canvas"); 
            var width=img.width; 
            var height=img.height; 
            if(width>height){ 
                if(width>100){ 
                    height=Math.round(height*=100/width); 
                    width=100; 
                } 
            }else{ 
                if(height>100){ 
                    width=Math.round(width*=100/height); 
                } 
                height=100; 
            } 
 
            canvas.width=width; 
            canvas.height=height; 
            var ctx=canvas.getContext('2d'); 
            ctx.drawImage(img,0,0,width,height); 
 
            var dataUrl=canvas.toDataURL('image/png',0.8); 
            return dataUrl.replace('data:image/png:base64,',''); 
        } 
    }); 

//长传图片结束