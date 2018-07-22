define(['jquery', 'jqueryui'], function ($, jqui) {
    "use strict";

    var wwwroot = M.cfg.wwwroot;

    /**
     * This function is used to add highlighting for the active breadcrump item 
     * Initialising.
     */
    function initFormat() {

        $('#course-menu-toggle').click(function () {
            toggleCourseMenu();
        });
    }

    return {
        init: function () {
            initFormat();
        }
    };

    function toggleCourseMenu() {
        var courseMenu = $('#course-menu-toggle');

        if (courseMenu.hasClass('active')) {
            $('#format-glendon-content-left').toggle('slide');
            courseMenu.removeClass('active');
            $('#course-menu-toogle-image').prop('src', wwwroot + '/pix/t/switch_plus.svg');
            $('#format-glendon-content-right').addClass('col-md-12');
        } else {
            courseMenu.addClass('active');
            $('#course-menu-toogle-image').prop('src', wwwroot + '/pix/t/switch_minus.svg');
            $('#format-glendon-content-left').toggle('slide');
            $('#format-glendon-content-right').removeClass('col-md-12');
            $('#format-glendon-content-right').addClass('col-md-8');
        }
    }

});