/* Pi-hole: A black hole for Internet advertisements
*  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*  Network-wide ad blocking via your own hardware.
*
*  This file is copyright under the latest version of the EUPL.
*  Please see LICENSE file for your rights under this license. */
var offset, timer, pre, scrolling = true;

// Check every 200msec for fresh data
var interval = 200;

// Function that asks the API for new data
function reloadData(){
    clearTimeout(timer);
    $.getJSON("api.php?tailLog="+offset, function (data)
    {
        offset = data["offset"];
        pre.append(data["lines"]);
    });

    if(scrolling)
    {
        window.scrollTo(0,document.body.scrollHeight);
    }
    timer = setTimeout(reloadData, interval);
}

$(function(){
    // Get offset at first loading of page
    $.getJSON("api.php?tailLog", function (data)
    {
        offset = data["offset"];
    });
    pre = $("#output");
    // Trigger function that looks for new data
    reloadData();
});

$("#chk1").click(function() {
    $("#chk2").prop("checked",this.checked);
    scrolling = this.checked;
});
$("#chk2").click(function() {
    $("#chk1").prop("checked",this.checked);
    scrolling = this.checked;
});
