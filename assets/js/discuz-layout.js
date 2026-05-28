
(function($) {
    'use strict';

    var hoverTimer = null;
    var currentMenuId = null;
    var $menuInner = null;
    var $scrollLeftBtn = null;
    var $scrollRightBtn = null;

    function initDiscuzLayout() {
        cacheElements();
        
        currentMenuId = $('.ap-top-menu-item.current').first().data('menu-id');
        
        updateScrollButtons();
        
        bindEvents();
        initMobileSidebar();
    }

    function cacheElements() {
        $menuInner = $('.ap-top-menu-inner');
        $scrollLeftBtn = $('.ap-top-menu-scroll-left');
        $scrollRightBtn = $('.ap-top-menu-scroll-right');
    }

    function updateScrollButtons() {
        if (!$menuInner || !$menuInner.length) return;
        
        var containerWidth = $menuInner.innerWidth();
        var contentWidth = $menuInner[0].scrollWidth;
        var scrollLeft = $menuInner.scrollLeft();
        
        if (contentWidth > containerWidth) {
            $scrollLeftBtn.css('display', 'flex');
            $scrollRightBtn.css('display', 'flex');
            $menuInner.css('padding', '0 40px');
            
            if (scrollLeft <= 0) {
                $scrollLeftBtn.css('opacity', '0.3');
            } else {
                $scrollLeftBtn.css('opacity', '1');
            }
            
            if (scrollLeft + containerWidth >= contentWidth - 10) {
                $scrollRightBtn.css('opacity', '0.3');
            } else {
                $scrollRightBtn.css('opacity', '1');
            }
        } else {
            $scrollLeftBtn.css('display', 'none');
            $scrollRightBtn.css('display', 'none');
            $menuInner.css('padding', '0');
        }
    }

    function renderSidebarMenu(menuId) {
        var $sidebar = $('#ap-sidebar-menu');
        $sidebar.empty();
        
        var $menuItem = $('.ap-top-menu-item[data-menu-id="' + menuId + '"]');
        
        if ($menuItem.length === 0) return;
        
        var menuUrl = null;
        var menuTitle = '';
        var menuIcon = '';
        var menuCount = 0;
        
        var $link = $menuItem.find('a').first();
        if ($link.length) {
            menuUrl = $link.attr('href');
        }
        
        var $countBubble = $menuItem.find('.ap-menu-count').first();
        if ($countBubble.length) {
            menuCount = parseInt($countBubble.text()) || 0;
        }
        
        var $iconSpan = $menuItem.find('.dashicons').first();
        if ($iconSpan.length) {
            menuIcon = $iconSpan.attr('class') || '';
        }
        
        menuTitle = $menuItem.clone().find('.ap-menu-count, .dashicons').remove().end().text().trim();
        
        var submenuData = window.apSubmenuData && window.apSubmenuData[menuId];
        
        if (submenuData && submenuData.length > 0) {
            $.each(submenuData, function(i, subitem) {
                var subCountBubble = subitem.count > 0 ? '<span class="ap-menu-count">' + subitem.count + '</span>' : '';
                $sidebar.append('<a href="' + subitem.url + '" class="ap-sidebar-item' + (subitem.is_current ? ' current' : '') + '">' +
                        subitem.title +
                        subCountBubble +
                    '</a>');
            });
        } else if (menuUrl && menuUrl !== '#') {
            var mainCountBubble = menuCount > 0 ? '<span class="ap-menu-count">' + menuCount + '</span>' : '';
            $sidebar.append('<a href="' + menuUrl + '" class="ap-sidebar-item current">' +
                (menuIcon ? '<span class="' + menuIcon + '"></span>' : '') +
                menuTitle +
                mainCountBubble +
            '</a>');
        }
    }

    function initMobileSidebar() {
        if (!$('#ap-sidebar-overlay').length) {
            $('<div class="ap-sidebar-overlay" id="ap-sidebar-overlay"></div>').insertAfter('#ap-sidebar');
        }
    }

    function toggleMobileSidebar(open) {
        var isMobile = $(window).width() <= 782;
        if (!isMobile) return;
        
        var $sidebar = $('#ap-sidebar');
        var $overlay = $('#ap-sidebar-overlay');
        
        if (open === undefined) {
            open = !$sidebar.hasClass('ap-sidebar-open');
        }
        
        if (open) {
            $sidebar.addClass('ap-sidebar-open');
            $overlay.addClass('ap-sidebar-overlay-show');
        } else {
            $sidebar.removeClass('ap-sidebar-open');
            $overlay.removeClass('ap-sidebar-overlay-show');
        }
    }

    function bindEvents() {
        $('.ap-top-menu').on('mouseenter', '.ap-top-menu-item', function() {
            var menuId = $(this).data('menu-id');
            
            if (hoverTimer) {
                clearTimeout(hoverTimer);
            }
            
            hoverTimer = setTimeout(function() {
                $('.ap-top-menu-item').removeClass('current');
                $('.ap-top-menu-item[data-menu-id="' + menuId + '"]').addClass('current');
                
                renderSidebarMenu(menuId);
            }, 1000);
        });
        
        $('.ap-top-menu').on('mouseleave', '.ap-top-menu-item', function() {
            if (hoverTimer) {
                clearTimeout(hoverTimer);
            }
        });
        
        $('.ap-top-menu-scroll-left').on('click', function(e) {
            e.stopPropagation();
            $menuInner.animate({
                scrollLeft: $menuInner.scrollLeft() - 200
            }, 300, updateScrollButtons);
        });
        
        $('.ap-top-menu-scroll-right').on('click', function(e) {
            e.stopPropagation();
            $menuInner.animate({
                scrollLeft: $menuInner.scrollLeft() + 200
            }, 300, updateScrollButtons);
        });
        
        $('.ap-top-menu').on('wheel', function(e) {
            e.preventDefault();
            $menuInner.scrollLeft($menuInner.scrollLeft() + (e.originalEvent.deltaY > 0 ? 50 : -50));
            updateScrollButtons();
        });
        
        $('.ap-top-menu-inner').on('scroll', function() {
            updateScrollButtons();
        });
        
        $('.ap-top-menu').on('click', '.ap-top-menu-item', function() {
            var menuId = $(this).data('menu-id');
            var $link = $(this).find('a').first();
            if ($link.length && $link.attr('href')) {
                window.location.href = $link.attr('href');
            }
        });

        $(document).off('click.wpadminbar-toggle').on('click.wpadminbar-toggle', '#wp-admin-bar-menu-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMobileSidebar();
            
            $('body').toggleClass('wp-responsive-open');
            $(this).attr('aria-expanded', $('body').hasClass('wp-responsive-open') ? 'true' : 'false');
        });
        
        $(document).off('click.ap-overlay').on('click.ap-overlay', '#ap-sidebar-overlay', function() {
            toggleMobileSidebar(false);
        });
        
        $(window).on('resize', function() {
            updateScrollButtons();
            if ($(window).width() > 782) {
                $('#ap-sidebar').removeClass('ap-sidebar-open');
                $('#ap-sidebar-overlay').removeClass('ap-sidebar-overlay-show');
            }
        });
    }

    $(document).ready(function() {
        initDiscuzLayout();
    });

})(jQuery);
