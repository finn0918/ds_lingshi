define(function(require, exports, module) {
	
	var e = require('./zepto');
	
	function Lottery(obj, cover, coverType, width, height, drawPercentCallback) {
		this.conNode = obj;
		this.background = null;
		this.backCtx = null;
		this.lottery = null;
		this.lotteryType = 'image';
		this.cover = cover || '#000';
		this.coverType = coverType;
		this.pixlesData = null;
		this.width = width;
		this.height = height;
		this.lastPosition = null;
		this.drawPercentCallback = drawPercentCallback;
		this.vail = !1;
		
		//this.mask = null;
		//this.maskCtx = null;
		//this.clientRect = null;
	}

	Lottery.prototype = {
		createElement: function (id, attributes) {
			var ele = document.createElement(id);
			for (var key in attributes) {
				ele.setAttribute(key, attributes[key]);
			}
			return ele;s
		},
		getTransparentPercent: function(ctx, width, height) {
			var imgData = ctx.getImageData(0, 0, width, height),
				pixles = imgData.data,
				transPixs = [];
			for (var i = 0, j = pixles.length; i < j; i += 4) {
				var a = pixles[i + 3];
				if (a < 128) {
					transPixs.push(i);
				}
			}
			return (transPixs.length / (pixles.length / 4) * 100).toFixed(2);
		},
		resizeCanvas: function (canvas, width, height) {
			canvas.width = width;
			canvas.height = height;
			canvas.getContext('2d').clearRect(0, 0, width, height);
			this.vail ? this.drawLottery() : this.drawMask()
		},
		drawPoint: function (x, y) {
			this.maskCtx.beginPath();
			this.maskCtx.arc(x,y,30,0,2*Math.PI);
			this.maskCtx.fill();
			this.maskCtx.beginPath();
			this.maskCtx.lineWidth = 60;
			this.maskCtx.lineCap = this.maskCtx.lineJoin = "round";
			this.lastPosition && this.maskCtx.moveTo(this.lastPosition[0], this.lastPosition[1]);
			this.maskCtx.lineTo(x,y);
			this.maskCtx.stroke();
			this.lastPosition = [x,y];
			this.mask.style.zIndex = 20 == this.mask.style.zIndex ? 21 :20
			/*
			var radgrad = this.maskCtx.createRadialGradient(x, y, 0, x, y, 30);
			radgrad.addColorStop(0, 'rgba(0,0,0,0.6)');
			radgrad.addColorStop(1, 'rgba(255, 255, 255, 0)');
			this.maskCtx.fillStyle = radgrad;
			this.maskCtx.arc(x, y, 20, 20, Math.PI * 2, true);
			this.maskCtx.fill();
			if (!this.conNode.innerHTML.replace(/[\w\W]| /g, '')) {
				this.conNode.appendChild(this.background);
				this.conNode.appendChild(this.mask);
				this.clientRect = this.conNode ? this.conNode.getBoundingClientRect() : null;
			}
			if (this.drawPercentCallback) {
				this.drawPercentCallback.call(null, this.getTransparentPercent(this.maskCtx, this.width, this.height));
			}
			*/
		},
		bindEvent: function () {
			var a = this,
			b = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(navigator.userAgent.toLowerCase()),
			c = b ? "touchstart": "mousedown",
			d = b ? "touchmove": "mousemove";
			if (b) a.conNode.addEventListener("touchmove",
			function(a) {
				e && a.preventDefault(),
				a.cancelable ? a.preventDefault() : window.event.returnValue = !1
			},
			!1),
			a.conNode.addEventListener("touchend",
			function() {
				e = !1;
				var b = a.getTransparentPercent(a.maskCtx, a.width, a.height);
				b >= 50 && "function" == typeof a.drawPercentCallback && a.drawPercentCallback()
			},
			!1);
			else {
				var e = !1;
				a.conNode.addEventListener("mouseup",
				function(b) {
					b.preventDefault(),
					e = !1;
					var c = a.getTransparentPercent(a.maskCtx, a.width, a.height);
					c >= 50 && "function" == typeof a.drawPercentCallback && a.drawPercentCallback()
				},
				!1)
			}
			this.mask.addEventListener(c,
			function(c) {
				c.preventDefault(),
				e = !0;
				var d = b ? c.touches[0].pageX: c.pageX || c.x,
				f = b ? c.touches[0].pageY: c.pageY || c.y;
				a.drawPoint(d, f, e)
			},
			!1),
			this.mask.addEventListener(d,function(c) {
				if (c.preventDefault(), !e) return ! 1;
				c.preventDefault();
				var d = b ? c.touches[0].pageX: c.pageX || c.x,
				f = b ? c.touches[0].pageY: c.pageY || c.y;
				a.drawPoint(d, f, e)
			},
			!1)
			/*
			this.mask.addEventListener(moveEvtName, function (e) {
				if(e.preventDefault(), !e) return !1;
				e.preventDefault();
				var d = device ? e.touches[0].pageX : e.pageX || e.x,
					f = device ? e.touched[0].pageY : e.pageY || e.y;
				_this.drawPoint(d, f, e);
			}, false);
			*/
		},
		drawLottery: function () {
			/*
			this.background = this.background || this.createElement('canvas', {
				style: 'position:absolute;left:0;top:0;'
			});
			this.mask = this.mask || this.createElement('canvas', {
				style: 'position:absolute;left:0;top:0;'
			});

			if (!this.conNode.innerHTML.replace(/[\w\W]| /g, '')) {
				this.conNode.appendChild(this.background);
				this.conNode.appendChild(this.mask);
				this.clientRect = this.conNode ? this.conNode.getBoundingClientRect() : null;
				this.bindEvent();
			}

			this.backCtx = this.backCtx || this.background.getContext('2d');
			this.maskCtx = this.maskCtx || this.mask.getContext('2d');
			*/
			if (this.lotteryType == 'image') {
				var image = new Image(),
					_this = this;
				image.onload = function () {
					_this.width = this.width;
					_this.height = this.height;
					_this.resizeCanvas(_this.background, this.width, this.height);
					_this.backCtx.drawImage(this, 0, 0, _this.width, _this.height);
					_this.drawMask();
				}
				image.src = this.lottery;
			} else if (this.lotteryType == 'text') {
				this.width = this.width;
				this.height = this.height;
				this.resizeCanvas(this.background, this.width, this.height);
				this.backCtx.save();
				this.backCtx.fillStyle = '#FFF';
				this.backCtx.fillRect(0, 0, this.width, this.height);
				this.backCtx.restore();
				this.backCtx.save();
				var fontSize = 30;
				this.backCtx.font = 'Bold ' + fontSize + 'px Arial';
				this.backCtx.textAlign = 'center';
				this.backCtx.fillStyle = '#F60';
				this.backCtx.fillText(this.lottery, this.width / 2, this.height / 2 + fontSize / 2);
				this.backCtx.restore();
				this.drawMask();
			}
		},
		drawMask: function() {
			if (this.coverType == 'color') {
				this.maskCtx.fillStyle = this.cover;
				this.maskCtx.fillRect(0, 0, this.width, this.height);
				this.maskCtx.globalCompositeOperation = 'destination-out';
			} else if (this.coverType == 'image'){
				var image = new Image(),
					_this = this;
				image.onload = function () {
					_this.resizeCanvas(_this.mask, _this.width, _this.height);
					/android/i.test(navigator.userAgent.toLowerCase());
					_this.maskCtx.globalAlpha = .98;
					_this.maskCtx.drawImage(this, 0, 0, this.width, this.height, 0, 0, _this.width, _this.height);
					var a = 50,
						c = e("#ca-tips").val(),
						d = _this.maskCtx.createLinearGradient(0, 0, _this.width, 0);
					d.addColorStop("0", "#fff"),
					d.addColorStop("1.0", "#000"),
					_this.maskCtx.font = "Bold " + a + "px Arial",
					_this.maskCtx.textAlign = "left",
					_this.maskCtx.fillStyle = d,
					_this.maskCtx.fillText(c, _this.width / 2 - _this.maskCtx.measureText(c).width / 2, 100),
					_this.maskCtx.globalAlpha = 1,
					_this.maskCtx.globalCompositeOperation = "destination-out";
					
					
					//_this.maskCtx.drawImage(this, 0, 0);
					//_this.maskCtx.globalCompositeOperation = 'destination-out';
				}
				image.src = this.cover;
			}
		},
		init: function (lottery, lotteryType) {
			
			lottery && (this.lottery = lottery, this.lottery.width = this.width, this.lottery.height = this.height, this.lotteryType = lotteryType || "image", this.vail = !0),
			this.vail && (this.background = this.background || this.createElement("canvas", {
				style: "position:fixed;left:50%;top:0;width:640px;margin-left:-320px;height:100%;background-color:transparent;"
			})),
			this.mask = this.mask || this.createElement("canvas", {
				style: "position:fixed;left:50%;top:0;width:640px;margin-left:-320px;height:100%;background-color:transparent;"
			}),
			this.mask.style.zIndex = 20,
			this.conNode.innerHTML.replace(/[\w\W]| /g, "") || (this.vail && this.conNode.appendChild(this.background), this.conNode.appendChild(this.mask), this.bindEvent()),
			this.vail && (this.backCtx = this.backCtx || this.background.getContext("2d")),
			this.maskCtx = this.maskCtx || this.mask.getContext("2d"),
			this.vail ? this.drawLottery() : this.drawMask();
			var c = this;
			window.addEventListener("resize", 
				function() {
					c.width = document.documentElement.clientWidth,
					c.height = document.documentElement.clientHeight,
					c.resizeCanvas_w(c.mask, c.width, c.height)
				},
			!1)
			
			//this.lottery = lottery;
			//this.lotteryType = lotteryType || 'image';
			//this.drawLottery();
		}
	}
	
	module.exports = Lottery;
})