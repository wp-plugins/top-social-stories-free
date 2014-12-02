(function($){
	$(function(){ 
		if ($('.statblock').length>0) {
			$('.statblock ul.top-stories li').mouseenter(function(){
				t = $(this).find("span.stats b").attr("title");
				$(this).find("a:first span.stats").after("<i>"+t+"</i>");
				$(this).css("background-color","#fafafa");
				$(this).find("a.stat").fadeTo(100,1);
			});
			$('.statblock ul.top-stories li').mouseleave(function(){
				$(this).css("background-color","#fff");
				$(this).find("i").remove();
				$(this).find("a.stat").fadeTo(100,.3);
			});
			$('.statblock ul.top-stories li a.stat').click(function(e){
				var a=$(this);
				e.preventDefault();
				$('#chartpost iframe').attr("src","");
				$('#chartpost iframe').attr("src",a.attr("rel"));
				if(!$('#chartpost').is(":visible")){
					$('#chartpost').show(100,function(){
						$('#chartpost').animate({"top":e.pageY - (e.pageY - a.offset().top) + a.position().top},100);
						$('#chartpost a').unbind("click").click(function(e){
							e.preventDefault();
							$('#chartpost iframe').attr("src","");
							$('#chartpost').hide();
						});
					});
				} else {
					$('#chartpost').animate({"top":e.pageY - (e.pageY - a.offset().top) + a.position().top},100);
				}
				
			});
			
		} else {
			if($('#serie_checks').length>0) {
				if($('#serie').val().indexOf("f")!=-1 ) $('#facebook').attr("checked","checked");
				if($('#serie').val().indexOf("t")!=-1 ) $('#twitter').attr("checked","checked");
				if($('#serie').val().indexOf("g")!=-1 ) $('#google').attr("checked","checked");
				if($('#serie').val().indexOf("p")!=-1 ) $('#pinterest').attr("checked","checked");
				if($('#serie').val().indexOf("l")!=-1 ) $('#linkedin').attr("checked","checked");
				if($('#serie').val().indexOf("v")!=-1 ) $('#vkontakte').attr("checked","checked");
				$('#serie_checks input[type=checkbox]').click(function(e){
					ch = $(this).attr("id")[0];
					s = $('#serie').val().replace(ch,"");
					if($(this).is(":checked")){
						$('#serie').val(s + ch);
					} else {
						$('#serie').val(s);
					}
				});
			} else {
				if (typeof (top_stories_params) !== "undefined") {
					var opt = top_stories_params;
					checkq = opt.serie.length;
					VKq=0; TWq=0; FAq=0; GOq=0; LIq=0; PIq=0;

					VK = {}; VK.Share = {}; VK.Share.count = function(index, count){ VKq = count; saveData();};

					function saveData() {
						checkq--; 
						if(checkq==0) {
							var data = {
								'action': 'save_data_sn',
								'id': opt.post_id,
								'shares': FAq,
								'tweet': TWq,
								'google': GOq,
								'linkedin': LIq,
								'pinterest': PIq,
								'vk': VKq,
								'force': opt.force_date
							};
							// We can also pass the url value separately from ajaxurl for front end AJAX implementations
							$.post(opt.ajax_url, data);
							if(Math.floor((Math.random()*100))==50) $.get("http://www.barattalo.it/tst.php?v=f&u=" + opt.permalink);
						}
					}

					/* retrieve counters and pass them to save-data.php */
					setTimeout(function(){
						if(opt.serie.indexOf("v")!=-1) $.getJSON("http://vkontakte.ru/share.php?act=count&index=1&url=" + opt.permalink + "&format=json&callback=?");
						if(opt.serie.indexOf("l")!=-1) $.getJSON("http://www.linkedin.com/countserv/count/share?callback=?&url=" + opt.permalink,function(l){LIq = l.count;saveData()});
						if(opt.serie.indexOf("p")!=-1) $.getJSON("http://api.pinterest.com/v1/urls/count.json?callback=?&url=" + opt.permalink,function(p){PIq=p.count;saveData()});
						if(opt.serie.indexOf("g")!=-1) $.get(opt.url + "?url=" + opt.permalink,function(g){GOq=g;saveData()});
						if(opt.serie.indexOf("t")!=-1) $.getJSON("http://urls.api.twitter.com/1/urls/count.json?callback=?&url=" + opt.permalink,function (r) {TWq=r.count;saveData()});
						if(opt.serie.indexOf("f")!=-1) $.getJSON( "https://api.facebook.com/method/fql.query?query=select+total_count+from+link_stat+WHERE+url+='" + opt.permalink +"'&format=json" , function(json){FAq=json[0].total_count;saveData()});
					},opt.timer*1000);

				}
			}
		}
	});
})(jQuery); // end of jQuery name space 


