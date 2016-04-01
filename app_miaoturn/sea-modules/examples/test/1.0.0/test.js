define(function(require, exports, module) {
	var $ = require('jquery');
	
	module.exports = {
		init: function(arr) {
			var html = '';
			html += '<table>';
			for(var i in arr){
				html += '<tr>';
				html += '<td>'+arr[i].name+'</td>';
				html += '<td>'+arr[i].age+'</td>';
				html += '<td>'+arr[i].date+'</td>';
				html += '</tr>';
			}
			html += '</table>';
			$("body").append(html);
		}
	}
})