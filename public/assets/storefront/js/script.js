/* ---------------------------------------------------------------------------
 * SUA LOI: anh trang chi tiet san pham khong hien.
 *
 * own-carousel.min.js (slider trang chu) gan ham vao Object.prototype:
 *     Object.prototype.ownCarousel = function (options) { ... }
 * => moi object trong trang deu "co" thuoc tinh nay, va no LIET KE DUOC.
 *
 * Owl Carousel duyet danh sach plugin cua no bang for...in (di ca chuoi
 * prototype), nen thay 'ownCarousel' va tuong day la plugin cua minh:
 *     new Object.ownCarousel(owlInstance)
 * Ben trong ham goi this.querySelector(".own-carousel") — nhung `this` la
 * doi tuong Owl chu khong phai DOM element => TypeError, Owl chet giua chung,
 * khong bao gio gan duoc class .owl-loaded.
 *
 * Ma owl.carousel.min.css co: .owl-carousel { display: none }
 *                             .owl-carousel.owl-loaded { display: block }
 * => slider anh nam nguyen trong DOM nhung bi an hoan toan.
 *
 * Cach sua: giu nguyen ham (trang chu van dung element.ownCarousel(...)),
 * chi dat lai thanh KHONG liet ke duoc de for...in cua Owl khong nhin thay.
 * File nay nap sau own-carousel.min.js nen sua o day la kip.
 * ------------------------------------------------------------------------- */
if (Object.prototype.hasOwnProperty.call(Object.prototype, "ownCarousel")) {
  var __ownCarousel = Object.prototype.ownCarousel;
  delete Object.prototype.ownCarousel;
  Object.defineProperty(Object.prototype, "ownCarousel", {
    value: __ownCarousel,
    enumerable: false,
    writable: true,
    configurable: true,
  });
}

document.addEventListener("DOMContentLoaded", () => {
  if (document.querySelector(".own-carousel__container")) {
    document.querySelector(".own-carousel__container").ownCarousel({
      itemPerRow: 1,
      itemWidth: 100,
      nav: true,
      loop: true,
      autoplay: 3000,
    });
  }
});

if ($(".detail .slider-nav")) {
  $(document).ready(function () {
    var sync1 = $("#sync11");
    var sync2 = $("#sync21");
    var slidesPerPage = 5; //globaly define number of elements per page
    var syncedSecondary = true;

    sync1
      .owlCarousel({
        items: 1,
        slideSpeed: 3000,
        dots: false,
        loop: true,
        nav: true,
        responsiveRefreshRate: 200,

        autoplay: true,
        autoplayTimeout: 2000,
        autoplayHoverPause: true,
      })
      .on("changed.owl.carousel", syncPosition);

    sync2
      .on("initialized.owl.carousel", function () {
        sync2.find(".owl-item").eq(0).addClass("current");
      })
      .owlCarousel({
        items: slidesPerPage,
        dots: true,
        smartSpeed: 200,
        slideSpeed: 500,
        slideBy: slidesPerPage, //alternatively you can slide by 1, this way the active slide will stick to the first item in the second carousel
        responsiveRefreshRate: 100,
      })
      .on("changed.owl.carousel", syncPosition2);

    function syncPosition(el) {
      //if you set loop to false, you have to restore this next line
      //var current = el.item.index;

      //if you disable loop you have to comment this block
      var count = el.item.count - 1;
      var current = Math.round(el.item.index - el.item.count / 2 - 0.5);

      if (current < 0) {
        current = count;
      }
      if (current > count) {
        current = 0;
      }

      //end block

      sync2
        .find(".owl-item")
        .removeClass("current")
        .eq(current)
        .addClass("current");
      var onscreen = sync2.find(".owl-item.active").length - 1;
      var start = sync2.find(".owl-item.active").first().index();
      var end = sync2.find(".owl-item.active").last().index();

      if (current > end) {
        sync2.data("owl.carousel").to(current, 100, true);
      }
      if (current < start) {
        sync2.data("owl.carousel").to(current - onscreen, 100, true);
      }
    }

    function syncPosition2(el) {
      if (syncedSecondary) {
        var number = el.item.index;
        sync1.data("owl.carousel").to(number, 100, true);
      }
    }

    sync2.on("click", ".owl-item", function (e) {
      e.preventDefault();
      var number = $(this).index();
      sync1.data("owl.carousel").to(number, 300, true);
    });
  });
}
const topHeader = document.querySelector(".top-header");
const topBar = document.querySelector(".topbar");
const header = document.querySelector(".header");
window.addEventListener("scroll", () => {
  if (window.scrollY >= topHeader.clientHeight) {
    header.style.position = "fixed";
    header.style.left = 0;
    header.style.top = 0;
    header.style.right = 0;
    topHeader.style.display = "none";
    topBar.style.display = "none";
  }
  if (scrollY == 0) {
    topBar.style.display = "block";
    topHeader.style.display = "block";
    const content = document.querySelector(".content");
    content.style.paddingTop = header.clientHeight + "px";
  }
});
var btn = $("#button");
$(window).scroll(function () {
  if ($(window).scrollTop() > 300) {
    btn.addClass("show");
  } else {
    btn.removeClass("show");
  }
});

if ($(".related-products-slider").length) {
  $(document).ready(function () {
    $(".related-products-slider").owlCarousel({
      loop: true,
      margin: 10,
      nav: true,
      dots: false,
      autoplay: true,
      autoplayTimeout: 2000,
      autoplayHoverPause: true,
      responsive: {
        0: {
          items: 1,
          nav: false,
        },
        600: {
          items: 3,
          nav: true,
        },
        1000: {
          items: 4,
          nav: true,
        },
      },
    });
  });
}
