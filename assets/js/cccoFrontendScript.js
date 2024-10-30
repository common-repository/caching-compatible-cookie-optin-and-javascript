/***********************
FRONTEND SCRIPTS
***********************/

/***********************
Cookie Detection and Banner
***********************/
//wait for page load first
jQuery(document).ready(function(e){

  //always append the base scripts
  ccco_appendBaseScripts();

  //look for cookie
  ccco_cookieValue = ccco_getCookie('ccco_selection');

  //we have a selection stored in our cookie
  if (ccco_cookieValue != '') {

    if(ccco_cookieValue == 'optInScriptsAllowed'){
      ccco_appendOptInScripts();
      ccco_appendRevokeButton('all');
    }else if(ccco_cookieValue == 'baseScriptsAllowed'){
      ccco_appendRevokeButton('base');
    }else{
      ccco_appendRevokeButton('no');
    }

  //no selection so far, so we present the opt-in
  }else{

    ccco_appendRevokeButton('no');

    //show cookie banner - but only if we do not display the privacy page (checked in php...)
    if(cccoSettings.showBanner == 'true'){
      jQuery('body').append('<div role="banner" class="ccco-banner ccco-bannertype-'+cccoSettings.settings.behaviour.bannertype+'"><div class="ccco-banner-inner"><span class="ccco-banner-text">'+cccoSettings.settings.text.bannertext+'</span><span class="ccco-banner-buttons"><a href="#" data-ccco="accept" class="ccco-banner-button" target="_self">'+cccoSettings.settings.text.accepttext+'</a><a href="#" data-ccco="refuse" class="ccco-banner-button" target="_self">'+cccoSettings.settings.text.refusetext+'</a></span></div></div>');
      if(cccoSettings.settings.behaviour.linkpage != 0){
        jQuery('.ccco-banner .ccco-banner-buttons').append('<a href="'+cccoSettings.settings.behaviour.linkpageurl+'" target="_blank" class="ccco-banner-link">'+cccoSettings.settings.text.linktext+'</a>');
      }
    }
  }

  //handle different clicks on banner.and inline warning
  jQuery('body').on('click', '.ccco-banner-buttons a[data-ccco="accept"], .ccco-inline-container a[data-ccco="accept"]', function(e){
      e.preventDefault();
      ccco_setCookie('ccco_selection', 'optInScriptsAllowed', 30);
      location.reload();
  });
  jQuery('body').on('click', '.ccco-banner-buttons a[data-ccco="refuse"]', function(e){
      e.preventDefault();
      ccco_setCookie('ccco_selection', 'baseScriptsAllowed', 0);
      jQuery('.ccco-banner').hide('500');
  });
});

//helper to execute base scripts
function ccco_appendBaseScripts(){
  if(typeof cccoSettings.scripts.base != 'undefined' && typeof cccoSettings.scripts.base.htmlhead != 'undefined'){
    jQuery('head').append(cccoSettings.scripts.base.htmlhead);
  }
  if(typeof cccoSettings.scripts.base != 'undefined' && typeof cccoSettings.scripts.base.htmlbody != 'undefined'){
    jQuery('body').append(cccoSettings.scripts.base.htmlbody);
  }
}

//helper to execute optin scripts
function ccco_appendOptInScripts(){
  if(typeof cccoSettings.scripts.optin != 'undefined' && typeof cccoSettings.scripts.optin.htmlhead != 'undefined'){
    jQuery('head').append(cccoSettings.scripts.optin.htmlhead);
  }
  if(typeof cccoSettings.scripts.optin != 'undefined' && typeof cccoSettings.scripts.optin.htmlbody != 'undefined'){
    jQuery('body').append(cccoSettings.scripts.optin.htmlbody);
  }
}


/***********************
Revoke Functionality
***********************/
//helper to place revoke button
function ccco_appendRevokeButton(prefix){
  var textSelector = 'revoke_' + prefix + 'selection';
  jQuery('.ccco-revoke-button-wrap').append(cccoSettings.settings.text[textSelector]);

  if(prefix == 'all' || prefix == 'base'){
    jQuery('.ccco-revoke-button-wrap').append(' <a href="#" class="ccco-revoke-button">' + cccoSettings.settings.text.revoke_button + '</a>');
  }

  jQuery('.ccco-revoke-button-wrap').on('click', '.ccco-revoke-button', function(e){
    e.preventDefault();

    //unset our own cookie
    ccco_unsetCookie('ccco_selection');

    //unset additional defined cookies
    if(cccoSettings.settings.cookies.unset_on_revoke != ""){
      jQuery(cccoSettings.settings.cookies.unset_on_revoke.split(',')).each(function(index,cookie){
        ccco_unsetCookie(cookie);
      });
    }

    location.reload();
  });
}


