var BlueColorSelecter={
ValueControl:null,
ColorSelecter:null,
NowType:1,
options:{
Height:180,
Width:270,
Degree:6,
Zindex:5,
BackgroundColor:"#DDDDDD",
Alpha:100,
BorderWidth:1,
BorderColor:"#000000",
BorderStyle:"solid"
},
MainHtml:"<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\
<tr>\
<td style=\"height:25px; padding:0px; background-color:#999999;\">\
<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" style=\"height:25px;\">\
<tr>\
<td style=\"height:25px; width:60px; padding:2px;\">\
<input type=\"text\" name=\"SelectedColor\" id=\"SelectedColor\" readonly=\"readonly\" \
style=\"width:60px; height:20px; border-width:0px; background-color:#FFFFFF;\"/>\
</td>\
<td style=\"text-align:center;color:#000000; font-size:12px;\" id=\"ColorValueShower\">&nbsp;</td>\
<td style=\"height:25px; width:80px;\">\
<select name=\"select\" style=\"height:20px; width:80px;\" onchange=\"BlueColorSelecter.ShowColorArea(this.options[this.selectedIndex].value)\">\
<option value=\"1\">立方色</option>\
<option value=\"2\">连续色调</option>\
<option value=\"3\">灰读级别</option>\
</select>\
</td>\
</tr>\
</table>\
</td>\
</tr>\
<tr>\
<td id=\"ColorArea\" style=\" padding:0px;\">&nbsp;</td>\
</tr>\
</table>",
Create:function()
{var ColorSelecter = document.createElement("div");
ColorSelecter.setAttribute('id', "ColorSelecter");
if(document.all)
ColorSelecter.style.height=BlueColorSelecter.options.Height+"px";
ColorSelecter.style.width=BlueColorSelecter.options.Width+"px";
ColorSelecter.style.zIndex=BlueColorSelecter.options.Zindex;
ColorSelecter.style.position="absolute";ColorSelecter.style.backgroundColor=BlueColorSelecter.options.BackgroundColor;
ColorSelecter.style.filter = "progid:DXImageTransform.Microsoft.Alpha(opacity="+BlueColorSelecter.options.Alpha+")" ;
ColorSelecter.style.MozOpacity=BlueColorSelecter.options.Alpha/100;
ColorSelecter.style.visibility="hidden";
ColorSelecter.style.borderStyle=BlueColorSelecter.options.BorderStyle;
ColorSelecter.style.borderColor=BlueColorSelecter.options.BorderColor;
ColorSelecter.style.borderWidth=BlueColorSelecter.options.BorderWidth+"px";
try{
document.body.insertBefore(ColorSelecter, document.body.firstChild);
ColorSelecter.innerHTML=BlueColorSelecter.MainHtml;
   BlueColorSelecter.ColorSelecter=ColorSelecter;
}catch(err){alert("document.body不能为空！"); return null;}
},
ShowColorSelecter:function(obj,event)
{
obj.style.cursor="pointer";
obj.style.borderStyle=BlueColorSelecter.options.BorderStyle;
obj.style.borderColor=BlueColorSelecter.options.BorderColor; 
obj.style.borderWidth=BlueColorSelecter.options.BorderWidth+"px";
if(BlueColorSelecter.ColorSelecter==null)
BlueColorSelecter.Create();
BlueColorSelecter.ShowColorArea(BlueColorSelecter.NowType);
BlueColorSelecter.ValueControl=obj;
var X=(window.screen.availWidth-BlueColorSelecter.ColorSelecter.offsetWidth)/2;
var Y=(window.screen.availHeight-BlueColorSelecter.ColorSelecter.offsetHeight)/2;
if(event.offsetX == null)
{X=event.clientX-event.layerX;
if(X+BlueColorSelecter.ColorSelecter.offsetWidth>window.screen.availWidth)
X=X-BlueColorSelecter.ColorSelecter.offsetWidth+obj.offsetWidth;
}
else
{
X=event.clientX-event.offsetX;
if(X+BlueColorSelecter.ColorSelecter.offsetWidth>window.screen.availWidth)
X=X-BlueColorSelecter.ColorSelecter.offsetWidth+obj.offsetWidth;
}
if(event.offsetY == null)
{
Y=event.clientY-event.layerY+obj.offsetHeight;
}       
else
{Y=event.clientY-event.offsetY+obj.offsetHeight;
}
BlueColorSelecter.ColorSelecter.style.visibility="visible";BlueColorSelecter.ColorSelecter.style.left=X+"px";
BlueColorSelecter.ColorSelecter.style.top=Y+"px";
},
Hide:function()
{
if(BlueColorSelecter.ColorSelecter.style.visibility=="visible")
BlueColorSelecter.ColorSelecter.style.visibility="hidden";
},
HideColorSelecter:function()
{
BlueColorSelecter.ColorSelecter.style.visibility="hidden";
},
ShowColor:function(obj)
{
try{var SelectedColor=document.getElementById("SelectedColor");
var ColorValueShower=document.getElementById("ColorValueShower");
SelectedColor.style.backgroundColor=obj.style.backgroundColor;
ColorValueShower.innerHTML=obj.style.backgroundColor;
}
catch(err){}
},

SelectColor:function(obj)
{
BlueColorSelecter.ValueControl.style.backgroundColor=obj.style.backgroundColor;
BlueColorSelecter.ValueControl.value=obj.style.backgroundColor;
BlueColorSelecter.HideColorSelecter();
},
ShowColorArea:function(Type)
{
   BlueColorSelecter.NowType=Type;
var ColorArea=document.getElementById("ColorArea");
if(ColorArea!=null)
{
var ColorHTML="<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" style=\"border-collapse:collapse\">";
var ColorsPreRow=18;
     var ColorsPreCloum=12;
var Degree=BlueColorSelecter.options.Degree;
if(Type==1)
{
for(var i=0;i<ColorsPreCloum;i++)
{
ColorHTML+=" <tr>";
for(var j=0;j<ColorsPreRow;j++)
{
var hR=0;
var hG=0;
var hB=0;
var Temp=Math.floor(i/Degree)*3+(Math.floor(j/Degree)+Math.floor(i/Degree)*2)*3;
 hR=Temp*16+Temp;
Temp=j%Degree*3;
hG=Temp*16+Temp;
Temp=i%Degree*3;
hB=Temp*16+Temp;
var CnLeiColor=new MyColor(hR,hG,hB);
var ColorValue=CnLeiColor.hexValue();
ColorHTML+="<td style=\"background-color:"+ColorValue+"; height:15px; width:15px; font-size:5px;border:1px solid #000000\" \
onmouseover=\"BlueColorSelecter.ShowColor(this)\"\
onclick=\"BlueColorSelecter.SelectColor(this)\"\
></td>"
}
            ColorHTML+=" </tr>";
}
ColorHTML+=" </tr></table>";
ColorArea.innerHTML=ColorHTML;
}
else if(Type==2)
{
for(var i=0;i<ColorsPreCloum;i++)
{
ColorHTML+=" <tr>";
for(var j=0;j<ColorsPreRow;j++)
{
var hR=0;
var hG=0;
var hB=0;
var Temp=(Degree-Math.floor(j/Degree)*2-2+Math.floor(i/Degree))*3;
hR=Temp*16+Temp;
if(i<6)
Temp=(Degree-i%Degree-1)*3;
else
Temp=(i%Degree)*3;
hG=Temp*16+Temp;
if(j<6||(11<j&&j<18))
Temp=(Degree-j%Degree-1)*3;
else
Temp=(j%Degree)*3;
hB=Temp*16+Temp;
var CnLeiColor=new MyColor(hR,hG,hB); 
var ColorValue=CnLeiColor.hexValue();
ColorHTML+="<td style=\"background-color:"+ColorValue+"; height:15px; width:15px; font-size:5px;border:1px solid #000000\"\
onmouseover=\"BlueColorSelecter.ShowColor(this)\"\
onclick=\"BlueColorSelecter.SelectColor(this)\"\
></td>"
}
ColorHTML+=" </tr>";
}
ColorHTML+=" </tr></table>";
ColorArea.innerHTML=ColorHTML;
}

else if(Type==3)
{
for(var i=0;i<13;i++)
{
ColorHTML+=" <tr>";
for(var j=0;j<21;j++){
var Temp=16*15+15-(j+i*21);
if(Temp<0)Temp=0; 
var hR=Temp;
var hG=Temp;
var hB=Temp;
var CnLeiColor=new MyColor(hR,hG,hB);
var ColorValue=CnLeiColor.hexValue();
ColorHTML+="<td style=\"background-color:"+ColorValue+"; height:15px; width:15px; font-size:5px;border:1px solid #000000\" \
onmouseover=\"BlueColorSelecter.ShowColor(this)\"\
onclick=\"BlueColorSelecter.SelectColor(this)\"\
></td>"
}
ColorHTML+=" </tr>";
}
ColorHTML+=" </tr></table>";
ColorArea.innerHTML=ColorHTML;
} }
},
addEvent:function(o, t, f) {
if (o.addEventListener) o.addEventListener(t, f, false);
else if (o.attachEvent) o.attachEvent('on'+ t, f);
else o['on'+ t] = f;
}
}
function MyColor(r,g,b){
this.red=r;
this.green=g;
this.blue=b;
}
MyColor.prototype.hexValue=function(){
var hR=this.red.toString(16);
var hG=this.green.toString(16);
var hB=this.blue.toString(16);
return "#"+(this.red<16?("0"+hR):hR)+(this.green<16?("0"+hG):hG)+(this.blue<16?("0"+hB):hB);
};

