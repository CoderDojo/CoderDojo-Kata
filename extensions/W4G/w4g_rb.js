/**********************************************************************
** This file is part of the Rating Bar extension for MediaWiki
** Copyright (C)2009
**                - PatheticCockroach <www.patheticcockroach.com>
**                - Franck Dernoncourt <www.francky.me>
**
** Home Page : http://www.wiki4games.com
**
** This program is free software; you can redistribute it and/or
** modify it under the terms of the GNU General Public License
** as published by the Free Software Foundation; either
** version 3 of the License, or (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
** <http://www.gnu.org/licenses/>
*********************************************************************/

/*******************************************************************
** query2page is a basic AJAX function mainly based on
** W3Schools ajax tutorial
** Source: http://www.w3schools.com/ajax/ajax_server.asp
********************************************************************/
function query2page(full_query, target_id, target_type, display_type) {
    target_type = target_type == null ? 1 : target_type;
    display_type = target_type == null ? 1 : display_type;
    var xmlHttp;
    try {
        xmlHttp = new XMLHttpRequest
    } catch (e) {
        try {
            xmlHttp = new ActiveXObject("Msxml2.XMLHTTP")
        } catch (e) {
            try {
                xmlHttp = new ActiveXObject("Microsoft.XMLHTTP")
            } catch (e) {
                return alert("Your browser does not support AJAX!"), !1
            }
        }
    }
    xmlHttp.onreadystatechange = function() {
        if (xmlHttp.readyState == 4) {
            if (target_type == 1 || target_type == 2) document.getElementById(target_id).innerHTML = xmlHttp.responseText;
            target_type == 2 && (W4GRB.user_rating[display_type] = document.getElementById("w4g_rb_rating_value-" + display_type).innerHTML == null ? 0 : document.getElementById("w4g_rb_rating_value-" + display_type).innerHTML, updatebox(display_type, W4GRB.user_rating[display_type]))
        }
    };
    xmlHttp.open("GET", full_query, !0);
    xmlHttp.send(null)
}


/*******************************************************************
** This function fills a div ("parent_id") with 5 starred subdivs
********************************************************************/
function loadbox(parent_id)
{
	var output = "";
	for(var i=1; i<=5; i++){
		output += "<div class=\"w4g_rb_star_unit\" id=\"w4g_rb_star_unit_1_" + i + "\" style=\"margin-left:" + (i-1)*30 + "px;\" ";
		output += " onmouseover=\"updatebox(\'" + parent_id + "\'," + i*20 + ")\" ";
		output += " onclick=\"W4GRB.user_rating['" + parent_id + "']=" + i*20 + ";query2page(W4GRB.query_url['" + parent_id + "']+'&vote=" + i*20 + "\',\'w4g_rb_area-" + parent_id + "')\"></div>";
	}
	document.getElementById("rating_target-" +parent_id).innerHTML = output;
}

/*******************************************************************
** This function changes the background of starred subdivs
** The parent_id parameter has currently no use
** rating_val: a number ranging from 0 to 100 indicating the last
** colored star (must be divided by 20 for 5 stars)
********************************************************************/
function updatebox(parent_id, rating_val)
{
	var r_val = (rating_val == null) ? 0 : rating_val/20;
	max_star=Math.floor(r_val);
	
	if(r_val<1) return;
	for(var i=1; i<=5; i++){
		if(i <= max_star) 
			document.getElementById("w4g_rb_star_unit_1_" + i).className = "w4g_rb_star_hover";
		else 
			document.getElementById("w4g_rb_star_unit_1_" + i).className = "w4g_rb_star_unit";
	}
}