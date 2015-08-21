<?php
use \NoahBuscher\Macaw\Macaw;

//定义路由规则所有图片路径
Macaw::get('/(([^\.]*\.[jpg|gif|png|jpeg])(.*))', 'ImageController@index');



//未匹配 返回错误
Macaw::error(function() {
  echo '404 :: Not Found';
});

Macaw::dispatch();