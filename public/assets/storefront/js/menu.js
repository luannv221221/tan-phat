const menuToggle = document.querySelector(".menu-toggle");
const menuPrimary = document.querySelector(".header__primary-menu");
const menuClose = menuPrimary.querySelector(".close");

menuToggle.addEventListener("click", () => {
  menuPrimary.classList.toggle("open");
});

menuClose.addEventListener("click", () => {
  menuPrimary.classList.toggle("open");
});

//Xử lý menu đa cấp

const menuItems = document.querySelectorAll(".menu > li"); //lấy li tất cả các cấp menu

const width = window.innerWidth;

//console.log(menuItems);

if (menuItems.length > 0) {
  menuItems.forEach((menuItem) => {
    //console.log(menuItem);

    //Đưa mũi tên vào menu có cấp con
    if (menuItem.children[1] !== undefined) {
      const subMenu = menuItem.children[1]; //Lấy tất các con liền sau. giống > trong css

      menuItem.classList.add("has-children");

      const angleIcon = document.createElement("i");
      angleIcon.classList.add("fa", "fa-angle-down", "icon-down");

      menuItem.insertBefore(angleIcon, subMenu);

      //console.log(angleIcon);

      //Xử lý từ cấp 2

      const subMenuItems = subMenu.querySelectorAll("li");
      subMenuItems.forEach((subMenuItem) => {
        if (subMenuItem.children[1] !== undefined) {
          subMenuItem.classList.add("has-children");

          const angleIcon = document.createElement("i");
          angleIcon.classList.add("fa", "fa-angle-down", "icon-right");

          subMenuItem.insertBefore(angleIcon, subMenuItem.children[1]);
        }
      });
    }

    //Xử lý khi click vào menu item

    menuItem.addEventListener("click", function (e) {
      const menuItemActive = document.querySelector(".primary-menu li.current");
      if (menuItemActive !== null) {
        menuItemActive.classList.remove("current");
      }

      menuItem.classList.add("current");
      //console.log(menuItem);
      /*
            Từ khoá this sẽ lấy đối tượng DOM khi người dùng tác động sự kiện lên menuItem
            */
    });
  });
}

if (width <= 767) {
  const allMenuItems = document.querySelectorAll(".menu li i");

  let currentIndex = null;

  allMenuItems.forEach((menuItem, index) => {
    //const subMenuOpen = menuItem.nextElementSibling;
    //nextElementSibling => Chọn element ngay sau nó

    let subMenuOpen = null;
    menuItem.addEventListener("click", function (e) {
      const subMenu = e.target.nextElementSibling;
      //console.log(menuItem.parentElement.parentElement.querySelectorAll('li > ul'));

      if (subMenu !== null) {
        //Xử lý loại bỏ class của các menu con đang menu

        const parent = menuItem.parentElement.parentElement;

        const ulChilds = parent.querySelectorAll("li > ul");

        //console.log(currentIndex, index);

        if (currentIndex !== index) {
          //Xử lý trường hợp cấp 2
          if (!parent.classList.contains("menu")) {
            console.log("Cấp 2");

            ulChilds.forEach((ulChild) => {
              if (ulChild.classList.contains("open")) {
                ulChild.classList.remove("open");
              }
            });
          } else {
            //Xử lý trường hợp cấp 1
            subMenuOpen = document.querySelector(".menu > li > ul.open");
            console.log("Cấp 1");
            //console.log(menuItem.parentElement)

            if (subMenuOpen !== null) {
              //console.log(subMenuOpen);
              subMenuOpen.classList.remove("open");
              subMenuOpen.previousElementSibling.style.transform =
                "rotate(-90deg)";
              //console.log(subMenuOpen.previousElementSibling);
            }
          }
        }

        subMenu.classList.toggle("open");
        //console.log([subMenu]);

        // if (subMenu.style.maxHeight) {
        //   //this is if the accordion is open
        //   subMenu.style.maxHeight = null;
        //   subMenu.style.transition = "max-height 0.3s linear";
        // } else {
        //   //if the accordion is currently closed
        //   subMenu.style.maxHeight = "100vh";
        //   subMenu.style.transition = "max-height 1s linear";
        // }

        if (subMenu.classList.contains("open")) {
          subMenu.style.maxHeight = "100vh";
          subMenu.style.transition = "max-height 1s linear";
          //   console.log("ok");
          if (menuItem.classList.contains("icon-down")) {
            menuItem.style.transform = "rotate(0deg)";
          } else {
            menuItem.style.transform = "rotate(360deg)";
          }

          menuItem.style.transition = "transform 0.2s linear";
        } else {
          subMenu.style.maxHeight = null;
          subMenu.style.transition = "max-height 0.3s linear";
          if (menuItem.classList.contains("icon-down")) {
            menuItem.style.transform = "rotate(-90deg)";
          } else {
            menuItem.style.transform = "rotate(270deg)";
          }
        }
      }

      currentIndex = index;
    });
  });
}
