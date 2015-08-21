<?php
/**
 * ImageController
 *
 * 仿七牛图片处理接口
 *
 * @author    peiwen
 * @link      https://github.com/troubleman
 * @link      http://peiwen.xyz
 */

use Intervention\Image\ImageManager;

class ImageController extends BaseController{

	protected $imgInfo;			  	  	//图片尺寸/size/mime类型等

	protected $trueImg;   				//图片的真实路径

	protected $img;			  	//响应结果

	protected static $manager = null; 	//图片处理实例

	protected $param		  = array(); //图片处理参数

	protected $format		  = '';      //输出图片的格式

	protected $quality		  = 85;    //输出图片的质量 默认85

	/**
	 * 构造函数
	 * 处理图片信息和处理参数
	 */
	public function __construct(){

		if(self::$manager === null){
			self::$manager = new ImageManager();
		}

		$this->imageExist();	//图片路径检查
		$this->requestParam();	//图片处理参数
		$this->pictureInfo();  	//原图详细信息

	}

	//要处理的图片路径及检查
	private function imageExist(){
		//获取图片真实路径
		$this->trueImg = dirname(__DIR__).'/image'.parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

		//检查图片是否存在
		if(!is_file($this->trueImg)){

			$this->error('图片不存在！'); //临时错误处理方法
		}else{
			$this->img 	= self::$manager->make($this->trueImg); //读取图片
		}
	}

	/**
	 * 获取图片处理参数
	 * @param  [type] $query [description]
	 * @return [type]        [description]
	 */
	private function requestParam(){

		$query    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);//获取请求参数

		$query    = explode('|',$query);  //管道功能（同时多个处理）

		foreach($query as $q){
			$param = array('w'=>null,'h'=>null);  //临时存储参数

			$q = explode('/',$q);
			$param['action']	= $q[0]; //处理方法
			$param['mode']		= $q[1]; //处理模式

			foreach($q as $k=>$v){
				if($k<2 || $k%2==0)
					continue;
				if($v!==''){
					$param[$q[$k-1]] = $v;
				}
			}
			$this->param[] = $param;
		}
	}

	/**
	 * 获取图片信息
	 * @return [type] [description]
	 */
	private function pictureInfo(){
		$this->imgInfo['height'] 	= $this->img->height(); //长（单位像素）
		$this->imgInfo['width'] 	= $this->img->width();  //宽（单位像素）
		$this->imgInfo['size']      = $this->img->filesize(); // 大小（单位bytes）
		$this->imgInfo['mime']		= $this->img->mime();   //mime
	}

	public function index(){

		$param    = $this->param; //要处理的图片参数
		$action   = array('imageInfo','exif','imageView','imageMogr','watermark','imageAve'); //可使用的方法

		foreach($param as $p){
			if(in_array($p['action'], $action)){

				$this->$p['action']($p);
			}
		}

		//输出结果
		echo $this->img->response();

	}

	//图片基本信息
	public function imageInfo($param){
		var_dump($this->imgInfo);
	}


	//图片exif信息
	public function exif($param){
		var_dump($this->img->exif());
	}

	//图片基本处理
	public function imageView($param){
		$origin_w = $this->imgInfo['width']; //原图宽
		$origin_h = $this->imgInfo['height']; //原图高

		$width 	= intval($param['w']);//取整
		$height = intval($param['h']);//取整

		//只存在宽（或高），宽（或高）默认和高（或宽）相同
		//
		$w = $width?$width:($height?$height:null);	//宽
		$h = $height?$height:($width?$width:null);  //高

		if($w==null && $h==null){  //都不存在直接返回原图
			echo $this->img->response();die;
		}

		//和原图宽高相比，默认不超过原图
		$w = ($w > $origin_w)? $origin_w : $w;
		$h = ($h > $origin_h)? $origin_h : $h;

		// echo 'w:',$w,'----h:',$h;die;

		switch($param['mode']){
			case 1:
				// /1/w/<Width>/h/<Height> 限定缩略图的宽最少为<Width>，高最少为<Height>进行等比缩放，居中裁剪。
				// 如果只指定 w 参数或只指定 h 参数，代表限定为长宽相等的正方形。

				$this->ShortEdge($w, $h, $origin_w/$origin_h);

				$this->img->crop($w,$h);  //居中裁剪（不超过原图大小）

				break;

			case 2:
				//限定缩略图的宽最多为<Width>，高最多为<Height>，进行等比缩放，不裁剪。
				
				$this->LongEdge($w, $h, $origin_w/$origin_h);

				break;
			case 3:
				//限定缩略图的宽最少为<Width>，高最少为<Height>，进行等比缩放，不裁剪。
				//如果只指定 w 参数或只指定 h 参数，代表长宽限定为同样的值。
				//你可以理解为模式1是模式3的结果再做居中裁剪得到的。
				
				$this->ShortEdge($w, $h, $origin_w/$origin_h);

				break;

			default:
				$this->error('unsupport mode不支持该模式！');	//临时错误处理方法
				break;
		}

		//后续处理 输出的图片格式、图片质量
		// if(!empty($param['format']) && in_array($param['format'], array('jpg', 'png', 'gif', 'tif', 'bmp') )){
		// 	$this->format = $param['format'];
		// }
		//质量大于 50 小于100 默认 85
		// if(intval($param['q']) &&  intval($param['q'])>50 && intval($param['q'])<100){
		// 	$this->quality = intval($param['q']);
		// }
	}
	
	//图片高级处理
	public function imageMogr($param){var_dump($param);die;

		switch ($param['mode']) {
			case 'rotate':
				//旋转
				$this->img->rotate();
				break;
			
			default:
				# code...
				break;
		}
	}

	//水印处理
	public function watermark(){
	}

	//图片主色调
	public function imageAve(){
	
	}


	/**
	 * 以短边为标准 进行缩放（宽最少为$width 高 最少为 $height）
	 * @param [type] $width  缩放后的宽
	 * @param [type] $height 缩放后的高
	 * @param [type] $radio   要缩放图片的类型 >1 ：横图（高比宽大） 或 <1 :竖图（宽比高大）
	 */
	private function ShortEdge($width, $height, $radio){
		//以短边为缩放标准
		if($width < $height * $radio){

			//要缩放的宽，小于     比按高缩放的宽
			$this->img->heighten($height, function ($constraint) {
			    $constraint->upsize();
			});

		}else{
			//要缩放的宽，大于等于 比按高缩放的宽
			$this->img->widen($width, function ($constraint) {
			    $constraint->upsize();
			});
		}
	}

	/**
	 * 以长边为标准 进行缩放（宽最多为$width 高 最多为 $height）
	 * @param [type] $width  [description]
	 * @param [type] $height [description]
	 * @param [type] $radio  [description]
	 */
	private function LongEdge($width, $height, $radio){
		//以长边为缩放标准
		if($width < $height * $radio){
			//要缩放的宽，大于等于 比按高缩放的宽
			$this->img->widen($width, function ($constraint) {
			    $constraint->upsize();
			});
			
		}else{
			//要缩放的宽，小于     比按高缩放的宽
			$this->img->heighten($height, function ($constraint) {
			    $constraint->upsize();
			});
		}
	}

	/**
	 * 临时错误输出函数
	 * @param  [string] $info [错误信息]
	 * @return [type]       [description]
	 */
	public function error($info){
		die($info.'<br/>错误位置：'.__FILE__.'----'.__LINE__.'行：');
	}
}