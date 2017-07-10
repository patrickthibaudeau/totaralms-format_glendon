$(document).ready(function () {
    $('#course-menu-toggle').click(function () {
        if ($(this).hasClass('active')) {
           $('#course-menu-title').hide();
            $(this).removeClass('active');
            $('.fa-window-minimize').addClass('fa-window-maximize');
            $('.fa-window-minimize').removeClass('fa-window-minimize');
            $('#format-glendon-content-right').addClass('col-md-12');
            $('#format-glendon-content-left').removeClass('col-md-4');
            $('#format-glendon-content-right').removeClass('col-md-8');
            $('.format_glendon_ul').hide();
        } else {
            $(this).addClass('active');
            $('#course-menu-title').show();
            $('.fa-window-maximize').addClass('fa-window-minimize');
            $('.fa-window-maximize').removeClass('fa-window-maximize');
            $('#format-glendon-content-left').addClass('col-md-4');
            $('#format-glendon-content-right').removeClass('col-md-12');
            $('#format-glendon-content-right').addClass('col-md-8');
            $('.format_glendon_ul').show();
        }
    });
});


