define(function(require) {
	var b = [
		{name:"json",age:"18",date:"2014-11-26"},
		{name:"will",age:"16",date:"2014-11-26"},
	];
	var lucky = require('./test');
	lucky.init(b);
});