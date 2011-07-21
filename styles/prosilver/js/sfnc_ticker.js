function sfnc_ticker_load() // feed_id, forum_id
{
    $("#sfnc_loading").fadeIn();
    $("#sfnc_ticker").fadeOut();
    $("#sfnc_ticker").load("sfnc_ticker_data.php").fadeIn();// ?feed_id=" + feed_id + "&forum_id=" + forum_id);
    $("#sfnc_loading").fadeOut();
}

$(document).ready(function(){
    // init sfnc ticker
    sfnc_ticker_load();
});