// Global Variables
var adminModeActive  = false;
var nightModeActive  = false;
var showingAllImages = false;
var imageData        = [];

// admin mode initialization
var adminModeConfig   = getLocalStorage('admin-mode', { 'active' : false });
adminModeActive       = adminModeConfig.active;
applyAdminMode();

// Night mode initialization
var nightModeConfig   = getLocalStorage('night-mode', { 'active' : false });
nightModeActive       = nightModeConfig.active;
applyNightMode();

var adminHTML = ' | <a href="#" id="admin-mode">Admin Mode: <span id="admin-mode-status">Disabled</span></a>';

// Startup Script
$(document).ready(function() {
  $('#view-all-images').click(viewAllImages);
  $('#night-mode').click(toggleNightMode);
  
  if($("a[title|='Unstick thread']").length || $("a[title|='Sticky thread']").length){
    $("#night-mode").after(adminHTML);
    $('#admin-mode').click(toggleAdminMode);
  }

  // Initialization for image resizing
  document.addEventListener('dragstart', function() {return false}, false);

  // Admin Mode
  applyAdminMode();

  // Night Mode
  applyNightMode();

  // Image Resizing
  $.map($('div.message img'), function(img) {
      imageData[img] = {};
      imageData[img].resized = false;
      makeImageZoomable(img);
  });
});

// Admin Mode
function toggleAdminMode() {
  adminModeActive = !adminModeActive;
  setLocalStorage('admin-mode', { 'active' : adminModeActive });
  applyAdminMode();
}

function applyAdminMode(){
  if (!adminModeActive) {
    $("a[title|='Mark offtopic']").hide();
    $("a[title|='Mark on-topic']").hide();
    $("a[title|='Delete']").hide();
    $("a[title|='Undelete']").hide();
    $("a[title|='Sticky thread']").hide();
    $("a[title|='Unstick thread']").hide();
    $("a[title|='Lock']").hide();
    $('#admin-mode-status').text('Disabled');
  } else {
    $("a[title|='Mark offtopic']").show();
    $("a[title|='Mark on-topic']").show();
    $("a[title|='Delete']").show();
    $("a[title|='Undelete']").show();
    $("a[title|='Sticky thread']").show();
    $("a[title|='Unstick thread']").show();
    $("a[title|='Lock']").show();
    $('#admin-mode-status').text('Active');
  }    
}

// Night Mode
function toggleNightMode() {
    nightModeActive = !nightModeActive;
    setLocalStorage('night-mode', { 'active' : nightModeActive });
    applyNightMode();
}

function applyNightMode() { 
	if (!nightModeActive) {
    	$('.night-mode').removeClass('night-mode');
        
        var allImages = $('img');
        $.map(allImages, function(image) {
            var imgSrc = $(image).attr('src');
            if (imgSrc.indexOf('/pic-night.gif') >= 0)
                $(image).attr('src', imgSrc.replace('pic-night.gif', 'pic.gif'));
            else if (imgSrc.indexOf('/nt-night.gif') >= 0)
                $(image).attr('src', imgSrc.replace('nt-night.gif', 'nt.gif'));
            else if (imgSrc.indexOf('/url-night.gif') >= 0)
                $(image).attr('src', imgSrc.replace('url-night.gif', 'url.gif'));
            else if (imgSrc.indexOf('/followup-night.gif') >= 0)
                $(image).attr('src', imgSrc.replace('followup-night.gif', 'followup.gif'));
            else if (imgSrc.indexOf('/post-night.gif') >= 0)
                $(image).attr('src', imgSrc.replace('post-night.gif', 'post.gif'));
            else if (imgSrc.indexOf('/change-night.gif') >= 0)
                $(image).attr('src', imgSrc.replace('change-night.gif', 'change.gif'));
            else if (imgSrc.indexOf('/other-night.png') >= 0)
                $(image).attr('src', imgSrc.replace('other-night.png', 'other.png'));
        });
        
        $('#night-mode-status').text('Off');
    } else {
	$('html, body, select, a, em, a.tt, a.ut, .row0, .row1, .srow0, .srow1, .trow0, .trow1, .grow0, .grow1, .username, .threadinfo, .messageblock .subject, .vmid, .postform tr, .postform .text, .thread > li > a, .preferences tr, td.signaturepreview, textarea, .arrow, .navigate, div.changes').addClass('night-mode');
        
        var allImages = $('img');
        $.map(allImages, function(image) {
            var imgSrc = $(image).attr('src');
            if (imgSrc.indexOf('/pic.gif') >= 0)
                $(image).attr('src', imgSrc.replace('pic.gif', 'pic-night.gif'));
            else if (imgSrc.indexOf('/nt.gif') >= 0)
                $(image).attr('src', imgSrc.replace('nt.gif', 'nt-night.gif'));
            else if (imgSrc.indexOf('/url.gif') >= 0)
                $(image).attr('src', imgSrc.replace('url.gif', 'url-night.gif'));
            else if (imgSrc.indexOf('/followup.gif') >= 0)
                $(image).attr('src', imgSrc.replace('followup.gif', 'followup-night.gif'));
            else if (imgSrc.indexOf('/post.gif') >= 0)
                $(image).attr('src', imgSrc.replace('post.gif', 'post-night.gif'));
            else if (imgSrc.indexOf('/change.gif') >= 0)
                $(image).attr('src', imgSrc.replace('change.gif', 'change-night.gif'));
            else if (imgSrc.indexOf('/other.png') >= 0)
                $(image).attr('src', imgSrc.replace('other.png', 'other-night.png'));
        });

        $('#night-mode-status').text('Active');
    }    
}

