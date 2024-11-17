(function ($) {
  "use strict";

  $(document).ready(function () {
    // ------------------------------
    // Spinner
    // ------------------------------
    var spinner = function () {
      setTimeout(function () {
        if ($("#spinner").length > 0) {
          $("#spinner").removeClass("show");
        }
      }, 1);
    };
    spinner();

    // ------------------------------
    // WOW.js Initialization
    // ------------------------------
    if (typeof WOW === "function") {
      new WOW().init();
    }

    // ------------------------------
    // Sticky Navbar
    // ------------------------------
    $(window).scroll(function () {
      if ($(this).scrollTop() > 45) {
        $(".nav-bar").addClass("sticky-top");
      } else {
        $(".nav-bar").removeClass("sticky-top");
      }
    });

    // ------------------------------
    // Back to Top Button
    // ------------------------------
    $(window).scroll(function () {
      if ($(this).scrollTop() > 300) {
        $(".back-to-top").fadeIn("slow");
      } else {
        $(".back-to-top").fadeOut("slow");
      }
    });
    $(".back-to-top").click(function () {
      $("html, body").animate({ scrollTop: 0 }, 1500, "easeInOutExpo");
      return false;
    });

    // ------------------------------
    // Header Carousel
    // ------------------------------
    if ($(".header-carousel").length) {
      $(".header-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1500,
        items: 1,
        dots: true,
        loop: true,
        nav: false,
        navText: [
          '<i class="bi bi-chevron-left"></i>',
          '<i class="bi bi-chevron-right"></i>',
        ],
      });
    }

    // ------------------------------
    // Testimonials Carousel
    // ------------------------------
    if ($(".popup-link").length) {
      $(".popup-link").magnificPopup({
        type: "image",
        gallery: {
          enabled: true,
        },
      });
    }

    if ($(".testimonial-carousel").length) {
      $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1000,
        margin: 0,
        dots: false,
        loop: true,
        nav: true,
        navText: [
          '<i class="bi bi-arrow-left"></i>',
          '<i class="bi bi-arrow-right"></i>',
        ],
        responsive: {
          0: {
            items: 1,
          },
          992: {
            items: 3,
          },
        },
      });
    }

    // ------------------------------
    // Set Current Date in Input
    // ------------------------------
    if (document.getElementById("dateInput")) {
      const currentDate = new Date();
      const formattedDate = currentDate.toISOString().slice(0, 10);
      document.getElementById("dateInput").value = formattedDate;
    }

    // ------------------------------
    // Form Submission with Google Script and WhatsApp Redirect
    // ------------------------------
    if (document.forms["tenant-form"]) {
      const form = document.forms["tenant-form"];
      const scriptURL =
        "https://script.google.com/macros/s/AKfycbxoTS2Q9BMalDJZkJR9UOz7K_josr7eAAsh1lSekB9aAQCcsqChrK6Ps6nbjhAsPQ5INg/exec";
      const defaultWhatsAppLink =
        "https://wa.me/601125423742?text=Im%20inquiring%20rentronic%20property";

      form.addEventListener("submit", (e) => {
        e.preventDefault();
        const isConfirmed = confirm(
          "Thank you! Your form is submitted successfully. You will be redirected to our agent on WhatsApp. Do you want to proceed?"
        );
        if (isConfirmed) {
          fetch(scriptURL, { method: "POST", body: new FormData(form) })
            .then((response) => response.json())
            .then((data) => {
              window.open(defaultWhatsAppLink, "_blank");
              window.location.reload();
            })
            .catch((error) => console.error("Error!", error.message));
        }
      });
    }

    // ------------------------------
    // Modal Focus Fix
    // ------------------------------
    $("#exampleModal").on("shown.bs.modal", function (e) {
      $(document).off("focusin.modal");
    });

    // ------------------------------
    // Sidebar Toggle
    // ------------------------------
    if (document.getElementById("sidebarToggle")) {
      document
        .getElementById("sidebarToggle")
        .addEventListener("click", function () {
          var sidebar = document.querySelector(".sidebar");
          var navbar = document.querySelector(".navbar");
          var page = document.querySelector(".page-wrapper");

          // Toggle the sidebar visibility
          sidebar.classList.toggle("show");

          // Toggle the navbar's expanded class
          navbar.classList.toggle("expand");

          page.classList.toggle("expand");
        });
    }

    // ------------------------------
    // Submenu Toggle
    // ------------------------------
    document
      .querySelectorAll(".sidebar-item.submenu > .sidebar-link")
      .forEach((item) => {
        item.addEventListener("click", function (e) {
          e.preventDefault();
          const parent = this.parentNode;
          parent.classList.toggle("active");
        });
      });

    document.addEventListener("DOMContentLoaded", function () {
      const sidebar = document.querySelector(".sidebar");
      const dropdownToggles = document.querySelectorAll(
        ".sidebar-item.submenu > a.sidebar-link"
      );

      // Listen for click events on the dropdown toggles
      dropdownToggles.forEach(function (toggle) {
        toggle.addEventListener("click", function (event) {
          // Check if the sidebar is in expanded mode
          if (!sidebar.classList.contains("show")) {
            event.preventDefault(); // Prevent dropdown from toggling when sidebar is collapsed
            return false; // Ensure it doesn't open
          }

          // If sidebar is expanded, allow dropdown to toggle
          const parentItem = toggle.closest(".sidebar-item");
          parentItem.classList.toggle("active"); // Toggle the 'active' class to show/hide the dropdown
        });
      });
    });

    // ------------------------------
    // Update Deposit and Advanced Rental Amounts
    // ------------------------------
    var roomIdSelect = document.getElementById("roomId");
    var depositAmountSelect = document.getElementById("depositAmount");
    var advancedRentalAmountSelect = document.getElementById(
      "advancedRentalAmount"
    );

    if (roomIdSelect) {
      roomIdSelect.addEventListener("change", function () {
        var selectedRoomOption =
          roomIdSelect.options[roomIdSelect.selectedIndex];
        var rawDepositAmount = selectedRoomOption.getAttribute("data-deposit");
        var depositAmount = Number(rawDepositAmount.replace("RM ", "").trim());

        depositAmountSelect.innerHTML =
          '<option class="depositAmount" value="0">0</option>';
        advancedRentalAmountSelect.innerHTML =
          '<option class="advancedRentalAmount" value="0">0</option>';

        if (!isNaN(depositAmount)) {
          var reducedDepositAmount = depositAmount - 100;

          var fullOption = document.createElement("option");
          fullOption.value = depositAmount;
          fullOption.textContent = depositAmount;
          depositAmountSelect.appendChild(fullOption);

          var reducedOption = document.createElement("option");
          reducedOption.value = reducedDepositAmount;
          reducedOption.textContent = reducedDepositAmount;
          depositAmountSelect.appendChild(reducedOption);

          var advancedRentalOption = document.createElement("option");
          advancedRentalOption.value = depositAmount;
          advancedRentalOption.textContent = depositAmount;
          advancedRentalAmountSelect.appendChild(advancedRentalOption);
        }
      });
    }

    // ------------------------------
    // Filter Rooms Based on Property
    // ------------------------------
    if (document.getElementById("propertyId")) {
      document
        .getElementById("propertyId")
        .addEventListener("change", filterRooms);
    }

    function filterRooms() {
      var propertyId = document.getElementById("propertyId").value;
      var rooms = document.querySelectorAll(".roomOption");

      rooms.forEach(function (room) {
        var roomProperty = room.classList.contains("property-" + propertyId);
        var isRelatedValueTrue =
          room.getAttribute("data-related-value") === "TRUE";

        if (roomProperty && isRelatedValueTrue) {
          room.style.display = "block";
        } else {
          room.style.display = "none";
        }
      });
    }

    // ------------------------------
    // Update Room Name Based on Room ID
    // ------------------------------
    var roomNameInput = document.getElementById("roomName");
    if (roomIdSelect) {
      roomIdSelect.addEventListener("change", function () {
        var selectedRoomId = this.value;
        var correspondingRoomNameOption = document.querySelector(
          `#roomNameOptions option[data-room-id="${selectedRoomId}"]`
        );

        if (correspondingRoomNameOption) {
          roomNameInput.value = correspondingRoomNameOption.value;
        } else {
          roomNameInput.value = "";
        }
      });
    }

    // ------------------------------
    // Synchronize Room ID and Months Selects
    // ------------------------------
    var monthsSelect = document.getElementById("months");
    var roomIDCellRefSelect = document.getElementById("RoomIDCellRef");
    var monthIDCellRefSelect = document.getElementById("MonthIDCellRef");

    function syncRoomID() {
      if (roomIdSelect && roomIDCellRefSelect) {
        var selectedRoomID = roomIdSelect.value;
        Array.from(roomIDCellRefSelect.options).forEach((option) => {
          if (option.getAttribute("data-related-value") === selectedRoomID) {
            roomIDCellRefSelect.value = option.value;
          }
        });
      }
    }

    function syncMonths() {
      if (monthsSelect && monthIDCellRefSelect) {
        var selectedMonth = monthsSelect.value;
        Array.from(monthIDCellRefSelect.options).forEach((option) => {
          if (option.getAttribute("data-related-value") === selectedMonth) {
            monthIDCellRefSelect.value = option.value;
          }
        });
      }
    }

    if (roomIdSelect) {
      roomIdSelect.addEventListener("change", syncRoomID);
    }
    if (monthsSelect) {
      monthsSelect.addEventListener("change", syncMonths);
    }

    syncRoomID();
    syncMonths();
  });
})(jQuery);
