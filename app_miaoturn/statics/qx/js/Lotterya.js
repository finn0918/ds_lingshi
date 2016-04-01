define(function(require, exports, module) {

	var e = require('./zepto');
	
	function d(a, b, c, d, e, f) {
		this.conNode = a,
		this.background = null,
		this.backCtx = null,
		this.mask = null,
		this.maskCtx = null,
		this.lottery = null,
		this.lotteryType = "image",
		this.cover = b || "#000",
		this.coverType = c,
		this.pixlesData = null,
		this.width = d,
		this.height = e,
		this.lastPosition = null,
		this.drawPercentCallback = f,
		this.vail = !1
	}
	d.prototype = {
		createElement: function(a, b) {
			var c = document.createElement(a);
			for (var d in b) c.setAttribute(d, b[d]);
			return c
		},
		getTransparentPercent: function(a, b, c) {
			for (var d = a.getImageData(0, 0, b, c), 
			e = d.data, f = [], g = 0, h = e.length; h > g; g += 4) {
				var i = e[g + 3];
				128 > i && f.push(g)
			}
			return (f.length / (e.length / 4) * 100).toFixed(2)
		},
		resizeCanvas: function(a, b, c) {
			a.width = b,
			a.height = c,
			a.getContext("2d").clearRect(0, 0, b, c)
		},
		resizeCanvas_w: function(a, b, c) {
			a.width = b,
			a.height = c,
			a.getContext("2d").clearRect(0, 0, b, c),
			this.vail ? this.drawLottery() : this.drawMask()
		},
		drawPoint: function(a, b) {
			this.maskCtx.beginPath(),
			this.maskCtx.arc(a, b, 30, 0, 2 * Math.PI),
			this.maskCtx.fill(),
			this.maskCtx.beginPath(),
			this.maskCtx.lineWidth = 60,
			this.maskCtx.lineCap = this.maskCtx.lineJoin = "round",
			this.lastPosition && this.maskCtx.moveTo(this.lastPosition[0], this.lastPosition[1]),
			this.maskCtx.lineTo(a, b),
			this.maskCtx.stroke(),
			this.lastPosition = [a, b],
			this.mask.style.zIndex = 20 == this.mask.style.zIndex ? 21 : 20
		},
		bindEvent: function() {
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
			a.conNode.addEventListener("touchend",function() {
				console.log(b);
				e = !1;
				var b = a.getTransparentPercent(a.maskCtx, a.width, a.height);
				b >= 50 && "function" == typeof a.drawPercentCallback && a.drawPercentCallback()
			},
			!1);
			else {
				var e = !1;
				a.conNode.addEventListener("mouseup",function(b) {
					console.log(c);
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
		},
		drawLottery: function() {
			if ("image" == this.lotteryType) {
				var a = new Image,
				b = this;
				a.onload = function() {
					this.width = b.width,
					this.height = b.height,
					b.resizeCanvas(b.background, b.width, b.height),
					b.backCtx.drawImage(this, 0, 0, b.width, b.height),
					b.drawMask()
				},
				a.src = this.lottery
			} else if ("text" == this.lotteryType) {
				this.width = this.width,
				this.height = this.height,
				this.resizeCanvas(this.background, this.width, this.height),
				this.backCtx.save(),
				this.backCtx.fillStyle = "#FFF",
				this.backCtx.fillRect(0, 0, this.width, this.height),
				this.backCtx.restore(),
				this.backCtx.save();
				var c = 30;
				this.backCtx.font = "Bold " + c + "px Arial",
				this.backCtx.textAlign = "center",
				this.backCtx.fillStyle = "#F60",
				this.backCtx.fillText(this.lottery, this.width / 2, this.height / 2 + c / 2),
				this.backCtx.restore(),
				this.drawMask()
			}
		},
		drawMask: function() {
			if ("color" == this.coverType) this.maskCtx.fillStyle = this.cover,
			this.maskCtx.fillRect(0, 0, this.width, this.height),
			this.maskCtx.globalCompositeOperation = "destination-out";
			else if ("image" == this.coverType) {
				var a = new Image,
				b = this;
				a.onload = function() {
					b.resizeCanvas(b.mask, b.width, b.height);
					/android/i.test(navigator.userAgent.toLowerCase());
					b.maskCtx.globalAlpha = .98,
					b.maskCtx.drawImage(this, 0, 0, this.width, this.height, 0, 0, b.width, b.height);
					var a = 50,
					c = e("#ca-tips").val() || "",
					d = b.maskCtx.createLinearGradient(0, 0, b.width, 0);
					d.addColorStop("0", "#fff"),
					d.addColorStop("1.0", "#000"),
					b.maskCtx.font = "Bold " + a + "px Arial",
					b.maskCtx.textAlign = "left",
					b.maskCtx.fillStyle = d,
					b.maskCtx.fillText(c, b.width / 2 - b.maskCtx.measureText(c).width / 2, 100),
					b.maskCtx.globalAlpha = 1,
					b.maskCtx.globalCompositeOperation = "destination-out"
				},
				a.src = this.cover
			}
		},
		init: function(a, b) {
			a && (this.lottery = a, this.lottery.width = this.width, this.lottery.height = this.height, this.lotteryType = b || "image", this.vail = !0),
			this.vail && (this.background = this.background || this.createElement("canvas", {
				style: "position:fixed;left:0;top:0;width:640px;max-width:100%;margin-left:0px;height:100%;background-color:transparent;"
			})),
			this.mask = this.mask || this.createElement("canvas", {
				style: "position:fixed;left:0;top:0;width:640px;max-width:100%;margin-left:0px;height:100%;background-color:transparent;"
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
		}
	};
	module.exports = d;
})