// View All Images
function viewAllImages() {
	if (showingAllImages) {
    	showingAllImages = false;
        $('#view-all-images').text('View All Images');
        $('.view-all-images-inline').remove();
    } else {
		showingAllImages = true;
    	$('#view-all-images').text('Hide All Images');
	
        var imgThreads = $('img[src$="/pic.gif"], img[src$="/pic-night.gif"]');
        $.map(imgThreads, function(imgThread) {
            var threadURL = $('a', $(imgThread).parent())[0].href;
            $.ajax({
                url: threadURL,
                dataType: "html",
                success: function (data) {
                    var threadHTML   = $.parseHTML(data);
                    var threadImages = $('img', $('div[class="message"]', threadHTML));
                    var threadInfo 	 = $('span.threadinfo', $(imgThread).parent())[0];
                    var imgContainer = $('<div class="view-all-images-inline"></div>');

					threadImages.attr('title', 'Drag to resize');
                    threadImages.load(function() {
                    	if ($(this).width() > 640)
                        	$(this).attr('width', 640);
                    });

					threadImages.appendTo(imgContainer);
                    $(threadInfo).after(imgContainer);
                    
                    for(i = 0; i < threadImages.length; i++) {
                    	imageData[threadImages[i]] = {};
                        imageData[threadImages[i]].resized = false;
                        makeImageZoomable(threadImages[i]);
                    }
                }
            });
        });
	}  
}

// Drag to Resize (borrowed from https://github.com/kabaka/drag-to-resize)
function getDragSize(e) {
    return (p = Math.pow)(p(e.clientX - (rc = e.target.getBoundingClientRect()).left, 2) + p(e.clientY - rc.top, 2), .5);
}

function getHeight() {
  	return window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
}

function makeImageZoomable(imgTag) {
  dragTargetData = {};

  imgTag.addEventListener('mousedown', function(e) {
    if(e.ctrlKey != 0)
      return true;

	if(e.metaKey != null)
      if(e.metaKey != 0)
        return true;

    if(e.button == 0) {
      if(imageData[e.target].position ==  null) {
        imageData[e.target].zIndex = e.target.style.zIndex;
        imageData[e.target].width = e.target.style.width;
        imageData[e.target].height = e.target.style.height;
        imageData[e.target].position = e.target.style.position;
      }

      dragTargetData.iw = e.target.width;
      dragTargetData.d = getDragSize(e);
      dragTargetData.dr = false;

      e.preventDefault();
    }
  }, true);

  imgTag.addEventListener('contextmenu', function(e) {
    if(imageData[e.target].resized) {
      imageData[e.target].resized = false;
      e.target.style.zIndex = imageData[e.target].zIndex;
      e.target.style.maxWidth = e.target.style.width = imageData[e.target].width;
      e.target.style.maxHeight = e.target.style.height = imageData[e.target].height;
      e.target.style.position = imageData[e.target].position;

      e.preventDefault();
      e.returnValue = false;
      e.stopPropagation();
      return false;
    }
  }, true);
  
  imgTag.addEventListener('dblclick', function(e) {
    if(e.ctrlKey != 0)
      return true;

    if(e.metaKey != null)
      if(e.metaKey != 0)
        return true;

    if(imageData[e.target].resized)
      e.target.style.maxWidth = e.target.style.width = imageData[e.target].width;

    e.target.style.position = "fixed";
    e.target.style.zIndex = 1000;
    e.target.style.top = 0;
    e.target.style.left = 0;
    e.target.style.maxWidth = e.target.style.width = "auto";
    e.target.style.maxHeight = e.target.style.height = getHeight() + "px";
      
    imageData[e.target].resized = true;

    e.preventDefault();
    e.returnValue = false;
    e.stopPropagation();
    return false;
  }, true);
  
  imgTag.addEventListener('mousemove', function(e){
    if (dragTargetData.d) {
      e.target.style.maxWidth = e.target.style.width = ((getDragSize(e)) * dragTargetData.iw / dragTargetData.d) + "px";
      e.target.style.maxHeight = '';
      e.target.style.height = 'auto';
      e.target.style.zIndex = 1000;

      if(e.target.style.position == '')
      	e.target.style.position = 'relative';

      dragTargetData.dr = true;
      imageData[e.target].resized = true;
    }
  }, false);

  imgTag.addEventListener('mouseout', function(e) {
    dragTargetData.d = false;
      if (dragTargetData.dr) return false;
  }, false);

  imgTag.addEventListener('mouseup', function(e) {
    dragTargetData.d = false;
    if (dragTargetData.dr) return false;

  }, true);

  imgTag.addEventListener('click', function(e) {
    if(e.ctrlKey != 0)
      return true;

    if(e.metaKey != null)
      if(e.metaKey != 0)
        return true;

    dragTargetData.d = false;
    if (dragTargetData.dr) {
      e.preventDefault();
      return false;
    }
    
    if(imageData[e.target].resized) {
      e.preventDefault();
      e.returnValue = false;
      e.stopPropagation();
      return false;
    }
  }, false);
}

// Helper Methods
function hasLocalStorage() {
    try {
        return 'localStorage' in window && window['localStorage'] !== null;
    } catch (e) { return false; }
}

function getLocalStorage(key, def) {
    if (!hasLocalStorage())
        return def;
    else {
        if (localStorage.getItem(key) === null)
            return def;
        else
            return JSON.parse(localStorage.getItem(key));
    }
}

function setLocalStorage(key, val) {
    if (!hasLocalStorage())
        return;
    else
        localStorage.setItem(key, JSON.stringify(val));
}
