/* AShop
 * Copyright 2012 - AShop Software - http://www.ashopsoftware.com
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

function updatecart(ajaxRequest) {
	parameters = ajaxRequest.responseText;
	parametersarray = parameters.split('|');
	price = String(parametersarray[0]);
	msg = String(parametersarray[1]);
	$('confirmmsg').update(msg);
	document.getElementsByName('amount')[0].value = price;
}

function buyitem(itemno, quantity) {
	subtotal = document.getElementsByName('amount')[0].value;
	buttonid = '#addtocart'+itemno;
	buttonid = buttonid.replace('s','');
	jQuery(buttonid).effect("transfer", { to: jQuery("#cartbox") }, 1000);
	var productattributequery = '';
	if (window.productattributes)
	{
		for ( var i=0; i<productattributes.length; ++i )
		{
			var productattributevalue = document.getElementsByName('parameter'+productattributes[i])[0].value;
			productattributequery = productattributequery+'&attribute'+productattributes[i]+'='+productattributevalue;
		}
	}
	var myAjax = new Ajax.Request(
		'buy.php', 
		{
			method: 'get', 
			parameters: 'item='+itemno+'&quantity='+quantity+'&currenttotal='+subtotal+productattributequery+'&dummy='+ new Date().getTime(), 
			onSuccess: updatecart
		}
	);
	return false;
}