/***********************
Inline Script Functionality
***********************/
//wait for page load first
jQuery(document).ready(function(){

  //check each script container
  jQuery('.ccco-inline-container').each(function(i,ccco_container){

    //fetch values from attributes
    var type = jQuery(ccco_container).attr('data-type');
    var permission = jQuery(ccco_container).attr('data-permission');

    //just get the script and that's it
    if(type == 'immediate'){

      //check if this is a basescript and therefore always allowed
      if(permission == 'base'){
        ccco_appendInlineScript(ccco_container);

      //or is this inlince script only intended for opted-in visitors?
      }else if(permission == 'optin'){

        //if visitor opted in, then append
        if(ccco_cookieValue == 'optInScriptsAllowed'){
          ccco_appendInlineScript(ccco_container);

        //opt-in needed but not yet given
        }else{
          ccco_appendInlineWarning(ccco_container);
        }
      }

    }else if(type == 'button'){

      //check if this is a basescript and therefore always allowed
      if(permission == 'base'){
        ccco_appendInlineScriptButton(ccco_container);

      //or is this inlince script only intended for opted-in visitors?
      }else if(permission == 'optin'){

        //if visitor opted in, then append the button
        if(ccco_cookieValue == 'optInScriptsAllowed'){
          ccco_appendInlineScriptButton(ccco_container);

        //opt-in needed but not yet given
        }else{
          ccco_appendInlineWarning(ccco_container);
        }
      }

    }

  });

  //listen for clicks on our appended buttons
  jQuery('.ccco-inline-container').on('click', 'a.ccco-inline-appendbutton', function(e){
    e.preventDefault();
    jQuery(this).closest('.ccco-inline-container').each(function(i,ccco_container){
      ccco_appendInlineScript(ccco_container);
    });
  });
});

//helper function to append html per container
function ccco_appendInlineScript(ccco_container){
  var html = jQuery(ccco_container).attr('data-html');
  html = html.replace(/&gt;/g, '>');
  html = html.replace(/&lt;/g, '<');
  html = html.replace(/&#8220;/g, '"');
  html = html.replace(/&#8243;/g, '"');
  html = html.replace(/&quot;/g, '"');
  html = html.replace(/&apos;/g, "'");
  html = html.replace(/&amp;/g, '&');
  jQuery(ccco_container).html(html);
}
//helper function to append button per container
function ccco_appendInlineScriptButton(ccco_container){
  jQuery(ccco_container).html('<a href="#" class="ccco-inline-appendbutton">' + jQuery(ccco_container).attr('data-buttontext') + '</a>');
}

//helper function to append the warning that opt-in is required per container
function ccco_appendInlineWarning(ccco_container){
  //for easier styling of this warning
  jQuery(ccco_container).addClass('ccco-inline-warning');

  //display our warning-text
  jQuery(ccco_container).append('<span class="ccco-inline-text">' + cccoSettings.settings.text.inline_warning + '</span>');

  //display the opt-in-banner here
  jQuery(ccco_container).append('<a href="#" data-ccco="accept" class="ccco-inline-button" target="_self">'+cccoSettings.settings.text.accepttext+'</a>');

  //show our fallback-link, if we have provided one
  if(jQuery(ccco_container).attr('data-fallbackurl') != '' && jQuery(ccco_container).attr('data-fallbacktext') != ''){
    jQuery(ccco_container).append('<a href="' + jQuery(ccco_container).attr('data-fallbackurl') + '" target="_blank" class="ccco-inline-link">' + jQuery(ccco_container).attr('data-fallbacktext') + '</a>');
  }

  //display the privacy-page-link here too
  if(cccoSettings.settings.behaviour.linkpage != 0){
    jQuery(ccco_container).append('<a href="'+cccoSettings.settings.behaviour.linkpageurl+'" target="_blank" class="ccco-inline-link">'+cccoSettings.settings.text.linktext+'</a>');
  }
}


/***********************
Cookie Helpers
***********************/
//helper functions from: https://www.w3schools.com/js/js_cookies.asp
function ccco_setCookie(cname, cvalue, exdays) {
  if(exdays == 0){
    document.cookie = cname + "=" + cvalue + ";path=/";
  }else{
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
  }
}
function ccco_unsetCookie(cname){
  var d = new Date();
  d.setTime(d.getTime() - 1);
  var expires = "expires="+ d.toUTCString();

  //first, try to unset the cookie on the current domain
  document.cookie = cname + "=;" + expires + ";path=/";

  //now, lets try it on a .domain.org way
  var nonwwwHost = window.location.hostname.replace('www.', '');
  document.cookie = cname + "=;" + expires + ";path=/;domain=." + nonwwwHost;
}
function ccco_getCookie(cname) {
  var name = cname + "=";
  var decodedCookie = decodeURIComponent(document.cookie);
  var ca = decodedCookie.split(';');
  for(var i = 0; i <ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}
