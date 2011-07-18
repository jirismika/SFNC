function sfnc_ticker_load() // feed_id, forum_id
{
    // TODO
    // hide actual content with animation
    // fill div with new data
    // show actual content with animation
    $("#sfnc_ticker").load("sfnc_ticker_data.php");// ?feed_id=" + feed_id);
}

$(document).ready(function(){
    // init sfnc ticker
    sfnc_ticker_load();
});