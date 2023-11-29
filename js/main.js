(function ($) {
    "use strict";

    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner();


    // Initiate the wowjs
    new WOW().init();


    // Sticky Navbar
    $(window).scroll(function () {
        if ($(this).scrollTop() > 45) {
            $('.nav-bar').addClass('sticky-top');
        } else {
            $('.nav-bar').removeClass('sticky-top');
        }
    });


    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({ scrollTop: 0 }, 1500, 'easeInOutExpo');
        return false;
    });


    // Header carousel
    $(".header-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1500,
        items: 1,
        dots: true,
        loop: true,
        nav: true,
        navText: [
            '<i class="bi bi-chevron-left"></i>',
            '<i class="bi bi-chevron-right"></i>'
        ]
    });


    // Testimonials carousel
    $('.popup-link').magnificPopup({
        type: 'image',
        gallery: {
            enabled: true
        }
    });

    $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1000,
        margin: 0,
        dots: false,
        loop: true,
        nav: true,
        navText: [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ],
        responsive: {
            0: {
                items: 1
            },
            992: {
                items: 3
            }
        }
    });
    // Get the current date
    const currentDate = new Date();

    // Format the date as yyyy-mm-dd (e.g., 2023-10-28)
    const formattedDate = currentDate.toISOString().slice(0, 10);

    // Set the formatted date as the input field's value
    document.getElementById("dateInput").value = formattedDate;

    const scriptURL = 'https://script.google.com/macros/s/AKfycbxoTS2Q9BMalDJZkJR9UOz7K_josr7eAAsh1lSekB9aAQCcsqChrK6Ps6nbjhAsPQ5INg/exec';

    const form = document.forms['tenant-form'];

    const defaultWhatsAppLink = 'https://www.wassap.my/+601125423742/rentalroom';

    form.addEventListener('submit', e => {
        e.preventDefault();
    
        // Display a confirmation prompt
        const isConfirmed = confirm("Thank you! Your form is submitted successfully. You will be redirected to our agent on WhatsApp. Do you want to proceed?");
        
        if (isConfirmed) {
            fetch(scriptURL, { method: 'POST', body: new FormData(form) })
                .then(response => response.json())
                .then(data => {
                    window.open(defaultWhatsAppLink, '_blank');
    
                    // Optional: You can remove or modify the following line based on your preference
                    window.location.reload();
                })
                .catch(error => console.error('Error!', error.message));
        }
    });
    
    

    $('#exampleModal').on('shown.bs.modal', function (e) {
        $(document).off('focusin.modal');
    })


})(jQuery);

