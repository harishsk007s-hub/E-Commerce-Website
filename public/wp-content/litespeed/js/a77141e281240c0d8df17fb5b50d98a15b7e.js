(function($){'use strict';$(document).ready(function(){var $search_site=$('.site-search-popup'),$body=$('body');if(!$search_site.length){return}
$body.on('click','.button-search-popup',function(e){e.preventDefault();console.log('test');var $parrent=$(this).closest('.elementor-section');if(!$parrent.length){$parrent=$(this).closest('#masthead')}
if($parrent.length){var search_site=$search_site.detach();search_site.appendTo($parrent)}
$search_site.addClass('active fadein');setTimeout(function(){$search_site.find('input[type="search"]').focus()},600)});$('.site-search-popup-close').on('click',function(e){e.preventDefault();$search_site.removeClass('active fadein');$search_site.addClass("fadeout")
setTimeout(function(){$search_site.removeClass("fadeout")},300)})})})(jQuery)
;