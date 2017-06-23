$(document).ready(function () {
    $('#course-menu-toggle').click(function () {
        if ($(this).hasClass('active')) {
           $('#course-menu-title').hide();
            $(this).removeClass('active');
            $('.fa-minus').addClass('fa-bars');
            $('.fa-minus').removeClass('fa-minus');
            $('#format-glendon-content-right').addClass('col-md-12');
            $('#format-glendon-content-left').removeClass('col-md-4');
            $('#format-glendon-content-right').removeClass('col-md-8');
            $('.format_glendon_ul').hide();
        } else {
            $(this).addClass('active');
            $('#course-menu-title').show();
            $('.fa-bars').addClass('fa-minus');
            $('.fa-bars').removeClass('fa-bars');
            $('#format-glendon-content-left').addClass('col-md-4');
            $('#format-glendon-content-right').removeClass('col-md-12');
            $('#format-glendon-content-right').addClass('col-md-8');
            $('.format_glendon_ul').show();
        }
    });
});


