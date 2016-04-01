<?php
/**
 * 验证码类 imagick
 * @package     tools_class
 * @author      nangua <410966126@qq.com>
 */
class Code{
	//资源
    private $img;
    //画布宽度
    public $width;
    //画布高度
    public $height;
    //背景颜色
    public $bgColor;
    //验证码
    public $code;
    //验证码的随机种子
    public $codeStr;
    //验证码长度
    public $codeLen;
    //验证码字体
    public $font;
    //验证码字体大小
    public $fontSize;
    //验证码字体颜色
    public $fontColor;
    // 生成验证码url路径
    public $codeUrl;
        /**
     * 构造函数
     */
    public function __construct($width = '', $height = '', $bgColor = '', $fontColor = '', $codeLen = '', $fontSize = '') {
        $this->codeStr = C("CODE_STR");
        $this->font = C("CODE_FONT");
        if (!is_file($this->font)) {
            echo "验证码字体文件不存在";
        }
        $this->width = empty($width) ? C("CODE_WIDTH") : $width;
        $this->height = empty($height) ? C("CODE_HEIGHT") : $height;
        $this->bgColor = empty($bgColor) ? C("CODE_BG_COLOR") : $bgColor;
        $this->codeLen = empty($codeLen) ? C("CODE_LEN") : $codeLen;
        $this->fontSize = empty($fontSize) ? C("CODE_FONT_SIZE") : $fontSize;
        $this->fontColor = empty($fontColor) ? C("CODE_FONT_COLOR") : $fontColor;

    }
    /**
     * 生成验证码
     */
    private function createCode() {
        $code = '';
        for ($i = 0; $i < $this->codeLen; $i++) {
            $code .= $this->codeStr [mt_rand(0, strlen($this->codeStr) - 1)];
        }
        $this->code = strtoupper($code);
    }
    /**
     * 返回验证码
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * 建画布
     */
    public function create() {
        
        $w = $this->width;
        $h = $this->height;
        $bgColor = $this->bgColor;
        $this->image = new Imagick();
		$draw = new ImagickDraw();
		$pixel = new ImagickPixel($this->bgColor);
		$this->image->newImage($w, $h, $pixel);
		$pixel->setColor($this->fontColor);
		$draw->setFont($this->font);
		$draw->setFontSize( $this->fontSize );
		$this->createCode();
		$this->image->addNoiseImage(imagick::NOISE_POISSON,imagick::CHANNEL_OPACITY);
		$this->image->annotateImage($draw, 5, 20, 0, $this->code);
		$this->image->setImageFormat('png');

//         $this->createLine();
        // $this->createPix();
    }
    /**
    *  画线
    */
    private function createLine(){
        $w = $this->width;
        $h = $this->height;
        $line_color = "#dcdcdc";
        $color = imagecolorallocate($this->img, hexdec(substr($line_color, 1, 2)), hexdec(substr($line_color, 3, 2)), hexdec(substr($line_color, 5, 2)));
        $l = $h/5;
        for($i=1;$i<$l;$i++){
            $step =$i*5;
            imageline($this->img, 0, $step, $w,$step, $color);
        }
        $l= $w/10;
        for($i=1;$i<$l;$i++){
            $step =$i*10;
            imageline($this->img, $step, 0, $step,$h, $color);
        }
    }

    /**
     * 写入验证码文字
     */
    private function createFont() {
        $this->createCode();
        $color = $this->fontColor;
        if (!empty($color)) {
            $fontColor = imagecolorallocate($this->img, hexdec(substr($color, 1, 2)), hexdec(substr($color, 3, 2)), hexdec(substr($color, 5, 2)));
        }
        $x = ($this->width - 10) / $this->codeLen;
        for ($i = 0; $i < $this->codeLen; $i++) {
            if (empty($color)) {
                $fontColor = imagecolorallocate($this->img, mt_rand(50, 155), mt_rand(50, 155), mt_rand(50, 155));
            }
            imagettftext($this->img, $this->fontSize, mt_rand(- 30, 30), $x * $i + mt_rand(6, 10), mt_rand($this->height / 1.3, $this->height - 5), $fontColor, $this->font, $this->code [$i]);
        }
        $this->fontColor = $fontColor;
    }

    /**
     * 画线
     */
    private function createPix() {
        $pix_color = $this->fontColor;
        for ($i = 0; $i < 50; $i++) {
            imagesetpixel($this->img, mt_rand(0, $this->width), mt_rand(0, $this->height), $pix_color);
        }

        for ($i = 0; $i < 2; $i++) {
            imageline($this->img, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $pix_color);
        }
        //画圆弧
        for ($i = 0; $i < 1; $i++) {
            // 设置画线宽度
           // imagesetthickness($this->img, mt_rand(1, 3));
            imagearc($this->img, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height)
                    , mt_rand(0, 160), mt_rand(0, 200), $pix_color);
        }
        imagesetthickness($this->img, 1);
    }
    /**
     * 显示验证码
     */
    public function show($cid=0) {
        $this->create();//生成验证码
//            is_dir("code")? true :mkdir("code",0777);
//            $this->image->writeImages(ROOT_PATH."/code/".$cid.".png",false);
//            $this->image->clear();

            return $this->image;

    }
}