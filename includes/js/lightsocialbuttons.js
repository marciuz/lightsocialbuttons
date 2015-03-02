/* Javascript */
/**
 * @file
 * Client side actions of the lightsocialbuttons drupal module
 */

var lightsocialbuttons_loaded=[];
var lightsocialbuttons_tot=0;
var lightsocialbuttons_tot_shares=0;

jQuery(document).ready( function (){

	if(jQuery('#lightsocialbuttons-container').length > 0){

		// Debug URL or real URL
		var url= (Drupal.settings.lightsocialbuttons.debugurl=='') 
			? window.location.href
			: Drupal.settings.lightsocialbuttons.debugurl;

		// Nid of the page (not all the URL have is a node...)
		var nid=Drupal.settings.lightsocialbuttons.nid;

		// Encoded URL
		var urlenc = encodeURIComponent(url);

		// Dictionary of json paths
		var url_json = {
		    googleplus: Drupal.settings.basePath+'lightsocialbuttons/googleplus?url='+urlenc,
			facebook: "https://graph.facebook.com/fql?q=SELECT%20url,%20normalized_url,%20share_count,%20like_count,%20comment_count,%20total_count,commentsbox_count,%20comments_fbid,%20click_count%20FROM%20link_stat%20WHERE%20url=%27"+urlenc+"%27&callback=?",
		    twitter: "http://cdn.api.twitter.com/1/urls/count.json?url="+urlenc+"&callback=?",
		    digg: "http://services.digg.com/2.0/story.getInfo?links="+urlenc+"&type=javascript&callback=?",
		    delicious: 'http://feeds.delicious.com/v2/json/urlinfo/data?url="+urlenc+"&callback=?',
		    linkedin: "http://www.linkedin.com/countserv/count/share?format=jsonp&url="+urlenc+"&callback=?",
		    pinterest: "http://api.pinterest.com/v1/urls/count.json?url="+urlenc+"&callback=?"
		  };

		// Dictionary of classes html
		var spans = {
			googleplus: '.gplusCount',
			twitter: '.twCount',
			facebook: '.fbCount',
			linkedin: '.liCount',
			pinterest: '.piCount'
		};

		// Dictionary of field names in json results (facebook is an exception)
		var fields = {
			linkedin: 'count',
			twitter: 'count',
			facebook: '',
			googleplus: 'share',
			pinterest: 'count',
		}

		// Function to get json data from services
		get_jsons = function (sn){
	    	jQuery.getJSON(url_json[sn], function(json) {
	    		
	    		if(sn=='facebook'){
	    			shares = (json.data.length==0) ? 0 : json.data[0]['total_count'];
	    		}
	    		else{
			    	shares = (isNaN(json[fields[sn]])) ? 0 : json[fields[sn]];
			    }

			    // Add to total
			    lightsocialbuttons_tot+=parseInt(shares);

			    // test if is finish and (if yes) store
			    total_and_cache(sn, shares);

			    return setCount(jQuery(".share "+spans[sn]), shares);
			});
	    }

	    // Get the services and set the numbers
		for(var i=0; i<Drupal.settings.lightsocialbuttons.services.length; i++){

			get_jsons(Drupal.settings.lightsocialbuttons.services[i]);
	    }


		// Set the configuration for the popups
		var Config = {
	        Link: "a.lshare",
	        Width: 500,
	        Height: 500
	    };
	 
	    // add handler links
	    jQuery("a.lshare").click( function (e){
	        e.preventDefault();
	        popup_handler(jQuery(this).attr('href'));
	    });

	    // Function for the animation
	    countUp = function($item) {
		    return setTimeout(function() {
		      var current, newCount, target;
		      current = $item.attr("data-current-count") * 1;
		      target = $item.attr("data-target-count") * 1;
		      newCount = current + Math.ceil((target - current) / 2);
		      $item.attr("data-current-count", newCount);
		      $item.html(format_share_counter(newCount));
		      if (newCount < target) {
		        return countUp($item);
		      }
		    }, 100);
		};

		if(Drupal.settings.lightsocialbuttons.animate){
			setCount = function($item, count) {
			    if (count == null) {
			      count = null;
			    }
			    $item.attr("data-target-count", count);
			    if($item.attr("data-current-count")==undefined){
			    	$item.attr("data-current-count", 0);
			    }
			    return countUp($item);
			};
		}
		else{
			setCount = function($item, count) {
				$item.html(format_share_counter(count));
			}
		}

		// Format the short numbers (like 1.5k)
		function format_share_counter(n){

			if(Drupal.settings.lightsocialbuttons.showk === 0){
				return n;
			}
			else {

				if (n >= 1e6){
			      return (n / 1e6).toFixed(2) + "M"
			    } 
			    else if (n >= 1e3){ 
			      return (n / 1e3).toFixed(1) + "k"
			    }
			    else{
			    	return n;
			    }
			}
		}
	    
	    
	    // create popup
	    function popup_handler(url) {

	        // popup position
	        var
	            px = Math.floor(((screen.availWidth || 1024) - Config.Width) / 2),
	            py = Math.floor(((screen.availHeight || 700) - Config.Height) / 2);
	    
	        // open popup
	        var popup = window.open(url, "social", 
	            "width="+Config.Width+",height="+Config.Height+
	            ",left="+px+",top="+py+
	            ",location=0,menubar=0,toolbar=0,status=0,scrollbars=1,resizable=1");
	        if (popup) {
	            popup.focus();
	        }
	        
	        return !!popup;
	    }

	    function total_and_cache(service, n){
	    	var tot=Drupal.settings.lightsocialbuttons.services.length;
	    	var o={}
	    	o[service]=n;
	    	lightsocialbuttons_loaded.push(o);
	    	lightsocialbuttons_tot_shares+=n;

	    	if(lightsocialbuttons_loaded.length == tot){

	    		setCount(jQuery(".share_total_value"), lightsocialbuttons_tot_shares);
	    		store_cache_light_buttons(lightsocialbuttons_loaded, urlenc);
	    	}

	    }

	    function store_cache_light_buttons(){

	    	jQuery.ajax({
	    		url: Drupal.settings.basePath + 'lightsocialbuttons/storecache',
	    		type: 'post',
	    		dataType: 'json',
	    		data: 'json_data=' + JSON.stringify(lightsocialbuttons_loaded) + '&url=' + urlenc + '&nid=' + nid
	    	});
	    }
	}


});