<?php
/*
Plugin Name: Woot Watcher
Plugin URI: http://mysqlhow2.com/
Description: This Sidebar widget will monitor Woot.com and display products. If there is a wootoff then the refresh time will change from 3600000(10 hours) to 30000 (30) sec.
Author: Lee Thompson
Version: 1.3
Author URI: http://mysqlhow2.com

Copyright 2010  Lee Thompson (email : mysql_dba@cox.net) and Mark Stoecker  (email : admin@poundbangwhack.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action('admin_menu', 'wpWootWatcher_add_admin_menu');
add_action('init', 'wpWootWatcher_init');
add_action('wp_ajax_wpw_watch', 'wpWootWatcherReload');
add_action('wp_ajax_nopriv_wpw_watch', 'wpWootWatcherReload');
add_action('wp_head', 'add_woot_css');
function add_woot_css() {
 echo '<link rel="stylesheet" type="text/css" href="'.get_settings('siteurl').'/wp-content/plugins/wordpress-woot-watcher/woot.css">';
}

function wpWootWatcher_add_admin_menu() {
 add_submenu_page('options-general.php', 'Woot Watcher', 'Woot Watcher', 8, __FILE__, 'wpWootWatcher_admin_menu');
}

// Widget Initialize Function
function wpWootWatcherReload() {
        wpWootWatcher('Woot');
        exit;
}

function widget_wpWootWatcher($args) {
        extract($args);
        echo $before_widget;
        echo $before_title;
	echo "Woot Watcher";
	echo $after_title;
        $getfeeds = get_option('wpww_feeds');
        $feeds = explode(',',$getfeeds);
        echo "<table>";
	$count=0;
        $count = count($getfeeds);
        foreach ($feeds as $feedtitle) {
		if($feedtitle=='Woot')
		{

                        echo "<tr><td valign= top colspan = 3 class = woot><center><h2>".strtoupper($feedtitle)."</h4></center><br>";
                        echo "<div id = \"ReloadThis-$feedtitle\">";
                        wpWootWatcher($feedtitle);
                        echo "</div></td></tr>";
		}
		else
		{
			if ($count%2)
			{
                		echo "<tr><td valign= top class = left><center><h2>".strtoupper($feedtitle)."</h4></center><br>";
		                echo "<div id = \"ReloadThis-$feedtitle\">";
        		        wpWootWatcher($feedtitle);
                		echo "</td></div>";
				$count++;

        		}
			else
			{
                		echo "<td valign= top class = right><center><h2>".strtoupper($feedtitle)."</h4></center><br>";
		                echo "<div id = \"ReloadThis-$feedtitle\">";
        		        wpWootWatcher($feedtitle);
                		echo "</td></div></td></tr>";
				$count++;
			}
		}
	}	
        echo "</table>";
       echo $after_widget;
}

function wpWootWatcher_admin_menu() {
 include('wpwootwatcher_admin.php');
}

function wpWootWatcher_init() {
        if(!get_option('wpww_feeds')) {
                add_option('wpww_feeds');
                update_option('wpww_feeds','Woot');
        }
        register_sidebar_widget(__('Woot Watcher'), 'widget_wpWootWatcher');
        add_filter('plugin_row_meta', 'wpWootWatcher_Plugin_Links',10,2);
}

function wpWootWatcher_Plugin_Links($links, $file) {
        $plugin = plugin_basename(__FILE__);
        if ($file == $plugin) {
                $links[] = '<a href="options-general.php?page='.$plugin.'">' . __('Settings') . '</a>';
                $links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=25LDNVSUTHKAJ" target="_blank">' . __('Donate to this plugin') . '</a>';
        }
        return $links;
}

// End Widget Initialize Function


//Start of Woot Widget
function wpWootWatcher($feedtitle) {
        $feed = file_get_contents("http://$feedtitle.woot.com/salerss.aspx");
        $xml = new SimpleXmlElement($feed);
                foreach ($xml->channel->item as $entry){
                //Use that namespace
                        $namespaces = $entry->getNameSpaces(true);
                        $dc = $entry->children($namespaces['woot']);
                        if ( $dc->wootoff == "True" ) {
                                $refresh_time=30*1000;
                        }
                        else {
                                $refresh_time=36000*1000;
                        }
                }
        if ($feedtitle == 'Woot'):
?>
        <script type="text/javascript">
        function Ajax(){
        var xmlHttp;
                try{
                        xmlHttp=new XMLHttpRequest();// Firefox, Opera 8.0+, Safari
                }catch (e){
                try{
                        xmlHttp=new ActiveXObject("Msxml2.XMLHTTP"); // Internet Explorer
                }catch (e){
                    try{
                                xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
                }catch (e){
                                alert("No AJAX!?");
                                return false;
                }
                }
                }
                xmlHttp.onreadystatechange=function(){
                        if(xmlHttp.readyState==4){
                                document.getElementById('ReloadThis-Woot').innerHTML=xmlHttp.responseText;
//                              alert('I Updated Woot');
                                setTimeout('Ajax()',<?php echo $refresh_time; ?>);
                }
                }
                xmlHttp.open("GET","/wp-admin/admin-ajax.php?action=wpw_watch",true);
                xmlHttp.send(null);
        }
        window.onload=function(){
                setTimeout('Ajax()',3000);
        }
        </script>

<?php
        endif;
        echo "<center><b>".$entry->title."<br>";
        echo $dc->price."</b>";
        echo "<br>";
	if($feedtitle=='Woot')
                {
		$size='';
		$imgsize='width = 50%';
		}
		else
		{
		$size = 'width = 100%';
		$imgsize='width = 100%';
		}

        echo "<a href = \"". $dc->standardimage . "\"rel = \"lightbox\" title= \"" .$entry->title ."\" target = \"_blank\"><img src = \"" .$dc->thumbnailimage. "\"" .$size."></a>";
        echo "<br>";
        if ( $dc->soldout == "False" ){
                echo "<hr><b><a href = \"" .$dc->purchaseurl. "\"target = \"_blank\"> I WANT ONE </a></center></b></hr>";
        }else{
                echo "SOLD OUT";
        }
        if ( $dc->wootoff == "True"){
	$percent = $dc->soldoutpercentage;
	$percent = 100 - (float) $percent[0] * 100;	
                echo "<table><tr><td class = wootimage> <img src = \"".WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__))."light.gif\" ".$imgsize."></td><td style= \"text-align:center;\"><div class=\"progress-container\"> <div title = \"".$percent."% remaining\" style=\"width:".$percent."%\"></div></div>$percent% Left</td><td class = wootimage><img src = \"".WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__))."light.gif\" ".$imgsize."></td></tr></table>";
        }

}
?>
