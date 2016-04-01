define(function(require, exports, module) {
	
	var v = require('jquery');
	
	function getRandom(e, t){
		if (!t) {
			var n = e;
			if (!n || n < 0) {
				n = 0
			}
			return Math.round(Math.random() * n)
		}
		return Math.round(Math.random() * (t - e) + e)
	};
	function versions(){
		var u = navigator.userAgent,
			app = navigator.appVersion;
		return {
			//移动终端浏览器版本信息
			trident: u.indexOf('Trident') > -1, //IE内核
			presto: u.indexOf('Presto') > -1, //opera内核
			webKit: u.indexOf('AppleWebKit') > -1, //苹果、谷歌内核
			gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1, //火狐内核
			mobile: !!u.match(/AppleWebKit.*Mobile.*/), //是否为移动终端
			ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
			android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, //android终端或uc浏览器
			iPhone: u.indexOf('iPhone') > -1 , //是否为iPhone或者QQHD浏览器
			iPad: u.indexOf('iPad') > -1, //是否iPad
			webApp: u.indexOf('Safari') == -1 //是否web应该程序，没有头部与底部
		};
	};
	function cookie(name, value, options) {
        if (typeof value != "undefined") {
            options = options || {};
            if (value === null) {
                value = "";
                options.expires = -1
            }
            var expires = "";
            if (options.expires && (typeof options.expires == "number" || options.expires.toUTCString)) {
                var date;
                if (typeof options.expires == "number") {
                    date = new Date();
                    date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000))
                } else {
                    date = options.expires
                }
                expires = "; expires=" + date.toUTCString()
            }
            var path = options.path ? "; path=" + options.path : "";
            var domain = options.domain ? "; domain=" + options.domain : "";
            var secure = options.secure ? "; secure" : "";
            document.cookie = [name, "=", encodeURIComponent(value), expires, path, domain, secure].join("")
        } else {
            var cookieValue = null;
            var len = parseInt(name.length + 1);
            if (document.cookie && document.cookie != "") {
                var cookies = document.cookie.split(";");
                for (var i = 0; i < cookies.length; i++) {
                    var cookie = cookies[i].replace(/(^\s+|\s+$)/g, "");
                    if (cookie.substring(0, len) == (name + "=")) {
                        cookieValue = decodeURIComponent(cookie.substring(len));
                        break
                    }
                }
            }
            return cookieValue
        }
    };
    function getItem(key) {
        if (localStorage) {
            return localStorage.getItem(key)
        } else {
            return cookie(key)
        }
    }
    function setItem(key, val) {
        if (localStorage) {
            if (!val) {
                localStorage.removeItem(key)
            } else {
                localStorage.setItem(key, val)
            }
        } else {
            cookie(key, val)
        }
    };
	
	exports.random = getRandom;
	exports.version = versions;
	exports.cookie = cookie;
	exports.getItem = getItem;
	exports.setItem = setItem;
});

