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

		$w = $param['w']?$param['w']:null;	//宽
		$h = $param['h']?$param['h']:null;  //高

		switch($param['mode']){
			case 1:
				// /1/w/<Width>/h/<Height> 限定缩略图的宽最少为<Width>，高最少为<Height>进行等比缩放，居中裁剪。
				// 如果只指定 w 参数或只指定 h 参数，代表限定为长宽相等的正方形。
				
				if($w==null && $h==null){
					echo $this->img->response();die;
				}
				$this->img->resize($w,$h,function($constraint){
					$constraint->aspectRatio();
				});

				break;
			case 2:
				//限定缩略图的宽最多为<Width>，高最多为<Height>，进行等比缩放，不裁剪。
				//
				if($w==null && $h==null){
					echo $this->img->response();die;
				}
				$this->img->resize($w,$h,function($constraint){
					$constraint->aspectRatio();
				});

				break;
			case 3:
				//限定缩略图的宽最少为<Width>，高最少为<Height>，进行等比缩放，不裁剪。
				//如果只指定 w 参数或只指定 h 参数，代表长宽限定为同样的值。

				break;
			case 4:
				//限定缩略图的长边最少为<LongEdge>，短边最少为<ShortEdge>，进行等比缩放，不裁剪。
				//这个模式很适合在手持设备做图片的全屏查看（把这里的长边短边分别设为手机屏幕的分辨率即可），生成的图片尺寸刚好充满整个屏幕（某一个边可能会超出屏幕）。

				break;
			case 5:
				//限定缩略图的长边最少为<LongEdge>，短边最少为<ShortEdge>，进行等比缩放，居中裁剪。如果只指定 w 参数或只指定 h 参数，表示长边短边限定为同样的值。同上模式4，但超出限定的矩形部分会被裁剪

				break;
			default:
				$this->error('unsupport mode不支持该模式！');	//临时错误处理方法
				break;

		}
	}
	
	//图片高级处理
	public function imageMogr(){
	}

	//水印处理
	public function watermark(){
	}

	//图片主色调
	public function imageAve(){
	
	}

	public function error($info){
		die($info.'<br/>错误位置：'.__FILE__.'----'.__LINE__.'行：');
	}
}