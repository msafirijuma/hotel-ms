const header = document.querySelector(".header");

window.addEventListener("scroll", () => {
    if (header.classList.contains("header-dark")) {
        if (window.document.documentElement.scrollTop >= 40) {
            header.classList.add("navbar-scroll-dark-mode")
        } else {
            header.classList.remove("navbar-scroll-dark-mode")
        }
    } else {
        if (window.document.documentElement.scrollTop >= 50) {
            header.classList.add("navbar-scroll")
        } else {
            header.classList.remove("navbar-scroll")
        }
    }
})

