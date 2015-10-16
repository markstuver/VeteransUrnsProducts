/* AShop
 * Copyright 2011 - AShop Software - http://www.ashopsoftware.com
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see: http://www.gnu.org/licenses/.
 * --------------------------------------------------------------------*/

function updatebid(ajaxRequest) {
	var time = new Date().getTime();
	time = time/1000;
	time = time-timediff;
	time = Math.round(time);
	parameters = ajaxRequest.responseText;
	parametersarray = parameters.split('|');
	productid = parseInt(parametersarray[0]);
	newstarttime = parseInt(parametersarray[1]);
	if (!newstarttime) newstarttime = 0;
	newprice = parametersarray[3];
	newscreenname = parametersarray[5];
	newscreennamearray = newscreenname.split('-');
	if (newstarttime == 0 || newstarttime != starttime[productid]) starttime[productid] = newstarttime;
	$('price'+productid).update(precurrency+newprice+postcurrency);
	if (newscreennamearray[0] == 'won') $('screenname'+productid).update(wonby+': <b>'+newscreennamearray[1]+'</b>');
	else $('screenname'+productid).update(bidderword+': <b>'+newscreenname+'</b>');
}

function isIE()
{
  return /msie/i.test(navigator.userAgent) && !/opera/i.test(navigator.userAgent);
}

function countdown() {
	var time = new Date().getTime();
	time = time/1000;
	time = time-timediff;
	time = Math.round(time);
	IDs.each(function(productid) {
		if (activated[productid] == 1) {
			var seconds = time - starttime[productid];
			var secondsleft = fplength[productid] - seconds;
			if (secondsleft > fplength[productid]) secondsleft = fplength[productid];
			if (starttime[productid] <= 0) secondsleft = fplength[productid];
			if (secondsleft <= 0) {
				$('countdown'+productid).update('SOLD!');
				$('bidbutton'+productid).update('');
				if (auctiontype[productid] != 'standard') $('buybutton'+productid).style.display='block';
			} else {
				var secleft = secondsleft;
				var daysleft = secleft/86400;
				daysleft = Math.floor(daysleft);
				secleft -= daysleft*86400;
				var hoursleft = secleft/3600;
				hoursleft = Math.floor(hoursleft);
				secleft -= hoursleft*3600;
				var minutesleft = secleft/60;
				minutesleft = Math.floor(minutesleft);
				secleft -= minutesleft*60;
				var showtime = '';
				if (daysleft == 1) showtime = showtime+daysleft+' '+counterday+', ';
				else if (daysleft > 1) showtime = showtime+daysleft+' '+counterdays+', ';
				if (hoursleft > 0) showtime = showtime+hoursleft+' '+counterhours+', ';
				if (minutesleft > 0) showtime = showtime+minutesleft+' '+counterminutes+', ';
				showtime = showtime+secleft+' '+counterseconds;
				if (secondsleft <= 10) $('countdown'+productid).update('<font color=red>'+showtime+'</font>');
				else $('countdown'+productid).update(showtime);
			}
			if (auctiontype[productid] == 'standard') {
				if ((time - lastupdate) > 10 || secondsleft <= 0) {
					lastupdate = time;
					var myAjax = new Ajax.Request(
						'admin/bidengine.php', 
						{
							method: 'get', 
							parameters: 'productid='+productid+'&dummy='+ new Date().getTime(), 
							onSuccess: updatebid
						}
					);
				}
			} else {
				var myAjax = new Ajax.Request(
					'admin/bidengine.php', 
					{
						method: 'get', 
						parameters: 'productid='+productid+'&dummy='+ new Date().getTime(), 
						onSuccess: updatebid
					}
				);
			}
		} else {
			if (time >= activatetime[productid]) {
				activated[productid] = 1;
				$('bidbutton'+productid).style.display='block';
				$('price'+productid).style.display='block';
				$('screenname'+productid).style.display='block';
			}
		}
	});
}

function placebid(productid,bidder) {
	var myAjax = new Ajax.Request(
		'admin/bidengine.php', 
		{
			method: 'get', 
			parameters: 'placebid='+productid+'&bidder='+bidder+'&dummy='+ new Date().getTime(),
			onSuccess: updatebid
		}
	);
	var bids = parseInt($('bidsinfo').innerHTML);
	if (bids > 0) bids--;
	$('bidsinfo').update(bids);
}