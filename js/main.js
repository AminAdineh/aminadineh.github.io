/*
* Template Name: Amin Adineh - Responsive vCard Template
* Author: Amin Adineh
* Author URL: https://www.aminadineh.ir
* Version: 1.0.0
*/

(function($) {
"use strict";
    var body = $('body');
    var diaryEntriesCache = null;
    var diaryRequest = null;

    function fetchDiaryEntries(forceRefresh) {
        if (forceRefresh) {
            diaryEntriesCache = null;
        }

        if (diaryEntriesCache !== null) {
            return $.Deferred().resolve(diaryEntriesCache).promise();
        }

        if (diaryRequest && diaryRequest.readyState !== 4) {
            diaryRequest.abort();
        }

        var deferred = $.Deferred();

        diaryRequest = $.ajax({
            url: 'contact_form/diary_feed.php',
            method: 'GET',
            dataType: 'json',
            cache: false
        }).done(function(response) {
            var entries = [];
            if (response && response.success && $.isArray(response.entries)) {
                entries = response.entries;
            }
            diaryEntriesCache = entries;
            if (window.console && response && response.errorDetails && response.errorDetails.length) {
                console.warn('Diary feed parse notices:', response.errorDetails);
            }
            deferred.resolve(entries);
        }).fail(function() {
            deferred.reject();
        });

        return deferred.promise();
    }

    function renderDiaryTimeline(entries) {
        var $timeline = $('#diary_timeline');
        if (!$timeline.length) {
            return;
        }

        $timeline.empty();

        if (!entries || !entries.length) {
            var $empty = $('<div class="timeline-item clearfix"></div>');
            $empty.append($('<h5 class="item-period highlighted"></h5>').text('No notes yet'));
            $empty.append($('<h4 class="item-title"></h4>').text('Check back soon for new updates.'));
            $timeline.append($empty);
            return;
        }

        $.each(entries, function(index, entry) {
            var $item = $('<div class="timeline-item clearfix"></div>');
            var dateLabel = entry.date_label || 'Recent';
            var timeLabel = entry.time_label ? ' • ' + entry.time_label : '';
            $item.append($('<h5 class="item-period highlighted"></h5>').text(dateLabel + timeLabel));

            var titleText = entry.subject || 'New diary note';
            $item.append($('<h4 class="item-title"></h4>').text(titleText));

            if (entry.name) {
                $item.append($('<h5 class="item-company"></h5>').text(entry.name));
            }

            if (entry.message) {
                var safeMessage = entry.message.replace(/\r\n/g, '\n');
                safeMessage = safeMessage.replace(/\r/g, '\n');
                var $message = $('<p></p>').text(safeMessage);
                $message.html($message.html().replace(/\n/g, '<br>'));
                $item.append($message);
            }

            if (entry.attachment_url) {
                var $attachment = $('<p class="diary-attachment"></p>');
                $('<a target="_blank" rel="noopener"></a>')
                    .attr('href', entry.attachment_url)
                    .text(entry.attachment_name || 'View attachment')
                    .appendTo($attachment);
                $item.append($attachment);
            }

            $timeline.append($item);
        });
    }

    function renderSidebarNotifications(entries) {
        var $sidebarList = $('#sidebar_notifications');
        if (!$sidebarList.length) {
            return;
        }

        $sidebarList.empty();

        if (!entries || !entries.length) {
            $sidebarList.append($('<li class="empty"></li>').text('No updates yet.'));
            return;
        }

        var maxItems = 5;
        $.each(entries.slice(0, maxItems), function(index, entry) {
            var subject = entry.subject || 'New diary note';
            var name = entry.name ? entry.name : '';
            var dateLabel = entry.date_label || '';

            var $li = $('<li></li>');
            var $link = $('<a></a>')
                .attr('href', 'index.html')
                .text(subject);
            $li.append($link);

            var metaParts = [];
            if (name) {
                metaParts.push(name);
            }
            if (dateLabel) {
                metaParts.push(dateLabel);
            }

            if (metaParts.length) {
                var $meta = $('<small class="sidebar-meta"></small>').text(metaParts.join(' • '));
                $li.append($meta);
            }

            $sidebarList.append($li);
        });
    }

    function refreshDiaryUI(forceRefresh) {
        fetchDiaryEntries(forceRefresh).done(function(entries) {
            renderDiaryTimeline(entries);
            renderSidebarNotifications(entries);
        }).fail(function() {
            renderDiaryTimeline([]);
            renderSidebarNotifications([]);
        });
    }

    function imageCarousel() {
        $('.portfolio-page-carousel').each(function() {
            $(this).imagesLoaded(function () {
                $('.portfolio-page-carousel').owlCarousel({
                    smartSpeed:1200,
                    items: 1,
                    loop: true,
                    dots: true,
                    nav: true,
                    navText: false,
                    autoHeight: true,
                    margin: 10
                });
            });
        });
    }

    // Hide Mobile menu
    function mobileMenuHide() {
        var windowWidth = $(window).width(),
            siteHeader = $('.site-menu');

        if (windowWidth < 1025) {
            $('.menu-toggle').removeClass('open');
            setTimeout(function(){
                siteHeader.addClass('animate');
            }, 500);
        } else {
            siteHeader.removeClass('animate');
        }
    }
    // /Hide Mobile menu

    // Ajax Pages loader
    function ajaxLoader() {
        var ajaxLoadedContent = $('#page-ajax-loaded');
        function showContent() {
            ajaxLoadedContent.removeClass('animated-section-moveToRight closed');
            ajaxLoadedContent.show();
            $('body').addClass('ajax-page-visible');
        }
        function hideContent() {
            $('#page-ajax-loaded').addClass('animated-section-moveToRight closed');
            $('body').removeClass('ajax-page-visible');
            setTimeout(function(){
                $('#page-ajax-loaded.closed').html('');
                ajaxLoadedContent.hide();
            }, 500);
        }

        var href = $('.ajax-page-load').each(function(){
            href = $(this).attr('href');
            if(location.hash == location.hash.split('/')[0] + '/' + href.substr(0,href.length-5)){
                var toLoad =  $(this).attr('href');
                showContent();
                ajaxLoadedContent.load(toLoad);
                return false;
            }
        });

        $(document)
            .on("click","#portfolio-page-close-button", function (e) { // Hide Ajax Loaded Page on Navigation cleck and Close button
                e.preventDefault();
                hideContent();
                location.hash = location.hash.split('/')[0];
            })
            .on("click",".ajax-page-load", function () { // Show Ajax Loaded Page
                var hash = location.hash.split('/')[0] + '/' + $(this).attr('href').substr(0,$(this).attr('href').length-5);
                location.hash = hash;
                showContent();

                return false;
            });
    }
    // /Ajax Pages loader

    // Contact form validator
    $(function () {

        var forms = $('#contact_form');

        forms.each(function(){
            var $form = $(this);

            if (!$form.length) return;

            $form.validator();

            $form.on('submit', function (e) {
                if (!e.isDefaultPrevented()) {
                    var url = "contact_form/contact_form.php";
                    var ajaxOptions = {
                        type: 'POST',
                        url: url,
                        dataType: 'json',
                        success: function (data) {
                            if (!data) {
                                data = { type: 'danger', message: 'There was a problem submitting the form. Please try again later.' };
                            }

                            var messageAlert = 'alert-' + data.type;
                            var messageText = data.message || 'There was a problem submitting the form. Please try again later.';

                            var alertBox = '<div class="alert ' + messageAlert + ' alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' + messageText + '</div>';
                            $form.find('.messages').html(alertBox);

                            if (data.type === 'success') {
                                $form[0].reset();
                                if (typeof grecaptcha !== 'undefined') {
                                    grecaptcha.reset();
                                }
                            }
                        },
                        error: function () {
                            var alertBox = '<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>There was an error while submitting the form. Please try again later.</div>';
                            $form.find('.messages').html(alertBox);
                        }
                    };

                    if ($form.attr('enctype') === 'multipart/form-data') {
                        var formData = new FormData($form[0]);
                        ajaxOptions.data = formData;
                        ajaxOptions.processData = false;
                        ajaxOptions.contentType = false;
                    } else {
                        ajaxOptions.data = $form.serialize();
                    }

                    $.ajax(ajaxOptions);
                    return false;
                }
            });
        });
    });
    // /Contact form validator

    // Portfolio subpage filters
    function portfolio_init() {
        $( '.portfolio-content' ).each( function() {
            var portfolio_grid_container = $(this),
                portfolio_grid_container_id = $(this).attr('id'),
                portfolio_grid = $('#' + portfolio_grid_container_id + ' .portfolio-grid'),
                portfolio_filter = $('#' + portfolio_grid_container_id + ' .portfolio-filters'),
                portfolio_filter_item = $('#' + portfolio_grid_container_id + ' .portfolio-filters .filter');
                
            if (portfolio_grid) {

                portfolio_grid.shuffle({
                    speed: 450,
                    itemSelector: 'figure'
                });

                $('.site-auto-menu').on("click", "a", function (e) {
                    portfolio_grid.shuffle('update');
                });

                portfolio_filter.on("click", ".filter", function (e) {
                    portfolio_grid.shuffle('update');
                    e.preventDefault();
                    portfolio_filter_item.parent().removeClass('active');
                    $(this).parent().addClass('active');
                    portfolio_grid.shuffle('shuffle', $(this).attr('data-group') );
                });

            }
        })
    }
    // /Portfolio subpage filters

    // Animate layout
    function animateLayout() {
        var animatedContainer = '',
            blogSidebar = $(".blog-sidebar"),
            animatedContainer = $(".content-area"),
            animateType = animatedContainer.attr('data-animation');


        animatedContainer.addClass("animated " + animateType);
        $('.page-scroll').addClass('add-prespective');
        animatedContainer.addClass('transform3d');
        setTimeout(function() {
            blogSidebar.removeClass("hidden-sidebar");
            $('.page-scroll').removeClass('add-prespective');
            animatedContainer.removeClass('transform3d');
        }, 1000);
    }
    // /Animate layout

    // Skills
    function skillsStyles() {
        var $custom_styles = "",
            $custom_style = "";
        $( '.skill-container' ).each( function() {
            var value = $(this).attr('data-value'),
                $id = $(this).attr('id');

            if( value >= 101) {
                value = '100';
            }

            if( typeof value != 'undefined' ) {
                $custom_style = '#' + $id + ' .skill-percentage { width: ' + value + '%; } ';
                $custom_styles += $custom_style;
            }
        });
        $('head').append('<style data-styles="amin-skills-css" type="text/css">' + $custom_styles + '</style>');
    }
    // /Skills

    //On Window load & Resize
    $(window)
        .on('load', function() { //Load
            // Animation on Page Loading
            animateLayout();
        })
        .on('hashchange', function(event) {
            if(location.hash) {
                ajaxLoader();
            }
        });


    // On Document Load
    $(document).on('ready', function() {
        var movementStrength = 15;
        var height = movementStrength / $(document).height();
        var width = movementStrength / $(document).width();
        $("body").on('mousemove', function(e){
            var pageX = e.pageX - ($(document).width() / 2),
                pageY = e.pageY - ($(document).height() / 2),
                newvalueX = width * pageX * -1,
                newvalueY = height * pageY * -1;
            if ($('.page-wrapper').hasClass('bg-move-effect')) {
                var elements = $('.home-photo .hp-inner:not(.without-move), .lm-animated-bg');
            } else {
                var elements = $('.home-photo .hp-inner:not(.without-move)');
            }
            elements.addClass('transition');
            elements.css({
                "background-position": "calc( 50% + " + newvalueX + "px ) calc( 50% + " + newvalueY + "px )",
            });

            setTimeout(function() {
                elements.removeClass('transition');
            }, 300);
        })

        // Initialize Portfolio grid
        var $portfolio_container = $(".portfolio-grid"),
            $gallery_container = $("#portfolio-gallery-grid");

        $gallery_container.imagesLoaded(function () {
            $gallery_container.masonry();
        });

        $portfolio_container.imagesLoaded(function () {
            portfolio_init(this);
        });

        imageCarousel();

        // Clients Slider
        $(".clients.owl-carousel").imagesLoaded().owlCarousel({
            nav: true, // Show next/prev buttons.
            items: 2, // The number of items you want to see on the screen.
            loop: false, // Infinity loop. Duplicate last and first items to get loop illusion.
            navText: false,
            margin: 10,
            autoHeight: false,
            responsive : {
                // breakpoint from 0 up
                0 : {
                    items: 2,
                },
                // breakpoint from 768 up
                768 : {
                    items: 4,
                },
                1200 : {
                    items: 6,
                }
            }
        });

        // Testimonials Slider
        $(".testimonials.owl-carousel").owlCarousel({
            nav: true, // Show next/prev buttons.
            items: 3, // The number of items you want to see on the screen.
            loop: false, // Infinity loop. Duplicate last and first items to get loop illusion.
            navText: false,
            margin: 25,
            responsive : {
                // breakpoint from 0 up
                0 : {
                    items: 1,
                },
                // breakpoint from 480 up
                480 : {
                    items: 1,
                },
                // breakpoint from 768 up
                768 : {
                    items: 2,
                },
                1200 : {
                    items: 2,
                }
            }
        });

        // Text rotation
        $('.text-rotation').owlCarousel({
            loop: true,
            dots: false,
            nav: false,
            margin: 10,
            items: 1,
            autoplay: true,
            autoplayHoverPause: false,
            autoplayTimeout: 3800,
            animateOut: 'fadeOut',
            animateIn: 'fadeIn'
        });

        // Blog grid init
        var $container = $(".blog-masonry");
        $container.imagesLoaded(function () {
            $container.masonry({
              itemSelector: '.item',
              resize: false
            });
        });

        // Mobile menu hide on main menu item click
        $('.main-menu').on("click", "a", function (e) {
            mobileMenuHide();
        });

        refreshDiaryUI(false);

        // Lightbox init
        body.magnificPopup({
            fixedContentPos: false,
            delegate: 'a.lightbox',
            type: 'image',
            removalDelay: 300,

            // Class that is added to popup wrapper and background
            // make it unique to apply your CSS animations just to this exact popup
            mainClass: 'mfp-fade',
            image: {
                // options for image content type
                titleSrc: 'title',
                gallery: {
                    enabled: true
                },
            },

            iframe: {
                markup: '<div class="mfp-iframe-scaler">'+
                        '<div class="mfp-close"></div>'+
                        '<iframe class="mfp-iframe" frameborder="0" allowfullscreen></iframe>'+
                        '<div class="mfp-title mfp-bottom-iframe-title"></div>'+
                      '</div>', // HTML markup of popup, `mfp-close` will be replaced by the close button

                patterns: {
                    youtube: {
                      index: 'youtube.com/', // String that detects type of video (in this case YouTube). Simply via url.indexOf(index).

                      id: null, // String that splits URL in a two parts, second part should be %id%
                      // Or null - full URL will be returned
                      // Or a function that should return %id%, for example:
                      // id: function(url) { return 'parsed id'; }

                      src: '%id%?autoplay=1' // URL that will be set as a source for iframe.
                    },
                    vimeo: {
                      index: 'vimeo.com/',
                      id: '/',
                      src: '//player.vimeo.com/video/%id%?autoplay=1'
                    },
                    gmaps: {
                      index: '//maps.google.',
                      src: '%id%&output=embed'
                    }
                },

                srcAction: 'iframe_src', // Templating object key. First part defines CSS selector, second attribute. "iframe_src" means: find "iframe" and set attribute "src".
            },

            callbacks: {
                markupParse: function(template, values, item) {
                 values.title = item.el.attr('title');
                }
            },
        });

        $('.ajax-page-load-link').magnificPopup({
            type: 'ajax',
            removalDelay: 300,
            mainClass: 'mfp-fade',
            gallery: {
                enabled: true
            },
        });

        $('.portfolio-page-wrapper .portfolio-grid').each(function() {
            $(this).magnificPopup({
                delegate: 'a.gallery-lightbox',
                type: 'image',
                gallery: {
                  enabled:true
                }
            });
        });

        //Form Controls
        $('.form-control')
            .val('')
            .on("focusin, click", function(){
                $(this).parent('.form-group:not(.form-group-checkbox)').addClass('form-group-focus');
            })
            .on("focusout", function(){
                if($(this).val().length === 0) {
                    $(this).parent('.form-group:not(.form-group-checkbox)').removeClass('form-group-focus');
                }
            });

        $('body').append('<div id="page-ajax-loaded" class="page-portfolio-loaded animated animated-section-moveFromLeft" style="display: none"></div>');
        ajaxLoader();

        // Sidebar toggle
        $('.sidebar-button').on("click", function () {
            $('#blog-sidebar').toggleClass('open');
            $(this).toggleClass('open');
            $('.page-wrapper').toggleClass('sidebar-open');
        });

        // Menu toggle
        $('.menu-button').on("click", function () {
            $('.site-menu').toggleClass('open');
            $(this).toggleClass('open');
            $('.page-wrapper').toggleClass('sidebar-open');
        });

        $( '.dl-menuwrapper' ).dlmenu();

        $('.content-wrapper, .r-sidebar').on("click", function (event) {
            if ($('.site-menu').hasClass('open')) {
                event.stopPropagation();
                $('.site-menu').removeClass('open');
                $('.menu-button').removeClass('open');
                $('.page-wrapper').toggleClass('sidebar-open');
            }
        });

        $('.content-wrapper, #site_header').on("click", function (event) {
            if ($('.blog-sidebar').hasClass('open')) {
                event.stopPropagation();
                $('.blog-sidebar').removeClass('open');
                $('.sidebar-button').removeClass('open');
                $('.page-wrapper').toggleClass('sidebar-open');
            }
        });

        skillsStyles();

        //Google Maps
        if ($(".lmpixels-map")[0]){
            var address = 'San Francisco, S601 Townsend Street, California, USA', //Replace with Your Address
                address = encodeURIComponent(address),
                src = 'https://maps.google.com/maps?q=' + address + '&amp;t=m&amp;z=16&amp;output=embed&amp;iwloc=near&output=embed';
            $(".lmpixels-map iframe").attr("src", src);
        }

        if ($(".home-bgvideo")[0]){
            var mpLink = $('.home-bgvideo').attr('data-videomp'),
            webmLink = $('.home-bgvideo').attr('data-videowebm'),
            imgLink = $('.home-bgvideo').attr('data-img'),
            
            videoBackground = new vidbg('.home-bgvideo', {
                mp4: mpLink,
                webm: webmLink,
                poster: imgLink,
            })
        }

    });

})(jQuery);