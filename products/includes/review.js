/*  AShop
 *  Copyright 2002-2011 - All Rights Reserved Worldwide
 *  http://www.ashopsoftware.com
 *  This software is licensed per individual site.
 *  By installing or using this software, you agree to the licensing terms,
 *  which are located at http://www.ashopsoftware.com/license.htm
 *  Unauthorized use or distribution of this software 
 *  is a violation U.S. and international copyright laws.
 *--------------------------------------------------------------------------*/

var set=false;
var v=0;
var a;
function loadStars()
{
   star1 = new Image(12,12);
   star1.src = "images/staroff.gif";
   star2 = new Image(12,12);
   star2.src= "images/staron.gif";
}

function highlight(x)
{
   if (set==false)
   {
   y=x*1+1
   switch(x)
   {
   case "1": 
   document.getElementById(x).src= star2.src;
   break;
   case "2":for (i=1;i<y;i++)
   {
   document.getElementById(i).src= star2.src;
   }
   break;
   case "3":for (i=1;i<y;i++)
   {
   document.getElementById(i).src= star2.src;
   }
   break;
   case "4":for (i=1;i<y;i++)
   {
   document.getElementById(i).src= star2.src;
   }
   break;
   case "5":for (i=1;i<y;i++)
   {
   document.getElementById(i).src= star2.src;
   }
   break;
   }
   }
}
function losehighlight(x)
{
   if (set==false)
   {
  	 for (i=1;i<6;i++)
  	 {
  		 document.getElementById(i).src=star1.src;
  		 document.getElementById('vote').innerHTML=""
 	 }
   }
}

function setStar(x)
{
   y=x*1+1
   if (set==false)
   {
   	switch(x)
   {
   case "1": 
   a="1"
   flash(a);
   document.review.rating.value = '1'; 
   break;
   case "2":
   a="2"
   flash(a);
   document.review.rating.value = '2';
   break;
   case "3": 
   a="3"
   flash(a);
   document.review.rating.value = '3';
   break;
   case "4":
   a="4"
   flash(a);
   document.review.rating.value = '4';
   break;
   case "5":
   a="5"
   flash(a);
   document.review.rating.value = '5';
   break;
   }
   set=true;
   }
}
function flash()
{
   y=a*1+1
   switch(v)
   {
   case 0:
   for (i=1;i<y;i++)
   {
   document.getElementById(i).src= star1.src;
   }
   v=1
   setTimeout(flash,200)
   break;
   case 1:
   for (i=1;i<y;i++)
   {
   document.getElementById(i).src= star2.src;
   }
   v=2
   setTimeout(flash,200)
   break;
   case 2:
   for (i=1;i<y;i++)
   {
   document.getElementById(i).src= star1.src;
   }
   v=3
   setTimeout(flash,200)
   break;
   case 3:
   for (i=1;i<y;i++)
   {
   document.getElementById(i).src= star2.src;
   }
   v=4
   setTimeout(flash,200)
   break;
   case 4:
   for (i=1;i<y;i++)
   {
   document.getElementById(i).src= star1.src;
   }
   v=5
   setTimeout(flash,200)
   break;
   case 5:
   for (i=1;i<y;i++)
   {
   document.getElementById(i).src= star2.src;
   }
   v=6
   setTimeout(flash,200)
   break;
   }
}
